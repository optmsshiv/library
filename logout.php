<?php
ob_start();
session_start();
session_unset();
session_destroy();

// Clear session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
// Also clear at root path (catches cookies set at /)
setcookie(session_name(), '', time() - 42000, '/');

// Tell Chrome to wipe its SSL session cache + cookies + cache
// This is the key fix for Chrome ERR_FAILED after logout
header('Clear-Site-Data: "cache", "cookies", "storage"');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Connection: close');

// Use full absolute URL — prevents any ambiguity in Chrome's redirect handling
header('Location: https://library.optms.co.in/login.php');
ob_end_flush();
exit;