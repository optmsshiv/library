<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: /pages/dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 'active' LIMIT 1");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user']    = [
                    'id'          => $user['id'],
                    'name'        => $user['name'],
                    'username'    => $user['username'],
                    'email'       => $user['email'],
                    'role'        => $user['role'],
                    'permissions' => $user['permissions'],
                ];

                // Update last login
                $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
                logActivity('login', 'user', $user['id'], 'Login successful');

                header('Location: /pages/dashboard.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (PDOException $e) {
            $error = 'Database error. Please check your setup.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — OPTMS Tech ERP</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=DM+Sans:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/login.css">
</head>
<body>
<div class="login-bg">
  <div class="login-card">
    <div class="login-brand">
      <div class="brand-icon">📚</div>
      <h1>OPTMS Tech ERP</h1>
      <p>Library Management System v6.0</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" class="login-form">
      <div class="form-group">
        <label>Username or Email</label>
        <div class="input-wrap">
          <span class="input-icon">👤</span>
          <input type="text" name="username" placeholder="admin" required autocomplete="username"
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        </div>
      </div>
      <div class="form-group">
        <label>Password</label>
        <div class="input-wrap">
          <span class="input-icon">🔒</span>
          <input type="password" name="password" id="pwdField" placeholder="••••••••" required autocomplete="current-password">
          <button type="button" class="pw-toggle" onclick="togglePw()">👁</button>
        </div>
      </div>
      <div class="form-row">
        <label class="remember-label">
          <input type="checkbox" name="remember"> Remember me
        </label>
        <a href="#" class="forgot-link">Forgot password?</a>
      </div>
      <button type="submit" class="login-btn">Sign In →</button>
    </form>

    <div class="login-footer">
      <span>Default credentials: <code>admin</code> / <code>admin123</code></span>
    </div>
  </div>

  <div class="bg-decoration">
    <div class="deco-circle c1"></div>
    <div class="deco-circle c2"></div>
    <div class="deco-circle c3"></div>
  </div>
</div>
<script>
function togglePw() {
  const f = document.getElementById('pwdField');
  f.type = f.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>
