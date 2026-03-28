<?php
session_start();
// Already logged in? Go to dashboard
if (!empty($_SESSION['staff_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/includes/db.php';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter username and password.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, name, role, password_hash, status FROM staff WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $staff = $stmt->fetch();

        if ($staff && $staff['status'] === 'active' && password_verify($password, $staff['password_hash'])) {
            $_SESSION['staff_id']   = $staff['id'];
            $_SESSION['staff_name'] = $staff['name'];
            $_SESSION['staff_role'] = $staff['role'];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login – OPTMS Tech Study Library</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=DM+Sans:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#f0ede8;--sf:#faf8f5;--sf2:#ede9e3;--sf3:#e4dfd8;
  --br:#d8d3cc;--br2:#c8c2ba;
  --ac:#4a7c6f;--ac2:#5a9186;
  --ro:#c0444f;--tx:#2c2825;--tx2:#5a534c;--tx3:#8a8078;
  --fd:'DM Serif Display',serif;--fb:'DM Sans',sans-serif;--fm:'JetBrains Mono',monospace;
  --r:12px;--r2:8px;
  --sh:0 2px 16px rgba(60,50,40,.10);--sh2:0 8px 32px rgba(60,50,40,.18);
}
*{margin:0;padding:0;box-sizing:border-box}
body{
  font-family:var(--fb);font-size:14px;
  background:var(--bg);color:var(--tx);
  min-height:100vh;display:flex;align-items:center;justify-content:center;
  background-image:
    radial-gradient(circle at 20% 20%, rgba(74,124,111,.07) 0%, transparent 60%),
    radial-gradient(circle at 80% 80%, rgba(196,125,43,.06) 0%, transparent 60%);
}
.login-wrap{
  width:100%;max-width:400px;padding:16px;
}
.login-card{
  background:var(--sf);
  border:1px solid var(--br);
  border-radius:16px;
  box-shadow:var(--sh2);
  overflow:hidden;
}
.login-head{
  padding:28px 28px 22px;
  text-align:center;
  border-bottom:1px solid var(--br);
  background:linear-gradient(135deg, rgba(74,124,111,.04), rgba(196,125,43,.03));
}
.logo-ic{
  width:52px;height:52px;
  background:linear-gradient(135deg,var(--ac),#7c5cbf);
  border-radius:14px;
  display:flex;align-items:center;justify-content:center;
  font-size:22px;margin:0 auto 14px;
  box-shadow:0 4px 14px rgba(74,124,111,.3);
}
.login-title{
  font-family:var(--fd);font-size:22px;color:var(--tx);margin-bottom:4px;
}
.login-sub{
  font-size:11px;color:var(--tx3);font-family:var(--fm);
  letter-spacing:1.5px;text-transform:uppercase;
}
.login-body{padding:26px 28px 28px;}
.error-box{
  background:rgba(192,68,79,.08);
  border:1px solid rgba(192,68,79,.25);
  border-radius:var(--r2);
  padding:10px 12px;
  color:var(--ro);
  font-size:12.5px;
  margin-bottom:18px;
  display:flex;align-items:center;gap:7px;
}
.fgi{display:flex;flex-direction:column;gap:5px;margin-bottom:15px;}
label{font-size:11px;font-weight:600;color:var(--tx2);letter-spacing:.3px;}
input{
  padding:9px 12px;
  border:1px solid var(--br2);
  border-radius:var(--r2);
  background:var(--sf2);
  color:var(--tx);
  font-size:13px;
  font-family:var(--fb);
  outline:none;
  transition:border-color .2s, box-shadow .2s;
  width:100%;
}
input:focus{border-color:var(--ac);box-shadow:0 0 0 3px rgba(74,124,111,.12);}
input::placeholder{color:var(--tx3);}
.pw-wrap{position:relative;}
.pw-wrap input{padding-right:38px;}
.pw-toggle{
  position:absolute;right:10px;top:50%;transform:translateY(-50%);
  background:none;border:none;cursor:pointer;
  font-size:14px;color:var(--tx3);padding:2px;
  transition:color .2s;
}
.pw-toggle:hover{color:var(--tx2);}
.btn-login{
  width:100%;padding:10px;
  background:linear-gradient(135deg,var(--ac),var(--ac2));
  color:#fff;border:none;
  border-radius:var(--r2);
  font-size:13.5px;font-weight:600;
  font-family:var(--fb);
  cursor:pointer;
  transition:all .2s;
  margin-top:6px;
  box-shadow:0 2px 10px rgba(74,124,111,.25);
}
.btn-login:hover{transform:translateY(-1px);box-shadow:0 4px 16px rgba(74,124,111,.35);}
.btn-login:active{transform:translateY(0);}
.login-foot{
  text-align:center;margin-top:20px;
  font-size:11px;color:var(--tx3);font-family:var(--fm);
}
</style>
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-head">
      <div class="logo-ic">📚</div>
      <div class="login-title">OPTMS Library</div>
      <div class="login-sub">Staff Portal · ERP v6</div>
    </div>
    <div class="login-body">
      <?php if ($error): ?>
        <div class="error-box">⚠️ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <form method="POST" autocomplete="on">
        <div class="fgi">
          <label for="username">Username</label>
          <input type="text" id="username" name="username"
                 placeholder="Enter your username"
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                 autocomplete="username" required>
        </div>
        <div class="fgi">
          <label for="password">Password</label>
          <div class="pw-wrap">
            <input type="password" id="password" name="password"
                   placeholder="Enter your password"
                   autocomplete="current-password" required>
            <button type="button" class="pw-toggle" onclick="togglePw()" title="Show/hide password">👁</button>
          </div>
        </div>
        <button type="submit" class="btn-login">Sign In →</button>
      </form>
    </div>
  </div>
  <div class="login-foot">OPTMS Tech Study Library · Madhepura, Bihar</div>
</div>
<script>
function togglePw() {
  const inp = document.getElementById('password');
  inp.type = inp.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>
