<?php
// ── Database Configuration ──
// Update these values to match your server
define('DB_HOST', 'localhost');
define('DB_NAME', 'edrppymy_udaanlibrary');
define('DB_USER', 'edrppymy_udaanlibrary');       // Change to your MySQL username
define('DB_PASS', '1234@Libraryerp');           // Change to your MySQL password
define('DB_CHARSET', 'utf8mb4');

//function getInput(): array {
//    $raw = file_get_contents('php://input');
//    if ($raw) {
//        $decoded = json_decode($raw, true);
//        if (is_array($decoded)) return $decoded;
//    }
//    return $_POST ?: [];
//}
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'DB connection failed: ' . $e->getMessage()]);
            exit;
        }
    }
    return $pdo;
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function jsonError($msg, $code = 400) {
    jsonResponse(['error' => $msg], $code);
}

function getInput() {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}

function generateId($prefix, $table, $col = 'id') {
    $db = getDB();
    $count = $db->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
    return $prefix . '-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
}

// CORS headers for dev
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }
