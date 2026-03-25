<?php
require_once 'includes/db.php';
if (isset($_SESSION['user_id'])) {
    header("Location: /pages/dashboard.php");
    exit;
}

if ($_POST) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password, name FROM admins WHERE email = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_name'] = $row['name'];
            header("Location: /pages/dashboard.php");
            exit;
        }
    }
    $error = "Invalid credentials";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - OPTMS ERP v6</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { background: #f0ede8; font-family: 'DM Sans', sans-serif; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; }
        .login-box { background:white; padding:40px; border-radius:16px; box-shadow:0 10px 40px rgba(0,0,0,0.1); width:380px; }
        h1 { font-family: 'DM Serif Display', serif; text-align:center; color:#4a7c6f; }
        input { width:100%; padding:12px; margin:10px 0; border:1px solid #d8d3cc; border-radius:8px; font-size:15px; }
        button { width:100%; padding:14px; background:#4a7c6f; color:white; border:none; border-radius:8px; font-size:16px; cursor:pointer; }
    </style>
</head>
<body>
<div class="login-box">
    <h1>📚 OPTMS Tech</h1>
    <p style="text-align:center;color:#666">ERP v6.0</p>
    <?php if(isset($error)) echo "<p style='color:red;text-align:center'>$error</p>"; ?>
    <form method="post">
        <input type="text" name="username" placeholder="Email" value="sumati@domain.com" required>
        <input type="password" name="password" placeholder="Password" value="123@Sumati" required>
        <button type="submit">Login</button>
    </form>
    <p style="text-align:center;margin-top:20px;font-size:13px;color:#888">Default: sumati@domain.com / 123@Sumati</p>
</div>
</body>
</html>