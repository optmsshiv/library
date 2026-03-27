<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
requireLogin();

header('Content-Type: application/json');
$db     = getDB();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $db->prepare("UPDATE students SET status='inactive' WHERE id=?")->execute([$id]);
    logActivity("Deactivated student ID $id", 'student', $id);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'search') {
    $q = '%'.trim($_GET['q'] ?? '').'%';
    $rows = $db->prepare("SELECT id, first_name, last_name, student_code, net_fee, paid_fee FROM students WHERE status='active' AND (first_name LIKE ? OR last_name LIKE ? OR student_code LIKE ?) LIMIT 20");
    $rows->execute([$q, $q, $q]);
    echo json_encode($rows->fetchAll());
    exit;
}

if ($action === 'whatsapp') {
    $id = (int)($_GET['id'] ?? 0);
    $s  = $db->prepare("SELECT * FROM students WHERE id=?");
    $s->execute([$id]);
    $s = $s->fetch();
    if ($s) {
        $balance = $s['net_fee'] - $s['paid_fee'];
        $msg = urlencode("Dear {$s['first_name']},\nYour library fee balance is ₹".number_format($balance).". Please pay by ".($s['due_date'] ?? 'due date').".\n\nThank you,\nOPTMS Tech Library");
        $phone = preg_replace('/[^0-9]/', '', $s['phone']);
        header("Location: https://wa.me/91{$phone}?text={$msg}");
        exit;
    }
    echo json_encode(['error' => 'Student not found']);
    exit;
}

echo json_encode(['error' => 'Unknown action']);
