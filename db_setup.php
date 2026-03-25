<?php
$error = "";
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim($_POST['host']);
    $user = trim($_POST['user']);
    $pass = $_POST['pass'];
    $dbname = trim($_POST['dbname']);

    if (!$host || !$user || !$dbname) {
        $error = "All fields except password are required.";
    } else {
        $conn = @new mysqli($host, $user, $pass);

        if ($conn->connect_error) {
            $error = "Connection failed: " . $conn->connect_error;
        } else {

            // Create DB
            if (!$conn->query("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
                $error = "Database creation failed: " . $conn->error;
            } else {

                $conn->select_db($dbname);

                // Run schema
                $sql = file_get_contents('sql/schema.sql');

                if (!$sql) {
                    $error = "schema.sql file not found.";
                } else {
                    if ($conn->multi_query($sql)) {

                        // Flush remaining queries
                        while ($conn->more_results() && $conn->next_result()) {}

                        // Create config safely
                        $config = "<?php
define('DB_HOST', '" . addslashes($host) . "');
define('DB_USER', '" . addslashes($user) . "');
define('DB_PASS', '" . addslashes($pass) . "');
define('DB_NAME', '" . addslashes($dbname) . "');
?>";

                        if (file_put_contents('config.php', $config)) {
                            $success = true;
                        } else {
                            $error = "Failed to write config.php (check permissions).";
                        }

                    } else {
                        $error = "Schema execution failed: " . $conn->error;
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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea, #764ba2);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card {
            background: #fff;
            padding: 40px;
            width: 100%;
            max-width: 450px;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from {opacity:0; transform:translateY(20px);}
            to {opacity:1; transform:translateY(0);}
        }

        h1 {
            text-align: center;
            margin-bottom: 10px;
            color: #333;
        }

        p.sub {
            text-align: center;
            color: #777;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        input {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #ddd;
            outline: none;
            transition: 0.3s;
        }

        input:focus {
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102,126,234,0.5);
        }

        button {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            background: #5a67d8;
        }

        .msg {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 8px;
            font-size: 14px;
        }

        .error {
            background: #ffe0e0;
            color: #d8000c;
        }

        .success {
            background: #e0ffe5;
            color: #0a7d2c;
            text-align: center;
        }

        .footer {
            text-align: center;
            margin-top: 15px;
            font-size: 13px;
            color: #aaa;
        }

        a.btn {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 20px;
            background: #28a745;
            color: #fff;
            border-radius: 6px;
            text-decoration: none;
        }

    </style>
</head>

<body>

<div class="card">

    <h1>🚀 OPTMS ERP</h1>
    <p class="sub">Database Setup Wizard</p>

    <?php if ($error): ?>
        <div class="msg error"><?= $error ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="msg success">
            ✅ Installation Successful!<br>
            <a class="btn" href="login.php">Go to Login →</a>
        </div>
    <?php else: ?>

    <form method="post">

        <div class="form-group">
            <input type="text" name="host" placeholder="Database Host" value="localhost" required>
        </div>

        <div class="form-group">
            <input type="text" name="user" placeholder="Database Username" value="root" required>
        </div>

        <div class="form-group">
            <input type="password" name="pass" placeholder="Database Password">
        </div>

        <div class="form-group">
            <input type="text" name="dbname" placeholder="Database Name" value="optms_erp" required>
        </div>

        <button type="submit">Install Database</button>

    </form>

    <?php endif; ?>

    <div class="footer">
        OPTMS Tech © <?= date('Y') ?>
    </div>

</div>

</body>
</html>