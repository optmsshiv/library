<?php
/**
 * OPTMS Tech Study Library — Backend API
 * File  : api/index.php
 * Place : same directory as index.php  →  api/index.php
 *
 * All responses are JSON.
 * GET  requests  : api/index.php?action=xxx&param=yyy
 * POST requests  : api/index.php?action=xxx   body: JSON
 */

session_start();

/* ── CORS / Content-Type ── */
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

/* ── Auth guard (skip for login action if you add one later) ── */
if (empty($_SESSION['staff_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

/* ── DB connection ── */
require_once __DIR__ . '/../includes/db.php';   // define $pdo (PDO instance)
// db.php example:
// <?php
// $pdo = new PDO('mysql:host=localhost;dbname=edrppymy_udaanlibrary;charset=utf8mb4',
//               'db_user', 'db_pass',
//               [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
//                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

/* ── Helpers ── */
function ok(array $data = [])  { echo json_encode(array_merge(['success' => true], $data)); exit; }
function err(string $msg, int $code = 200) { http_response_code($code); echo json_encode(['error' => $msg]); exit; }

function bodyJson(): array {
    $raw = file_get_contents('php://input');
    return $raw ? (json_decode($raw, true) ?? []) : [];
}

function generateId(PDO $pdo, string $table, string $prefix, string $col = 'id'): string {
    $stmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
    $n = (int)$stmt->fetchColumn() + 1;
    // keep bumping until unique
    do {
        $id = $prefix . str_pad($n, 3, '0', STR_PAD_LEFT);
        $s  = $pdo->prepare("SELECT 1 FROM `$table` WHERE `$col` = ?");
        $s->execute([$id]);
        $n++;
    } while ($s->fetchColumn());
    return $id;
}

/* ── Route ── */
$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {

    // ════════════════════════════════════════════════════════
    // GET — Dashboard (all tables in one round-trip)
    // ════════════════════════════════════════════════════════
    case 'get_dashboard':
        $data = [];

        $data['batches']      = $pdo->query("SELECT * FROM batches ORDER BY start_time")->fetchAll();
        $data['students']     = $pdo->query("SELECT * FROM students ORDER BY created_at DESC")->fetchAll();
        $data['books']        = $pdo->query("SELECT * FROM books ORDER BY title")->fetchAll();
        $data['transactions'] = $pdo->query("SELECT * FROM transactions ORDER BY created_at DESC")->fetchAll();
        $data['expenses']     = $pdo->query("SELECT * FROM expenses ORDER BY expense_date DESC")->fetchAll();
        $data['invoices']     = $pdo->query("SELECT * FROM invoices ORDER BY created_at DESC")->fetchAll();
        $data['activities']   = $pdo->query("SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 30")->fetchAll();
        $data['notifications']= $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 50")->fetchAll();
        $data['staff']        = $pdo->query("SELECT id,name,role,email,phone,username,perm_students,perm_fees,perm_books,perm_expenses,perm_reports,perm_staff,perm_settings,status FROM staff ORDER BY name")->fetchAll();
        $data['settings']     = $pdo->query("SELECT * FROM settings LIMIT 1")->fetch() ?: [];

        // Current staff member (for nav-perm enforcement in JS)
        $me = $pdo->prepare("SELECT id,name,role,email,perm_students,perm_fees,perm_books,perm_expenses,perm_reports,perm_staff,perm_settings FROM staff WHERE id = ?");
        $me->execute([$_SESSION['staff_id']]);
        $data['me'] = $me->fetch() ?: null;

        echo json_encode($data);
        break;

    // ════════════════════════════════════════════════════════
    // GET — Attendance for a given date
    // ════════════════════════════════════════════════════════
    case 'get_attendance':
        $date = $_GET['date'] ?? date('Y-m-d');
        $rows = $pdo->prepare("SELECT student_id, status FROM attendance WHERE attendance_date = ?");
        $rows->execute([$date]);
        $map = [];
        foreach ($rows->fetchAll() as $r) $map[$r['student_id']] = $r['status'];
        echo json_encode(['attendance' => $map]);
        break;

    // ════════════════════════════════════════════════════════
    // GET — WA send log
    // ════════════════════════════════════════════════════════
    case 'get_wa_log':
        $rows = $pdo->query("SELECT * FROM wa_send_log ORDER BY created_at DESC LIMIT 100")->fetchAll();
        echo json_encode($rows);
        break;

    // ════════════════════════════════════════════════════════
    // GET — Staff attendance summary (for salary page)
    // ════════════════════════════════════════════════════════
    case 'get_staff_attendance_summary':
        $month = $_GET['month'] ?? date('Y-m');  // format: 2026-03
        // staff_attendance table is expected if you add staff attendance feature.
        // If not yet created, return empty array gracefully.
        try {
            $rows = $pdo->prepare(
                "SELECT sf.id,
                        SUM(sa.status = 'present') AS present,
                        SUM(sa.status = 'absent')  AS absent,
                        SUM(sa.status = 'half')    AS half
                 FROM staff sf
                 LEFT JOIN staff_attendance sa
                        ON sa.staff_id = sf.id AND DATE_FORMAT(sa.att_date,'%Y-%m') = ?
                 GROUP BY sf.id"
            );
            $rows->execute([$month]);
            echo json_encode($rows->fetchAll());
        } catch (\Exception $e) {
            echo json_encode([]);
        }
        break;

    // ════════════════════════════════════════════════════════
    // GET — Clear all notifications
    // ════════════════════════════════════════════════════════
    case 'clear_notifs':
        $pdo->exec("DELETE FROM notifications");
        ok();

    // ════════════════════════════════════════════════════════
    // POST — Add student
    // ════════════════════════════════════════════════════════
    case 'add_student': {
        $d = bodyJson();
        $fn    = trim($d['fname']          ?? '');
        $ln    = trim($d['lname']          ?? '');
        $bId   = trim($d['batch_id']       ?? '');
        if (!$fn || !$bId) err('First name and batch are required');

        // Fetch batch fee if not provided
        $batch = null;
        if ($bId) {
            $bs = $pdo->prepare("SELECT * FROM batches WHERE id = ?");
            $bs->execute([$bId]);
            $batch = $bs->fetch();
        }

        $baseFee      = (int)($d['base_fee'] ?? ($batch['base_fee'] ?? 0));
        $discType     = $d['discount_type']  ?? 'none';
        $discVal      = (float)($d['discount_value'] ?? 0);
        $discReason   = trim($d['discount_reason'] ?? '');

        // Calculate net fee
        $netFee = $baseFee;
        if ($discType === 'flat')    $netFee = max(0, $baseFee - $discVal);
        if ($discType === 'percent') $netFee = max(0, $baseFee - round($baseFee * $discVal / 100));

        $seatType = $d['seat_type'] ?? 'non-ac';
        if ($seatType === 'ac' && $batch) $netFee += (int)($batch['ac_extra'] ?? 0);

        $joinDate = $d['join_date'] ?? date('Y-m-d');
        // Due date = join_date + 30 days
        $dueDate  = date('Y-m-d', strtotime($joinDate . ' +30 days'));

        $colors = ['#e67e22','#c0444f','#3d6ff0','#16a34a','#7c3aed','#0284c7','#d97706','#ea580c'];
        $color  = $colors[array_rand($colors)];

        $id = generateId($pdo, 'students', 'STU-');

        $stmt = $pdo->prepare(
            "INSERT INTO students
             (id, fname, lname, phone, batch_id, seat_type, seat, base_fee,
              discount_type, discount_value, discount_reason, net_fee,
              paid_amt, fee_status, due_date, course, color, join_date)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,0,'pending',?,?,?,?)"
        );
        $stmt->execute([
            $id, $fn, $ln,
            $d['phone']   ?? '',
            $bId,
            $seatType,
            $d['seat']    ?? '',
            $baseFee, $discType, $discVal, $discReason, $netFee,
            $dueDate,
            $d['course']  ?? '',
            $color,
            $joinDate
        ]);

        // Log activity
        $pdo->prepare("INSERT INTO activity_log (icon,bg,text) VALUES (?,?,?)")
            ->execute(['👨‍🎓','rgba(74,124,111,.14)',"New student <strong>$fn $ln</strong> enrolled"]);

        ok(['id' => $id]);
    }

    // ════════════════════════════════════════════════════════
    // POST — Update student basic info (from profile edit)
    // ════════════════════════════════════════════════════════
    case 'update_student': {
        $d = bodyJson();
        $id = $d['id'] ?? '';
        if (!$id) err('Student ID required');
        $stmt = $pdo->prepare(
            "UPDATE students SET fname=?,lname=?,phone=?,course=? WHERE id=?"
        );
        $stmt->execute([
            trim($d['fname']  ?? ''),
            trim($d['lname']  ?? ''),
            trim($d['phone']  ?? ''),
            trim($d['course'] ?? ''),
            $id
        ]);
        ok();
    }

    // ════════════════════════════════════════════════════════
    // POST — Delete student
    // ════════════════════════════════════════════════════════
    case 'delete_student': {
        $d = bodyJson();
        $id = $d['id'] ?? '';
        if (!$id) err('ID required');
        $pdo->prepare("DELETE FROM students WHERE id=?")->execute([$id]);
        ok();
    }

    // ════════════════════════════════════════════════════════
    // POST — Save batch (add or edit)
    // ════════════════════════════════════════════════════════
    case 'save_batch': {
        $d  = bodyJson();
        $nm = trim($d['name']       ?? '');
        $st = $d['start_time']      ?? '';
        $et = $d['end_time']        ?? '';
        $ts = (int)($d['total_seats']?? 80);
        $fe = (int)($d['base_fee']  ?? 0);
        $ac = (int)($d['ac_extra']  ?? 0);
        if (!$nm || !$st || !$et || !$ts || !$fe) err('Fill all required fields');

        if (!empty($d['id'])) {
            // Edit
            $pdo->prepare("UPDATE batches SET name=?,start_time=?,end_time=?,total_seats=?,base_fee=?,ac_extra=? WHERE id=?")
                ->execute([$nm,$st,$et,$ts,$fe,$ac,$d['id']]);
        } else {
            // Add
            $id = generateId($pdo, 'batches', 'BT-');
            $pdo->prepare("INSERT INTO batches (id,name,start_time,end_time,total_seats,base_fee,ac_extra) VALUES (?,?,?,?,?,?,?)")
                ->execute([$id,$nm,$st,$et,$ts,$fe,$ac]);
            $pdo->prepare("INSERT INTO activity_log (icon,bg,text) VALUES (?,?,?)")
                ->execute(['🆕','rgba(74,124,111,.14)',"Batch \"<strong>$nm</strong>\" added"]);
        }
        ok();
    }

    // ════════════════════════════════════════════════════════
    // POST — Delete batch
    // ════════════════════════════════════════════════════════
    case 'delete_batch': {
        $d = bodyJson();
        $id = $d['id'] ?? '';
        if (!$id) err('ID required');
        $pdo->prepare("DELETE FROM batches WHERE id=?")->execute([$id]);
        ok();
    }

    // ════════════════════════════════════════════════════════
    // POST — Allocate seat
    // ════════════════════════════════════════════════════════
    case 'alloc_seat': {
        $d     = bodyJson();
        $stuId = $d['student_id'] ?? '';
        $bId   = $d['batch_id']   ?? '';
        $seat  = trim($d['seat']  ?? '');
        if (!$stuId || !$bId || !$seat) err('Fill all fields');

        // Check seat not already taken in same batch
        $chk = $pdo->prepare("SELECT id FROM students WHERE batch_id=? AND seat=? AND id!=?");
        $chk->execute([$bId, $seat, $stuId]);
        if ($chk->fetch()) err("Seat $seat is already taken in this batch");

        $pdo->prepare("UPDATE students SET seat=?,batch_id=? WHERE id=?")
            ->execute([$seat, $bId, $stuId]);
        $pdo->prepare("UPDATE batches SET occupied_seats = (SELECT COUNT(*) FROM students WHERE batch_id=? AND seat IS NOT NULL AND seat != '') WHERE id=?")
            ->execute([$bId, $bId]);

        // Activity log
        $pdo->prepare("INSERT INTO activity_log (icon,bg,text) VALUES (?,?,?)")
            ->execute(['🪑','rgba(196,125,43,.14)',"Seat <strong>$seat</strong> allocated"]);

        ok();
    }

    // ════════════════════════════════════════════════════════
    // POST — Add book
    // ════════════════════════════════════════════════════════
    case 'add_book': {
        $d  = bodyJson();
        $tl = trim($d['title'] ?? '');
        if (!$tl) err('Title required');
        $cp = max(1, (int)($d['copies'] ?? 1));
        $emojis = ['📘','📙','📗','📕','📔','📒'];
        $emoji  = $emojis[array_rand($emojis)];
        $id = generateId($pdo, 'books', 'BK-');
        $pdo->prepare("INSERT INTO books (id,title,author,isbn,category,copies,available,shelf,emoji) VALUES (?,?,?,?,?,?,?,?,?)")
            ->execute([$id,$tl,$d['author']??'',$d['isbn']??'',$d['category']??'Other',$cp,$cp,$d['shelf']??'',$emoji]);
        $pdo->prepare("INSERT INTO activity_log (icon,bg,text) VALUES (?,?,?)")
            ->execute(['📚','rgba(58,122,176,.14)',"Book \"<strong>$tl</strong>\" added"]);
        ok(['id' => $id]);
    }

    // ════════════════════════════════════════════════════════
    // POST — Issue book
    // ════════════════════════════════════════════════════════
    case 'issue_book': {
        $d     = bodyJson();
        $stuId = $d['student_id'] ?? '';
        $bkId  = $d['book_id']    ?? '';
        if (!$stuId || !$bkId) err('Select student and book');

        $bk = $pdo->prepare("SELECT * FROM books WHERE id=?");
        $bk->execute([$bkId]);
        $book = $bk->fetch();
        if (!$book)               err('Book not found');
        if ($book['available'] < 1) err('No copies available');

        $today   = date('Y-m-d');
        $loanDays = (int)($pdo->query("SELECT loan_days FROM settings LIMIT 1")->fetchColumn() ?: 14);
        $dueDate  = date('Y-m-d', strtotime("+$loanDays days"));

        $txId = 'TX-' . time();
        $pdo->prepare("INSERT INTO transactions (id,student_id,book_id,issue_date,due_date,status) VALUES (?,?,?,?,?,'issued')")
            ->execute([$txId,$stuId,$bkId,$today,$dueDate]);
        $pdo->prepare("UPDATE books SET available = available - 1 WHERE id=?")->execute([$bkId]);

        $stu = $pdo->prepare("SELECT fname FROM students WHERE id=?");
        $stu->execute([$stuId]);
        $name = $stu->fetchColumn() ?: 'Student';
        $pdo->prepare("INSERT INTO activity_log (icon,bg,text) VALUES (?,?,?)")
            ->execute(['📤','rgba(124,92,191,.14)',"<strong>$name</strong> issued \"{$book['title']}\""]);

        ok(['id' => $txId]);
    }

    // ════════════════════════════════════════════════════════
    // POST — Return book
    // ════════════════════════════════════════════════════════
    case 'return_book': {
        $d      = bodyJson();
        $txId   = $d['tx_id']    ?? '';
        $fine   = (int)($d['fine'] ?? 0);
        $cond   = $d['condition'] ?? 'Good';
        if (!$txId) err('Transaction ID required');

        $tx = $pdo->prepare("SELECT * FROM transactions WHERE id=?");
        $tx->execute([$txId]);
        $row = $tx->fetch();
        if (!$row) err('Transaction not found');

        $today = date('Y-m-d');
        $pdo->prepare("UPDATE transactions SET status='returned', return_date=?, fine=? WHERE id=?")
            ->execute([$today, $fine, $txId]);

        if ($cond !== 'Lost') {
            $pdo->prepare("UPDATE books SET available = available + 1 WHERE id=?")->execute([$row['book_id']]);
        }

        $bk = $pdo->prepare("SELECT title FROM books WHERE id=?");
        $bk->execute([$row['book_id']]);
        $title = $bk->fetchColumn() ?: 'Book';
        $stu = $pdo->prepare("SELECT fname FROM students WHERE id=?");
        $stu->execute([$row['student_id']]);
        $name = $stu->fetchColumn() ?: 'Student';
        $extra = $fine > 0 ? " Fine ₹$fine" : '';
        $pdo->prepare("INSERT INTO activity_log (icon,bg,text) VALUES (?,?,?)")
            ->execute(['📩','rgba(58,125,94,.14)',"<strong>$name</strong> returned \"$title\"$extra"]);

        ok();
    }

    // ════════════════════════════════════════════════════════
    // POST — Collect fee
    // ════════════════════════════════════════════════════════
    case 'collect_fee': {
        $d     = bodyJson();
        $stuId = $d['student_id'] ?? '';
        $amt   = (int)($d['amount'] ?? 0);
        $mode  = $d['mode']  ?? 'Cash';
        $month = $d['month'] ?? date('F Y');
        if (!$stuId || !$amt) err('Student and amount required');

        $stu = $pdo->prepare("SELECT * FROM students WHERE id=?");
        $stu->execute([$stuId]);
        $s = $stu->fetch();
        if (!$s) err('Student not found');

        $newPaid = min($s['net_fee'], $s['paid_amt'] + $amt);
        $bal     = $s['net_fee'] - $newPaid;
        $status  = $bal <= 0 ? 'paid' : 'partial';
        $today   = date('Y-m-d');

        $pdo->prepare("UPDATE students SET paid_amt=?,fee_status=?,paid_on=? WHERE id=?")
            ->execute([$newPaid,$status,$today,$stuId]);

        // Invoice
        $invId = generateId($pdo, 'invoices', 'INV-');
        $pdo->prepare(
            "INSERT INTO invoices (id,student_id,type,amount,base_fee,discount,net_fee,paid_amt,balance,invoice_date,month,mode,status)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)"
        )->execute([
            $invId, $stuId, 'Monthly Fee', $amt,
            $s['base_fee'],
            $s['base_fee'] - $s['net_fee'],
            $s['net_fee'], $newPaid, $bal,
            $today, $month, $mode, $status
        ]);

        // Activity + notification
        $name = $s['fname'] . ' ' . $s['lname'];
        $pendingText = $bal > 0 ? "(₹$bal pending)" : "(full)";
        $pdo->prepare("INSERT INTO activity_log (icon,bg,text) VALUES (?,?,?)")
            ->execute(['💳','rgba(58,125,94,.14)',"<strong>{$s['fname']}</strong> paid ₹$amt via $mode $pendingText"]);
        $pdo->prepare("INSERT INTO notifications (type,title,msg) VALUES (?,?,?)")
            ->execute(['success','Fee Collected',"$name paid ₹$amt — $status"]);

        ok(['balance' => $bal, 'fee_status' => $status, 'invoice_id' => $invId]);
    }

    // ════════════════════════════════════════════════════════
    // POST — Generate invoice (manual)
    // ════════════════════════════════════════════════════════
    case 'gen_invoice': {
        $d     = bodyJson();
        $stuId = $d['student_id'] ?? '';
        $amt   = (int)($d['amount'] ?? 0);
        if (!$stuId || !$amt) err('Fill required fields');

        $stu = $pdo->prepare("SELECT * FROM students WHERE id=?");
        $stu->execute([$stuId]);
        $s = $stu->fetch();
        if (!$s) err('Student not found');

        $typeMap = ['fee' => 'Monthly Fee', 'fine' => 'Book Fine', 'other' => 'Other'];
        $type    = $typeMap[$d['type'] ?? 'fee'] ?? 'Monthly Fee';
        $month   = $d['month'] ?? date('F Y');
        $today   = date('Y-m-d');

        $invId = generateId($pdo, 'invoices', 'INV-');
        $pdo->prepare(
            "INSERT INTO invoices (id,student_id,type,amount,base_fee,discount,net_fee,paid_amt,balance,invoice_date,month,mode,status)
             VALUES (?,?,?,?,?,?,?,?,0,?,?,?,?)"
        )->execute([
            $invId,$stuId,$type,$amt,
            $s['base_fee'], $s['base_fee']-$s['net_fee'], $s['net_fee'], $amt,
            $today,$month,'Manual','paid'
        ]);

        ok(['id' => $invId]);
    }

    // ════════════════════════════════════════════════════════
    // POST — Add expense
    // ════════════════════════════════════════════════════════
    case 'add_expense': {
        $d  = bodyJson();
        $nm = trim($d['name']   ?? '');
        $am = (int)($d['amount'] ?? 0);
        if (!$nm || !$am) err('Name and amount required');

        $catEmoji = [
            'Utilities'=>'💡','Staff'=>'👥','Maintenance'=>'🔧',
            'Supplies'=>'📦','Books'=>'📚','Other'=>'💰'
        ];
        $cat   = $d['category'] ?? 'Other';
        $emoji = $catEmoji[$cat] ?? '💰';
        $date  = $d['date']  ?? date('Y-m-d');
        $notes = $d['notes'] ?? '';

        // Normalise date (frontend may send "28 Mar 2026" format)
        $parsed = strtotime($date);
        if ($parsed === false) $parsed = time();
        $date = date('Y-m-d', $parsed);

        $id = generateId($pdo, 'expenses', 'EX-');
        $pdo->prepare("INSERT INTO expenses (id,name,amount,category,expense_date,notes,emoji) VALUES (?,?,?,?,?,?,?)")
            ->execute([$id,$nm,$am,$cat,$date,$notes,$emoji]);

        ok(['id' => $id]);
    }

    // ════════════════════════════════════════════════════════
    // POST — Delete expense
    // ════════════════════════════════════════════════════════
    case 'delete_expense': {
        $d = bodyJson();
        $id = $d['id'] ?? '';
        if (!$id) err('ID required');
        $pdo->prepare("DELETE FROM expenses WHERE id=?")->execute([$id]);
        ok();
    }

    // ════════════════════════════════════════════════════════
    // POST — Save attendance (bulk upsert)
    // ════════════════════════════════════════════════════════
    case 'save_attendance': {
        $d    = bodyJson();
        $date = $d['date'] ?? date('Y-m-d');
        $att  = $d['attendance'] ?? [];
        if (!$att || !is_array($att)) err('No attendance data');

        $stmt = $pdo->prepare(
            "INSERT INTO attendance (student_id,attendance_date,status) VALUES (?,?,?)
             ON DUPLICATE KEY UPDATE status=VALUES(status)"
        );
        foreach ($att as $stuId => $status) {
            if (!in_array($status,['present','absent'])) $status = 'present';
            $stmt->execute([$stuId, $date, $status]);
        }
        ok(['count' => count($att)]);
    }

    // ════════════════════════════════════════════════════════
    // POST — Save staff attendance
    // ════════════════════════════════════════════════════════
    case 'save_staff_attendance': {
        $d    = bodyJson();
        $date = $d['date'] ?? date('Y-m-d');
        $att  = $d['attendance'] ?? [];

        // Create table if missing (graceful)
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS `staff_attendance` (
              `id`        INT AUTO_INCREMENT PRIMARY KEY,
              `staff_id`  VARCHAR(30) NOT NULL,
              `att_date`  DATE NOT NULL,
              `status`    ENUM('present','absent','half') DEFAULT 'present',
              UNIQUE KEY `uq_sa` (`staff_id`,`att_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $stmt = $pdo->prepare(
            "INSERT INTO staff_attendance (staff_id,att_date,status) VALUES (?,?,?)
             ON DUPLICATE KEY UPDATE status=VALUES(status)"
        );
        foreach ($att as $staffId => $status) {
            if (!in_array($status,['present','absent','half'])) $status = 'present';
            $stmt->execute([$staffId, $date, $status]);
        }
        ok(['count' => count($att)]);
    }

    // ════════════════════════════════════════════════════════
    // POST — Save staff (add or edit)
    // ════════════════════════════════════════════════════════
    case 'save_staff': {
        $d  = bodyJson();
        $nm = trim($d['name']  ?? '');
        $rl = $d['role']       ?? 'librarian';
        $em = trim($d['email'] ?? '');
        if (!$nm || !$em) err('Name and email required');

        $ph  = $d['phone']    ?? '';
        $un  = $d['username'] ?? strtolower(explode(' ', $nm)[0]);
        $pw  = $d['password'] ?? '';
        $p   = $d['perms']    ?? [];

        $perms = [
            'perm_students'  => !empty($p['students'])  ? 1 : 0,
            'perm_fees'      => !empty($p['fees'])      ? 1 : 0,
            'perm_books'     => !empty($p['books'])     ? 1 : 0,
            'perm_expenses'  => !empty($p['expenses'])  ? 1 : 0,
            'perm_reports'   => !empty($p['reports'])   ? 1 : 0,
            'perm_staff'     => !empty($p['staff'])     ? 1 : 0,
            'perm_settings'  => !empty($p['settings'])  ? 1 : 0,
        ];

        if (!empty($d['id'])) {
            // Edit
            $sets = "name=?,role=?,email=?,phone=?,perm_students=?,perm_fees=?,perm_books=?,perm_expenses=?,perm_reports=?,perm_staff=?,perm_settings=?";
            $vals = [$nm,$rl,$em,$ph,
                     $perms['perm_students'],$perms['perm_fees'],$perms['perm_books'],
                     $perms['perm_expenses'],$perms['perm_reports'],$perms['perm_staff'],
                     $perms['perm_settings']];
            if ($pw) { $sets .= ',password_hash=?'; $vals[] = password_hash($pw, PASSWORD_BCRYPT); }
            $vals[] = $d['id'];
            $pdo->prepare("UPDATE staff SET $sets WHERE id=?")->execute($vals);
            ok(['id' => $d['id']]);
        } else {
            // Add
            if (!$pw) err('Password required for new staff');
            // Check username unique
            $cx = $pdo->prepare("SELECT 1 FROM staff WHERE username=?");
            $cx->execute([$un]);
            if ($cx->fetchColumn()) $un .= rand(10,99);

            $id = generateId($pdo, 'staff', 'SF-');
            $pdo->prepare(
                "INSERT INTO staff (id,name,role,email,phone,username,password_hash,
                 perm_students,perm_fees,perm_books,perm_expenses,perm_reports,perm_staff,perm_settings)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
            )->execute([
                $id,$nm,$rl,$em,$ph,$un,
                password_hash($pw, PASSWORD_BCRYPT),
                $perms['perm_students'],$perms['perm_fees'],$perms['perm_books'],
                $perms['perm_expenses'],$perms['perm_reports'],$perms['perm_staff'],
                $perms['perm_settings']
            ]);
            ok(['id' => $id]);
        }
    }

    // ════════════════════════════════════════════════════════
    // POST — Delete staff
    // ════════════════════════════════════════════════════════
    case 'delete_staff': {
        $d = bodyJson();
        $id = $d['id'] ?? '';
        if (!$id) err('ID required');
        if ($id === 'SF-001') err('Cannot delete the primary admin');
        $pdo->prepare("DELETE FROM staff WHERE id=?")->execute([$id]);
        ok();
    }

    // ════════════════════════════════════════════════════════
    // POST — Change password (logged-in staff)
    // ════════════════════════════════════════════════════════
    case 'change_password': {
        $d   = bodyJson();
        $cur = $d['current_password'] ?? '';
        $nw  = $d['new_password']     ?? '';
        if (!$cur || !$nw) err('Fill all fields');
        if (strlen($nw) < 6) err('Password must be 6+ characters');

        $s = $pdo->prepare("SELECT password_hash FROM staff WHERE id=?");
        $s->execute([$_SESSION['staff_id']]);
        $row = $s->fetch();
        if (!$row || !password_verify($cur, $row['password_hash'])) err('Current password is incorrect');

        $pdo->prepare("UPDATE staff SET password_hash=? WHERE id=?")
            ->execute([password_hash($nw, PASSWORD_BCRYPT), $_SESSION['staff_id']]);
        ok();
    }

    // ════════════════════════════════════════════════════════
    // POST — Mark notification read
    // ════════════════════════════════════════════════════════
    case 'mark_read': {
        $d = bodyJson();
        $id = (int)($d['id'] ?? 0);
        if (!$id) err('ID required');
        $pdo->prepare("UPDATE notifications SET is_read=1 WHERE id=?")->execute([$id]);
        ok();
    }

    // ════════════════════════════════════════════════════════
    // POST — Delete notification
    // ════════════════════════════════════════════════════════
    case 'delete_notif': {
        $d = bodyJson();
        $id = (int)($d['id'] ?? 0);
        if (!$id) err('ID required');
        $pdo->prepare("DELETE FROM notifications WHERE id=?")->execute([$id]);
        ok();
    }

    // ════════════════════════════════════════════════════════
    // POST — Save settings
    // ════════════════════════════════════════════════════════
    case 'save_settings': {
        $d = bodyJson();
        $pdo->prepare(
            "UPDATE settings SET name=?,phone=?,email=?,addr=?,fine_per_day=?,loan_days=?,wa_number=? WHERE id=1"
        )->execute([
            $d['name']      ?? '',
            $d['phone']     ?? '',
            $d['email']     ?? '',
            $d['addr']      ?? '',
            (int)($d['fine'] ?? 5),
            (int)($d['days'] ?? 14),
            $d['wa_number'] ?? '',
        ]);
        ok();
    }

    // ════════════════════════════════════════════════════════
    // POST — Log WhatsApp send
    // ════════════════════════════════════════════════════════
    case 'log_wa': {
        $d = bodyJson();
        $pdo->prepare("INSERT INTO wa_send_log (sent_to,preview,type) VALUES (?,?,?)")
            ->execute([
                $d['to']      ?? '',
                substr($d['preview'] ?? '', 0, 255),
                $d['type']    ?? 'single'
            ]);
        ok();
    }

    // ════════════════════════════════════════════════════════
    // POST — Renew student
    // ════════════════════════════════════════════════════════
    case 'renew_student': {
        $d      = bodyJson();
        $stuId  = $d['student_id']  ?? '';
        $amt    = (int)($d['amount']?? 0);
        $months = (int)($d['months']?? 1);
        $mode   = $d['mode']        ?? 'Cash';
        $newDue = $d['new_due_date']?? '';
        if (!$stuId) err('Student ID required');

        $stu = $pdo->prepare("SELECT * FROM students WHERE id=?");
        $stu->execute([$stuId]);
        $s = $stu->fetch();
        if (!$s) err('Student not found');

        // Extend due_date
        if (!$newDue) {
            $base   = max(time(), strtotime($s['due_date']));
            $newDue = date('Y-m-d', strtotime("+$months months", $base));
        }

        $newPaid  = $s['paid_amt'] + $amt;
        $bal      = max(0, $s['net_fee'] - $newPaid);
        $status   = $bal <= 0 ? 'paid' : ($newPaid > 0 ? 'partial' : 'pending');
        $today    = date('Y-m-d');

        $pdo->prepare("UPDATE students SET due_date=?,paid_amt=?,fee_status=?,paid_on=? WHERE id=?")
            ->execute([$newDue, $newPaid, $status, $today, $stuId]);

        // Invoice
        $invId = generateId($pdo, 'invoices', 'INV-');
        $monthLabel = date('F Y');
        $pdo->prepare(
            "INSERT INTO invoices (id,student_id,type,amount,base_fee,discount,net_fee,paid_amt,balance,invoice_date,month,mode,status)
             VALUES (?,?,?,?,?,?,?,?,0,?,?,?,?)"
        )->execute([
            $invId,$stuId,"Renewal ({$months}mo)",$amt,
            $s['base_fee'], $s['base_fee']-$s['net_fee'], $s['net_fee'], $amt,
            $today, $monthLabel, $mode, 'paid'
        ]);

        $name = $s['fname'] . ' ' . $s['lname'];
        $pdo->prepare("INSERT INTO activity_log (icon,bg,text) VALUES (?,?,?)")
            ->execute(['🔄','rgba(61,111,240,.14)',"Renewed <strong>$name</strong> for $months month(s)"]);
        $pdo->prepare("INSERT INTO notifications (type,title,msg) VALUES (?,?,?)")
            ->execute(['success','Renewal','Student '.$name.' renewed for '.$months.' month(s)']);

        ok(['invoice_id' => $invId]);
    }

    // ════════════════════════════════════════════════════════
    // Fallback
    // ════════════════════════════════════════════════════════
    default:
        err("Unknown action: $action", 400);
    }

} catch (\PDOException $e) {
    // Never leak stack traces in production; log internally
    error_log('[LibraryAPI] PDO Error: ' . $e->getMessage());
    err('Database error — please try again.', 500);
} catch (\Throwable $e) {
    error_log('[LibraryAPI] Error: ' . $e->getMessage());
    err('Server error — please try again.', 500);
}
