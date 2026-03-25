<?php
if ($_POST) {
    $host = $_POST['host'];
    $user = $_POST['user'];
    $pass = $_POST['pass'];
    $dbname = $_POST['dbname'];

    $conn = new mysqli($host, $user, $pass);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $conn->query("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->select_db($dbname);

    // Run schema
    $sql = file_get_contents('sql/schema.sql');
    $conn->multi_query($sql);

    // Create config.php
    $config = "<?php
define('DB_HOST', '$host');
define('DB_USER', '$user');
define('DB_PASS', '$pass');
define('DB_NAME', '$dbname');
?>";
    file_put_contents('config.php', $config);

    echo "<h2 style='color:green'>✅ Database Setup Successful!</h2>";
    echo "<p><a href='login.php'>Go to Login →</a></p>";
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>OPTMS ERP - Database Setup</title>
    <style>body{font-family:Arial;background:#f0ede8;padding:50px}</style>
</head>
<body>
    <h1>OPTMS Tech ERP v6 - Database Setup</h1>
    <form method="post">
        <p>Host: <input type="text" name="host" value="localhost" required></p>
        <p>Username: <input type="text" name="user" value="root" required></p>
        <p>Password: <input type="password" name="pass"></p>
        <p>Database Name: <input type="text" name="dbname" value="optms_erp" required></p>
        <button type="submit" style="padding:10px 20px;font-size:16px">Install Database</button>
    </form>
</body>
</html>