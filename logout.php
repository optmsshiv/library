<?php
session_start();
session_unset();
session_destroy();
header('Location: library/login.php');
exit;
