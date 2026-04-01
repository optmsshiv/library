<?php
// ═══════════════════════════════════════════════
//  OPTMS Tech Library ERP — Setup Wizard
//  Run once to configure DB, create tables & admin
//  DELETE this file from server after setup!
// ═══════════════════════════════════════════════

session_start();

// ── Security: block if already set up ──
$lockFile = __DIR__ . '/.setup_complete';
if (file_exists($lockFile)) {
    header('Location: login.php');
    exit;
}

$step   = (int)($_POST['step'] ?? $_GET['step'] ?? 1);
$errors = [];
$success = false;

// ─────────────────────────────────────────────
//  STEP 3: Run actual setup
// ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 3) {
    $dbHost   = trim($_POST['db_host']    ?? 'localhost');
    $dbName   = trim($_POST['db_name']    ?? '');
    $dbUser   = trim($_POST['db_user']    ?? '');
    $dbPass   = $_POST['db_pass']         ?? '';
    $libName  = trim($_POST['lib_name']   ?? 'OPTMS Tech Study Library');
    $libPhone = trim($_POST['lib_phone']  ?? '');
    $libEmail = trim($_POST['lib_email']  ?? '');
    $libAddr  = trim($_POST['lib_addr']   ?? '');
    $waNum    = trim($_POST['wa_num']     ?? '');
    $fineDay  = max(0, (int)($_POST['fine_day'] ?? 5));
    $loanDay  = max(1, (int)($_POST['loan_day'] ?? 14));
    $adminName  = trim($_POST['admin_name']  ?? 'Admin');
    $adminUser  = trim($_POST['admin_user']  ?? 'admin');
    $adminPass  = $_POST['admin_pass']       ?? '';
    $adminPass2 = $_POST['admin_pass2']      ?? '';
    $adminEmail = trim($_POST['admin_email'] ?? '');

    if (!$dbName)  $errors[] = 'Database name is required.';
    if (!$dbUser)  $errors[] = 'Database username is required.';
    if (!$adminUser) $errors[] = 'Admin username is required.';
    if (strlen($adminPass) < 6) $errors[] = 'Admin password must be at least 6 characters.';
    if ($adminPass !== $adminPass2) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        try {
            $pdo = new PDO("mysql:host={$dbHost};charset=utf8mb4", $dbUser, $dbPass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$dbName}`");

            // Run schema
            $sql = file_get_contents(__DIR__ . '/database.sql');
            foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
                if (!$stmt || preg_match('/^\s*--/', $stmt)) continue;
                try { $pdo->exec($stmt); } catch (Exception $e) { /* skip existing */ }
            }

            // Update library settings
            $waClean = preg_replace('/\D/', '', $waNum);
            $pdo->prepare("UPDATE settings SET name=?,phone=?,email=?,addr=?,fine_per_day=?,loan_days=?,wa_number=? WHERE id=1")
                ->execute([$libName, $libPhone, $libEmail, $libAddr, $fineDay, $loanDay, $waClean]);

            // Create / update admin staff
            $hash  = password_hash($adminPass, PASSWORD_BCRYPT);
            $exists = (int)$pdo->query("SELECT COUNT(*) FROM staff WHERE username=" . $pdo->quote($adminUser))->fetchColumn();
            if ($exists) {
                $pdo->prepare("UPDATE staff SET name=?,email=?,password_hash=?,role='admin',status='active',perm_students=1,perm_fees=1,perm_books=1,perm_expenses=1,perm_reports=1,perm_staff=1,perm_settings=1 WHERE username=?")
                    ->execute([$adminName, $adminEmail, $hash, $adminUser]);
            } else {
                $pdo->prepare("INSERT INTO staff (id,name,role,email,username,password_hash,perm_students,perm_fees,perm_books,perm_expenses,perm_reports,perm_staff,perm_settings,status) VALUES ('SF-001',?,'admin',?,?,?,1,1,1,1,1,1,1,'active')")
                    ->execute([$adminName, $adminEmail, $adminUser, $hash]);
            }

            // Write db.php with correct credentials
            $dbPhp = "<?php\ndefine('DB_HOST', " . var_export($dbHost,true) . ");\ndefine('DB_NAME', " . var_export($dbName,true) . ");\ndefine('DB_USER', " . var_export($dbUser,true) . ");\ndefine('DB_PASS', " . var_export($dbPass,true) . ");\ndefine('DB_CHARSET', 'utf8mb4');\n\nfunction getDB() {\n    static \$pdo = null;\n    if (\$pdo === null) {\n        \$dsn = \"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=\" . DB_CHARSET;\n        \$options = [\n            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,\n            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\n            PDO::ATTR_EMULATE_PREPARES   => false,\n        ];\n        try {\n            \$pdo = new PDO(\$dsn, DB_USER, DB_PASS, \$options);\n        } catch (\\PDOException \$e) {\n            http_response_code(500);\n            echo json_encode(['error' => 'DB connection failed: ' . \$e->getMessage()]);\n            exit;\n        }\n    }\n    return \$pdo;\n}\n\nfunction jsonResponse(\$data, \$code = 200) {\n    http_response_code(\$code);\n    header('Content-Type: application/json');\n    echo json_encode(\$data);\n    exit;\n}\n\nfunction jsonError(\$msg, \$code = 400) {\n    jsonResponse(['error' => \$msg], \$code);\n}\n\nfunction getInput() {\n    \$raw = file_get_contents('php://input');\n    return json_decode(\$raw, true) ?? [];\n}\n\nfunction generateId(\$prefix, \$table, \$col = 'id') {\n    \$db = getDB();\n    \$count = \$db->query(\"SELECT COUNT(*) FROM `\$table`\")->fetchColumn();\n    return \$prefix . '-' . str_pad(\$count + 1, 3, '0', STR_PAD_LEFT);\n}\n\nheader('Access-Control-Allow-Origin: *');\nheader('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');\nheader('Access-Control-Allow-Headers: Content-Type');\nif (\$_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }\n";
            file_put_contents(__DIR__ . '/includes/db.php', $dbPhp);

            // Lock file
            file_put_contents($lockFile, date('Y-m-d H:i:s') . ' — Setup completed');

            $step = 4;
        } catch (Exception $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
            $step = 2;
        }
    } else {
        $step = 2;
    }
}

// ─────────────────────────────────────────────
//  System checks for step 1
// ─────────────────────────────────────────────
$sysChecks = [
    'PHP ' . PHP_VERSION . ' (≥ 7.4 required)' => version_compare(PHP_VERSION, '7.4.0', '>='),
    'PDO Extension'     => extension_loaded('pdo'),
    'PDO MySQL Driver'  => extension_loaded('pdo_mysql'),
    'includes/ writable'=> is_writable(__DIR__ . '/includes'),
    'database.sql found'=> file_exists(__DIR__ . '/database.sql'),
];
$allChecksOk = !in_array(false, $sysChecks, true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Setup Wizard — OPTMS Tech Library ERP</title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
<style>
:root{
  --bg:#f0f4fb;--sf:#fff;--sf2:#f5f7fc;--br:#e2e7f0;--br2:#ccd3e0;
  --ac:#3d6ff0;--ac2:#2d5de0;--em:#16a34a;--ro:#dc2626;--gd:#d97706;--vi:#7c3aed;
  --tx:#0f172a;--tx2:#334155;--tx3:#64748b;
  --r:14px;--r2:9px;
  --sh:0 1px 4px rgba(15,23,42,.06),0 4px 16px rgba(15,23,42,.05);
  --sh2:0 8px 40px rgba(15,23,42,.13),0 2px 8px rgba(15,23,42,.06);
}
*{margin:0;padding:0;box-sizing:border-box}
body{
  font-family:'Sora',sans-serif;font-size:14px;
  background:var(--bg);color:var(--tx);
  min-height:100vh;display:flex;align-items:flex-start;
  justify-content:center;padding:32px 16px 60px;
  background-image:
    radial-gradient(ellipse 70% 50% at 15% 0%,rgba(61,111,240,.08) 0%,transparent 65%),
    radial-gradient(ellipse 55% 40% at 85% 100%,rgba(124,58,237,.06) 0%,transparent 60%);
}

/* ── CARD ── */
.wrap{width:100%;max-width:640px}
.card{background:var(--sf);border:1px solid var(--br);border-radius:20px;box-shadow:var(--sh2);overflow:hidden}

/* ── HEADER ── */
.hd{
  background:linear-gradient(135deg,#111827 0%,#1e3a8a 40%,#3d6ff0 75%,#7c3aed 100%);
  padding:28px 32px 24px;position:relative;overflow:hidden;
}
.hd::before{content:'';position:absolute;width:280px;height:280px;border-radius:50%;background:rgba(255,255,255,.04);top:-120px;right:-60px}
.hd::after{content:'';position:absolute;width:180px;height:180px;border-radius:50%;background:rgba(255,255,255,.03);bottom:-80px;left:-30px}
.hd-logo{display:flex;align-items:center;gap:13px;margin-bottom:22px;position:relative;z-index:1}
.hd-ic{
  width:46px;height:46px;
  background:rgba(255,255,255,.12);
  border:1px solid rgba(255,255,255,.22);
  border-radius:13px;
  display:flex;align-items:center;justify-content:center;
  backdrop-filter:blur(8px);font-size:22px;
}
.hd-tx{color:#fff;font-size:19px;font-weight:700;line-height:1.2}
.hd-sb{color:rgba(255,255,255,.55);font-size:10px;font-family:'JetBrains Mono',monospace;letter-spacing:1.8px;text-transform:uppercase;margin-top:3px}

/* ── STEP TRACK ── */
.steps{display:flex;align-items:center;position:relative;z-index:1}
.step{display:flex;align-items:center;gap:7px}
.step-n{
  width:30px;height:30px;border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  font-size:11px;font-weight:700;flex-shrink:0;transition:all .3s;
}
.step-n.done{background:rgba(255,255,255,.95);color:var(--em)}
.step-n.active{background:#fff;color:var(--ac);box-shadow:0 0 0 4px rgba(255,255,255,.3)}
.step-n.pend{background:rgba(255,255,255,.1);color:rgba(255,255,255,.4);border:1px solid rgba(255,255,255,.18)}
.step-lb{font-size:10.5px;font-weight:600;color:rgba(255,255,255,.75);white-space:nowrap}
.step-lb.active{color:#fff;font-weight:700}
.step-lb.pend{color:rgba(255,255,255,.35)}
.step-line{flex:1;height:1px;background:rgba(255,255,255,.18);margin:0 9px}
.step-line.done{background:rgba(255,255,255,.45)}

/* ── BODY ── */
.bd{padding:32px}
.sec-title{font-size:16px;font-weight:700;color:var(--tx);display:flex;align-items:center;gap:9px;margin-bottom:5px}
.sec-sub{font-size:12.5px;color:var(--tx3);margin-bottom:24px;line-height:1.6}

/* ── ALERTS ── */
.alert{padding:12px 15px;border-radius:var(--r2);font-size:12.5px;margin-bottom:18px;display:flex;align-items:flex-start;gap:9px;border:1px solid}
.a-err{background:#fff1f2;border-color:#fecdd3;color:#9f1239}
.a-ok{background:#f0fdf4;border-color:#bbf7d0;color:#166534}
.a-info{background:#eff4ff;border-color:#bfcffd;color:#1e40af}
.a-warn{background:#fffbeb;border-color:#fde68a;color:#92400e}
.alert ul{padding-left:16px;margin:5px 0 0}
.alert li{margin-bottom:2px}
.alert code{font-family:'JetBrains Mono',monospace;font-size:11px;background:rgba(0,0,0,.07);padding:1px 5px;border-radius:4px}

/* ── FORMS ── */
.fg{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.fgi{display:flex;flex-direction:column;gap:5px}
.fgi.full{grid-column:1/-1}
label{font-size:11px;font-weight:600;color:var(--tx2);letter-spacing:.3px}
label .req{color:var(--ro)}
input,select{
  padding:9px 12px;border:1px solid var(--br);border-radius:var(--r2);
  background:var(--sf2);color:var(--tx);font-size:13px;
  font-family:'Sora',sans-serif;outline:none;transition:all .18s;width:100%;
}
input:focus,select:focus{border-color:var(--ac);box-shadow:0 0 0 3px rgba(61,111,240,.1);background:#fff}
input::placeholder{color:var(--tx3)}
.hint{font-size:10.5px;color:var(--tx3);margin-top:3px}

/* ── SECTION DIVIDER ── */
.sdiv{
  font-size:10px;font-weight:700;color:var(--tx3);
  text-transform:uppercase;letter-spacing:1.4px;
  font-family:'JetBrains Mono',monospace;
  padding:16px 0 10px;border-bottom:1px solid var(--br);
  margin-bottom:14px;display:flex;align-items:center;gap:8px;
}
.sdiv .mi{font-size:15px;color:var(--ac)}

/* ── CHECKLIST ── */
.checklist{display:flex;flex-direction:column;gap:8px;margin:18px 0}
.ci{display:flex;align-items:center;gap:12px;padding:11px 15px;background:var(--sf2);border:1px solid var(--br);border-radius:var(--r2);font-size:12.5px;font-weight:500;transition:border-color .2s}
.ci:hover{border-color:var(--br2)}
.ci-ic{width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.ci-tx strong{display:block;color:var(--tx)}
.ci-tx span{font-size:11px;color:var(--tx3);font-weight:400}

/* ── SYS CHECKS ── */
.check-row{
  display:flex;align-items:center;justify-content:space-between;
  padding:8px 12px;border-radius:8px;margin-bottom:5px;font-size:12.5px;font-weight:500;
}

/* ── BUTTONS ── */
.btn-row{display:flex;gap:10px;justify-content:flex-end;margin-top:26px;padding-top:20px;border-top:1px solid var(--br)}
.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 20px;border-radius:var(--r2);font-size:13px;font-weight:600;cursor:pointer;border:none;transition:all .18s;font-family:'Sora',sans-serif}
.btn-p{background:var(--ac);color:#fff;box-shadow:0 2px 8px rgba(61,111,240,.3)}
.btn-p:hover{background:var(--ac2);transform:translateY(-1px);box-shadow:0 4px 14px rgba(61,111,240,.38)}
.btn-p:disabled{opacity:.5;pointer-events:none}
.btn-s{background:var(--sf2);color:var(--tx2);border:1px solid var(--br)}
.btn-s:hover{background:var(--bg);color:var(--tx)}

/* ── PASSWORD ── */
.pw-wrap{position:relative}
.pw-wrap input{padding-right:40px}
.pw-eye{position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--tx3);padding:2px}
.pw-eye:hover{color:var(--tx2)}
.pw-str{height:3px;border-radius:2px;margin-top:5px;background:var(--br);overflow:hidden}
.pw-str-fill{height:100%;border-radius:2px;transition:all .3s;width:0}

/* ── SUCCESS ── */
.suc-wrap{text-align:center;padding:8px 0 24px}
.suc-icon{
  width:74px;height:74px;
  background:linear-gradient(135deg,#16a34a,#4ade80);
  border-radius:50%;display:flex;align-items:center;justify-content:center;
  margin:0 auto 22px;
  box-shadow:0 6px 24px rgba(22,163,74,.35);
  animation:popIn .5s cubic-bezier(.34,1.56,.64,1) both;
}
@keyframes popIn{from{opacity:0;transform:scale(.4)}to{opacity:1;transform:scale(1)}}
.suc-title{font-size:24px;font-weight:800;color:var(--tx);margin-bottom:6px}
.suc-sub{font-size:13px;color:var(--tx3);margin-bottom:28px;line-height:1.6}
.cred-box{
  background:linear-gradient(135deg,#111827,#1e3a8a 50%,#3d6ff0);
  border-radius:var(--r);padding:22px 26px;text-align:left;margin-bottom:20px;
}
.cred-ttl{font-size:9.5px;font-weight:700;letter-spacing:1.8px;text-transform:uppercase;font-family:'JetBrains Mono',monospace;color:rgba(255,255,255,.45);margin-bottom:16px}
.cred-row{display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid rgba(255,255,255,.1)}
.cred-row:last-child{border-bottom:none}
.cred-lbl{font-size:11px;color:rgba(255,255,255,.55);font-weight:500}
.cred-val{font-family:'JetBrains Mono',monospace;font-size:13.5px;font-weight:600;color:#fff}
.btn-go{
  width:100%;padding:14px;
  background:linear-gradient(135deg,var(--ac),var(--vi));
  color:#fff;border:none;border-radius:var(--r2);
  font-size:14px;font-weight:700;cursor:pointer;font-family:'Sora',sans-serif;
  box-shadow:0 4px 18px rgba(61,111,240,.38);transition:all .2s;
  display:flex;align-items:center;justify-content:center;gap:8px;
}
.btn-go:hover{transform:translateY(-2px);box-shadow:0 6px 24px rgba(61,111,240,.48)}

/* ── FOOTER ── */
.ft{
  text-align:center;padding:14px 20px;border-top:1px solid var(--br);
  font-size:10.5px;color:var(--tx3);font-family:'JetBrains Mono',monospace;
  background:var(--sf2);letter-spacing:.5px;
}

/* ── MI ── */
.mi{font-family:'Material Icons Round';font-style:normal;font-size:18px;line-height:1;display:inline-flex;align-items:center;vertical-align:middle;user-select:none}
.mi.sm{font-size:15px}
.mi.lg{font-size:22px}
</style>
</head>
<body>
<div class="wrap">
<div class="card">

<!-- ── HEADER ── -->
<div class="hd">
  <div class="hd-logo">
    <div class="hd-ic">📚</div>
    <div>
      <div class="hd-tx">OPTMS Tech Library ERP</div>
      <div class="hd-sb">Setup Wizard · v6.0</div>
    </div>
  </div>
  <!-- Steps -->
  <div class="steps">
    <?php
    $stepLabels = ['Welcome','Database & Library','Admin Account','Complete'];
    $cur = min($step, 4);
    foreach ($stepLabels as $i => $lbl):
      $n = $i + 1;
      $done = $n < $cur; $active = $n === $cur; $pend = $n > $cur;
    ?>
    <?php if ($i > 0): ?><div class="step-line <?= $done || $active ? 'done':'' ?>"></div><?php endif ?>
    <div class="step">
      <div class="step-n <?= $done?'done':($active?'active':'pend') ?>">
        <?= $done ? '<span class="mi sm">check</span>' : $n ?>
      </div>
      <div class="step-lb <?= $active?'active':($pend?'pend':'') ?>"><?= $lbl ?></div>
    </div>
    <?php endforeach ?>
  </div>
</div>

<!-- ── BODY ── -->
<div class="bd">

<?php if (!empty($errors)): ?>
<div class="alert a-err">
  <span class="mi sm" style="margin-top:1px">error</span>
  <div><strong>Please fix the following issues:</strong><ul>
    <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach ?>
  </ul></div>
</div>
<?php endif ?>

<!-- ════════ STEP 1 — WELCOME ════════ -->
<?php if ($step === 1): ?>
<div class="sec-title"><span class="mi">auto_stories</span> Welcome to Setup</div>
<p class="sec-sub">This one-time wizard will create your database tables, configure your library profile, and set up the admin account. It takes about 2 minutes.</p>

<div class="alert a-info" style="margin-bottom:22px">
  <span class="mi sm" style="margin-top:1px;flex-shrink:0">info</span>
  <div><strong>You will need:</strong>
    <ul>
      <li>MySQL database credentials (host, name, username, password)</li>
      <li>PHP 7.4+ with PDO MySQL extension enabled</li>
      <li>Write access to the <code>includes/</code> directory</li>
    </ul>
  </div>
</div>

<div class="checklist">
  <div class="ci"><div class="ci-ic" style="background:#eff4ff;color:var(--ac)"><span class="mi">storage</span></div><div class="ci-tx"><strong>Database & Tables</strong><span>Creates all tables: students, staff, books, fees, invoices, batches, attendance…</span></div></div>
  <div class="ci"><div class="ci-ic" style="background:#faf5ff;color:var(--vi)"><span class="mi">business</span></div><div class="ci-tx"><strong>Library Profile</strong><span>Name, contact info, WhatsApp number, fine rules, loan period</span></div></div>
  <div class="ci"><div class="ci-ic" style="background:#f0fdf4;color:var(--em)"><span class="mi">admin_panel_settings</span></div><div class="ci-tx"><strong>Admin Account</strong><span>Full-access super-admin login with all permissions enabled</span></div></div>
  <div class="ci"><div class="ci-ic" style="background:#fffbeb;color:var(--gd)"><span class="mi">lock</span></div><div class="ci-tx"><strong>Auto-Lock After Setup</strong><span>A lock file is created — setup cannot be re-run without deleting it</span></div></div>
</div>

<div class="sdiv"><span class="mi">checklist_rtl</span> System Requirements</div>
<?php foreach ($sysChecks as $lbl => $ok): ?>
<div class="check-row" style="background:<?= $ok?'#f0fdf4':'#fff1f2' ?>;border:1px solid <?= $ok?'#bbf7d0':'#fecdd3' ?>">
  <span style="color:var(--tx2)"><?= htmlspecialchars($lbl) ?></span>
  <span class="mi sm" style="color:<?= $ok?'#16a34a':'#dc2626' ?>"><?= $ok?'check_circle':'cancel' ?></span>
</div>
<?php endforeach ?>

<div class="btn-row">
  <form method="POST">
    <input type="hidden" name="step" value="2">
    <button type="submit" class="btn btn-p" <?= !$allChecksOk ? 'disabled title="Fix requirements above first"' : '' ?>>
      <span class="mi sm">arrow_forward</span> Start Setup
    </button>
  </form>
</div>

<!-- ════════ STEP 2 — DATABASE + LIBRARY + ADMIN ════════ -->
<?php elseif ($step === 2): ?>
<form method="POST" autocomplete="off">
  <input type="hidden" name="step" value="3">

  <!-- Database -->
  <div class="sec-title"><span class="mi">storage</span> Database Connection</div>
  <p class="sec-sub">Your credentials will be saved to <code>includes/db.php</code>. The database and all tables will be created automatically.</p>

  <div class="fg">
    <div class="fgi">
      <label>Host <span class="req">*</span></label>
      <input type="text" name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>" placeholder="localhost" required>
    </div>
    <div class="fgi">
      <label>Database Name <span class="req">*</span></label>
      <input type="text" name="db_name" value="<?= htmlspecialchars($_POST['db_name'] ?? '') ?>" placeholder="library_erp" required>
      <div class="hint">Will be created if it doesn't exist</div>
    </div>
    <div class="fgi">
      <label>Username <span class="req">*</span></label>
      <input type="text" name="db_user" value="<?= htmlspecialchars($_POST['db_user'] ?? '') ?>" placeholder="root" required autocomplete="off">
    </div>
    <div class="fgi">
      <label>Password</label>
      <div class="pw-wrap">
        <input type="password" name="db_pass" id="dbPass" value="<?= htmlspecialchars($_POST['db_pass'] ?? '') ?>" placeholder="••••••••" autocomplete="off">
        <button type="button" class="pw-eye" onclick="togglePw('dbPass',this)"><span class="mi sm">visibility</span></button>
      </div>
    </div>
  </div>

  <!-- Library Profile -->
  <div class="sdiv" style="margin-top:8px"><span class="mi">library</span> Library Profile</div>
  <div class="fg">
    <div class="fgi full">
      <label>Library Name <span class="req">*</span></label>
      <input type="text" name="lib_name" value="<?= htmlspecialchars($_POST['lib_name'] ?? 'OPTMS Tech Study Library') ?>" placeholder="My Study Library" required>
    </div>
    <div class="fgi">
      <label>Phone / WhatsApp</label>
      <input type="text" name="lib_phone" value="<?= htmlspecialchars($_POST['lib_phone'] ?? '') ?>" placeholder="+91 98765 43210">
    </div>
    <div class="fgi">
      <label>Email</label>
      <input type="email" name="lib_email" value="<?= htmlspecialchars($_POST['lib_email'] ?? '') ?>" placeholder="admin@library.com">
    </div>
    <div class="fgi full">
      <label>Address</label>
      <input type="text" name="lib_addr" value="<?= htmlspecialchars($_POST['lib_addr'] ?? '') ?>" placeholder="City, State — PIN Code">
    </div>
    <div class="fgi">
      <label>WhatsApp Number</label>
      <input type="text" name="wa_num" value="<?= htmlspecialchars($_POST['wa_num'] ?? '') ?>" placeholder="917282071620">
      <div class="hint">Country code + number, digits only (no +, spaces, or dashes)</div>
    </div>
    <div class="fgi">
      <label>Fine Per Day (₹)</label>
      <input type="number" name="fine_day" value="<?= htmlspecialchars($_POST['fine_day'] ?? '5') ?>" min="0" max="500">
    </div>
    <div class="fgi">
      <label>Max Book Loan Days</label>
      <input type="number" name="loan_day" value="<?= htmlspecialchars($_POST['loan_day'] ?? '14') ?>" min="1" max="90">
    </div>
  </div>

  <!-- Admin Account -->
  <div class="sdiv"><span class="mi">admin_panel_settings</span> Admin Account</div>
  <div class="fg">
    <div class="fgi">
      <label>Full Name <span class="req">*</span></label>
      <input type="text" name="admin_name" value="<?= htmlspecialchars($_POST['admin_name'] ?? '') ?>" placeholder="Admin Name" required>
    </div>
    <div class="fgi">
      <label>Email</label>
      <input type="email" name="admin_email" value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>" placeholder="admin@library.com">
    </div>
    <div class="fgi">
      <label>Username <span class="req">*</span></label>
      <input type="text" name="admin_user" value="<?= htmlspecialchars($_POST['admin_user'] ?? 'admin') ?>" placeholder="admin" required autocomplete="off">
    </div>
    <div class="fgi"></div>
    <div class="fgi">
      <label>Password <span class="req">*</span> <span style="font-weight:400;color:var(--tx3)">(min 6 characters)</span></label>
      <div class="pw-wrap">
        <input type="password" name="admin_pass" id="adminPass" placeholder="Create a strong password" required autocomplete="new-password" oninput="checkStr(this)">
        <button type="button" class="pw-eye" onclick="togglePw('adminPass',this)"><span class="mi sm">visibility</span></button>
      </div>
      <div class="pw-str"><div class="pw-str-fill" id="pwBar"></div></div>
      <div class="hint" id="strLabel">Enter a password</div>
    </div>
    <div class="fgi">
      <label>Confirm Password <span class="req">*</span></label>
      <div class="pw-wrap">
        <input type="password" name="admin_pass2" id="adminPass2" placeholder="Repeat password" required autocomplete="new-password">
        <button type="button" class="pw-eye" onclick="togglePw('adminPass2',this)"><span class="mi sm">visibility</span></button>
      </div>
    </div>
  </div>

  <div class="alert a-warn" style="margin-top:18px">
    <span class="mi sm" style="margin-top:1px;flex-shrink:0">warning</span>
    <div>After setup completes, <strong>delete <code>setup.php</code></strong> from your server immediately. The setup wizard auto-locks, but physical deletion is the safest practice.</div>
  </div>

  <div class="btn-row">
    <button type="button" class="btn btn-s" onclick="history.back()"><span class="mi sm">arrow_back</span> Back</button>
    <button type="submit" class="btn btn-p"><span class="mi sm">rocket_launch</span> Create Database & Admin</button>
  </div>
</form>

<!-- ════════ STEP 4 — SUCCESS ════════ -->
<?php elseif ($step === 4): ?>
<div class="suc-wrap">
  <div class="suc-icon">
    <span class="mi lg" style="color:#fff;font-size:36px">check</span>
  </div>
  <div class="suc-title">Setup Complete! 🎉</div>
  <p class="suc-sub">Your OPTMS Tech Library ERP is ready.<br>All tables created and your admin account is active.</p>

  <div class="cred-box">
    <div class="cred-ttl">🔐 Admin Login Details — Save These Now</div>
    <div class="cred-row">
      <span class="cred-lbl">Username</span>
      <span class="cred-val"><?= htmlspecialchars($_POST['admin_user'] ?? 'admin') ?></span>
    </div>
    <div class="cred-row">
      <span class="cred-lbl">Password</span>
      <span class="cred-val" id="credPw">•••••••••</span>
      <button onclick="revealPw()" style="background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);color:rgba(255,255,255,.7);font-size:10px;padding:3px 9px;border-radius:5px;cursor:pointer;font-family:'Sora',sans-serif" id="revealBtn">Show</button>
    </div>
    <div class="cred-row">
      <span class="cred-lbl">Library</span>
      <span class="cred-val" style="font-size:12px"><?= htmlspecialchars($_POST['lib_name'] ?? '') ?></span>
    </div>
    <div class="cred-row">
      <span class="cred-lbl">URL</span>
      <span class="cred-val" style="font-size:11px"><?= htmlspecialchars((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/login.php') ?></span>
    </div>
  </div>

  <div class="alert a-warn" style="margin-bottom:22px;text-align:left">
    <span class="mi sm" style="flex-shrink:0;margin-top:1px">warning</span>
    <div>
      <strong>Delete <code>setup.php</code> from your server now.</strong><br>
      The file is locked and cannot be re-run, but removing it is the safest practice. <br>
      Also note: anyone who visits this URL can see this credentials screen until the file is deleted.
    </div>
  </div>

  <a href="login.php" style="text-decoration:none">
    <button class="btn-go">
      <span class="mi">login</span> Go to Login Page
    </button>
  </a>
</div>

<?php if (!empty($_POST['admin_pass'])): ?>
<script>
const realPw = <?= json_encode($_POST['admin_pass']) ?>;
function revealPw(){
  const el = document.getElementById('credPw');
  const btn = document.getElementById('revealBtn');
  if(el.textContent === '•••••••••'){el.textContent=realPw;btn.textContent='Hide';}
  else{el.textContent='•••••••••';btn.textContent='Show';}
}
</script>
<?php endif ?>

<?php endif ?>

</div><!-- /bd -->

<div class="ft">
  OPTMS Tech Library ERP v6.0 &nbsp;·&nbsp; Setup Wizard
  <?php if ($step < 4): ?>&nbsp;·&nbsp; Step <?= $step ?> of 3<?php else: ?>
  &nbsp;·&nbsp; <span style="color:var(--em)">✓ Setup Complete</span><?php endif ?>
</div>

</div><!-- /card -->
</div><!-- /wrap -->

<script>
function togglePw(id, btn) {
  const inp = document.getElementById(id);
  const show = inp.type === 'password';
  inp.type = show ? 'text' : 'password';
  btn.innerHTML = show
    ? '<span class="mi sm">visibility_off</span>'
    : '<span class="mi sm">visibility</span>';
}

function checkStr(inp) {
  const v = inp.value;
  const bar = document.getElementById('pwBar');
  const lbl = document.getElementById('strLabel');
  if (!bar) return;
  let s = 0;
  if (v.length >= 6)  s++;
  if (v.length >= 10) s++;
  if (/[A-Z]/.test(v)) s++;
  if (/[0-9]/.test(v)) s++;
  if (/[^A-Za-z0-9]/.test(v)) s++;
  const cols  = ['#dc2626','#ea580c','#d97706','#16a34a','#15803d'];
  const wids  = ['20%','40%','60%','80%','100%'];
  const lbls  = ['Too weak','Weak','Fair','Strong','Very strong'];
  bar.style.width = v ? (wids[s-1]||'10%') : '0%';
  bar.style.background = v ? (cols[s-1]||'#dc2626') : '';
  if (lbl) lbl.textContent = v ? lbls[s-1]||'Too weak' : 'Enter a password';
}
</script>
</body>
</html>
