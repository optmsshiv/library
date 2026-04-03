<?php
// Prevent caching of this page
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

session_start();

// Clear all session variables
session_unset();

// Destroy the session
session_destroy();

// Clear session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', array(
        'expires'  => time() - 42000,
        'path'     => $params["path"],
        'domain'   => $params["domain"],
        'secure'   => $params["secure"],
        'httponly' => $params["httponly"],
        'samesite' => $params["samesite"]
    ));
}

// Clear remember me cookie
setcookie('optms_remember', '', [
    'expires'  => time() - 42000,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Get the protocol
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$redirect_url = $protocol . '://' . $host . '/login.php';

header('Location: ' . $redirect_url);
exit;
