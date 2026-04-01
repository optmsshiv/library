<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$db = getDB();

switch ($action) {

    // ══════════════════════════════════
    // AUTH
    // ══════════════════════════════════
    case 'login':
        $d = getInput();
        $username = trim($d['username'] ?? '');
        $password = $d['password'] ?? '';
        if (!$username || !$password) {
            jsonError('Username and password are required.');
        }
        $stmt = $db->prepare("SELECT id, name, role, password_hash, status FROM staff WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $staff = $stmt->fetch();
        if (!$staff || $staff['status'] !== 'active' || !password_verify($password, $staff['password_hash'])) {
            jsonError('Invalid username or password.', 401);
        }
        $_SESSION['staff_id']   = $staff['id'];
        $_SESSION['staff_name'] = $staff['name'];
        $_SESSION['staff_role'] = $staff['role'];
        jsonResponse(['ok' => true, 'name' => $staff['name'], 'role' => $staff['role']]);

    case 'logout':
        session_unset();
        session_destroy();
        jsonResponse(['ok' => true]);

    // ══════════════════════════════════
    // DASHBOARD
    // ══════════════════════════════════
    case 'get_dashboard':
        $students = $db->query("SELECT * FROM students")->fetchAll();
        // Auto-heal bad date values
        foreach ($students as &$row) {
            if (empty($row['due_date']) || $row['due_date'] === '0000-00-00') {
                $base = (!empty($row['join_date']) && $row['join_date'] !== '0000-00-00')
                    ? $row['join_date']
                    : date('Y-m-d', strtotime($row['created_at'] ?? 'now'));
                $row['due_date'] = date('Y-m-d', strtotime('+30 days', strtotime($base)));
                $db->prepare("UPDATE students SET due_date=? WHERE id=?")->execute([$row['due_date'], $row['id']]);
            }
            if (empty($row['join_date']) || $row['join_date'] === '0000-00-00') {
                $row['join_date'] = date('Y-m-d', strtotime($row['created_at'] ?? 'now'));
                $db->prepare("UPDATE students SET join_date=? WHERE id=?")->execute([$row['join_date'], $row['id']]);
            }
            if (isset($row['paid_on']) && ($row['paid_on'] === '0000-00-00' || $row['paid_on'] === '-' || $row['paid_on'] === '')) {
                $row['paid_on'] = null;
                $db->prepare("UPDATE students SET paid_on=NULL WHERE id=?")->execute([$row['id']]);
            }
        }
        unset($row);
        $batches  = $db->query("SELECT * FROM batches")->fetchAll();
        $books    = $db->query("SELECT * FROM books")->fetchAll();
        $transactions = $db->query("SELECT * FROM transactions")->fetchAll();
        $expenses = $db->query("SELECT * FROM expenses")->fetchAll();
        $activities = $db->query("SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 15")->fetchAll();
        $notifications = $db->query("SELECT * FROM notifications ORDER BY created_at DESC")->fetchAll();
        $settings = $db->query("SELECT * FROM settings WHERE id=1")->fetch();
        $invoices = $db->query("SELECT * FROM invoices ORDER BY created_at DESC")->fetchAll();
        $staff    = $db->query("SELECT id,name,role,email,phone,username,perm_students,perm_fees,perm_books,perm_expenses,perm_reports,perm_staff,perm_settings,status FROM staff ORDER BY created_at")->fetchAll();
        $meStmt   = $db->prepare("SELECT role,perm_students,perm_fees,perm_books,perm_expenses,perm_reports,perm_staff,perm_settings FROM staff WHERE id=? LIMIT 1");
        $meStmt->execute([$_SESSION['staff_id']]);
        $me = $meStmt->fetch();
        if (!$me) {
            // Fallback: admin gets full access
            $me = ['role'=>'admin','perm_students'=>1,'perm_fees'=>1,'perm_books'=>1,'perm_expenses'=>1,'perm_reports'=>1,'perm_staff'=>1,'perm_settings'=>1];
        }
        jsonResponse([
            'students'      => $students,
            'batches'       => $batches,
            'books'         => $books,
            'transactions'  => $transactions,
            'expenses'      => $expenses,
            'activities'    => $activities,
            'notifications' => $notifications,
            'settings'      => $settings,
            'invoices'      => $invoices,
            'staff'         => $staff,
            'me'            => $me,
        ]);
        break;

    // ══════════════════════════════════
    // STUDENTS
    // ══════════════════════════════════
    case 'get_students':
        $rows = $db->query("SELECT s.*, b.name as batch_name FROM students s LEFT JOIN batches b ON s.batch_id=b.id ORDER BY s.created_at DESC")->fetchAll();
        // Auto-heal bad date values so frontend always gets valid data
        foreach ($rows as &$row) {
            // due_date: if NULL or 0000-00-00, calculate from join_date or created_at
            if (empty($row['due_date']) || $row['due_date'] === '0000-00-00') {
                $base = (!empty($row['join_date']) && $row['join_date'] !== '0000-00-00')
                    ? $row['join_date']
                    : date('Y-m-d', strtotime($row['created_at'] ?? 'now'));
                $row['due_date'] = date('Y-m-d', strtotime('+30 days', strtotime($base)));
                // Persist the fix so it doesn't repeat every request
                $db->prepare("UPDATE students SET due_date=? WHERE id=?")->execute([$row['due_date'], $row['id']]);
            }
            // join_date: heal 0000-00-00
            if (empty($row['join_date']) || $row['join_date'] === '0000-00-00') {
                $row['join_date'] = date('Y-m-d', strtotime($row['created_at'] ?? 'now'));
                $db->prepare("UPDATE students SET join_date=? WHERE id=?")->execute([$row['join_date'], $row['id']]);
            }
            // paid_on: normalize bad values to null
            if (isset($row['paid_on']) && ($row['paid_on'] === '0000-00-00' || $row['paid_on'] === '-' || $row['paid_on'] === '')) {
                $row['paid_on'] = null;
                $db->prepare("UPDATE students SET paid_on=NULL WHERE id=?")->execute([$row['id']]);
            }
        }
        unset($row);
        jsonResponse($rows);

    case 'add_student':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $d = getInput();
        if (empty($d['fname']) || empty($d['batch_id'])) jsonError('First name and batch are required');
        $newId = 'STU-' . str_pad((int)$db->query("SELECT COUNT(*) FROM students")->fetchColumn() + 1, 3, '0', STR_PAD_LEFT);
        $baseFee = (int)($d['base_fee'] ?? 0);
        $discType  = $d['discount_type'] ?? 'none';
        $discVal   = (float)($d['discount_value'] ?? 0);
        $disc = 0;
        if ($discType === 'flat') $disc = min($discVal, $baseFee);
        elseif ($discType === 'percent') $disc = round($baseFee * $discVal / 100);
        $netFee = $baseFee - $disc;
        $colors = ['#4a7c6f','#c47d2b','#3a7ab0','#7c5cbf','#c0444f','#3a7d5e','#e67e22'];
        $color = $colors[array_rand($colors)];
        $sql = "INSERT INTO students (id,fname,lname,phone,batch_id,seat_type,seat,base_fee,discount_type,discount_value,discount_reason,net_fee,paid_amt,fee_status,paid_on,due_date,course,color,join_date) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,0,'pending',NULL,?,?,?,?)";
        $stmt = $db->prepare($sql);
        // Calculate due_date: 30 days from join date (or today)
        $joinRaw = $d['join_date'] ?? '';
        $joinTs  = $joinRaw ? strtotime($joinRaw) : time();
        if (!$joinTs) $joinTs = time();
        $joinDate = date('Y-m-d', $joinTs);
        $dueDate  = date('Y-m-d', strtotime('+30 days', $joinTs));
        $stmt->execute([
            $newId, $d['fname'], $d['lname'] ?? '', $d['phone'] ?? '', $d['batch_id'],
            $d['seat_type'] ?? 'non-ac', $d['seat'] ?? '',
            $baseFee, $discType, $discVal, $d['discount_reason'] ?? '',
            $netFee, $dueDate, $d['course'] ?? '', $color,
            $joinDate
        ]);
        addActivity($db, '👨‍🎓', 'rgba(74,124,111,.14)', "New student <strong>{$d['fname']} {$d['lname']}</strong> enrolled");
        addNotif($db, 'info', 'New Enrollment', "{$d['fname']} {$d['lname']} enrolled");
        jsonResponse(['success' => true, 'id' => $newId]);

    case 'delete_student':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $d = getInput();
        $id = $d['id'] ?? '';
        if (!$id) jsonError('ID required');
        $db->prepare("DELETE FROM students WHERE id=?")->execute([$id]);
        jsonResponse(['success' => true]);

    // ══════════════════════════════════
    // BATCHES
    // ══════════════════════════════════
    case 'get_batches':
        $rows = $db->query("SELECT * FROM batches ORDER BY start_time")->fetchAll();
        jsonResponse($rows);

    case 'save_batch':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $d = getInput();
        if (empty($d['name']) || empty($d['start_time']) || empty($d['total_seats'])) jsonError('Required fields missing');
        $isEdit = !empty($d['id']);
        if ($isEdit) {
            // Check not reducing below occupied
            $occ = (int)$db->prepare("SELECT occupied_seats FROM batches WHERE id=?")->execute([$d['id']]) ? $db->prepare("SELECT occupied_seats FROM batches WHERE id=?")->execute([$d['id']]) : 0;
            $stmt2 = $db->prepare("SELECT occupied_seats FROM batches WHERE id=?");
            $stmt2->execute([$d['id']]);
            $row2 = $stmt2->fetch();
            if ($row2 && (int)$d['total_seats'] < (int)$row2['occupied_seats'])
                jsonError('Cannot reduce seats below currently occupied');
            $db->prepare("UPDATE batches SET name=?,start_time=?,end_time=?,total_seats=?,base_fee=?,ac_extra=? WHERE id=?")
               ->execute([$d['name'],$d['start_time'],$d['end_time'],(int)$d['total_seats'],(int)$d['base_fee'],(int)$d['ac_extra'],$d['id']]);
        } else {
            $newId = 'BT-' . (time() % 100000);
            $db->prepare("INSERT INTO batches (id,name,start_time,end_time,total_seats,occupied_seats,base_fee,ac_extra) VALUES (?,?,?,?,?,0,?,?)")
               ->execute([$newId,$d['name'],$d['start_time'],$d['end_time'],(int)$d['total_seats'],(int)$d['base_fee'],(int)$d['ac_extra']]);
            addActivity($db, '🆕', 'rgba(74,124,111,.14)', "Batch \"<strong>{$d['name']}</strong>\" added");
        }
        jsonResponse(['success' => true]);

    case 'delete_batch':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $d = getInput();
        $db->prepare("DELETE FROM batches WHERE id=?")->execute([$d['id'] ?? '']);
        jsonResponse(['success' => true]);

    // ══════════════════════════════════
    // SEATS
    // ══════════════════════════════════
    case 'alloc_seat':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $d = getInput();
        if (empty($d['student_id']) || empty($d['batch_id']) || empty($d['seat'])) jsonError('All fields required');
        $db->prepare("UPDATE students SET seat=?, batch_id=? WHERE id=?")->execute([$d['seat'], $d['batch_id'], $d['student_id']]);
        // update occupied count
        $db->prepare("UPDATE batches SET occupied_seats = (SELECT COUNT(*) FROM students WHERE batch_id=? AND seat IS NOT NULL AND seat != '') WHERE id=?")->execute([$d['batch_id'], $d['batch_id']]);
        $s = $db->prepare("SELECT fname FROM students WHERE id=?")->execute([$d['student_id']]);
        addActivity($db, '🪑', 'rgba(196,125,43,.14)', "Seat <strong>{$d['seat']}</strong> allocated");
        jsonResponse(['success' => true]);

    // ══════════════════════════════════
    // BOOKS
    // ══════════════════════════════════
    case 'get_books':
        $rows = $db->query("SELECT * FROM books ORDER BY created_at DESC")->fetchAll();
        jsonResponse($rows);

    case 'add_book':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $d = getInput();
        if (empty($d['title'])) jsonError('Title required');
        $newId = 'BK-' . str_pad((int)$db->query("SELECT COUNT(*) FROM books")->fetchColumn() + 1, 3, '0', STR_PAD_LEFT);
        $copies = (int)($d['copies'] ?? 1);
        $db->prepare("INSERT INTO books (id,title,author,isbn,category,copies,available,shelf,emoji) VALUES (?,?,?,?,?,?,?,?,?)")
           ->execute([$newId,$d['title'],$d['author'] ?? '',$d['isbn'] ?? '',$d['category'] ?? 'Other',$copies,$copies,$d['shelf'] ?? '','📘']);
        addActivity($db, '📚', 'rgba(196,125,43,.14)', "Book \"<strong>{$d['title']}</strong>\" added");
        jsonResponse(['success' => true, 'id' => $newId]);

    case 'delete_book':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $d = getInput();
        $db->prepare("DELETE FROM books WHERE id=?")->execute([$d['id'] ?? '']);
        jsonResponse(['success' => true]);

    // ══════════════════════════════════
    // TRANSACTIONS
    // ══════════════════════════════════
    case 'issue_book':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $d = getInput();
        if (empty($d['student_id']) || empty($d['book_id'])) jsonError('Student and book required');
        $book = $db->prepare("SELECT * FROM books WHERE id=?")->execute([$d['book_id']]) ? null : null;
        $stmt = $db->prepare("SELECT * FROM books WHERE id=?");
        $stmt->execute([$d['book_id']]);
        $book = $stmt->fetch();
        if (!$book || $book['available'] <= 0) jsonError('No copies available');
        $newId = 'TX-' . (time() % 1000000);
        $settings = $db->query("SELECT * FROM settings WHERE id=1")->fetch();
        $loanDays = $settings['loan_days'] ?? 14;
        $issueDate = date('M j, Y');
        $dueDate = date('M j, Y', strtotime("+{$loanDays} days"));
        $db->prepare("INSERT INTO transactions (id,student_id,book_id,issue_date,due_date,return_date,fine,status) VALUES (?,?,?,?,?,NULL,0,'issued')")
           ->execute([$newId,$d['student_id'],$d['book_id'],$issueDate,$dueDate]);
        $db->prepare("UPDATE books SET available=available-1 WHERE id=?")->execute([$d['book_id']]);
        $stuStmt = $db->prepare("SELECT fname FROM students WHERE id=?");
        $stuStmt->execute([$d['student_id']]);
        $stu = $stuStmt->fetch();
        addActivity($db, '📤', 'rgba(124,92,191,.14)', "<strong>{$stu['fname']}</strong> issued \"{$book['title']}\"");
        addNotif($db, 'info', 'Book Issued', "{$stu['fname']} issued {$book['title']}");
        jsonResponse(['success' => true, 'id' => $newId]);

    case 'return_book':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $d = getInput();
        if (empty($d['tx_id'])) jsonError('Transaction ID required');
        $txStmt = $db->prepare("SELECT t.*, b.title, b.id as bid, s.fname FROM transactions t JOIN books b ON t.book_id=b.id JOIN students s ON t.student_id=s.id WHERE t.id=?");
        $txStmt->execute([$d['tx_id']]);
        $tx = $txStmt->fetch();
        if (!$tx) jsonError('Transaction not found');
        $fine = (int)($d['fine'] ?? 0);
        $returnDate = date('M j, Y');
        $cond = $d['condition'] ?? 'Good';
        $db->prepare("UPDATE transactions SET status='returned',return_date=?,fine=? WHERE id=?")->execute([$returnDate,$fine,$d['tx_id']]);
        if ($cond !== 'Lost') {
            $db->prepare("UPDATE books SET available=available+1 WHERE id=?")->execute([$tx['bid']]);
        }
        addActivity($db, '📩', 'rgba(58,125,94,.14)', "<strong>{$tx['fname']}</strong> returned \"{$tx['title']}\"" . ($fine > 0 ? " Fine ₹$fine" : ''));
        addNotif($db, 'success', 'Book Returned', "{$tx['fname']} returned {$tx['title']}" . ($fine ? " — Fine ₹$fine" : ''));
        jsonResponse(['success' => true]);

    // ══════════════════════════════════
    // FEES
    // ══════════════════════════════════
    case 'collect_fee':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $d = getInput();
        if (empty($d['student_id']) || empty($d['amount'])) jsonError('Student and amount required');
        $stuStmt = $db->prepare("SELECT * FROM students WHERE id=?");
        $stuStmt->execute([$d['student_id']]);
        $s = $stuStmt->fetch();
        if (!$s) jsonError('Student not found');
        $amt = (int)$d['amount'];
        $newPaid = min($s['net_fee'], $s['paid_amt'] + $amt);
        $feeStatus = $newPaid >= $s['net_fee'] ? 'paid' : 'partial';
        $paidOn = date('Y-m-d');
        $db->prepare("UPDATE students SET paid_amt=?,fee_status=?,paid_on=? WHERE id=?")->execute([$newPaid,$feeStatus,$paidOn,$d['student_id']]);
        $balance = $s['net_fee'] - $newPaid;
        // Create invoice
        $invId = 'INV-' . str_pad((int)$db->query("SELECT COUNT(*) FROM invoices")->fetchColumn() + 1, 4, '0', STR_PAD_LEFT);
        $mode = $d['mode'] ?? 'Cash';
        if (!empty($d['split_mode'])) $mode = $d['split_mode'];
        $db->prepare("INSERT INTO invoices (id,student_id,type,amount,base_fee,discount,net_fee,paid_amt,balance,invoice_date,month,mode,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)")
           ->execute([$invId,$d['student_id'],'Monthly Fee',$amt,$s['base_fee'],$s['base_fee']-$s['net_fee'],$s['net_fee'],$newPaid,$balance,date('Y-m-d'),$d['month'] ?? date('F Y'),$mode,$feeStatus]);
        addActivity($db, '💳', 'rgba(58,125,94,.14)', "<strong>{$s['fname']}</strong> paid ₹{$amt} via {$mode}" . ($feeStatus==='partial' ? " (₹{$balance} pending)" : ' (full)'));
        addNotif($db, 'success', 'Fee Collected', "{$s['fname']} paid ₹{$amt}" . ($feeStatus==='partial' ? " — partial" : ''));
        jsonResponse(['success' => true, 'invoice_id' => $invId, 'fee_status' => $feeStatus, 'balance' => $balance]);

    // ══════════════════════════════════
    // INVOICES
    // ══════════════════════════════════
    case 'gen_invoice':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $d = getInput();
        if (empty($d['student_id']) || empty($d['amount'])) jsonError('Required fields missing');
        $stuStmt = $db->prepare("SELECT * FROM students WHERE id=?");
        $stuStmt->execute([$d['student_id']]);
        $s = $stuStmt->fetch();
        $invId = 'INV-' . str_pad((int)$db->query("SELECT COUNT(*) FROM invoices")->fetchColumn() + 1, 4, '0', STR_PAD_LEFT);
        $typeMap = ['fee'=>'Monthly Fee','fine'=>'Book Fine','other'=>'Other'];
        $type = $typeMap[$d['type'] ?? 'fee'] ?? 'Monthly Fee';
        $amt = (int)$d['amount'];
        $db->prepare("INSERT INTO invoices (id,student_id,type,amount,base_fee,discount,net_fee,paid_amt,balance,invoice_date,month,mode,status) VALUES (?,?,?,?,?,?,?,?,0,?,?,?,?)")
           ->execute([$invId,$d['student_id'],$type,$amt,$s['base_fee'] ?? $amt,$s['base_fee'] - $s['net_fee'] ?? 0,$s['net_fee'] ?? $amt,$amt,date('Y-m-d'),$d['month'] ?? date('F Y'),'Manual','paid']);
        jsonResponse(['success' => true, 'id' => $invId]);

    case 'get_invoices':
        $rows = $db->query("SELECT i.*, s.fname, s.lname, s.color FROM invoices i LEFT JOIN students s ON i.student_id=s.id ORDER BY i.created_at DESC")->fetchAll();
        foreach ($rows as &$row) {
            if (empty($row['invoice_date']) || $row['invoice_date'] === '0000-00-00') {
                $fixed = date('Y-m-d', strtotime($row['created_at'] ?? 'now'));
                $row['invoice_date'] = $fixed;
                $db->prepare("UPDATE invoices SET invoice_date=? WHERE id=?")->execute([$fixed, $row['id']]);
            }
        }
        unset($row);
        jsonResponse($rows);

    // ══════════════════════════════════
    // EXPENSES
    // ══════════════════════════════════
    case 'get_expenses':
        $rows = $db->query("SELECT * FROM expenses ORDER BY created_at DESC")->fetchAll();
        foreach ($rows as &$row) {
            if (empty($row['expense_date']) || $row['expense_date'] === '0000-00-00') {
                $fixed = date('Y-m-d', strtotime($row['created_at'] ?? 'now'));
                $row['expense_date'] = $fixed;
                $db->prepare("UPDATE expenses SET expense_date=? WHERE id=?")->execute([$fixed, $row['id']]);
            }
        }
        unset($row);
        jsonResponse($rows);

    case 'add_expense':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $d = getInput();
        if (empty($d['name']) || empty($d['amount'])) jsonError('Name and amount required');
        $newId = 'EX-' . str_pad((int)$db->query("SELECT COUNT(*) FROM expenses")->fetchColumn() + 1, 3, '0', STR_PAD_LEFT);
        $catEmojis = ['Utilities'=>'⚡','Staff'=>'👨‍💼','Maintenance'=>'🔧','Supplies'=>'📦','Books'=>'📚','Other'=>'💸'];
        $cat = $d['category'] ?? 'Other';
        $emoji = $catEmojis[$cat] ?? '💸';
        $db->prepare("INSERT INTO expenses (id,name,amount,category,expense_date,notes,emoji) VALUES (?,?,?,?,?,?,?)")
           ->execute([$newId,$d['name'],(int)$d['amount'],$cat, !empty($d['date']) ? date('Y-m-d', strtotime($d['date'])) : date('Y-m-d'), $d['notes'] ?? '',$emoji]);
        addActivity($db, '💸', 'rgba(212,144,47,.14)', "Expense: <strong>{$d['name']}</strong> ₹{$d['amount']}");
        jsonResponse(['success' => true, 'id' => $newId]);

    case 'delete_expense':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $d = getInput();
        $db->prepare("DELETE FROM expenses WHERE id=?")->execute([$d['id'] ?? '']);
        jsonResponse(['success' => true]);

    // ══════════════════════════════════
    // ATTENDANCE
    // ══════════════════════════════════
    case 'get_attendance':
        $date = $_GET['date'] ?? date('Y-m-d');
        $rows = $db->prepare("SELECT student_id, status FROM attendance WHERE attendance_date=?");
        $rows->execute([$date]);
        $att = [];
        foreach ($rows->fetchAll() as $r) $att[$r['student_id']] = $r['status'];
        jsonResponse(['date' => $date, 'attendance' => $att]);

    case 'save_attendance':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $d = getInput();
        $date = $d['date'] ?? date('Y-m-d');
        $attendance = $d['attendance'] ?? [];
        foreach ($attendance as $stuId => $status) {
            $db->prepare("INSERT INTO attendance (student_id,attendance_date,status) VALUES (?,?,?) ON DUPLICATE KEY UPDATE status=?")->execute([$stuId,$date,$status,$status]);
        }
        addActivity($db, '📋', 'rgba(58,122,176,.14)', "Attendance saved for <strong>$date</strong>");
        jsonResponse(['success' => true]);

    // ══════════════════════════════════
    // STAFF
    // ══════════════════════════════════
    case 'get_staff':
        $rows = $db->query("SELECT id,name,role,email,phone,username,perm_students,perm_fees,perm_books,perm_expenses,perm_reports,perm_staff,perm_settings,status FROM staff ORDER BY created_at")->fetchAll();
        jsonResponse($rows);

    case 'save_staff':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $d = getInput();
        if (empty($d['name']) || empty($d['role']) || empty($d['email'])) jsonError('Name, role, email required');
        $perms = $d['perms'] ?? [];
        $isEdit = !empty($d['id']);
        if ($isEdit) {
            // If a new password is provided, update it too; otherwise keep existing hash
            if (!empty($d['password'])) {
                $newHash = password_hash($d['password'], PASSWORD_BCRYPT);
                $db->prepare("UPDATE staff SET name=?,role=?,email=?,phone=?,username=?,password_hash=?,perm_students=?,perm_fees=?,perm_books=?,perm_expenses=?,perm_reports=?,perm_staff=?,perm_settings=? WHERE id=?")
                   ->execute([$d['name'],$d['role'],$d['email'],$d['phone'] ?? '',$d['username'] ?? '',
                     $newHash,
                     (int)($perms['students'] ?? 0),(int)($perms['fees'] ?? 0),(int)($perms['books'] ?? 0),
                     (int)($perms['expenses'] ?? 0),(int)($perms['reports'] ?? 0),(int)($perms['staff'] ?? 0),(int)($perms['settings'] ?? 0),
                     $d['id']]);
            } else {
                $db->prepare("UPDATE staff SET name=?,role=?,email=?,phone=?,username=?,perm_students=?,perm_fees=?,perm_books=?,perm_expenses=?,perm_reports=?,perm_staff=?,perm_settings=? WHERE id=?")
                   ->execute([$d['name'],$d['role'],$d['email'],$d['phone'] ?? '',$d['username'] ?? '',
                     (int)($perms['students'] ?? 0),(int)($perms['fees'] ?? 0),(int)($perms['books'] ?? 0),
                     (int)($perms['expenses'] ?? 0),(int)($perms['reports'] ?? 0),(int)($perms['staff'] ?? 0),(int)($perms['settings'] ?? 0),
                     $d['id']]);
            }
        } else {
            // New staff: require username; default password is 'Pass@1234' if none given
            if (empty($d['username'])) jsonError('Username is required for new staff.');
            $rawPassword = !empty($d['password']) ? $d['password'] : 'Pass@1234';
            $hash = password_hash($rawPassword, PASSWORD_BCRYPT);
            $newId = 'SF-' . str_pad((int)$db->query("SELECT COUNT(*) FROM staff")->fetchColumn() + 1, 3, '0', STR_PAD_LEFT);
            $db->prepare("INSERT INTO staff (id,name,role,email,phone,username,password_hash,perm_students,perm_fees,perm_books,perm_expenses,perm_reports,perm_staff,perm_settings,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
               ->execute([$newId,$d['name'],$d['role'],$d['email'],$d['phone'] ?? '',$d['username'],$hash,
                 (int)($perms['students'] ?? 0),(int)($perms['fees'] ?? 0),(int)($perms['books'] ?? 0),
                 (int)($perms['expenses'] ?? 0),(int)($perms['reports'] ?? 0),(int)($perms['staff'] ?? 0),(int)($perms['settings'] ?? 0),
                 'active']);
            addActivity($db, '👥', 'rgba(74,124,111,.14)', "Staff <strong>{$d['name']}</strong> added");
        }
        jsonResponse(['success' => true]);

    case 'delete_staff':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $d = getInput();
        $db->prepare("DELETE FROM staff WHERE id=?")->execute([$d['id'] ?? '']);
        jsonResponse(['success' => true]);

    case 'change_password':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        if (empty($_SESSION['staff_id'])) jsonError('Not authenticated', 401);
        $d = getInput();
        $current  = $d['current_password'] ?? '';
        $newPass  = $d['new_password'] ?? '';
        if (strlen($newPass) < 6) jsonError('New password must be at least 6 characters.');
        $stmt = $db->prepare("SELECT password_hash FROM staff WHERE id=? LIMIT 1");
        $stmt->execute([$_SESSION['staff_id']]);
        $row = $stmt->fetch();
        if (!$row || !password_verify($current, $row['password_hash'])) {
            jsonError('Current password is incorrect.', 403);
        }
        $newHash = password_hash($newPass, PASSWORD_BCRYPT);
        $db->prepare("UPDATE staff SET password_hash=? WHERE id=?")->execute([$newHash, $_SESSION['staff_id']]);
        jsonResponse(['success' => true]);

    // ══════════════════════════════════
    // NOTIFICATIONS
    // ══════════════════════════════════
    case 'get_notifications':
        $rows = $db->query("SELECT * FROM notifications ORDER BY created_at DESC")->fetchAll();
        jsonResponse($rows);

    case 'mark_read':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $d = getInput();
        $db->prepare("UPDATE notifications SET is_read=1 WHERE id=?")->execute([$d['id'] ?? 0]);
        jsonResponse(['success' => true]);

    case 'delete_notif':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $d = getInput();
        $db->prepare("DELETE FROM notifications WHERE id=?")->execute([$d['id'] ?? 0]);
        jsonResponse(['success' => true]);

    case 'clear_notifs':
        $db->exec("DELETE FROM notifications");
        jsonResponse(['success' => true]);

    // ══════════════════════════════════
    // SETTINGS
    // ══════════════════════════════════
    case 'get_settings':
        $row = $db->query("SELECT * FROM settings WHERE id=1")->fetch();
        jsonResponse($row);

    case 'save_settings':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $d = getInput();
        // Check if ac_extra column exists; if not, skip it gracefully
        try {
            $db->prepare("UPDATE settings SET name=?,phone=?,email=?,addr=?,fine_per_day=?,loan_days=?,wa_number=?,ac_extra=? WHERE id=1")
               ->execute([$d['name'] ?? '',$d['phone'] ?? '',$d['email'] ?? '',$d['addr'] ?? '',(int)($d['fine'] ?? 5),(int)($d['days'] ?? 14),$d['wa_number'] ?? '',(int)($d['ac_extra'] ?? 200)]);
        } catch (\PDOException $e) {
            // ac_extra column may not exist yet — fall back without it
            $db->prepare("UPDATE settings SET name=?,phone=?,email=?,addr=?,fine_per_day=?,loan_days=?,wa_number=? WHERE id=1")
               ->execute([$d['name'] ?? '',$d['phone'] ?? '',$d['email'] ?? '',$d['addr'] ?? '',(int)($d['fine'] ?? 5),(int)($d['days'] ?? 14),$d['wa_number'] ?? '']);
        }
        addActivity($db, '⚙', 'rgba(100,116,139,.14)', "Settings updated by <strong>" . htmlspecialchars($_SESSION['staff_name'] ?? 'Staff') . "</strong>");
        jsonResponse(['success' => true]);

    // ══════════════════════════════════
    // WA LOG
    // ══════════════════════════════════
    case 'log_wa':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $d = getInput();
        $db->prepare("INSERT INTO wa_send_log (sent_to,preview,type) VALUES (?,?,?)")->execute([$d['to'] ?? '',$d['preview'] ?? '',$d['type'] ?? 'single']);
        jsonResponse(['success' => true]);

    case 'get_wa_log':
        $rows = $db->query("SELECT * FROM wa_send_log ORDER BY created_at DESC LIMIT 20")->fetchAll();
        jsonResponse($rows);

    // ══════════════════════════════════
    // ACTIVITIES
    // ══════════════════════════════════
    case 'get_activities':
        $rows = $db->query("SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 20")->fetchAll();
        jsonResponse($rows);

    case 'get_my_dp':
        if (empty($_SESSION['staff_id'])) jsonError('Not authenticated', 401);
        try {
            $stmt = $db->prepare("SELECT dp_image FROM staff WHERE id=? LIMIT 1");
            $stmt->execute([$_SESSION['staff_id']]);
            $row = $stmt->fetch();
            jsonResponse(['dp' => $row['dp_image'] ?? null]);
        } catch (\PDOException $e) {
            jsonResponse(['dp' => null]); // column doesn't exist yet
        }

    // ══════════════════════════════════
    // PROFILE IMAGE (DP) UPLOAD
    // ══════════════════════════════════
    case 'upload_dp':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        if (empty($_SESSION['staff_id'])) jsonError('Not authenticated', 401);
        if (empty($_FILES['dp'])) jsonError('No file uploaded');
        $file = $_FILES['dp'];
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        if (!in_array($file['type'], $allowed)) jsonError('Only JPG/PNG/GIF/WebP allowed');
        if ($file['size'] > 2 * 1024 * 1024) jsonError('File too large (max 2MB)');
        // Convert to base64 data URI for storage
        $data = base64_encode(file_get_contents($file['tmp_name']));
        $uri  = 'data:' . $file['type'] . ';base64,' . $data;
        try {
            $db->prepare("UPDATE staff SET dp_image=? WHERE id=?")->execute([$uri, $_SESSION['staff_id']]);
        } catch (\PDOException $e) {
            jsonError('dp_image column missing — run: ALTER TABLE staff ADD COLUMN dp_image TEXT NULL;');
        }
        jsonResponse(['success' => true, 'dp' => $uri]);

    default:
        jsonError('Unknown action', 404);
}

// ─── Helper functions ────────────────────────────
function addActivity($db, $icon, $bg, $text) {
    $db->prepare("INSERT INTO activity_log (icon,bg,text) VALUES (?,?,?)")->execute([$icon,$bg,$text]);
    // Keep only last 50
    $db->exec("DELETE FROM activity_log WHERE id NOT IN (SELECT id FROM (SELECT id FROM activity_log ORDER BY created_at DESC LIMIT 50) t)");
}

function addNotif($db, $type, $title, $msg) {
    $db->prepare("INSERT INTO notifications (type,title,msg,is_read) VALUES (?,?,?,0)")->execute([$type,$title,$msg]);
}
