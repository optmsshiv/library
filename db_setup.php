<?php
// ══════════════════════════════════════════════════════════════
// OPTMS Tech ERP v6 — Admin Setup
// Run this ONCE to create the first admin account.
// Delete or restrict this file after setup is complete.
// ══════════════════════════════════════════════════════════════

session_start();

// ── DB CONFIG — change these to match your server ──────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'edrppymy_udaanlibrary');   // ← change this
define('DB_USER', 'edrppymy_udaanlibrary');         // ← change this
define('DB_PASS', '1234@Libraryerp');     // ← change this
define('DB_PORT', 3306);
// ───────────────────────────────────────────────────────────────

$step        = 'check';   // check | form | done | error
$dbOk        = false;
$adminExists = false;
$dbError     = '';
$formError   = '';
$successMsg  = '';

// ── 1. TEST DB CONNECTION ───────────────────────────────────────
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT            => 5,
        ]
    );
    $dbOk = true;
} catch (PDOException $e) {
    $dbError = htmlspecialchars($e->getMessage());
    $step    = 'error';
}

// ── 2. CHECK IF ADMIN ALREADY EXISTS ──────────────────────────
if ($dbOk) {
    $chk = $pdo->query("SELECT COUNT(*) AS cnt FROM staff WHERE role='admin' AND status='active'")->fetch();
    $adminExists = ($chk['cnt'] > 0);
    $step = $adminExists ? 'done' : 'form';
}

// ── 3. HANDLE FORM SUBMISSION ──────────────────────────────────
if ($dbOk && !$adminExists && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $phone    = trim($_POST['phone']    ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password']      ?? '';
    $confirm  = $_POST['confirm']       ?? '';

    // Validation
    if (!$name || !$username || !$password) {
        $formError = 'Name, Username and Password are required.';
    } elseif (strlen($username) < 4) {
        $formError = 'Username must be at least 4 characters.';
    } elseif (strlen($password) < 8) {
        $formError = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $formError = 'Passwords do not match.';
    } elseif ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $formError = 'Enter a valid email address.';
    } else {
        // Check username uniqueness
        $uq = $pdo->prepare("SELECT id FROM staff WHERE username = ?");
        $uq->execute([$username]);
        if ($uq->fetch()) {
            $formError = 'That username is already taken.';
        }
    }

    if (!$formError) {
        // Generate admin ID: ADM-YYYYMMDD-XXXX
        $adminId   = 'ADM-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 4));
        $passHash  = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        $ins = $pdo->prepare("
            INSERT INTO staff
                (id, name, role, email, phone, username, password_hash,
                 perm_students, perm_fees, perm_books, perm_expenses,
                 perm_reports, perm_staff, perm_settings, status)
            VALUES
                (:id, :name, 'admin', :email, :phone, :username, :password_hash,
                 1, 1, 1, 1, 1, 1, 1, 'active')
        ");
        $ins->execute([
            ':id'            => $adminId,
            ':name'          => $name,
            ':email'         => $email,
            ':phone'         => $phone,
            ':username'      => $username,
            ':password_hash' => $passHash,
        ]);

        $successMsg  = "Admin account created! ID: <strong>$adminId</strong>";
        $step        = 'done';
        $adminExists = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Setup — OPTMS Tech ERP v6</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=DM+Serif+Display&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#f0f4fb;--sf:#fff;--br:#e2e7f0;--br2:#ccd3e0;
  --ac:#3d6ff0;--ac2:#2d5de0;--vi:#7c3aed;
  --em:#16a34a;--ro:#dc2626;--gd:#d97706;
  --tx:#0f172a;--tx2:#334155;--tx3:#64748b;
  --fb:'Inter',sans-serif;--fd:'DM Serif Display',serif;--fm:'JetBrains Mono',monospace;
  --r:14px;--r2:9px;
  --sh:0 1px 4px rgba(15,23,42,.07),0 6px 20px rgba(15,23,42,.07);
}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:var(--fb);background:var(--bg);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}

.card{background:var(--sf);border:1px solid var(--br);border-radius:var(--r);box-shadow:var(--sh);width:100%;max-width:480px;overflow:hidden}

/* Header */
.card-head{padding:28px 28px 20px;background:linear-gradient(135deg,rgba(61,111,240,.07),rgba(124,58,237,.06));border-bottom:1px solid var(--br)}
.logo-row{display:flex;align-items:center;gap:11px;margin-bottom:14px}
.logo-ic{width:40px;height:40px;background:linear-gradient(135deg,var(--ac),var(--vi));border-radius:11px;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 10px rgba(61,111,240,.3)}
.logo-ic svg{fill:#fff}
.logo-tx{font-family:var(--fd);font-size:19px;color:var(--tx)}
.logo-sb{font-size:9px;color:var(--tx3);font-family:var(--fm);letter-spacing:1.5px;text-transform:uppercase}
.card-title{font-family:var(--fd);font-size:22px;color:var(--tx);margin-bottom:4px}
.card-sub{font-size:12px;color:var(--tx3)}

/* Steps indicator */
.steps{display:flex;align-items:center;gap:0;margin-top:16px}
.step-dot{width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;font-family:var(--fm);transition:all .2s}
.step-dot.done{background:var(--em);color:#fff}
.step-dot.active{background:var(--ac);color:#fff;box-shadow:0 0 0 3px rgba(61,111,240,.2)}
.step-dot.pending{background:var(--br);color:var(--tx3)}
.step-line{flex:1;height:2px;background:var(--br);margin:0 6px}
.step-line.done{background:var(--em)}
.step-lbl{font-size:9px;color:var(--tx3);margin-top:3px;font-family:var(--fm);text-align:center}
.step-wrap{display:flex;flex-direction:column;align-items:center}

/* Body */
.card-body{padding:24px 28px}

/* Alerts */
.alert{padding:12px 14px;border-radius:var(--r2);font-size:12.5px;margin-bottom:16px;display:flex;align-items:flex-start;gap:9px;line-height:1.5}
.alert-ok{background:#f0fdf4;border:1px solid #bbf7d0;color:#166534}
.alert-er{background:#fff1f2;border:1px solid #fecdd3;color:#9f1239}
.alert-wn{background:#fffbeb;border:1px solid #fde68a;color:#92400e}
.alert-ic{font-size:16px;margin-top:1px;flex-shrink:0}

/* DB status */
.db-status{display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:var(--r2);border:1px solid var(--br);background:var(--bg);margin-bottom:20px}
.db-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0}
.db-dot.ok{background:var(--em);box-shadow:0 0 0 3px rgba(22,163,74,.15)}
.db-dot.fail{background:var(--ro);box-shadow:0 0 0 3px rgba(220,38,38,.15)}
.db-info{flex:1}
.db-title{font-size:12px;font-weight:600;color:var(--tx)}
.db-detail{font-size:10.5px;color:var(--tx3);font-family:var(--fm)}

/* Form */
.fg{margin-bottom:14px}
label{display:block;font-size:11px;font-weight:600;color:var(--tx2);margin-bottom:5px;letter-spacing:.3px}
label span{color:var(--ro)}
input[type=text],input[type=email],input[type=tel],input[type=password]{
  width:100%;padding:9px 12px;border:1px solid var(--br);border-radius:var(--r2);
  font-family:var(--fb);font-size:13px;color:var(--tx);background:var(--sf);
  outline:none;transition:border-color .18s,box-shadow .18s
}
input:focus{border-color:var(--ac);box-shadow:0 0 0 3px rgba(61,111,240,.1)}
input.err{border-color:var(--ro)}
.hint{font-size:10px;color:var(--tx3);margin-top:4px}

.row2{display:grid;grid-template-columns:1fr 1fr;gap:12px}

/* Password strength */
.pw-bar{height:4px;border-radius:3px;background:var(--br);margin-top:6px;overflow:hidden}
.pw-fill{height:100%;border-radius:3px;transition:width .3s,background .3s;width:0}

/* Permissions preview */
.perm-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:6px;margin-top:10px}
.perm-chip{display:flex;align-items:center;gap:4px;font-size:10px;font-weight:600;color:var(--em);background:#f0fdf4;border:1px solid #bbf7d0;padding:4px 8px;border-radius:5px;font-family:var(--fm)}

/* Button */
.btn-submit{width:100%;padding:11px;background:var(--ac);color:#fff;border:none;border-radius:var(--r2);font-size:13px;font-weight:600;font-family:var(--fb);cursor:pointer;transition:all .18s;box-shadow:0 2px 8px rgba(61,111,240,.3);margin-top:6px;display:flex;align-items:center;justify-content:center;gap:6px}
.btn-submit:hover{background:var(--ac2);transform:translateY(-1px);box-shadow:0 4px 14px rgba(61,111,240,.4)}
.btn-login{width:100%;padding:11px;background:var(--em);color:#fff;border:none;border-radius:var(--r2);font-size:13px;font-weight:600;font-family:var(--fb);cursor:pointer;transition:all .18s;display:flex;align-items:center;justify-content:center;gap:6px;text-decoration:none}
.btn-login:hover{background:#15803d}

/* Success / Already exists */
.success-ic{width:64px;height:64px;background:linear-gradient(135deg,var(--em),#15803d);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;box-shadow:0 4px 16px rgba(22,163,74,.3)}
.success-ic svg{fill:#fff}
.success-title{font-family:var(--fd);font-size:20px;color:var(--tx);text-align:center;margin-bottom:6px}
.success-sub{font-size:12px;color:var(--tx3);text-align:center;margin-bottom:20px;line-height:1.6}
.id-box{background:var(--bg);border:1px solid var(--br2);border-radius:var(--r2);padding:10px 14px;font-family:var(--fm);font-size:13px;font-weight:600;color:var(--ac);text-align:center;margin-bottom:16px}

/* Warning footer */
.security-note{margin-top:20px;padding:10px 14px;background:#fffbeb;border:1px solid #fde68a;border-radius:var(--r2);font-size:11px;color:#92400e;display:flex;gap:7px}

/* Footer */
.card-foot{padding:12px 28px;border-top:1px solid var(--br);background:var(--bg);font-size:10.5px;color:var(--tx3);font-family:var(--fm);display:flex;justify-content:space-between}
</style>
</head>
<body>
<div class="card">

  <!-- ── HEADER ── -->
  <div class="card-head">
    <div class="logo-row">
      <div class="logo-ic">
        <svg width="20" height="20" viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
      </div>
      <div>
        <div class="logo-tx">OPTMS ERP</div>
        <div class="logo-sb">v6 · Admin Setup</div>
      </div>
    </div>
    <div class="card-title">Initial Setup Wizard</div>
    <div class="card-sub">Create the first administrator account for your ERP system</div>

    <!-- Steps -->
    <div class="steps" style="margin-top:16px">
      <div class="step-wrap">
        <div class="step-dot <?= $dbOk ? 'done' : ($step==='error' ? 'active' : 'pending') ?>">1</div>
        <div class="step-lbl">DB Check</div>
      </div>
      <div class="step-line <?= $dbOk ? 'done' : '' ?>"></div>
      <div class="step-wrap">
        <div class="step-dot <?= ($step==='done' && $successMsg) ? 'done' : ($step==='form' ? 'active' : 'pending') ?>">2</div>
        <div class="step-lbl">Create Admin</div>
      </div>
      <div class="step-line <?= ($step==='done' && $successMsg) ? 'done' : '' ?>"></div>
      <div class="step-wrap">
        <div class="step-dot <?= $step==='done' ? 'done' : 'pending' ?>">3</div>
        <div class="step-lbl">Done</div>
      </div>
    </div>
  </div>

  <div class="card-body">

    <!-- ── DB STATUS ── -->
    <div class="db-status">
      <div class="db-dot <?= $dbOk ? 'ok' : 'fail' ?>"></div>
      <div class="db-info">
        <div class="db-title"><?= $dbOk ? '✓ Database Connected' : '✗ Database Connection Failed' ?></div>
        <div class="db-detail">
          <?php if ($dbOk): ?>
            <?= DB_HOST ?> · <?= DB_NAME ?> · MySQL/MariaDB
          <?php else: ?>
            <?= $dbError ?: 'Could not connect — check DB_HOST, DB_NAME, DB_USER, DB_PASS' ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <?php if ($step === 'error'): ?>
      <!-- ── ERROR STATE ── -->
      <div class="alert alert-er">
        <span class="alert-ic">⚠️</span>
        <div>
          <strong>Cannot connect to the database.</strong><br>
          Open <code>setup_admin.php</code> and update the DB constants at the top of the file, then reload this page.
          <br><br>
          <strong>Error:</strong> <?= $dbError ?>
        </div>
      </div>

    <?php elseif ($step === 'done' && $adminExists && !$successMsg): ?>
      <!-- ── ADMIN ALREADY EXISTS ── -->
      <div class="alert alert-wn">
        <span class="alert-ic">🔒</span>
        <div>
          An active admin account already exists in the database. Setup is not needed again.
          <br>If you've forgotten credentials, update the <code>password_hash</code> directly via phpMyAdmin or SQL.
        </div>
      </div>
      <a href="login.php" class="btn-login">🔑 Go to Login</a>

    <?php elseif ($step === 'done' && $successMsg): ?>
      <!-- ── SUCCESS STATE ── -->
      <div class="success-ic">
        <svg width="30" height="30" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>
      </div>
      <div class="success-title">Admin Created!</div>
      <div class="success-sub">
        Your administrator account has been set up successfully.<br>
        All permissions have been granted.
      </div>

      <?php if ($successMsg): ?>
        <div class="alert alert-ok">
          <span class="alert-ic">✅</span>
          <div><?= $successMsg ?></div>
        </div>
      <?php endif; ?>

      <a href="login.php" class="btn-login">🔑 Go to Login</a>

      <div class="security-note">
        ⚠️ <strong>Security:</strong> Delete or rename <code>setup_admin.php</code> immediately after logging in to prevent unauthorised access.
      </div>

    <?php else: ?>
      <!-- ── SETUP FORM ── -->
      <?php if ($formError): ?>
        <div class="alert alert-er">
          <span class="alert-ic">⚠️</span>
          <div><?= htmlspecialchars($formError) ?></div>
        </div>
      <?php endif; ?>

      <form method="POST" novalidate>

        <div class="fg">
          <label>Full Name <span>*</span></label>
          <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                 placeholder="e.g. Rajesh Kumar" required>
        </div>

        <div class="row2">
          <div class="fg">
            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                   placeholder="admin@school.com">
          </div>
          <div class="fg">
            <label>Phone</label>
            <input type="tel" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                   placeholder="+91 98765 43210">
          </div>
        </div>

        <div class="fg">
          <label>Username <span>*</span></label>
          <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                 placeholder="e.g. admin" autocomplete="off" required>
          <div class="hint">Min 4 characters · Used to login</div>
        </div>

        <div class="row2">
          <div class="fg">
            <label>Password <span>*</span></label>
            <input type="password" name="password" id="pwInput"
                   placeholder="Min 8 characters" autocomplete="new-password" required
                   oninput="updateStrength(this.value)">
            <div class="pw-bar"><div class="pw-fill" id="pwFill"></div></div>
            <div class="hint" id="pwHint">Enter a password</div>
          </div>
          <div class="fg">
            <label>Confirm Password <span>*</span></label>
            <input type="password" name="confirm" placeholder="Repeat password" required>
          </div>
        </div>

        <!-- Permissions preview -->
        <div class="fg">
          <label>Admin Permissions (all granted)</label>
          <div class="perm-grid">
            <div class="perm-chip">✓ Students</div>
            <div class="perm-chip">✓ Fees</div>
            <div class="perm-chip">✓ Books</div>
            <div class="perm-chip">✓ Expenses</div>
            <div class="perm-chip">✓ Reports</div>
            <div class="perm-chip">✓ Staff</div>
            <div class="perm-chip">✓ Settings</div>
          </div>
        </div>

        <button type="submit" class="btn-submit">
          🚀 Create Admin Account
        </button>
      </form>

      <div class="security-note" style="margin-top:16px">
        ⚠️ <strong>Security reminder:</strong> Delete <code>setup_admin.php</code> from your server after the first admin is created.
      </div>

    <?php endif; ?>

  </div>

  <div class="card-foot">
    <span>OPTMS Tech ERP v6</span>
    <span><?= date('d M Y, H:i') ?></span>
  </div>
</div>

<script>
function updateStrength(val) {
  const fill = document.getElementById('pwFill');
  const hint = document.getElementById('pwHint');
  let score = 0;
  if (val.length >= 8)  score++;
  if (val.length >= 12) score++;
  if (/[A-Z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;

  const levels = [
    { w: '0%',   bg: '#e2e7f0', label: 'Enter a password' },
    { w: '20%',  bg: '#dc2626', label: '🔴 Very weak' },
    { w: '40%',  bg: '#ea580c', label: '🟠 Weak' },
    { w: '60%',  bg: '#d97706', label: '🟡 Fair' },
    { w: '80%',  bg: '#16a34a', label: '🟢 Strong' },
    { w: '100%', bg: '#15803d', label: '💪 Very strong' },
  ];
  const lvl = val.length === 0 ? 0 : Math.max(1, score);
  fill.style.width      = levels[lvl].w;
  fill.style.background = levels[lvl].bg;
  hint.textContent      = levels[lvl].label;
}
</script>
</body>
</html>
