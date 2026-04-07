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
        $staff    = $db->query("SELECT s.id,s.name,s.role,s.email,s.phone,s.username,s.perm_students,s.perm_fees,s.perm_books,s.perm_expenses,s.perm_reports,s.perm_staff,s.perm_settings,s.status,COALESCE(ss.base_monthly,0) AS base_salary FROM staff s LEFT JOIN staff_salary ss ON ss.staff_id=s.id ORDER BY s.created_at")->fetchAll();
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
        try {
            $lastId = $db->query("SELECT id FROM students ORDER BY created_at DESC LIMIT 1")->fetchColumn();
            $lastNum = $lastId ? (int)substr($lastId, 4) : 0;
            $newId = 'STU-' . str_pad($lastNum + 1, 3, '0', STR_PAD_LEFT);
            $baseFee = (int)($d['base_fee'] ?? 0);
            $discType  = $d['discount_type'] ?? 'none';
            $discVal   = (float)($d['discount_value'] ?? 0);
            $disc = 0;
            if ($discType === 'flat') $disc = min($discVal, $baseFee);
            elseif ($discType === 'percent') $disc = round($baseFee * $discVal / 100);
            $netFee = $baseFee - $disc;
            $colors = ['#4a7c6f','#c47d2b','#3a7ab0','#7c5cbf','#c0444f','#3a7d5e','#e67e22'];
            $color = $colors[array_rand($colors)];
            $sql = "INSERT INTO students (id,fname,lname,phone,email,addr,batch_id,seat_type,seat,base_fee,discount_type,discount_value,discount_reason,net_fee,paid_amt,fee_status,paid_on,due_date,course,color,join_date) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,0,'pending',NULL,?,?,?,?)";
            $stmt = $db->prepare($sql);
            // Calculate due_date: 30 days from join date (or today)
            $joinRaw = $d['join_date'] ?? '';
            $joinTs  = $joinRaw ? strtotime($joinRaw) : time();
            if (!$joinTs) $joinTs = time();
            $joinDate = date('Y-m-d', $joinTs);
            $dueDate  = date('Y-m-d', strtotime('+30 days', $joinTs));
            $stmt->execute([
                $newId, $d['fname'], $d['lname'] ?? '', $d['phone'] ?? '',
                $d['email'] ?? '', $d['addr'] ?? '',
                $d['batch_id'], $d['seat_type'] ?? 'non-ac', $d['seat'] ?? '',
                $baseFee, $discType, $discVal, $d['discount_reason'] ?? '',
                $netFee, $dueDate, $d['course'] ?? '', $color,
                $joinDate
            ]);
            addActivity($db, '👨‍🎓', 'rgba(74,124,111,.14)', "New student <strong>{$d['fname']} {$d['lname']}</strong> enrolled");
            addNotif($db, 'info', 'New Enrollment', "{$d['fname']} {$d['lname']} enrolled");
            jsonResponse(['success' => true, 'id' => $newId]);
        } catch (Exception $e) {
            jsonError('Failed to enroll student: ' . $e->getMessage(), 500);
        }

    case 'update_student':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $d = getInput();
        $id = $d['id'] ?? '';
        if (!$id) jsonError('ID required');
        $db->prepare(
            "UPDATE students SET fname=?, lname=?, phone=?, email=?, course=?, addr=? WHERE id=?"
        )->execute([
            $d['fname']  ?? '',
            $d['lname']  ?? '',
            $d['phone']  ?? '',
            $d['email']  ?? '',
            $d['course'] ?? '',
            $d['addr']   ?? '',
            $id
        ]);
        jsonResponse(['success' => true]);

    case 'delete_student':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $d = getInput();
        $id = $d['id'] ?? '';
        if (!$id) jsonError('ID required');
        // $db->prepare("DELETE FROM students WHERE id=?")->execute([$id]);
        // jsonResponse(['success' => true]);

        // Store student name before deleting for invoice history
            $stuRow = $db->prepare("SELECT fname, lname FROM students WHERE id=? LIMIT 1");
            $stuRow->execute([$id]);
            $stuData = $stuRow->fetch();
            $deletedName = $stuData ? trim($stuData['fname'].' '.$stuData['lname']) : 'Deleted Student';
                
            // Keep student_id in invoices intact — just mark with deleted name
            // Remove foreign key risk by nullifying only non-critical references
            $db->prepare("UPDATE attendance SET student_id=student_id WHERE student_id=?")->execute([$id]);
                
            // Delete student
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
        $lastBkId = $db->query("SELECT id FROM books ORDER BY id DESC LIMIT 1")->fetchColumn();
        $lastBkNum = $lastBkId ? (int)substr($lastBkId, 3) : 0;
        $newId = 'BK-' . str_pad($lastBkNum + 1, 3, '0', STR_PAD_LEFT);
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
        $lastInvId = $db->query("SELECT id FROM invoices ORDER BY created_at DESC LIMIT 1")->fetchColumn();
        $lastInvNum = $lastInvId ? (int)substr($lastInvId, 4) : 0;
        $invId = 'INV-' . str_pad($lastInvNum + 1, 4, '0', STR_PAD_LEFT);
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
        $lastInvId2 = $db->query("SELECT id FROM invoices ORDER BY created_at DESC LIMIT 1")->fetchColumn();
        $lastInvNum2 = $lastInvId2 ? (int)substr($lastInvId2, 4) : 0;
        $invId = 'INV-' . str_pad($lastInvNum2 + 1, 4, '0', STR_PAD_LEFT);
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
        $lastExId = $db->query("SELECT id FROM expenses ORDER BY id DESC LIMIT 1")->fetchColumn();
        $lastExNum = $lastExId ? (int)substr($lastExId, 3) : 0;
        $newId = 'EX-' . str_pad($lastExNum + 1, 3, '0', STR_PAD_LEFT);
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
            $lastSfId = $db->query("SELECT id FROM staff ORDER BY id DESC LIMIT 1")->fetchColumn();
            $lastSfNum = $lastSfId ? (int)substr($lastSfId, 3) : 0;
            $newId = 'SF-' . str_pad($lastSfNum + 1, 3, '0', STR_PAD_LEFT);
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
        if (!$row) {
            // No row yet — insert a default row so subsequent saves work
            $db->exec("INSERT INTO settings (id,name,phone,email,addr,fine_per_day,loan_days,wa_number) VALUES (1,'','','','','5','14','')");
            $row = $db->query("SELECT * FROM settings WHERE id=1")->fetch();
        }
        jsonResponse($row);

    case 'save_settings':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $d = getInput();

        // Detect which columns actually exist in the settings table
        $cols = [];
        foreach ($db->query("SHOW COLUMNS FROM settings")->fetchAll() as $c) {
            $cols[] = $c['Field'];
        }

        // Always-present fields
        $set  = ["name=?","phone=?","email=?","addr=?","fine_per_day=?","loan_days=?","wa_number=?"];
        $vals = [
            $d['name']      ?? '',
            $d['phone']     ?? '',
            $d['email']     ?? '',
            $d['addr']      ?? '',
            (int)($d['fine']  ?? 5),
            (int)($d['days']  ?? 14),
            $d['wa_number'] ?? '',
        ];

        // AC fee — save to whichever column(s) the table actually has
        $acVal = (int)($d['ac_fee'] ?? 200);
        if (in_array('ac_fee',   $cols)) { $set[] = "ac_fee=?";   $vals[] = $acVal; }
        if (in_array('ac_extra', $cols)) { $set[] = "ac_extra=?"; $vals[] = $acVal; }

        // Check if row id=1 exists; if not, INSERT it first
        $exists = $db->query("SELECT COUNT(*) FROM settings WHERE id=1")->fetchColumn();
        if (!$exists) {
            $db->exec("INSERT INTO settings (id) VALUES (1)");
        }

        $vals[] = 1; // WHERE id=1
        $db->prepare("UPDATE settings SET " . implode(',', $set) . " WHERE id=?")->execute($vals);
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

    // ══════════════════════════════════
    // STAFF PROFILE PHOTO (DP)
    // ══════════════════════════════════
    case 'get_my_dp':
        if (empty($_SESSION['staff_id'])) jsonError('Not authenticated', 401);
        try {
            $stmt = $db->prepare("SELECT dp_image FROM staff WHERE id=? LIMIT 1");
            $stmt->execute([$_SESSION['staff_id']]);
            $row = $stmt->fetch();
            jsonResponse(['dp' => $row['dp_image'] ?? null]);
        } catch (\PDOException $e) {
            jsonResponse(['dp' => null]); // column doesn't exist yet — safe fallback
        }

    case 'upload_dp':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        if (empty($_SESSION['staff_id'])) jsonError('Not authenticated', 401);
        if (empty($_FILES['dp'])) jsonError('No file uploaded');
        $file    = $_FILES['dp'];
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        if (!in_array($file['type'], $allowed)) jsonError('Only JPG/PNG/GIF/WebP images allowed');
        if ($file['size'] > 2 * 1024 * 1024)   jsonError('File too large — max 2MB');
        $base64 = base64_encode(file_get_contents($file['tmp_name']));
        $uri    = 'data:' . $file['type'] . ';base64,' . $base64;
        try {
            $db->prepare("UPDATE staff SET dp_image=? WHERE id=?")->execute([$uri, $_SESSION['staff_id']]);
        } catch (\PDOException $e) {
            jsonError('dp_image column missing. Run: ALTER TABLE staff ADD COLUMN dp_image MEDIUMTEXT NULL;');
        }
        jsonResponse(['success' => true, 'dp' => $uri]);

    case 'upload_logo':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        if (empty($_FILES['logo'])) jsonError('No file uploaded');
        $file    = $_FILES['logo'];
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        if (!in_array($file['type'], $allowed)) jsonError('Only JPG/PNG/GIF/WebP images allowed');
        if ($file['size'] > 2 * 1024 * 1024)   jsonError('File too large — max 2MB');
        $base64  = base64_encode(file_get_contents($file['tmp_name']));
        $uri     = 'data:' . $file['type'] . ';base64,' . $base64;
        try {
            // Ensure logo_url column exists
            $db->exec("ALTER TABLE settings ADD COLUMN IF NOT EXISTS logo_url MEDIUMTEXT NULL");
        } catch (\PDOException $e) { /* column may already exist */ }
        // Ensure row exists
        $exists = $db->query("SELECT COUNT(*) FROM settings WHERE id=1")->fetchColumn();
        if (!$exists) $db->exec("INSERT INTO settings (id) VALUES (1)");
        $db->prepare("UPDATE settings SET logo_url=? WHERE id=1")->execute([$uri]);
        jsonResponse(['success' => true, 'logo_url' => $uri]);

    case 'get_logo':
        try {
            $row = $db->query("SELECT logo_url FROM settings WHERE id=1")->fetch();
            jsonResponse(['logo_url' => $row['logo_url'] ?? '']);
        } catch (\PDOException $e) {
            jsonResponse(['logo_url' => '']);
        }

    case 'remove_logo':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        try {
            $db->prepare("UPDATE settings SET logo_url=NULL WHERE id=1")->execute();
        } catch (\PDOException $e) { /* safe to ignore */ }
        jsonResponse(['success' => true]);

    // ══════════════════════════════════
    // RENEWALS
    // ══════════════════════════════════
    case 'renew_student':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $d = getInput();

        // Required fields
        $studentId = trim($d['student_id'] ?? '');
        $amount    = (int)($d['amount']     ?? 0);
        $months    = (int)($d['months']     ?? 1);
        $mode      = trim($d['mode']        ?? 'Cash');
        $note      = trim($d['note']        ?? '');

        if (!$studentId)   jsonError('student_id is required');
        if ($amount <= 0)  jsonError('amount must be greater than 0');
        if ($months <= 0)  jsonError('months must be at least 1');

        // Fetch student
        $stuStmt = $db->prepare("SELECT * FROM students WHERE id=? LIMIT 1");
        $stuStmt->execute([$studentId]);
        $stu = $stuStmt->fetch();
        if (!$stu) jsonError('Student not found');

        // Calculate new due_date: extend from current due_date (or today if expired)
        $currentDue = $stu['due_date'] ?? null;
        if (empty($currentDue) || $currentDue === '0000-00-00') {
            $baseTs = time();
        } else {
            $baseTs = max(time(), strtotime($currentDue));
        }
        $newDueDate = date('Y-m-d', strtotime("+{$months} months", $baseTs));

        // Update student: reset fee status, paid_amt, due_date
        $db->prepare(
            "UPDATE students SET due_date=?, paid_amt=paid_amt+?, fee_status='paid', paid_on=? WHERE id=?"
        )->execute([$newDueDate, $amount, date('Y-m-d'), $studentId]);

        // Insert into renewals table
        $lastRnwId = $db->query("SELECT id FROM renewals ORDER BY created_at DESC LIMIT 1")->fetchColumn();
        $lastRnwNum = $lastRnwId ? (int)substr($lastRnwId, 4) : 0;
        $renewId = 'RNW-' . str_pad($lastRnwNum + 1, 4, '0', STR_PAD_LEFT);
        $renewedBy = $_SESSION['staff_name'] ?? 'Staff';

        $db->prepare(
            "INSERT INTO renewals
                (id, student_id, amount, months, mode, note, renewed_by, renewal_date, new_due_date)
             VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        )->execute([
            $renewId,
            $studentId,
            $amount,
            $months,
            $mode,
            $note,
            $renewedBy,
            date('Y-m-d'),
            $newDueDate,
        ]);

        // Create invoice record for this renewal
        $lastInvIdR = $db->query("SELECT id FROM invoices ORDER BY created_at DESC LIMIT 1")->fetchColumn();
        $lastInvNumR = $lastInvIdR ? (int)substr($lastInvIdR, 4) : 0;
        $invId = 'INV-' . str_pad($lastInvNumR + 1, 4, '0', STR_PAD_LEFT);
        $db->prepare(
            "INSERT INTO invoices
                (id, student_id, type, amount, base_fee, discount, net_fee, paid_amt, balance,
                 invoice_date, month, mode, status)
             VALUES (?, ?, 'Renewal', ?, ?, 0, ?, ?, 0, ?, ?, ?, 'paid')"
        )->execute([
            $invId,
            $studentId,
            $amount,
            $stu['base_fee'] ?? $amount,
            $stu['net_fee']  ?? $amount,
            $amount,
            date('Y-m-d'),
            date('F Y'),
            $mode,
        ]);

        // Log activity & notification
        $stuName = trim(($stu['fname'] ?? '') . ' ' . ($stu['lname'] ?? ''));
        addActivity(
            $db, '🔄', 'rgba(61,111,240,.14)',
            "<strong>{$stuName}</strong> renewed for {$months} month(s) — ₹{$amount} via {$mode}. Due: {$newDueDate}"
        );
        addNotif(
            $db, 'success', 'Renewal Successful',
            "{$stuName} renewed for {$months} month(s). New due date: {$newDueDate}"
        );

        jsonResponse([
            'success'      => true,
            'renewal_id'   => $renewId,
            'invoice_id'   => $invId,
            'new_due_date' => $newDueDate,
            'student_id'   => $studentId,
        ]);

    case 'get_renewals':
        $rows = $db->query(
            "SELECT r.*, s.fname, s.lname, s.color
             FROM renewals r
             LEFT JOIN students s ON r.student_id = s.id
             ORDER BY r.renewal_date DESC, r.created_at DESC"
        )->fetchAll();
        jsonResponse($rows);

    // ══════════════════════════════════
    // AUDIT LOG
    // ══════════════════════════════════
    case 'get_audit_log':
        // Auto-create audit_log table if it doesn't exist
        $db->exec("CREATE TABLE IF NOT EXISTS audit_log (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            who         VARCHAR(128) NOT NULL DEFAULT 'Admin',
            type        VARCHAR(32)  NOT NULL DEFAULT 'other',
            text        TEXT         NOT NULL,
            ip          VARCHAR(64)  DEFAULT NULL,
            created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_created_at (created_at),
            INDEX idx_type (type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $limit = min((int)($_GET['limit'] ?? 500), 1000);
        $rows  = $db->query(
            "SELECT id, who, type, text, ip, created_at
             FROM audit_log
             ORDER BY created_at DESC
             LIMIT $limit"
        )->fetchAll(PDO::FETCH_ASSOC);
        jsonResponse(['logs' => $rows, 'records' => $rows]);
        break;

    case 'save_audit_log':
        $d    = getInput();
        $type = trim($d['type'] ?? 'other');
        $text = trim($d['text'] ?? '');
        $who  = trim($d['who']  ?? ($_SESSION['staff_name'] ?? 'Admin'));
        if (!$text) jsonResponse(['ok' => true]);

        // Auto-create audit_log table if it doesn't exist
        $db->exec("CREATE TABLE IF NOT EXISTS audit_log (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            who         VARCHAR(128) NOT NULL DEFAULT 'Admin',
            type        VARCHAR(32)  NOT NULL DEFAULT 'other',
            text        TEXT         NOT NULL,
            ip          VARCHAR(64)  DEFAULT NULL,
            created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_created_at (created_at),
            INDEX idx_type (type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
        $db->prepare("INSERT INTO audit_log (who, type, text, ip) VALUES (?, ?, ?, ?)")
           ->execute([$who, $type, $text, $ip]);

        // Keep only last 2000 rows to avoid unbounded growth
        $db->exec("DELETE FROM audit_log WHERE id NOT IN (
            SELECT id FROM (SELECT id FROM audit_log ORDER BY id DESC LIMIT 2000) t
        )");

        jsonResponse(['ok' => true]);
        break;

    // ══════════════════════════════════
    // STAFF ATTENDANCE
    // ══════════════════════════════════
    case 'save_staff_attendance':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        // Auto-create table if not exists
        $db->exec("CREATE TABLE IF NOT EXISTS staff_attendance (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            staff_id    VARCHAR(20) NOT NULL,
            att_date    DATE        NOT NULL,
            status      ENUM('present','absent','half') NOT NULL DEFAULT 'present',
            created_at  TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_staff_date (staff_id, att_date)
        )");
        $d = getInput();
        $date       = $d['date']       ?? date('Y-m-d');
        $attendance = $d['attendance'] ?? [];
        if (empty($date) || empty($attendance)) jsonError('date and attendance required');
        $count = 0;
        foreach ($attendance as $sfId => $row) {
            $status = is_array($row) ? ($row['status'] ?? 'present') : $row;
            $allowed_statuses = ['present', 'absent', 'half'];
            if (!in_array($status, $allowed_statuses)) $status = 'present';
            $db->prepare(
                "INSERT INTO staff_attendance (staff_id, att_date, status)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE status=?, created_at=NOW()"
            )->execute([$sfId, $date, $status, $status]);
            $count++;
        }
        $who = $_SESSION['staff_name'] ?? 'Staff';
        addActivity($db, '👥', 'rgba(61,111,240,.14)', "Staff attendance saved for <strong>$date</strong> by $who ($count records)");
        jsonResponse(['success' => true, 'count' => $count]);

    case 'get_staff_attendance':
        // Auto-create table if not exists
        $db->exec("CREATE TABLE IF NOT EXISTS staff_attendance (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            staff_id    VARCHAR(20) NOT NULL,
            att_date    DATE        NOT NULL,
            status      ENUM('present','absent','half') NOT NULL DEFAULT 'present',
            created_at  TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_staff_date (staff_id, att_date)
        )");
        $date = $_GET['date'] ?? date('Y-m-d');
        $stmt = $db->prepare("SELECT staff_id, status FROM staff_attendance WHERE att_date=?");
        $stmt->execute([$date]);
        $att = [];
        foreach ($stmt->fetchAll() as $r) {
            $att[$r['staff_id']] = ['status' => $r['status']];
        }
        jsonResponse(['date' => $date, 'attendance' => $att]);

    case 'get_staff_attendance_summary':
        // Auto-create table if not exists
        $db->exec("CREATE TABLE IF NOT EXISTS staff_attendance (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            staff_id    VARCHAR(20) NOT NULL,
            att_date    DATE        NOT NULL,
            status      ENUM('present','absent','half') NOT NULL DEFAULT 'present',
            created_at  TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_staff_date (staff_id, att_date)
        )");
        $month = $_GET['month'] ?? date('Y-m'); // format: 2026-04
        $stmt  = $db->prepare(
            "SELECT
                staff_id AS id,
                SUM(status='present') AS present,
                SUM(status='absent')  AS absent,
                SUM(status='half')    AS half
             FROM staff_attendance
             WHERE DATE_FORMAT(att_date, '%Y-%m') = ?
             GROUP BY staff_id"
        );
        $stmt->execute([$month]);
        jsonResponse($stmt->fetchAll());

    // ══════════════════════════════════
    // STAFF SALARY (uses staff_salary table: staff_id, base_monthly, updated_at)
    // ══════════════════════════════════
    case 'save_salary':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $d        = getInput();
        $salaries = $d['salaries'] ?? []; // { "SF-001": 30000, ... }
        if (empty($salaries) || !is_array($salaries)) jsonError('salaries object required');
        $stmt = $db->prepare(
            "INSERT INTO staff_salary (staff_id, base_monthly, updated_at)
             VALUES (?, ?, NOW())
             ON DUPLICATE KEY UPDATE base_monthly=?, updated_at=NOW()"
        );
        $count = 0;
        foreach ($salaries as $sfId => $amount) {
            $amount = max(0, (int)$amount);
            $stmt->execute([$sfId, $amount, $amount]);
            $count++;
        }
        $who = $_SESSION['staff_name'] ?? 'Staff';
        addActivity($db, '💰', 'rgba(22,163,74,.14)', "Salary updated for $count staff member(s) by $who");
        jsonResponse(['success' => true, 'count' => $count]);

    case 'get_salary':
        $rows     = $db->query("SELECT staff_id, base_monthly FROM staff_salary")->fetchAll();
        $salaries = [];
        foreach ($rows as $r) $salaries[$r['staff_id']] = (int)$r['base_monthly'];
        jsonResponse(['salaries' => $salaries]);

    // ══════════════════════════════════
    // QR ATTENDANCE SYSTEM
    // ══════════════════════════════════

    case 'generate_qr_token':
        // Generate or refresh a QR token for a student
        // Token is valid for 24 hours; student scans it to mark attendance
        $d = getInput();
        $studentId = trim($d['student_id'] ?? '');
        if (!$studentId) jsonError('student_id required');
        // Verify student exists
        $s = $db->prepare("SELECT id, fname, lname FROM students WHERE id=?");
        $s->execute([$studentId]);
        $stu = $s->fetch();
        if (!$stu) jsonError('Student not found');
        // Create qr_tokens table if not exists
        $db->exec("CREATE TABLE IF NOT EXISTS qr_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            token VARCHAR(64) NOT NULL UNIQUE,
            type VARCHAR(32) NOT NULL DEFAULT 'attendance',
            student_id VARCHAR(32) NOT NULL,
            date DATE NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        // Delete old tokens for this student
        $db->prepare("DELETE FROM qr_tokens WHERE student_id=? AND type='attendance'")->execute([$studentId]);
        // Generate new token
        $token = bin2hex(random_bytes(24));
        $date  = date('Y-m-d');
        $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
        $db->prepare("INSERT INTO qr_tokens (token, type, student_id, date, expires_at) VALUES (?,?,?,?,?)")
           ->execute([$token, 'attendance', $studentId, $date, $expires]);
        jsonResponse(['success' => true, 'token' => $token, 'expires_at' => $expires, 'student_id' => $studentId]);

    case 'get_student_qr':
        // Get existing token for student (for student_app.php) or generate one
        // No session required — identified by student_id + phone (simple auth)
        $studentId = $_GET['student_id'] ?? '';
        $phone     = $_GET['phone'] ?? '';
        if (!$studentId || !$phone) jsonError('student_id and phone required');
        // Verify student
        $s = $db->prepare("SELECT id, fname, lname, phone, batch_id, seat, seat_type, color, fee_status, due_date, net_fee, course, join_date FROM students WHERE id=? LIMIT 1");
        $s->execute([$studentId]);
        $stu = $s->fetch();
        if (!$stu) jsonError('Student not found');
        // Simple phone verification (last 10 digits)
        $dbPhone   = preg_replace('/\D/', '', $stu['phone']);
        $inPhone   = preg_replace('/\D/', '', $phone);
        if (substr($dbPhone, -10) !== substr($inPhone, -10)) jsonError('Phone number does not match');
        // Create qr_tokens table if not exists
        $db->exec("CREATE TABLE IF NOT EXISTS qr_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            token VARCHAR(64) NOT NULL UNIQUE,
            type VARCHAR(32) NOT NULL DEFAULT 'attendance',
            student_id VARCHAR(32) NOT NULL,
            date DATE NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        // Get or create token for today
        $today = date('Y-m-d');
        $tok = $db->prepare("SELECT token, expires_at FROM qr_tokens WHERE student_id=? AND type='attendance' AND date=? AND expires_at > NOW() LIMIT 1");
        $tok->execute([$studentId, $today]);
        $existing = $tok->fetch();
        if (!$existing) {
            $token   = bin2hex(random_bytes(24));
            $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
            $db->prepare("INSERT INTO qr_tokens (token, type, student_id, date, expires_at) VALUES (?,?,?,?,?)")
               ->execute([$token, 'attendance', $studentId, $today, $expires]);
        } else {
            $token   = $existing['token'];
            $expires = $existing['expires_at'];
        }
        // Get batch info
        $batch = null;
        if ($stu['batch_id']) {
            $bStmt = $db->prepare("SELECT name, start_time, end_time FROM batches WHERE id=? LIMIT 1");
            $bStmt->execute([$stu['batch_id']]);
            $batch = $bStmt->fetch();
        }
        // Get recent attendance (last 10 days)
        // Ensure student_attendance table exists before querying it
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
        
        // Get recent attendance (last 10 days)
        $attStmt = $db->prepare("SELECT * FROM student_attendance WHERE student_id=? ORDER BY date DESC LIMIT 10");
        $settings = $db->query("SELECT name, phone, addr, wa_number, logo_url FROM settings WHERE id=1")->fetch();

        $attStmt->execute([$studentId]);
        $recentAtt = $attStmt->fetchAll();
        jsonResponse([
            'student'    => $stu,
            'batch'      => $batch,
            'token'      => $token,
            'expires_at' => $expires,
            'attendance' => $recentAtt,
            'settings'   => $settings ?: []
        ]);

    case 'scan_qr':
        // Called when student scans the QR code — marks attendance in student_attendance table
        // No session required — public endpoint
        $d = getInput();
        $token = trim($d['token'] ?? ($_GET['token'] ?? ''));
        if (!$token) jsonError('Token required');
        // Create student_attendance table if not exists
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
        $db->exec("CREATE TABLE IF NOT EXISTS qr_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            token VARCHAR(64) NOT NULL UNIQUE,
            type VARCHAR(32) NOT NULL DEFAULT 'attendance',
            student_id VARCHAR(32) NOT NULL,
            date DATE NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        $tokStmt = $db->prepare("SELECT * FROM qr_tokens WHERE token=? AND type='attendance' AND expires_at > NOW() LIMIT 1");
        $tokStmt->execute([$token]);
        $tok = $tokStmt->fetch();
        if (!$tok) jsonError('Invalid or expired QR code. Please refresh your QR from the student app.');
        $studentId = $tok['student_id'];
        $today     = date('Y-m-d');
        $now       = date('H:i:s');
        // Get student info
        $stuStmt = $db->prepare("SELECT s.*, b.name as batch_name, b.start_time FROM students s LEFT JOIN batches b ON s.batch_id=b.id WHERE s.id=? LIMIT 1");
        $stuStmt->execute([$studentId]);
        $stu = $stuStmt->fetch();
        if (!$stu) jsonError('Student not found');
        // Check if already checked in today
        $existStmt = $db->prepare("SELECT * FROM student_attendance WHERE student_id=? AND date=? LIMIT 1");
        $existStmt->execute([$studentId, $today]);
        $existing = $existStmt->fetch();
        if ($existing) {
            // Already checked in — do check_out if not done
            if (!$existing['check_out']) {
                $db->prepare("UPDATE student_attendance SET check_out=? WHERE student_id=? AND date=?")
                   ->execute([$now, $studentId, $today]);
                jsonResponse(['success' => true, 'action' => 'check_out', 'time' => $now,
                    'student' => ['fname' => $stu['fname'], 'lname' => $stu['lname'], 'id' => $studentId, 'color' => $stu['color']]]);
            } else {
                jsonResponse(['success' => false, 'action' => 'already_complete',
                    'check_in' => $existing['check_in'], 'check_out' => $existing['check_out'],
                    'student' => ['fname' => $stu['fname'], 'lname' => $stu['lname'], 'id' => $studentId, 'color' => $stu['color']]]);
            }
        }
        // First scan = check_in
        $isLate = 0; $lateMinutes = 0;
        if ($stu['start_time']) {
            $batchStart  = strtotime(date('Y-m-d') . ' ' . $stu['start_time']);
            $nowTs       = strtotime(date('Y-m-d H:i:s'));
            $gracePeriod = 15 * 60; // 15 minute grace
            if ($nowTs > $batchStart + $gracePeriod) {
                $isLate      = 1;
                $lateMinutes = (int)round(($nowTs - $batchStart) / 60);
            }
        }
        $db->prepare("INSERT INTO student_attendance (student_id, date, status, check_in, is_late, late_minutes, marked_by)
            VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE check_in=VALUES(check_in), is_late=VALUES(is_late), late_minutes=VALUES(late_minutes)")
           ->execute([$studentId, $today, 'present', $now, $isLate, $lateMinutes, 'qr_scan']);
        // Also update the attendance table (used by admin dashboard)
        $db->prepare("INSERT INTO attendance (student_id, attendance_date, status) VALUES (?,?,?) ON DUPLICATE KEY UPDATE status='present'")
           ->execute([$studentId, $today, 'present']);
        addActivity($db, '📱', 'rgba(22,163,74,.14)', "<strong>{$stu['fname']} {$stu['lname']}</strong> checked in via QR at $now" . ($isLate ? " ⚠ Late by {$lateMinutes}min" : ''));
        jsonResponse(['success' => true, 'action' => 'check_in', 'time' => $now, 'is_late' => $isLate, 'late_minutes' => $lateMinutes,
            'student' => ['fname' => $stu['fname'], 'lname' => $stu['lname'], 'id' => $studentId, 'color' => $stu['color']]]);

    case 'get_student_attendance_history':
        // Get attendance history for a student (for student_app.php)
        $studentId = $_GET['student_id'] ?? '';
        $phone     = $_GET['phone'] ?? '';
        if (!$studentId || !$phone) jsonError('student_id and phone required');
        $s = $db->prepare("SELECT id, phone FROM students WHERE id=? LIMIT 1");
        $s->execute([$studentId]);
        $stu = $s->fetch();
        if (!$stu) jsonError('Student not found');
        $dbPhone = preg_replace('/\D/', '', $stu['phone']);
        $inPhone = preg_replace('/\D/', '', $phone);
        if (substr($dbPhone, -10) !== substr($inPhone, -10)) jsonError('Phone mismatch');
        $rows = $db->prepare("SELECT * FROM student_attendance WHERE student_id=? ORDER BY date DESC LIMIT 30");
        $rows->execute([$studentId]);
        jsonResponse(['attendance' => $rows->fetchAll()]);

    case 'get_todays_qr_attendance':
        // Admin view — get today's QR scan attendance
        $date  = $_GET['date'] ?? date('Y-m-d');
        $rows  = $db->prepare("SELECT sa.*, s.fname, s.lname, s.color, b.name as batch_name
            FROM student_attendance sa
            JOIN students s ON sa.student_id = s.id
            LEFT JOIN batches b ON s.batch_id = b.id
            WHERE sa.date=? ORDER BY sa.check_in ASC");
        $rows->execute([$date]);
        jsonResponse(['date' => $date, 'records' => $rows->fetchAll()]);

         case 'get_audit_log':
        $db->exec("CREATE TABLE IF NOT EXISTS audit_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            staff_id VARCHAR(32),
            staff_name VARCHAR(128),
            action VARCHAR(128),
            detail TEXT,
            ip VARCHAR(64),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        $limit = (int)($_GET['limit'] ?? 100);
        $rows = $db->query("SELECT * FROM audit_log ORDER BY created_at DESC LIMIT $limit")->fetchAll();
        jsonResponse(['logs' => $rows]);
        break;

    case 'get_wa_log':
        $db->exec("CREATE TABLE IF NOT EXISTS wa_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id VARCHAR(32),
            student_name VARCHAR(128),
            phone VARCHAR(32),
            message TEXT,
            status VARCHAR(32) DEFAULT 'sent',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        $limit = (int)($_GET['limit'] ?? 100);
        $rows = $db->query("SELECT * FROM wa_log ORDER BY created_at DESC LIMIT $limit")->fetchAll();
        jsonResponse(['logs' => $rows]);
        break;

    case 'forgot_password':
        $d        = getInput();
        $username = trim($d['username'] ?? '');
        $email    = trim($d['email'] ?? '');
        if (!$username || !$email) jsonError('Username and email required');
        
        // NEW CODE — verify username exists, then check email
        $stmt = $db->prepare("SELECT id, name, email FROM staff WHERE username=? AND status='active' LIMIT 1");
        $stmt->execute([$username]);
        $staff = $stmt->fetch();

    if (!$staff) {
        jsonResponse(['success' => true]); // username not found, silent fail
        break;
    }

    // If staff has no email stored, update it now
    if (empty($staff['email'])) {
        $db->prepare("UPDATE staff SET email=? WHERE id=?")->execute([$email, $staff['id']]);
        $staff['email'] = $email;
    } elseif (strtolower($staff['email']) !== strtolower($email)) {
        jsonError('The email address does not match our records.'); // email mismatch
        break;
    }

    // Generate reset token
    $token   = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));

    // Create table if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        staff_id VARCHAR(32) NOT NULL,
        token VARCHAR(128) NOT NULL UNIQUE,
        expires_at DATETIME NOT NULL,
        used TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Delete old tokens for this staff
    $db->prepare("DELETE FROM password_resets WHERE staff_id=?")->execute([$staff['id']]);

    // Save new token
    $db->prepare("INSERT INTO password_resets (staff_id, token, expires_at) VALUES (?,?,?)")
       ->execute([$staff['id'], $token, $expires]);

    // Send email
    $resetLink = "https://library.optms.co.in/reset_password?token=" . $token;
    $to      = $staff['email'];
    $subject = "Password Reset – OPTMS Tech Library";
    $message = "Hello {$staff['name']},\n\nClick the link below to reset your password:\n\n$resetLink\n\nThis link expires in 30 minutes.\n\nIf you did not request this, ignore this email.\n\n– OPTMS Tech Library";
    $headers = "From: noreply@optms.co.in\r\nX-Mailer: PHP/" . phpversion();

    mail($to, $subject, $message, $headers);
    jsonResponse(['success' => true]);
    break;

  case 'reset_password':
    $d        = getInput();
    $token    = trim($d['token'] ?? '');
    $password = $d['password'] ?? '';
    if (!$token || !$password) jsonError('Token and password required');
    if (strlen($password) < 6) jsonError('Password must be at least 6 characters');

    $db->exec("CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        staff_id VARCHAR(32) NOT NULL,
        token VARCHAR(128) NOT NULL UNIQUE,
        expires_at DATETIME NOT NULL,
        used TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $stmt = $db->prepare("SELECT * FROM password_resets WHERE token=? AND used=0 AND expires_at > NOW() LIMIT 1");
    $stmt->execute([$token]);
    $reset = $stmt->fetch();

    if (!$reset) jsonError('Reset link has expired or already been used. Please request a new one.');

    // Update password
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $db->prepare("UPDATE staff SET password_hash=? WHERE id=?")->execute([$hash, $reset['staff_id']]);

    // Mark token as used
    $db->prepare("UPDATE password_resets SET used=1 WHERE token=?")->execute([$token]);

    jsonResponse(['success' => true]);
    break;

    // ── Student Invoices (for student app) ──
    case 'get_student_invoices':
        $studentId = $_GET['student_id'] ?? '';
        $phone     = $_GET['phone'] ?? '';
        if (!$studentId || !$phone) jsonError('student_id and phone required');
        // Verify phone
        $s = $db->prepare("SELECT id, phone FROM students WHERE id=? LIMIT 1");
        $s->execute([$studentId]);
        $stu = $s->fetch();
        if (!$stu) jsonError('Student not found');
        $dbPhone = preg_replace('/\D/', '', $stu['phone']);
        $inPhone = preg_replace('/\D/', '', $phone);
        if (substr($dbPhone, -10) !== substr($inPhone, -10)) jsonError('Phone mismatch');
        // Get invoices
        $rows = $db->prepare("SELECT * FROM invoices WHERE student_id=? ORDER BY created_at DESC");
        $rows->execute([$studentId]);
        jsonResponse(['invoices' => $rows->fetchAll()]);
        break;
 
    // ── Student Issued Books (for student app) ──
    case 'get_student_books':
        $studentId = $_GET['student_id'] ?? '';
        $phone     = $_GET['phone'] ?? '';
        if (!$studentId || !$phone) jsonError('student_id and phone required');
        // Verify phone
        $s = $db->prepare("SELECT id, phone FROM students WHERE id=? LIMIT 1");
        $s->execute([$studentId]);
        $stu = $s->fetch();
        if (!$stu) jsonError('Student not found');
        $dbPhone = preg_replace('/\D/', '', $stu['phone']);
        $inPhone = preg_replace('/\D/', '', $phone);
        if (substr($dbPhone, -10) !== substr($inPhone, -10)) jsonError('Phone mismatch');
        // Get issued books
        $rows = $db->prepare("
            SELECT t.*, b.title, b.author, b.emoji
            FROM transactions t
            JOIN books b ON t.book_id = b.id
            WHERE t.student_id=? AND t.status='issued'
            ORDER BY t.issue_date DESC
        ");
        $rows->execute([$studentId]);
        jsonResponse(['books' => $rows->fetchAll()]);
        break;
 
    // ── Notices (public — all students see same notices) ──
    case 'get_student_notices':
        // Create notices table if not exists
        $db->exec("CREATE TABLE IF NOT EXISTS notices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            message TEXT NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        $rows = $db->query("SELECT * FROM notices WHERE is_active=1 ORDER BY created_at DESC LIMIT 10")->fetchAll();
        jsonResponse(['notices' => $rows]);
        break;
 
    // ── Add Notice (admin only) ──
    case 'add_notice':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $d = getInput();
        $title   = trim($d['title'] ?? '');
        $message = trim($d['message'] ?? '');
        if (!$title || !$message) jsonError('Title and message required');
        $db->exec("CREATE TABLE IF NOT EXISTS notices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            message TEXT NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        $db->prepare("INSERT INTO notices (title, message) VALUES (?,?)")->execute([$title, $message]);
        addActivity($db, '📢', 'rgba(79,70,229,.14)', "Notice posted: <strong>$title</strong>");
        jsonResponse(['success' => true]);
        break;
 
    // ── Delete Notice (admin only) ──
    case 'delete_notice':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $d = getInput();
        $id = (int)($d['id'] ?? 0);
        if (!$id) jsonError('id required');
        $db->prepare("UPDATE notices SET is_active=0 WHERE id=?")->execute([$id]);
        jsonResponse(['success' => true]);
        break;
 
    // ── Holidays (public) ──
    case 'get_student_holidays':
        // Create holidays table if not exists
        $db->exec("CREATE TABLE IF NOT EXISTS holidays (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(200) NOT NULL,
            date DATE NOT NULL,
            type VARCHAR(100) DEFAULT 'Holiday',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        $rows = $db->query("SELECT * FROM holidays WHERE date >= CURDATE() ORDER BY date ASC LIMIT 20")->fetchAll();
        jsonResponse(['holidays' => $rows]);
        break;
 
    // ── Add Holiday (admin only) ──
    case 'add_holiday':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $d = getInput();
        $name = trim($d['name'] ?? '');
        $date = trim($d['date'] ?? '');
        $type = trim($d['type'] ?? 'Holiday');
        if (!$name || !$date) jsonError('Name and date required');
        $db->exec("CREATE TABLE IF NOT EXISTS holidays (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(200) NOT NULL,
            date DATE NOT NULL,
            type VARCHAR(100) DEFAULT 'Holiday',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        $db->prepare("INSERT INTO holidays (name, date, type) VALUES (?,?,?)")->execute([$name, $date, $type]);
        addActivity($db, '🗓️', 'rgba(79,70,229,.14)', "Holiday added: <strong>$name</strong> on $date");
        jsonResponse(['success' => true]);
        break;
 
    // ── Delete Holiday (admin only) ──
    case 'delete_holiday':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $d = getInput();
        $id = (int)($d['id'] ?? 0);
        if (!$id) jsonError('id required');
        $db->prepare("DELETE FROM holidays WHERE id=?")->execute([$id]);
        jsonResponse(['success' => true]);
        break;

    case 'get_login_info':
    // Public endpoint — no auth needed
        $s = $db->query("SELECT name, logo_url FROM settings WHERE id=1")->fetch();
        jsonResponse([
            'name'     => $s['name'] ?? 'Nayi Udaan Library',
            'logo_url' => $s['logo_url'] ?? ''
        ]);
        break;

    default:
        jsonError('Unknown action', 404);

}

// ─── Helper functions ────────────────────────────
function addActivity($db, $icon, $bg, $text) {
    $db->prepare("INSERT INTO activity_log (icon,bg,text) VALUES (?,?,?)")->execute([$icon,$bg,$text]);
    // Keep only last 50
    $db->exec("DELETE FROM activity_log WHERE id NOT IN (SELECT id FROM (SELECT id FROM activity_log ORDER BY created_at DESC LIMIT 500) t)");
}

function addNotif($db, $type, $title, $msg) {
    $db->prepare("INSERT INTO notifications (type,title,msg,is_read) VALUES (?,?,?,0)")->execute([$type,$title,$msg]);
}