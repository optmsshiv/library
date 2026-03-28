<?php
// ── OPTMS Tech Library ERP — One-click Setup ──
// Visit this file ONCE to create tables and seed data. Delete after use.

define('DB_HOST', 'localhost');
define('DB_NAME', 'edrppymy_udaanlibrary');
define('DB_USER', 'edrppymy_udaanlibrary');    // ← Change this
define('DB_PASS', '1234@Libraryerp');         // ← Change this

try {
    // Create DB first (without selecting it)
    $pdo = new PDO("mysql:host=".DB_HOST.";charset=utf8mb4", DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `".DB_NAME."` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `".DB_NAME."`");

    $sql = file_get_contents(__DIR__ . '/database.sql');
    // Split by semicolons to run statement by statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    $ok = 0; $skip = 0;
    foreach ($statements as $stmt) {
        if (!$stmt || strpos($stmt,'--') === 0) { $skip++; continue; }
        try { $pdo->exec($stmt); $ok++; } catch(Exception $e) { /* ignore duplicate key etc */ $skip++; }
    }
    echo "<h2 style='font-family:sans-serif;color:#3a7d5e'>✅ Setup Complete!</h2>";
    echo "<p style='font-family:sans-serif'>Ran $ok statements, skipped $skip.<br><br>";
    echo "<strong>Next steps:</strong><ol><li>Delete <code>setup.php</code> from your server</li><li><a href='index.php'>Go to Dashboard →</a></li></ol></p>";
} catch(Exception $e) {
    echo "<h2 style='color:red;font-family:sans-serif'>❌ Setup Failed</h2><pre>".$e->getMessage()."</pre>";
    echo "<p style='font-family:sans-serif'>Check DB credentials in <code>setup.php</code> and <code>includes/db.php</code></p>";
}
