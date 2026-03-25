<?php
session_start();
if (!file_exists('../config.php')) {
    die("Please run <a href='../db_setup.php'>DB Setup</a> first.");
}
require_once '../config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?>