<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

$error = "";
$step = 1;

// 🔐 INSTALL LOCK
$lockFile = __DIR__ . '/install.lock';
$installed = file_exists($lockFile);

// Allow only with key (security)
$INSTALL_KEY = "OPTMS@2026";

if (!isset($_GET['key']) || $_GET['key'] !== $INSTALL_KEY) {
    die("⛔ Unauthorized Access");
}

// STEP 1: Test DB
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

// STEP 2: Setup
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

            // ✅ CHECK TABLE EXISTENCE
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

                    } else {
                        echo "<div class='msg success'>⚠️ schema.sql empty — skipped</div>";
                    }

                } else {
                    echo "<div class='msg success'>⚠️ schema.sql not found — skipped</div>";
                }

            } else {
                echo "<div class='msg success'>✅ Existing DB detected — schema skipped</div>";
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
                if (!$check) die("❌ Prepare failed: " . $conn->error);

                $check->bind_param("s", $email);
                $check->execute();
                $check->store_result();

                if ($check->num_rows > 0) {
                    $error = "Admin already exists.";
                    $step = 2;
                } else {

                    $stmt = $conn->prepare("INSERT INTO admins (name,email,password) VALUES (?,?,?)");
                    if (!$stmt) die("❌ Insert prepare failed: " . $conn->error);

                    $stmt->bind_param("sss", $name, $email, $hashed);

                    if (!$stmt->execute()) {
                        die("❌ Insert failed: " . $stmt->error);
                    }

                    // ⚙️ CONFIG
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
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
body{background:linear-gradient(135deg,#667eea,#764ba2);height:100vh;display:flex;align-items:center;justify-content:center;}
.card{background:#fff;padding:40px;width:100%;max-width:450px;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,0.2);}
h1{text-align:center;margin-bottom:10px;}
.sub{text-align:center;color:#777;margin-bottom:20px;}
.form-group{margin-bottom:15px;}
input{width:100%;padding:12px;border-radius:8px;border:1px solid #ddd;}
button{width:100%;padding:12px;background:#667eea;color:#fff;border:none;border-radius:8px;cursor:pointer;}
button:hover{background:#5a67d8;}
.msg{padding:10px;margin-bottom:15px;border-radius:8px;}
.error{background:#ffe0e0;color:#d8000c;}
.success{background:#e0ffe5;color:#0a7d2c;text-align:center;}
.glow{box-shadow:0 0 15px #28a745;}
a.btn{display:inline-block;margin-top:10px;padding:10px 20px;background:#28a745;color:#fff;border-radius:6px;text-decoration:none;}
.footer{text-align:center;margin-top:15px;font-size:13px;color:#aaa;}
</style>
</head>

<body>

<div class="card">
<h1>🚀 OPTMS ERP</h1>
<p class="sub">Secure Installer</p>

<?php if ($installed): ?>
<div class="msg success">⚠️ Already Installed</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="msg error"><?= $error ?></div>
<?php endif; ?>

<!-- STEP 1 -->
<?php if ($step == 1): ?>
<form method="post">
<div class="form-group"><input type="text" name="host" value="localhost" required></div>
<div class="form-group"><input type="text" name="user" placeholder="DB User" required></div>
<div class="form-group"><input type="password" name="pass" placeholder="Password"></div>
<div class="form-group"><input type="text" name="dbname" placeholder="Database Name" required></div>
<button name="test_db">🔌 Test Connection</button>
</form>
<?php endif; ?>

<!-- STEP 2 -->
<?php if ($step == 2): ?>
<div class="msg success glow">✅ Database Connected</div>

<form method="post">
<h3 style="margin-bottom:10px;">👤 Create Admin</h3>

<div class="form-group"><input type="text" name="admin_name" required></div>
<div class="form-group"><input type="email" name="admin_email" required></div>
<div class="form-group"><input type="password" name="admin_password" required></div>

<button name="create_admin">🚀 Finish Setup</button>
</form>
<?php endif; ?>

<!-- STEP 3 -->
<?php if ($step == 3): ?>
<div class="msg success">
🎉 Installation Complete!<br>
<a class="btn" href="login.php">Go to Login →</a>
</div>
<?php endif; ?>

<div class="footer">OPTMS Tech © <?= date('Y') ?></div>
</div>

</body>
</html>