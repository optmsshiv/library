<?php
// scan.php — Public QR Attendance Scanner
// Called when student scans their QR code
session_start();
require_once __DIR__ . '/includes/db.php';

$token  = $_GET['token'] ?? '';
$result = null;
$error  = null;

if ($token) {
    try {
        $db = getDB();

        // Create tables if not exist
        $db->exec("CREATE TABLE IF NOT EXISTS qr_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            token VARCHAR(64) NOT NULL UNIQUE,
            type VARCHAR(32) NOT NULL DEFAULT 'attendance',
            student_id VARCHAR(32) NOT NULL,
            date DATE NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        $db->exec("CREATE TABLE IF NOT EXISTS student_attendance (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id VARCHAR(32) NOT NULL,
            date DATE NOT NULL,
            status VARCHAR(16) NOT NULL DEFAULT 'present',
            check_in TIME NULL,
            check_out TIME NULL,
            is_late TINYINT(1) DEFAULT 0,
            late_minutes INT DEFAULT 0,
            marked_by VARCHAR(64) DEFAULT 'qr_scan',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_student_date (student_id, date)
        )");

        // Validate token
        $tokStmt = $db->prepare("SELECT * FROM qr_tokens WHERE token=? AND type='attendance' AND expires_at > NOW() LIMIT 1");
        $tokStmt->execute([$token]);
        $tok = $tokStmt->fetch();

        if (!$tok) {
            $error = ['type' => 'expired', 'msg' => 'QR code has expired or is invalid. Please open your Student App to get a fresh QR code.'];
        } else {
            $studentId = $tok['student_id'];
            $today     = date('Y-m-d');
            $now       = date('H:i:s');
            $nowDisplay = date('h:i A');

            // Get student + batch
            $stuStmt = $db->prepare("SELECT s.*, b.name as batch_name, b.start_time, b.end_time FROM students s LEFT JOIN batches b ON s.batch_id=b.id WHERE s.id=? LIMIT 1");
            $stuStmt->execute([$studentId]);
            $stu = $stuStmt->fetch();

            if (!$stu) {
                $error = ['type' => 'notfound', 'msg' => 'Student record not found.'];
            } else {
                // Check existing attendance
                $existStmt = $db->prepare("SELECT * FROM student_attendance WHERE student_id=? AND date=? LIMIT 1");
                $existStmt->execute([$studentId, $today]);
                $existing = $existStmt->fetch();

                $settings = $db->query("SELECT * FROM settings WHERE id=1")->fetch();
                $libName  = $settings['name'] ?? 'NAYI UDAAN LIBRARY';

                if ($existing && $existing['check_out']) {
                    // Both check-in and check-out done
                    $result = [
                        'action'    => 'already_complete',
                        'student'   => $stu,
                        'batch'     => $stu['batch_name'],
                        'check_in'  => date('h:i A', strtotime($existing['check_in'])),
                        'check_out' => date('h:i A', strtotime($existing['check_out'])),
                        'is_late'   => $existing['is_late'],
                        'late_min'  => $existing['late_minutes'],
                        'lib_name'  => $libName,
                    ];
                } elseif ($existing && !$existing['check_out']) {
                    // Check-in done, now check-out
                    $db->prepare("UPDATE student_attendance SET check_out=? WHERE student_id=? AND date=?")
                       ->execute([$now, $studentId, $today]);
                    $result = [
                        'action'    => 'check_out',
                        'student'   => $stu,
                        'batch'     => $stu['batch_name'],
                        'check_in'  => date('h:i A', strtotime($existing['check_in'])),
                        'check_out' => $nowDisplay,
                        'is_late'   => $existing['is_late'],
                        'late_min'  => $existing['late_minutes'],
                        'lib_name'  => $libName,
                    ];
                } else {
                    // First scan = check-in
                    $isLate = 0; $lateMinutes = 0;
                    if (!empty($stu['start_time'])) {
                        $batchStart  = strtotime($today . ' ' . $stu['start_time']);
                        $nowTs       = time();
                        $grace       = 15 * 60;
                        if ($nowTs > $batchStart + $grace) {
                            $isLate      = 1;
                            $lateMinutes = (int)round(($nowTs - $batchStart) / 60);
                        }
                    }
                    $db->prepare("INSERT INTO student_attendance (student_id, date, status, check_in, is_late, late_minutes, marked_by)
                        VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE check_in=VALUES(check_in), is_late=VALUES(is_late), late_minutes=VALUES(late_minutes)")
                       ->execute([$studentId, $today, 'present', $now, $isLate, $lateMinutes, 'qr_scan']);

                    // Sync to admin attendance table
                    $db->prepare("INSERT INTO attendance (student_id, attendance_date, status) VALUES (?,?,?) ON DUPLICATE KEY UPDATE status='present'")
                       ->execute([$studentId, $today, 'present']);

                    // Log activity
                    $lateNote = $isLate ? " ⚠ Late {$lateMinutes}min" : '';
                    $db->prepare("INSERT INTO activity_log (icon,bg,text) VALUES (?,?,?)")
                       ->execute(['📱','rgba(22,163,74,.14)',"<strong>{$stu['fname']} {$stu['lname']}</strong> checked in via QR at $nowDisplay$lateNote"]);

                    $result = [
                        'action'    => 'check_in',
                        'student'   => $stu,
                        'batch'     => $stu['batch_name'],
                        'check_in'  => $nowDisplay,
                        'check_out' => null,
                        'is_late'   => $isLate,
                        'late_min'  => $lateMinutes,
                        'lib_name'  => $libName,
                    ];
                }
            }
        }
    } catch (Exception $e) {
        $error = ['type' => 'error', 'msg' => 'System error: ' . $e->getMessage()];
    }
} else {
    $error = ['type' => 'notoken', 'msg' => 'No QR token provided. Please scan your personal QR code from the Student App.'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<title>QR Attendance — Scan Result</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{
  --ok:#16a34a;--ok-bg:#f0fdf4;--ok-border:#bbf7d0;
  --warn:#d97706;--warn-bg:#fffbeb;--warn-border:#fde68a;
  --err:#dc2626;--err-bg:#fff1f2;--err-border:#fecdd3;
  --blue:#3d6ff0;--blue-bg:#eff4ff;
  --tx:#0f172a;--tx2:#475569;--tx3:#94a3b8;
  --sf:#ffffff;--bg:#f1f5f9;
  --r:16px;
}
body{font-family:'DM Sans',sans-serif;background:var(--bg);min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:20px}

.card{background:#fff;border-radius:24px;width:100%;max-width:400px;overflow:hidden;box-shadow:0 20px 60px rgba(15,23,42,.12),0 4px 16px rgba(15,23,42,.06)}

/* Header strip */
.card-head{padding:28px 28px 20px;text-align:center;position:relative}
.card-head.ok{background:linear-gradient(135deg,#16a34a,#15803d)}
.card-head.warn{background:linear-gradient(135deg,#d97706,#b45309)}
.card-head.info{background:linear-gradient(135deg,#3d6ff0,#7c3aed)}
.card-head.err{background:linear-gradient(135deg,#dc2626,#b91c1c)}
.card-head.already{background:linear-gradient(135deg,#0284c7,#0369a1)}

.big-icon{font-size:56px;display:block;margin-bottom:10px;filter:drop-shadow(0 4px 12px rgba(0,0,0,.2))}
.action-label{font-family:'Syne',sans-serif;font-size:13px;font-weight:700;letter-spacing:2.5px;text-transform:uppercase;color:rgba(255,255,255,.75);margin-bottom:6px}
.action-title{font-family:'Syne',sans-serif;font-size:26px;font-weight:800;color:#fff;line-height:1.1}

/* Body */
.card-body{padding:24px 28px 28px}

/* Student card */
.stu-row{display:flex;align-items:center;gap:14px;margin-bottom:20px;padding:14px;background:#f8fafc;border-radius:14px;border:1px solid #e2e8f0}
.stu-av{width:48px;height:48px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-size:17px;font-weight:700;color:#fff;flex-shrink:0}
.stu-name{font-family:'Syne',sans-serif;font-size:16px;font-weight:700;color:var(--tx)}
.stu-id{font-size:12px;color:var(--tx3);font-family:monospace;margin-top:2px}
.stu-batch{font-size:12px;color:var(--tx2);margin-top:3px}

/* Time row */
.time-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px}
.time-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:12px;text-align:center}
.time-label{font-size:10px;font-weight:600;letter-spacing:1px;text-transform:uppercase;color:var(--tx3);margin-bottom:4px}
.time-val{font-family:'Syne',sans-serif;font-size:20px;font-weight:700;color:var(--tx)}
.time-val.green{color:var(--ok)}
.time-val.blue{color:#0284c7}

/* Late badge */
.late-badge{display:flex;align-items:center;gap:6px;background:var(--warn-bg);border:1px solid var(--warn-border);border-radius:10px;padding:10px 14px;margin-bottom:16px}
.late-badge-icon{font-size:18px}
.late-text{font-size:13px;font-weight:600;color:var(--warn)}

/* Status message */
.status-box{border-radius:12px;padding:14px;margin-bottom:16px;font-size:13.5px;font-weight:500;line-height:1.5}
.status-box.ok{background:var(--ok-bg);border:1px solid var(--ok-border);color:#166534}
.status-box.warn{background:var(--warn-bg);border:1px solid var(--warn-border);color:#92400e}
.status-box.err{background:var(--err-bg);border:1px solid var(--err-border);color:#991b1b}
.status-box.info{background:var(--blue-bg);border:1px solid #bfcffd;color:#1e40af}

/* Date chip */
.date-chip{display:inline-flex;align-items:center;gap:5px;font-size:12px;color:var(--tx3);background:#f1f5f9;border-radius:8px;padding:6px 10px;margin-bottom:16px}

/* Footer */
.lib-footer{text-align:center;font-size:11.5px;color:var(--tx3);padding-top:14px;border-top:1px solid #f1f5f9}
.lib-name{font-weight:600;color:var(--tx2);font-size:13px;margin-bottom:2px}

/* Error card */
.err-icon{font-size:64px;display:block;text-align:center;margin-bottom:14px}
.err-title{font-family:'Syne',sans-serif;font-size:20px;font-weight:700;color:var(--tx);text-align:center;margin-bottom:8px}
.err-msg{font-size:14px;color:var(--tx2);text-align:center;line-height:1.6;margin-bottom:20px}
.back-btn{display:block;background:var(--blue);color:#fff;text-align:center;padding:13px;border-radius:12px;font-weight:600;font-size:14px;text-decoration:none;font-family:'Syne',sans-serif}
.back-btn:hover{background:#2d5de0}

/* Animations */
@keyframes popIn{from{opacity:0;transform:scale(.8) translateY(20px)}to{opacity:1;transform:scale(1) translateY(0)}}
@keyframes fadeUp{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
.card{animation:popIn .45s cubic-bezier(.34,1.56,.64,1) both}
.card-body>*{animation:fadeUp .35s ease both}
.card-body>*:nth-child(1){animation-delay:.1s}
.card-body>*:nth-child(2){animation-delay:.18s}
.card-body>*:nth-child(3){animation-delay:.26s}
.card-body>*:nth-child(4){animation-delay:.34s}

.powered{margin-top:18px;font-size:11px;color:#94a3b8;text-align:center}
</style>
</head>
<body>

<?php if ($error): ?>
<div class="card">
  <div class="card-head err" style="padding:36px 28px">
    <span class="big-icon"><?= $error['type']==='expired' ? '⏰' : ($error['type']==='notfound'?'🔍':'⚠️') ?></span>
    <div class="action-title"><?= $error['type']==='expired' ? 'QR Expired' : ($error['type']==='notfound'?'Not Found':'Error') ?></div>
  </div>
  <div class="card-body">
    <p class="err-msg"><?= htmlspecialchars($error['msg']) ?></p>
    <a href="javascript:history.back()" class="back-btn">← Go Back</a>
  </div>
</div>

<?php elseif ($result): ?>
<?php
  $stu     = $result['student'];
  $action  = $result['action'];
  $initials= strtoupper(substr($stu['fname'],0,1).substr($stu['lname']??'',0,1));
  $color   = $stu['color'] ?? '#3d6ff0';
  $headClass = $action==='check_in' ? 'ok' : ($action==='check_out' ? 'info' : 'already');
  $bigIcon   = $action==='check_in' ? '✅' : ($action==='check_out' ? '👋' : '☑️');
  $actLabel  = $action==='check_in' ? 'CHECKED IN' : ($action==='check_out' ? 'CHECKED OUT' : 'ALREADY DONE');
  $actTitle  = $action==='check_in' ? 'Welcome!' : ($action==='check_out' ? 'See you!' : 'All Done');
?>
<div class="card">
  <div class="card-head <?= $headClass ?>">
    <span class="big-icon"><?= $bigIcon ?></span>
    <div class="action-label"><?= $actLabel ?></div>
    <div class="action-title"><?= $actTitle ?></div>
  </div>
  <div class="card-body">

    <div class="date-chip">
      📅 <?= date('D, d M Y') ?> &nbsp;·&nbsp; <?= date('h:i A') ?>
    </div>

    <div class="stu-row">
      <div class="stu-av" style="background:<?= htmlspecialchars($color) ?>"><?= $initials ?></div>
      <div>
        <div class="stu-name"><?= htmlspecialchars($stu['fname'].' '.($stu['lname']??'')) ?></div>
        <div class="stu-id"><?= htmlspecialchars($stu['id']) ?></div>
        <?php if($result['batch']): ?>
        <div class="stu-batch">🏫 <?= htmlspecialchars($result['batch']) ?></div>
        <?php endif; ?>
      </div>
    </div>

    <div class="time-grid">
      <div class="time-box">
        <div class="time-label">Check In</div>
        <div class="time-val green"><?= $result['check_in'] ?? '—' ?></div>
      </div>
      <div class="time-box">
        <div class="time-label">Check Out</div>
        <div class="time-val blue"><?= $result['check_out'] ?? '—' ?></div>
      </div>
    </div>

    <?php if ($result['is_late'] && $result['late_min'] > 0): ?>
    <div class="late-badge">
      <span class="late-badge-icon">⚠️</span>
      <div class="late-text">Late by <?= $result['late_min'] ?> minutes</div>
    </div>
    <?php endif; ?>

    <?php if ($action === 'check_in'): ?>
    <div class="status-box ok">🎓 Attendance marked successfully! Have a productive session.</div>
    <?php elseif ($action === 'check_out'): ?>
    <div class="status-box info">👋 Check-out recorded. See you next time!</div>
    <?php else: ?>
    <div class="status-box warn">☑️ Your attendance is already complete for today.<br>Check-in: <?= $result['check_in'] ?> · Check-out: <?= $result['check_out'] ?></div>
    <?php endif; ?>

    <div class="lib-footer">
      <div class="lib-name">📚 <?= htmlspecialchars($result['lib_name']) ?></div>
      <div>Attendance recorded · <?= date('d M Y') ?></div>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="powered">Powered by OPTMS Tech ERP v6</div>

</body>
</html>
