<?php
session_start();
$token = $_GET['token'] ?? '';
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/includes/db.php';
    $token    = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM password_resets WHERE token=? AND used=0 AND expires_at > NOW() LIMIT 1");
        $stmt->execute([$token]);
        $reset = $stmt->fetch();
        if (!$reset) {
            $error = 'This reset link has expired or already been used. Please request a new one.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $db->prepare("UPDATE staff SET password_hash=? WHERE id=?")->execute([$hash, $reset['staff_id']]);
            $db->prepare("UPDATE password_resets SET used=1 WHERE token=?")->execute([$token]);
            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password – OPTMS Tech Library</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{--ac:#4a7c6f;--ro:#c0444f;--tx:#2c2825;--tx2:#5a534c;--tx3:#8a8078;--bg:#f0ede8;--sf:#faf8f5;--br:#d8d3cc;}
body{font-family:'DM Sans',sans-serif;background:var(--bg);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:16px}
.card{background:var(--sf);border:1px solid var(--br);border-radius:16px;box-shadow:0 8px 32px rgba(60,50,40,.18);width:100%;max-width:400px;overflow:hidden}
.head{padding:28px;text-align:center;border-bottom:1px solid var(--br)}
.ic{width:52px;height:52px;background:linear-gradient(135deg,var(--ac),#7c5cbf);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:22px;margin:0 auto 14px}
h1{font-family:'DM Serif Display',serif;font-size:22px;color:var(--tx);margin-bottom:4px}
.sub{font-size:12px;color:var(--tx3)}
.body{padding:26px 28px 28px}
.fgi{display:flex;flex-direction:column;gap:5px;margin-bottom:14px}
label{font-size:11px;font-weight:600;color:var(--tx2)}
input{padding:9px 12px;border:1px solid var(--br);border-radius:8px;background:#ede9e3;color:var(--tx);font-size:13px;font-family:inherit;outline:none;transition:border-color .2s;width:100%}
input:focus{border-color:var(--ac);box-shadow:0 0 0 3px rgba(74,124,111,.12)}
.btn{width:100%;padding:11px;background:var(--ac);color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;font-family:inherit;margin-top:6px}
.btn:hover{background:#5a9186}
.err{background:rgba(192,68,79,.08);border:1px solid rgba(192,68,79,.25);border-radius:8px;padding:10px 12px;color:var(--ro);font-size:12.5px;margin-bottom:16px}
.ok{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:16px;color:#166534;text-align:center}
.back{display:block;text-align:center;margin-top:14px;font-size:12px;color:var(--ac);text-decoration:none}
</style>
</head>
<body>
<div class="card">
  <div class="head">
    <div class="ic">🔑</div>
    <h1>Reset Password</h1>
    <div class="sub">Enter your new password below</div>
  </div>
  <div class="body">
    <?php if ($success): ?>
      <div class="ok">✅ <strong>Password updated successfully!</strong><br>You can now log in with your new password.</div>
      <a href="/library/login" class="back">← Back to Login</a>
    <?php elseif (!$token): ?>
      <div class="err">⚠ Invalid reset link. Please request a new one.</div>
      <a href="/library/login" class="back">← Back to Login</a>
    <?php else: ?>
      <?php if ($error): ?><div class="err">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>
      <form method="POST">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <div class="fgi">
          <label>New Password</label>
          <input type="password" name="password" placeholder="Min. 6 characters" required minlength="6" autofocus>
        </div>
        <div class="fgi">
          <label>Confirm Password</label>
          <input type="password" name="confirm" placeholder="Repeat new password" required>
        </div>
        <button class="btn" type="submit">🔒 Set New Password</button>
      </form>
      <a href="/library/login" class="back">← Back to Login</a>
    <?php endif; ?>
  </div>
</div>
</body>
</html>