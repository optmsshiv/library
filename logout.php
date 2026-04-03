<?php
ob_start();
session_start();
session_unset();
session_destroy();

// Clear the session cookie immediately
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

// Kill any other stale cookies on this domain
setcookie(session_name(), '', time() - 42000, '/');

// Prevent browser from caching this response
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');

// Small delay before redirect gives cookie deletion time to propagate
header('Refresh: 0; url=login.php');
ob_end_flush();
exit;