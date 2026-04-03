<?php
session_start();
session_unset();
session_destroy();

// Clear the session cookie immediately so the browser doesn't re-send it
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

// Redirect to login page — use absolute path to avoid any ambiguity
header('Location: https://library.optms.co.in/login.php');
header('Cache-Control: no-store, no-cache, must-revalidate');
exit;