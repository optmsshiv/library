<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

if (isLoggedIn()) {
    logActivity('logout', 'user', $_SESSION['user_id'], 'User logged out');
}

// Destroy session
session_unset();
session_destroy();

// Clear cookie if set
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

header('Location: /login.php?msg=loggedout');
exit;
