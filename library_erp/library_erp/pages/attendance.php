<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
requireLogin();

$db = getDB();

$date    = $_GET['date'] ?? date('Y-m-d');
$batchId = $_GET['batch'] ?? 'all';

// Handle save attendance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_attendance') {
    $postDate    = $_POST['date'] ?? date('Y-m-d');
    $statuses    = $_POST['status'] ?? [];

    foreach ($statuses as $studentId => $status) {
        $studentId = (int)$studentId;
        $status    = in_array($status, ['present','absent','late','leave']) ? $status : 'present';

        // Upsert attendance
        $db->prepare("
            INSERT INTO attendance (student_id, date, status, marked_by)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE status = VALUES(status), marked_by = VALUES(marked_by)
        ")->execute([$studentId, $postDate, $status, $_SESSION['user_id']]);
    }

    logActivity("Saved attendance for $postDate");
    $_SESSION['toast'] = ['msg' => "Attendance saved for ".date('d M Y', strtotime($postDate)), 'type' => 'ok'];
    header('Location: /pages/attendance.php?date='.$postDate.'&batch='.$batchId);
    exit;
}

// Fetch students
$where  = ["s.status='active'"];
$params = [];
if ($batchId !== 'all') {
    $where[]  = "s.batch_id = ?";
    $params[] = $batchId;
}
$whereSQL = implode(' AND ', $where);

$students = $db->prepare("
    SELECT s.*, b.name AS batch_name,
           COALESCE(a.status, 'present') AS att_status
    FROM students s
    LEFT JOIN batches b ON s.batch_id = b.id
    LEFT JOIN attendance a ON a.student_id = s.id AND a.date = ?
    WHERE $whereSQL
    ORDER BY b.name, s.first_name
");
$params = array_merge([$date], $params);
$students->execute($params);
$students = $students->fetchAll();

$present  = count(array_filter($students, fn($s) => $s['att_status'] === 'present'));
$absent   = count(array_filter($students, fn($s) => $s['att_status'] === 'absent'));
$total    = count($students);
$rate     = $total > 0 ? round($present / $total * 100) : 0;

$batches = $db->query("SELECT * FROM batches ORDER BY time_start")->fetchAll();

$currentPage = 'attendance';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="sec-hd">
  <div>
    <div class="sec-t">Attendance</div>
    <div class="sec-s"><?= date('l, d F Y', strtotime($date)) ?></div>
  </div>
  <div style="display:flex;gap:7px;align-items:center;flex-wrap:wrap">
    <input type="date" value="<?= $date ?>" onchange="window.location='?date='+this.value+'&batch=<?= $batchId ?>'" style="font-size:12px;padding:6px 10px">
    <select onchange="window.location='?date=<?= $date ?>&batch='+this.value" style="font-size:12px;padding:6px 9px">
      <option value="all" <?= $batchId==='all'?'selected':'' ?>>All Batches</option>
      <?php foreach ($batches as $b): ?>
      <option value="<?= $b['id'] ?>" <?= $batchId==$b['id']?'selected':'' ?>><?= htmlspecialchars($b['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</div>

<div class="stats-grid" style="grid-template-columns:repeat(4,1fr)">
  <div class="sc" style="--ca:var(--em)"><div class="s-lb">Present</div><div class="s-vl"><?= $present ?></div></div>
  <div class="sc" style="--ca:var(--ro)"><div class="s-lb">Absent</div><div class="s-vl"><?= $absent ?></div></div>
  <div class="sc" style="--ca:var(--gd)"><div class="s-lb">Rate</div><div class="s-vl"><?= $rate ?>%</div></div>
  <div class="sc" style="--ca:var(--ac)"><div class="s-lb">Total</div><div class="s-vl"><?= $total ?></div></div>
</div>

<form method="POST">
  <input type="hidden" name="action" value="save_attendance">
  <input type="hidden" name="date" value="<?= $date ?>">

  <div style="display:flex;gap:7px;margin-bottom:12px">
    <button type="button" class="btn bg" onclick="markAllStatus('present')">✓ All Present</button>
    <button type="button" class="btn bd" onclick="markAllStatus('absent')">✗ All Absent</button>
    <button type="submit" class="btn bp">💾 Save Attendance</button>
  </div>

  <div class="panel">
    <div class="tw"><table>
      <thead><tr><th>Student</th><th>Batch</th><th>Seat</th><th>Fee Status</th><th>Attendance</th><th>Toggle</th></tr></thead>
      <tbody>
        <?php foreach ($students as $s):
          $stCls = ['paid'=>'tpd','partial'=>'tor','pending'=>'tpn','overdue'=>'tod'][$s['fee_status']] ?? 'tpn';
          $isPresent = $s['att_status'] === 'present';
        ?>
        <tr id="row-<?= $s['id'] ?>">
          <td>
            <div class="si">
              <div class="sav" style="background:linear-gradient(135deg,var(--ac),var(--vi))">
                <?= strtoupper(substr($s['first_name'],0,1).substr($s['last_name']??'',0,1)) ?>
              </div>
              <div>
                <div style="font-weight:600;font-size:12px"><?= htmlspecialchars($s['first_name'].' '.$s['last_name']) ?></div>
                <div style="font-size:10px;color:var(--tx3);font-family:var(--fm)"><?= $s['student_code'] ?></div>
              </div>
            </div>
          </td>
          <td style="font-size:11px"><?= htmlspecialchars($s['batch_name'] ?? '—') ?></td>
          <td style="font-family:var(--fm);font-size:11px"><?= htmlspecialchars($s['seat'] ?? '—') ?></td>
          <td><span class="tag <?= $stCls ?>"><?= ucfirst($s['fee_status']) ?></span></td>
          <td>
            <select name="status[<?= $s['id'] ?>]" class="att-select" data-id="<?= $s['id'] ?>"
                    style="font-size:11.5px;padding:4px 8px;border-radius:5px">
              <option value="present" <?= $s['att_status']==='present'?'selected':'' ?>>Present</option>
              <option value="absent" <?= $s['att_status']==='absent'?'selected':'' ?>>Absent</option>
              <option value="late" <?= $s['att_status']==='late'?'selected':'' ?>>Late</option>
              <option value="leave" <?= $s['att_status']==='leave'?'selected':'' ?>>Leave</option>
            </select>
          </td>
          <td>
            <label class="toggle-wrap">
              <input type="checkbox" class="toggle-inp att-toggle" data-id="<?= $s['id'] ?>" <?= $isPresent?'checked':'' ?>>
              <span class="toggle-sl"></span>
            </label>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($students)): ?>
        <tr><td colspan="6"><div class="empty"><div class="ei">📋</div><div class="et">No students in this batch</div></div></td></tr>
        <?php endif; ?>
      </tbody>
    </table></div>
  </div>
  <div style="margin-top:10px">
    <button type="submit" class="btn bp">💾 Save Attendance</button>
  </div>
</form>

<script>
document.querySelectorAll('.att-toggle').forEach(toggle => {
  toggle.addEventListener('change', function() {
    const id = this.dataset.id;
    const sel = document.querySelector(`select[name="status[${id}]"]`);
    if (sel) sel.value = this.checked ? 'present' : 'absent';
  });
});
function markAllStatus(status) {
  document.querySelectorAll('.att-select').forEach(sel => sel.value = status);
  document.querySelectorAll('.att-toggle').forEach(t => t.checked = status === 'present');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
