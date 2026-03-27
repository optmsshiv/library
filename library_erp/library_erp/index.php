<?php
require_once __DIR__ . '/includes/auth.php';
if (isLoggedIn()) {
    header('Location: /pages/dashboard.php');
} else {
    header('Location: /login.php');
}
exit;
