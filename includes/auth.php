<?php
// ═══ AUTH HELPERS ═══

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

function currentUser(): array {
    return $_SESSION['user'] ?? [];
}

function hasPermission(string $perm): bool {
    $user = currentUser();
    if (($user['role'] ?? '') === 'admin') return true;
    $perms = json_decode($user['permissions'] ?? '{}', true);
    return !empty($perms[$perm]);
}

function logActivity(string $action, string $entityType = '', int $entityId = 0, string $details = ''): void {
    if (!isLoggedIn()) return;
    try {
        require_once __DIR__ . '/db.php';
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO activity_log (user_id, action, entity_type, entity_id, details, ip_address) VALUES (?,?,?,?,?,?)");
        $stmt->execute([
            $_SESSION['user_id'],
            $action,
            $entityType,
            $entityId ?: null,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
    } catch (Exception $e) { /* silent */ }
}

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function sanitize(string $val): string {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}
