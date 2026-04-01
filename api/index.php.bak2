<?php
/**
 * OPTMS Tech Study Library — Backend API
 * File  : api/index.php
 *
 * All responses are JSON.
 * GET  : api/index.php?action=xxx&param=yyy
 * POST : api/index.php?action=xxx   body: JSON
 */

session_start();

require_once __DIR__ . '/../includes/index.php';
// Provides: getDB(), jsonError(), jsonResponse(), getInput(), generateId()

/* ── Auth guard ── */
if (empty($_SESSION['staff_id'])) {
    jsonError('Not authenticated', 401);
}

$db     = getDB();
$action = $_REQUEST['action'] ?? '';
$d      = getInput();  // parsed JSON body for POST requests

/* ── tiny helpers local to this file ── */
function activity(string $icon, string $bg, string $text): void {
    global $db;
    $db->prepare("INSERT INTO activity_log (icon,bg,text) VALUES (?,?,?)")
       ->execute([$icon, $bg, $text]);
}
function notification(string $type, string $title, string $msg): void {
    global $db;
    $db->prepare("INSERT INTO notifications (type,title,msg) VALUES (?,?,?)")
       ->execute([$type, $title, $msg]);
}

try {
    switch ($action) {

    // ════════════════════════════════════════════════════════
    // GET — Dashboard (all tables in one round-trip)
    // ════════════════════════════════════════════════════════
    case 'get_dashboard':
        $data['batches']       = $db->query("SELECT * FROM batches ORDER BY start_time")->fetchAll();
        $data['students']      = $db->query("SELECT * FROM students ORDER BY created_at DESC")->fetchAll();
        $data['books']         = $db->query("SELECT * FROM books ORDER BY title")->fetchAll();
        $data['transactions']  = $db->query("SELECT * FROM transactions ORDER BY created_at DESC")->fetchAll();
        $data['expenses']      = $db->query("SELECT * FROM expenses ORDER BY expense_date DESC")->fetchAll();
        $data['invoices']      = $db->query("SELECT * FROM invoices ORDER BY created_at DESC")->fetchAll();
        $data['activities']    = $db->query("SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 30")->fetchAll();
        $data['notifications'] = $db->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 50")->fetchAll();
        $data['staff']         = $db->query(
            "SELECT id,name,role,email,phone,username,
                    perm_students,perm_fees,perm_books,perm_expenses,
                    perm_reports,perm_staff,perm_settings,status
             FROM staff ORDER BY name"
        )->fetchAll();
        $data['settings'] = $db->query("SELECT * FROM settings LIMIT 1")->fetch() ?: [];

        // Current logged-in staff (for nav permission enforcement)
        $me = $db->prepare(
            "SELECT id,name,role,email,
                    perm_students,perm_fees,perm_books,perm_expenses,
                    perm_reports,perm_staff,perm_settings
             FROM staff WHERE id = ?"
        );
        $me->execute([$_SESSION['staff_id']]);
        $data['me'] = $me->fetch() ?: null;

        jsonResponse($data);

    // ════════════════════════════════════════════════════════
    // GET — Attendance for a given date
    // ════════════════════════════════════════════════════════
    case 'get_attendance':
        $date = $_GET['date'] ?? date('Y-m-d');
        $rows = $db->prepare("SELECT student_id, status FROM attendance WHERE attendance_date = ?");
        $rows->execute([$date]);
        $map = [];
        foreach ($rows->fetchAll() as $r) $map[$r['student_id']] = $r['status'];
        jsonResponse(['attendance' => $map]);

    // ════════════════════════════════════════════════════════
    // GET — WhatsApp send log
    // ════════════════════════════════════════════════════════
    case 'get_wa_log':
        $rows = $db->query("SELECT * FROM wa_send_log ORDER BY created_at DESC LIMIT 100")->fetchAll();
        jsonResponse($rows);

    // ════════════════════════════════════════════════════════
    // GET — Staff attendance summary (monthly)
    // ════════════════════════════════════════════════════════
    case 'get_staff_attendance_summary':
        $month = $_GET['month'] ?? date('Y-m');
        try {
            $rows = $db->prepare(
                "SELECT sf.id,
                        COALESCE(SUM(sa.status = 'present'),0) AS present,
                        COALESCE(SUM(sa.status = 'absent'),0)  AS absent,
                        COALESCE(SUM(sa.status = 'half'),0)    AS half
                 FROM staff sf
                 LEFT JOIN staff_attendance sa
                        ON sa.staff_id = sf.id
                       AND DATE_FORMAT(sa.att_date,'%Y-%m') = ?
                 GROUP BY sf.id"
            );
            $rows->execute([$month]);
            jsonResponse($rows->fetchAll());
        } catch (\Exception $e) {
            jsonResponse([]);
        }

    // ════════════════════════════════════════════════════════
    // GET — Clear all notifications
    // ════════════════════════════════════════════════════════
    case 'clear_notifs':
        $db->exec("DELETE FROM notifications");
        jsonResponse(['success' => true]);

    // ════════════════════════════════════════════════════════
    // POST — Enroll student
    // ════════════════════════════════════════════════════════
    case 'add_student':
        $fn  = trim($d['fname']    ?? '');
        $ln  = trim($d['lname']    ?? '');
        $bId = trim($d['batch_id'] ?? '');
        if (!$fn || !$bId) jsonError('First name and batch are required');

        $batch = null;
        if ($bId) {
            $bs = $db->prepare("SELECT * FROM batches WHERE id = ?");
            $bs->execute([$bId]);
            $batch = $bs->fetch();
        }

        $baseFee    = (int)($d['base_fee']        ?? ($batch['base_fee'] ?? 0));
        $discType   = $d['discount_type']          ?? 'none';
        $discVal    = (float)($d['discount_value'] ?? 0);
        $discReason = trim($d['discount_reason']   ?? '');

        $netFee = $baseFee;
        if ($discType === 'flat')    $netFee = max(0, $baseFee - $discVal);
        if ($discType === 'percent') $netFee = max(0, $baseFee - round($baseFee * $discVal / 100));

        $seatType = $d['seat_type'] ?? 'non-ac';
        if ($seatType === 'ac' && $batch) $netFee += (int)($batch['ac_extra'] ?? 0);

        $joinDate = $d['join_date'] ?? date('Y-m-d');
        $dueDate  = date('Y-m-d', strtotime($joinDate . ' +30 days'));

        $colors = ['#e67e22','#c0444f','#3d6ff0','#16a34a','#7c3aed','#0284c7','#d97706','#ea580c'];
        $color  = $colors[array_rand($colors)];

        $id = generateId('STU', 'students');

        $db->prepare(
            "INSERT INTO students
             (id,fname,lname,phone,batch_id,seat_type,seat,base_fee,
              discount_type,discount_value,discount_reason,net_fee,
              paid_amt,fee_status,due_date,course,color,join_date)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,0,'pending',?,?,?,?)"
        )->execute([
            $id, $fn, $ln,
            $d['phone']  ?? '',
            $bId, $seatType,
            $d['seat']   ?? '',
            $baseFee, $discType, $discVal, $discReason, (int)$netFee,
            $dueDate,
            $d['course'] ?? '',
            $color, $joinDate
        ]);

        activity('👨‍🎓', 'rgba(74,124,111,.14)', "New student <strong>$fn $ln</strong> enrolled");
        jsonResponse(['success' => true, 'id' => $id]);

    // ════════════════════════════════════════════════════════
    // POST — Update student basic info (profile edit)
    // ════════════════════════════════════════════════════════
    case 'update_student':
        $id = $d['id'] ?? '';
        if (!$id) jsonError('Student ID required');
        $db->prepare(
            "UPDATE students SET fname=?,lname=?,phone=?,course=? WHERE id=?"
        )->execute([
            trim($d['fname']  ?? ''),
            trim($d['lname']  ?? ''),
            trim($d['phone']  ?? ''),
            trim($d['course'] ?? ''),
            $id
        ]);
        jsonResponse(['success' => true]);

    // ════════════════════════════════════════════════════════
    // POST — Delete student
    // ════════════════════════════════════════════════════════
    case 'delete_student':
        $id = $d['id'] ?? '';
        if (!$id) jsonError('ID required');
        $db->prepare("DELETE FROM students WHERE id=?")->execute([$id]);
        jsonResponse(['success' => true]);

    // ════════════════════════════════════════════════════════
    // POST — Save batch (add or edit)
    // ════════════════════════════════════════════════════════
    case 'save_batch':
        $nm = trim($d['name']        ?? '');
        $st = $d['start_time']       ?? '';
        $et = $d['end_time']         ?? '';
        $ts = (int)($d['total_seats']?? 80);
        $fe = (int)($d['base_fee']   ?? 0);
        $ac = (int)($d['ac_extra']   ?? 0);
        if (!$nm || !$st || !$et || !$ts || !$fe) jsonError('Fill all required fields');

        if (!empty($d['id'])) {
            $db->prepare(
                "UPDATE batches SET name=?,start_time=?,end_time=?,total_seats=?,base_fee=?,ac_extra=? WHERE id=?"
            )->execute([$nm, $st, $et, $ts, $fe, $ac, $d['id']]);
        } else {
            $id = generateId('BT', 'batches');
            $db->prepare(
                "INSERT INTO batches (id,name,start_time,end_time,total_seats,base_fee,ac_extra)
                 VALUES (?,?,?,?,?,?,?)"
            )->execute([$id, $nm, $st, $et, $ts, $fe, $ac]);
            activity('🆕', 'rgba(74,124,111,.14)', "Batch \"<strong>$nm</strong>\" added");
        }
        jsonResponse(['success' => true]);

    // ════════════════════════════════════════════════════════
    // POST — Delete batch
    // ════════════════════════════════════════════════════════
    case 'delete_batch':
        $id = $d['id'] ?? '';
        if (!$id) jsonError('ID required');
        $db->prepare("DELETE FROM batches WHERE id=?")->execute([$id]);
        jsonResponse(['success' => true]);

    // ════════════════════════════════════════════════════════
    // POST — Allocate seat
    // ════════════════════════════════════════════════════════
    case 'alloc_seat':
        $stuId = $d['student_id'] ?? '';
        $bId   = $d['batch_id']   ?? '';
        $seat  = trim($d['seat']  ?? '');
        if (!$stuId || !$bId || !$seat) jsonError('Fill all fields');

        $chk = $db->prepare("SELECT id FROM students WHERE batch_id=? AND seat=? AND id!=?");
        $chk->execute([$bId, $seat, $stuId]);
        if ($chk->fetch()) jsonError("Seat $seat is already taken in this batch");

        $db->prepare("UPDATE students SET seat=?,batch_id=? WHERE id=?")
           ->execute([$seat, $bId, $stuId]);
        $db->prepare(
            "UPDATE batches SET occupied_seats =
             (SELECT COUNT(*) FROM students WHERE batch_id=? AND seat IS NOT NULL AND seat != '')
             WHERE id=?"
        )->execute([$bId, $bId]);

        activity('🪑', 'rgba(196,125,43,.14)', "Seat <strong>$seat</strong> allocated");
        jsonResponse(['success' => true]);

    // ════════════════════════════════════════════════════════
    // POST — Add book
    // ════════════════════════════════════════════════════════
    case 'add_book':
        $tl = trim($d['title'] ?? '');
        if (!$tl) jsonError('Title required');
        $cp     = max(1, (int)($d['copies'] ?? 1));
        $emojis = ['📘','📙','📗','📕','📔','📒'];
        $emoji  = $emojis[array_rand($emojis)];
        $id     = generateId('BK', 'books');
        $db->prepare(
            "INSERT INTO books (id,title,author,isbn,category,copies,available,shelf,emoji)
             VALUES (?,?,?,?,?,?,?,?,?)"
        )->execute([
            $id, $tl,
            $d['author']   ?? '',
            $d['isbn']     ?? '',
            $d['category'] ?? 'Other',
            $cp, $cp,
            $d['shelf']    ?? '',
            $emoji
        ]);
        activity('📚', 'rgba(58,122,176,.14)', "Book \"<strong>$tl</strong>\" added");
        jsonResponse(['success' => true, 'id' => $id]);

    // ════════════════════════════════════════════════════════
    // POST — Issue book
    // ════════════════════════════════════════════════════════
    case 'issue_book':
        $stuId = $d['student_id'] ?? '';
        $bkId  = $d['book_id']    ?? '';
        if (!$stuId || !$bkId) jsonError('Select student and book');

        $bk = $db->prepare("SELECT * FROM books WHERE id=?");
        $bk->execute([$bkId]);
        $book = $bk->fetch();
        if (!$book)                 jsonError('Book not found');
        if ($book['available'] < 1) jsonError('No copies available');

        $loanDays = (int)($db->query("SELECT loan_days FROM settings LIMIT 1")->fetchColumn() ?: 14);
        $today    = date('Y-m-d');
        $dueDate  = date('Y-m-d', strtotime("+$loanDays days"));
        $txId     = 'TX-' . time();

        $db->prepare(
            "INSERT INTO transactions (id,student_id,book_id,issue_date,due_date,status)
             VALUES (?,?,?,?,?,'issued')"
        )->execute([$txId, $stuId, $bkId, $today, $dueDate]);
        $db->prepare("UPDATE books SET available = available - 1 WHERE id=?")->execute([$bkId]);

        $nm = $db->prepare("SELECT fname FROM students WHERE id=?");
        $nm->execute([$stuId]);
        $fname = $nm->fetchColumn() ?: 'Student';
        activity('📤', 'rgba(124,92,191,.14)', "<strong>$fname</strong> issued \"{$book['title']}\"");
        jsonResponse(['success' => true, 'id' => $txId]);

    // ════════════════════════════════════════════════════════
    // POST — Return book
    // ════════════════════════════════════════════════════════
    case 'return_book':
        $txId = $d['tx_id']      ?? '';
        $fine = (int)($d['fine'] ?? 0);
        $cond = $d['condition']  ?? 'Good';
        if (!$txId) jsonError('Transaction ID required');

        $tx = $db->prepare("SELECT * FROM transactions WHERE id=?");
        $tx->execute([$txId]);
        $row = $tx->fetch();
        if (!$row) jsonError('Transaction not found');

        $db->prepare(
            "UPDATE transactions SET status='returned', return_date=?, fine=? WHERE id=?"
        )->execute([date('Y-m-d'), $fine, $txId]);

        if ($cond !== 'Lost') {
            $db->prepare("UPDATE books SET available = available + 1 WHERE id=?")
               ->execute([$row['book_id']]);
        }

        $bkTitle = $db->prepare("SELECT title FROM books WHERE id=?");
        $bkTitle->execute([$row['book_id']]);
        $title = $bkTitle->fetchColumn() ?: 'Book';

        $stuNm = $db->prepare("SELECT fname FROM students WHERE id=?");
        $stuNm->execute([$row['student_id']]);
        $fname = $stuNm->fetchColumn() ?: 'Student';

        $extra = $fine > 0 ? " Fine ₹$fine" : '';
        activity('📩', 'rgba(58,125,94,.14)', "<strong>$fname</strong> returned \"$title\"$extra");
        jsonResponse(['success' => true]);

    // ════════════════════════════════════════════════════════
    // POST — Collect fee
    // ════════════════════════════════════════════════════════
    case 'collect_fee':
        $stuId = $d['student_id'] ?? '';
        $amt   = (int)($d['amount'] ?? 0);
        $mode  = $d['mode']  ?? 'Cash';
        $month = $d['month'] ?? date('F Y');
        if (!$stuId || !$amt) jsonError('Student and amount required');

        $stu = $db->prepare("SELECT * FROM students WHERE id=?");
        $stu->execute([$stuId]);
        $s = $stu->fetch();
        if (!$s) jsonError('Student not found');

        $newPaid = min($s['net_fee'], $s['paid_amt'] + $amt);
        $bal     = $s['net_fee'] - $newPaid;
        $status  = $bal <= 0 ? 'paid' : 'partial';
        $today   = date('Y-m-d');

        $db->prepare(
            "UPDATE students SET paid_amt=?,fee_status=?,paid_on=? WHERE id=?"
        )->execute([$newPaid, $status, $today, $stuId]);

        $invId = generateId('INV', 'invoices');
        $db->prepare(
            "INSERT INTO invoices
             (id,student_id,type,amount,base_fee,discount,net_fee,paid_amt,balance,invoice_date,month,mode,status)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)"
        )->execute([
            $invId, $stuId, 'Monthly Fee', $amt,
            $s['base_fee'],
            $s['base_fee'] - $s['net_fee'],
            $s['net_fee'], $newPaid, $bal,
            $today, $month, $mode, $status
        ]);

        $name       = $s['fname'] . ' ' . $s['lname'];
        $pendingTxt = $bal > 0 ? "(₹$bal pending)" : "(full)";
        activity('💳', 'rgba(58,125,94,.14)',
            "<strong>{$s['fname']}</strong> paid ₹$amt via $mode $pendingTxt");
        notification('success', 'Fee Collected', "$name paid ₹$amt — $status");

        jsonResponse(['success' => true, 'balance' => $bal, 'fee_status' => $status, 'invoice_id' => $invId]);

    // ════════════════════════════════════════════════════════
    // POST — Generate invoice (manual)
    // ════════════════════════════════════════════════════════
    case 'gen_invoice':
        $stuId = $d['student_id'] ?? '';
        $amt   = (int)($d['amount'] ?? 0);
        if (!$stuId || !$amt) jsonError('Fill required fields');

        $stu = $db->prepare("SELECT * FROM students WHERE id=?");
        $stu->execute([$stuId]);
        $s = $stu->fetch();
        if (!$s) jsonError('Student not found');

        $typeMap = ['fee' => 'Monthly Fee', 'fine' => 'Book Fine', 'other' => 'Other'];
        $type    = $typeMap[$d['type'] ?? 'fee'] ?? 'Monthly Fee';
        $month   = $d['month'] ?? date('F Y');
        $today   = date('Y-m-d');

        $invId = generateId('INV', 'invoices');
        $db->prepare(
            "INSERT INTO invoices
             (id,student_id,type,amount,base_fee,discount,net_fee,paid_amt,balance,invoice_date,month,mode,status)
             VALUES (?,?,?,?,?,?,?,?,0,?,?,?,?)"
        )->execute([
            $invId, $stuId, $type, $amt,
            $s['base_fee'], $s['base_fee'] - $s['net_fee'], $s['net_fee'], $amt,
            $today, $month, 'Manual', 'paid'
        ]);
        jsonResponse(['success' => true, 'id' => $invId]);

    // ════════════════════════════════════════════════════════
    // POST — Add expense
    // ════════════════════════════════════════════════════════
    case 'add_expense':
        $nm = trim($d['name']    ?? '');
        $am = (int)($d['amount'] ?? 0);
        if (!$nm || !$am) jsonError('Name and amount required');

        $catEmoji = [
            'Utilities'   => '💡', 'Staff'   => '👥',
            'Maintenance' => '🔧', 'Books'   => '📚',
            'Supplies'    => '📦', 'Other'   => '💰'
        ];
        $cat   = $d['category'] ?? 'Other';
        $emoji = $catEmoji[$cat] ?? '💰';
        $notes = $d['notes'] ?? '';

        $rawDate = $d['date'] ?? date('Y-m-d');
        $parsed  = strtotime($rawDate);
        $date    = date('Y-m-d', $parsed !== false ? $parsed : time());

        $id = generateId('EX', 'expenses');
        $db->prepare(
            "INSERT INTO expenses (id,name,amount,category,expense_date,notes,emoji)
             VALUES (?,?,?,?,?,?,?)"
        )->execute([$id, $nm, $am, $cat, $date, $notes, $emoji]);
        jsonResponse(['success' => true, 'id' => $id]);

    // ════════════════════════════════════════════════════════
    // POST — Delete expense
    // ════════════════════════════════════════════════════════
    case 'delete_expense':
        $id = $d['id'] ?? '';
        if (!$id) jsonError('ID required');
        $db->prepare("DELETE FROM expenses WHERE id=?")->execute([$id]);
        jsonResponse(['success' => true]);

    // ════════════════════════════════════════════════════════
    // POST — Save student attendance (bulk upsert)
    // ════════════════════════════════════════════════════════
    case 'save_attendance':
        $date = $d['date']       ?? date('Y-m-d');
        $att  = $d['attendance'] ?? [];
        if (!$att || !is_array($att)) jsonError('No attendance data');

        $stmt = $db->prepare(
            "INSERT INTO attendance (student_id,attendance_date,status) VALUES (?,?,?)
             ON DUPLICATE KEY UPDATE status = VALUES(status)"
        );
        foreach ($att as $stuId => $status) {
            if (!in_array($status, ['present','absent'])) $status = 'present';
            $stmt->execute([$stuId, $date, $status]);
        }
        jsonResponse(['success' => true, 'count' => count($att)]);

    // ════════════════════════════════════════════════════════
    // POST — Save staff attendance (bulk upsert)
    // ════════════════════════════════════════════════════════
    case 'save_staff_attendance':
        $date = $d['date']       ?? date('Y-m-d');
        $att  = $d['attendance'] ?? [];

        $db->exec(
            "CREATE TABLE IF NOT EXISTS `staff_attendance` (
               `id`       INT AUTO_INCREMENT PRIMARY KEY,
               `staff_id` VARCHAR(30) NOT NULL,
               `att_date` DATE NOT NULL,
               `status`   ENUM('present','absent','half') DEFAULT 'present',
               UNIQUE KEY `uq_sa` (`staff_id`,`att_date`)
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $stmt = $db->prepare(
            "INSERT INTO staff_attendance (staff_id,att_date,status) VALUES (?,?,?)
             ON DUPLICATE KEY UPDATE status = VALUES(status)"
        );
        foreach ($att as $staffId => $status) {
            if (!in_array($status, ['present','absent','half'])) $status = 'present';
            $stmt->execute([$staffId, $date, $status]);
        }
        jsonResponse(['success' => true, 'count' => count($att)]);

    // ════════════════════════════════════════════════════════
    // POST — Save staff (add or edit)
    // ════════════════════════════════════════════════════════
    case 'save_staff':
        $nm = trim($d['name']  ?? '');
        $rl = $d['role']       ?? 'librarian';
        $em = trim($d['email'] ?? '');
        if (!$nm || !$em) jsonError('Name and email required');

        $ph  = $d['phone']    ?? '';
        $un  = $d['username'] ?? strtolower(explode(' ', $nm)[0]);
        $pw  = $d['password'] ?? '';
        $p   = $d['perms']    ?? [];

        $ps  = (int)!empty($p['students']);
        $pf  = (int)!empty($p['fees']);
        $pb  = (int)!empty($p['books']);
        $pe  = (int)!empty($p['expenses']);
        $pr  = (int)!empty($p['reports']);
        $pst = (int)!empty($p['staff']);
        $pg  = (int)!empty($p['settings']);

        if (!empty($d['id'])) {
            $sets = "name=?,role=?,email=?,phone=?,perm_students=?,perm_fees=?,perm_books=?,perm_expenses=?,perm_reports=?,perm_staff=?,perm_settings=?";
            $vals = [$nm,$rl,$em,$ph,$ps,$pf,$pb,$pe,$pr,$pst,$pg];
            if ($pw) { $sets .= ',password_hash=?'; $vals[] = password_hash($pw, PASSWORD_BCRYPT); }
            $vals[] = $d['id'];
            $db->prepare("UPDATE staff SET $sets WHERE id=?")->execute($vals);
            jsonResponse(['success' => true, 'id' => $d['id']]);
        } else {
            if (!$pw) jsonError('Password required for new staff');
            $cx = $db->prepare("SELECT 1 FROM staff WHERE username=?");
            $cx->execute([$un]);
            if ($cx->fetchColumn()) $un .= rand(10, 99);

            $id = generateId('SF', 'staff');
            $db->prepare(
                "INSERT INTO staff
                 (id,name,role,email,phone,username,password_hash,
                  perm_students,perm_fees,perm_books,perm_expenses,perm_reports,perm_staff,perm_settings)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
            )->execute([
                $id,$nm,$rl,$em,$ph,$un,
                password_hash($pw, PASSWORD_BCRYPT),
                $ps,$pf,$pb,$pe,$pr,$pst,$pg
            ]);
            jsonResponse(['success' => true, 'id' => $id]);
        }

    // ════════════════════════════════════════════════════════
    // POST — Delete staff
    // ════════════════════════════════════════════════════════
    case 'delete_staff':
        $id = $d['id'] ?? '';
        if (!$id) jsonError('ID required');
        if ($id === 'SF-001') jsonError('Cannot delete the primary admin');
        $db->prepare("DELETE FROM staff WHERE id=?")->execute([$id]);
        jsonResponse(['success' => true]);

    // ════════════════════════════════════════════════════════
    // POST — Change password (logged-in staff only)
    // ════════════════════════════════════════════════════════
    case 'change_password':
        $cur = $d['current_password'] ?? '';
        $nw  = $d['new_password']     ?? '';
        if (!$cur || !$nw)   jsonError('Fill all fields');
        if (strlen($nw) < 6) jsonError('Password must be 6+ characters');

        $s = $db->prepare("SELECT password_hash FROM staff WHERE id=?");
        $s->execute([$_SESSION['staff_id']]);
        $row = $s->fetch();
        if (!$row || !password_verify($cur, $row['password_hash']))
            jsonError('Current password is incorrect');

        $db->prepare("UPDATE staff SET password_hash=? WHERE id=?")
           ->execute([password_hash($nw, PASSWORD_BCRYPT), $_SESSION['staff_id']]);
        jsonResponse(['success' => true]);

    // ════════════════════════════════════════════════════════
    // POST — Mark notification read
    // ════════════════════════════════════════════════════════
    case 'mark_read':
        $id = (int)($d['id'] ?? 0);
        if (!$id) jsonError('ID required');
        $db->prepare("UPDATE notifications SET is_read=1 WHERE id=?")->execute([$id]);
        jsonResponse(['success' => true]);

    // ════════════════════════════════════════════════════════
    // POST — Delete notification
    // ════════════════════════════════════════════════════════
    case 'delete_notif':
        $id = (int)($d['id'] ?? 0);
        if (!$id) jsonError('ID required');
        $db->prepare("DELETE FROM notifications WHERE id=?")->execute([$id]);
        jsonResponse(['success' => true]);

    // ════════════════════════════════════════════════════════
    // POST — Save settings
    // ════════════════════════════════════════════════════════
    case 'save_settings':
        $db->prepare(
            "UPDATE settings SET name=?,phone=?,email=?,addr=?,fine_per_day=?,loan_days=?,wa_number=? WHERE id=1"
        )->execute([
            $d['name']      ?? '',
            $d['phone']     ?? '',
            $d['email']     ?? '',
            $d['addr']      ?? '',
            (int)($d['fine']?? 5),
            (int)($d['days']?? 14),
            $d['wa_number'] ?? '',
        ]);
        jsonResponse(['success' => true]);

    // ════════════════════════════════════════════════════════
    // POST — Log WhatsApp send
    // ════════════════════════════════════════════════════════
    case 'log_wa':
        $db->prepare(
            "INSERT INTO wa_send_log (sent_to,preview,type) VALUES (?,?,?)"
        )->execute([
            $d['to']   ?? '',
            substr($d['preview'] ?? '', 0, 255),
            $d['type'] ?? 'single'
        ]);
        jsonResponse(['success' => true]);

    // ════════════════════════════════════════════════════════
    // POST — Renew student
    // ════════════════════════════════════════════════════════
    case 'renew_student':
        $stuId  = $d['student_id']   ?? '';
        $amt    = (int)($d['amount'] ?? 0);
        $months = (int)($d['months'] ?? 1);
        $mode   = $d['mode']         ?? 'Cash';
        $newDue = $d['new_due_date'] ?? '';
        if (!$stuId) jsonError('Student ID required');

        $stu = $db->prepare("SELECT * FROM students WHERE id=?");
        $stu->execute([$stuId]);
        $s = $stu->fetch();
        if (!$s) jsonError('Student not found');

        if (!$newDue) {
            $base   = max(time(), strtotime($s['due_date']));
            $newDue = date('Y-m-d', strtotime("+$months months", $base));
        }

        $newPaid = $s['paid_amt'] + $amt;
        $bal     = max(0, $s['net_fee'] - $newPaid);
        $status  = $bal <= 0 ? 'paid' : ($newPaid > 0 ? 'partial' : 'pending');
        $today   = date('Y-m-d');

        $db->prepare(
            "UPDATE students SET due_date=?,paid_amt=?,fee_status=?,paid_on=? WHERE id=?"
        )->execute([$newDue, $newPaid, $status, $today, $stuId]);

        $invId      = generateId('INV', 'invoices');
        $monthLabel = date('F Y');
        $db->prepare(
            "INSERT INTO invoices
             (id,student_id,type,amount,base_fee,discount,net_fee,paid_amt,balance,invoice_date,month,mode,status)
             VALUES (?,?,?,?,?,?,?,?,0,?,?,?,?)"
        )->execute([
            $invId, $stuId, "Renewal ({$months}mo)", $amt,
            $s['base_fee'], $s['base_fee'] - $s['net_fee'], $s['net_fee'], $amt,
            $today, $monthLabel, $mode, 'paid'
        ]);

        $name = $s['fname'] . ' ' . $s['lname'];
        activity('🔄', 'rgba(61,111,240,.14)',
            "Renewed <strong>$name</strong> for $months month(s)");
        notification('success', 'Renewal',
            "Student $name renewed for $months month(s)");

        jsonResponse(['success' => true, 'invoice_id' => $invId]);

    // ════════════════════════════════════════════════════════
    // Fallback
    // ════════════════════════════════════════════════════════
    default:
        jsonError("Unknown action: $action", 400);
    }

} catch (\PDOException $e) {
    error_log('[LibraryAPI] PDO Error: ' . $e->getMessage());
    jsonError('Database error: ' . $e->getMessage(), 500);
} catch (\Throwable $e) {
    error_log('[LibraryAPI] Error: ' . $e->getMessage());
    jsonError('Server error: ' . $e->getMessage(), 500);
}
