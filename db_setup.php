<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

$error = "";
$info = "";
$step = 1;

// 🔐 INSTALL LOCK
$lockFile = __DIR__ . '/install.lock';
$installed = file_exists($lockFile);

// 🔑 INSTALL KEY
$INSTALL_KEY = "OPTMS@2026";

if (!isset($_GET['key']) || $_GET['key'] !== $INSTALL_KEY) {
    die("⛔ Unauthorized Access");
}

// 🚫 BLOCK IF ALREADY INSTALLED
if ($installed) {
    die("⚠️ ERP Already Installed. Delete install.lock to reinstall.");
}

// STEP 1: TEST DB
if (isset($_POST['test_db'])) {
    $host = trim($_POST['host']);
    $user = trim($_POST['user']);
    $pass = $_POST['pass'];
    $dbname = trim($_POST['dbname']);

    $conn = @new mysqli($host, $user, $pass, $dbname);

    if ($conn->connect_error) {
        $error = "❌ Connection failed: " . $conn->connect_error;
    } else {
        $_SESSION['db'] = $_POST;
        $step = 2;
    }
}

// STEP 2: CREATE ADMIN + SETUP
if (isset($_POST['create_admin'])) {

    if (!isset($_SESSION['db'])) {
        $error = "Session expired.";
        $step = 1;
    } else {

        $db = $_SESSION['db'];
        $conn = new mysqli($db['host'], $db['user'], $db['pass'], $db['dbname']);

        if ($conn->connect_error) {
            $error = "Database connection lost.";
            $step = 1;
        } else {

            // ✅ CHECK TABLE
            $tableCheck = $conn->query("SHOW TABLES LIKE 'admins'");

            if ($tableCheck && $tableCheck->num_rows == 0) {

                $sqlPath = __DIR__ . '/sql/schema.sql';

                if (file_exists($sqlPath)) {
                    $sql = file_get_contents($sqlPath);

                    if ($sql && trim($sql) !== '') {
                        if (!$conn->multi_query($sql)) {
                            die("❌ Schema error: " . $conn->error);
                        }
                        while ($conn->more_results() && $conn->next_result()) {}
                    }
                }
            } else {
                $info = "✅ Existing DB detected — schema skipped";
            }

            // 👤 ADMIN
            $name = trim($_POST['admin_name']);
            $email = trim($_POST['admin_email']);
            $password = $_POST['admin_password'];

            if (!$name || !$email || !$password) {
                $error = "All admin fields required.";
                $step = 2;
            } elseif (strlen($password) < 6) {
                $error = "Password must be at least 6 characters.";
                $step = 2;
            } else {

                $hashed = password_hash($password, PASSWORD_BCRYPT);

                $check = $conn->prepare("SELECT id FROM admins WHERE email=?");
                $check->bind_param("s", $email);
                $check->execute();
                $check->store_result();

                if ($check->num_rows > 0) {
                    $error = "Admin already exists.";
                    $step = 2;
                } else {

                    $stmt = $conn->prepare("INSERT INTO admins (name,email,password) VALUES (?,?,?)");
                    $stmt->bind_param("sss", $name, $email, $hashed);

                    if (!$stmt->execute()) {
                        die("❌ Insert failed: " . $stmt->error);
                    }

                    // ⚙️ CONFIG FILE
                    $config = "<?php
define('DB_HOST','{$db['host']}');
define('DB_USER','{$db['user']}');
define('DB_PASS','{$db['pass']}');
define('DB_NAME','{$db['dbname']}');
?>";

                    if (!file_put_contents('config.php', $config)) {
                        $error = "Failed to write config.php";
                        $step = 2;
                    } else {
                        file_put_contents($lockFile, 'installed');
                        session_destroy();
                        $step = 3;
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>OPTMS ERP Installer</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
body{
    background: linear-gradient(135deg,#667eea,#764ba2);
    height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    font-family:sans-serif;
}
.card{
    background:#fff;
    padding:40px;
    border-radius:15px;
    width:100%;
    max-width:420px;
    box-shadow:0 20px 60px rgba(0,0,0,0.2);
}
h1{text-align:center;}
input,button{
    width:100%;
    padding:12px;
    margin-bottom:12px;
    border-radius:8px;
    border:1px solid #ccc;
}
button{
    background:#667eea;
    color:#fff;
    border:none;
    cursor:pointer;
}
.msg{padding:10px;border-radius:8px;margin-bottom:10px;}
.error{background:#ffe0e0;color:red;}
.success{background:#e0ffe5;color:green;}
.success-box{text-align:center;}
</style>
</head>

<body>

<div class="card">
<h1>🚀 OPTMS ERP</h1>

<?php if ($error): ?>
<div class="msg error"><?= $error ?></div>
<?php endif; ?>

<?php if (!empty($info)): ?>
<div class="msg success"><?= $info ?></div>
<?php endif; ?>

<!-- STEP 1 -->
<?php if ($step == 1): ?>
<form method="post">
<input type="text" name="host" value="localhost" required>
<input type="text" name="user" placeholder="DB User" required>
<input type="password" name="pass" placeholder="Password">
<input type="text" name="dbname" placeholder="Database Name" required>
<button name="test_db">Test Connection</button>
</form>
<?php endif; ?>

<!-- STEP 2 -->
<?php if ($step == 2): ?>
<div class="msg success">✅ Database Connected</div>
<form method="post">
<input type="text" name="admin_name" placeholder="Admin Name" required>
<input type="email" name="admin_email" placeholder="Admin Email" required>
<input type="password" name="admin_password" placeholder="Password" required>
<button name="create_admin">Finish Setup</button>
</form>
<?php endif; ?>

<!-- STEP 3 -->
<?php if ($step == 3): ?>
<div class="success-box">
    <h2>🎉 Installation Complete</h2>
    <p>Redirecting to login in 3 seconds...</p>
    <a href="/login.php">Go to Login</a>
</div>

<script>
setTimeout(() => {
    window.location.href = "login.php?installed=success";
}, 3000);
</script>
<?php endif; ?>

</div>

</body>
</html>