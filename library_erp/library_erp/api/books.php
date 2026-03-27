<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
requireLogin();

header('Content-Type: application/json');
$db     = getDB();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $bk = $db->prepare("SELECT title FROM books WHERE id=?");
    $bk->execute([$id]);
    $bk = $bk->fetch();
    if ($bk) {
        $db->prepare("DELETE FROM books WHERE id=?")->execute([$id]);
        logActivity("Deleted book: ".$bk['title'], 'book', $id);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Book not found']);
    }
    exit;
}

if ($action === 'search') {
    $q = '%'.trim($_GET['q'] ?? '').'%';
    $rows = $db->prepare("SELECT id, title, book_code, available_copies FROM books WHERE title LIKE ? OR book_code LIKE ? LIMIT 20");
    $rows->execute([$q, $q]);
    echo json_encode($rows->fetchAll());
    exit;
}

echo json_encode(['error' => 'Unknown action']);
