<?php
/**
 * ═══════════════════════════════════════════════════════════════════
 *  OPTMS Tech ERP — New Client Setup Script
 *  Upload this ONE file to your Bluehost public_html folder
 *  Open: yourdomain.com/setup_client.php
 *  DELETE this file after setup is complete!
 * ═══════════════════════════════════════════════════════════════════
 */

// ── Master credentials (YOUR Bluehost MySQL root access) ────────────
// Fill these once — found in cPanel → MySQL Databases
define('MASTER_HOST',     'localhost');
define('MASTER_ROOT_USER','root');  // e.g. edrppymy
define('MASTER_ROOT_PASS','');  // your cPanel password
define('BASE_DOMAIN',     'localhost');              // e.g. optmstech.in
define('BASE_PATH',       'D:/project'); // server path
define('SETUP_SECRET',    'optms2024secret');             // change this!

// ── Security check ───────────────────────────────────────────────────
if (($_GET['key'] ?? '') !== SETUP_SECRET) {
    die('<h2 style="font-family:sans-serif;color:red;padding:40px">
        ❌ Access denied. Add ?key='.SETUP_SECRET.' to the URL.<br>
        <small>Example: yourdomain.com/setup_client.php?key='.SETUP_SECRET.'</small>
    </h2>');
}

session_start();
$step    = $_POST['step']    ?? 'form';
$message = '';
$errors  = [];

// ── Database schema (full SQL) ───────────────────────────────────────
function getSchema($libraryName, $phone, $email, $addr, $adminUsername, $adminPassHash) {
    return "
CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY,
    name VARCHAR(255) DEFAULT '{$libraryName}',
    phone VARCHAR(30) DEFAULT '{$phone}',
    email VARCHAR(255) DEFAULT '{$email}',
    addr VARCHAR(255) DEFAULT '{$addr}',
    fine_per_day INT DEFAULT 5,
    loan_days INT DEFAULT 14,
    ac_fee INT DEFAULT 200,
    wa_number VARCHAR(30) DEFAULT '',
    wa_gateway VARCHAR(30) DEFAULT 'meta',
    wa_meta_phone_id VARCHAR(100) DEFAULT '',
    wa_meta_token VARCHAR(500) DEFAULT '',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO settings (id) VALUES (1) ON DUPLICATE KEY UPDATE id=1;

CREATE TABLE IF NOT EXISTS batches (
    id VARCHAR(30) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    total_seats INT DEFAULT 80,
    occupied_seats INT DEFAULT 0,
    base_fee INT DEFAULT 1200,
    ac_extra INT DEFAULT 200,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS students (
    id VARCHAR(30) PRIMARY KEY,
    fname VARCHAR(100) NOT NULL,
    lname VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(255),
    batch_id VARCHAR(30),
    seat_type ENUM('ac','non-ac') DEFAULT 'non-ac',
    seat VARCHAR(20),
    base_fee INT DEFAULT 0,
    discount_type ENUM('none','flat','percent') DEFAULT 'none',
    discount_value DECIMAL(10,2) DEFAULT 0,
    discount_reason VARCHAR(255),
    net_fee INT DEFAULT 0,
    paid_amt INT DEFAULT 0,
    fee_status ENUM('paid','partial','pending','overdue') DEFAULT 'pending',
    paid_on DATE,
    due_date DATE,
    course VARCHAR(100),
    addr VARCHAR(255),
    color VARCHAR(20) DEFAULT '#4a7c6f',
    join_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_batch (batch_id),
    FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS books (
    id VARCHAR(30) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255),
    isbn VARCHAR(50),
    category ENUM('Academic','Self-Help','Fiction','Science','Other') DEFAULT 'Other',
    copies INT DEFAULT 1,
    available INT DEFAULT 1,
    shelf VARCHAR(50),
    emoji VARCHAR(10) DEFAULT '📘',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS transactions (
    id VARCHAR(30) PRIMARY KEY,
    student_id VARCHAR(30),
    book_id VARCHAR(30),
    issue_date DATE,
    due_date DATE,
    return_date DATE,
    fine INT DEFAULT 0,
    status ENUM('issued','returned','overdue') DEFAULT 'issued',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_student (student_id),
    INDEX idx_book (book_id),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expenses (
    id VARCHAR(30) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    amount INT NOT NULL,
    category ENUM('Utilities','Staff','Maintenance','Supplies','Books','Other') DEFAULT 'Other',
    expense_date DATE,
    notes TEXT,
    emoji VARCHAR(10) DEFAULT '💸',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS invoices (
    id VARCHAR(30) PRIMARY KEY,
    student_id VARCHAR(30),
    type VARCHAR(100) DEFAULT 'Monthly Fee',
    amount INT DEFAULT 0,
    base_fee INT DEFAULT 0,
    discount INT DEFAULT 0,
    net_fee INT DEFAULT 0,
    paid_amt INT DEFAULT 0,
    balance INT DEFAULT 0,
    invoice_date DATE,
    month VARCHAR(20),
    mode VARCHAR(100) DEFAULT 'Cash',
    status ENUM('paid','partial') DEFAULT 'paid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_invoice_student (student_id),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(30),
    attendance_date DATE NOT NULL,
    status ENUM('present','absent') DEFAULT 'present',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_attendance (student_id, attendance_date),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS staff (
    id VARCHAR(30) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    role ENUM('admin','librarian','accountant','receptionist') DEFAULT 'librarian',
    email VARCHAR(255),
    phone VARCHAR(20),
    username VARCHAR(100),
    password_hash VARCHAR(255),
    perm_students TINYINT(1) DEFAULT 1,
    perm_fees TINYINT(1) DEFAULT 0,
    perm_books TINYINT(1) DEFAULT 1,
    perm_expenses TINYINT(1) DEFAULT 0,
    perm_reports TINYINT(1) DEFAULT 1,
    perm_staff TINYINT(1) DEFAULT 0,
    perm_settings TINYINT(1) DEFAULT 0,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('warning','info','success','error') DEFAULT 'info',
    title VARCHAR(255),
    msg TEXT,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    icon VARCHAR(10),
    bg VARCHAR(100),
    text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS wa_send_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sent_to VARCHAR(255),
    preview TEXT,
    type ENUM('single','bulk') DEFAULT 'single',
    status VARCHAR(20) DEFAULT 'sent',
    gateway VARCHAR(30) DEFAULT 'meta',
    error_msg TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO staff (id,name,role,email,phone,username,password_hash,perm_students,perm_fees,perm_books,perm_expenses,perm_reports,perm_staff,perm_settings,status)
VALUES ('SF-001','Admin','admin','{$email}','{$phone}','{$adminUsername}','{$adminPassHash}',1,1,1,1,1,1,1,'active')
ON DUPLICATE KEY UPDATE username='{$adminUsername}', password_hash='{$adminPassHash}', status='active';

INSERT INTO activity_log (icon,bg,text) VALUES ('🎉','rgba(74,124,111,.14)','Library ERP setup completed successfully!');
";
}

// ── Generate db.php content ──────────────────────────────────────────
function generateDbPhp($host, $dbName, $dbUser, $dbPass) {
    return '<?php
define(\'DB_HOST\',    \'' . addslashes($host)   . '\');
define(\'DB_NAME\',    \'' . addslashes($dbName)  . '\');
define(\'DB_USER\',    \'' . addslashes($dbUser)  . '\');
define(\'DB_PASS\',    \'' . addslashes($dbPass)  . '\');
define(\'DB_CHARSET\', \'utf8mb4\');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode([\'error\' => \'DB connection failed: \' . $e->getMessage()]);
            exit;
        }
    }
    return $pdo;
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header(\'Content-Type: application/json\');
    echo json_encode($data);
    exit;
}

function jsonError($msg, $code = 400) {
    jsonResponse([\'error\' => $msg], $code);
}

function getInput() {
    $raw = file_get_contents(\'php://input\');
    return json_decode($raw, true) ?? [];
}

function generateId($prefix, $table, $col = \'id\') {
    $db    = getDB();
    $count = $db->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
    return $prefix . \'-\' . str_pad($count + 1, 3, \'0\', STR_PAD_LEFT);
}

header(\'Access-Control-Allow-Origin: *\');
header(\'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS\');
header(\'Access-Control-Allow-Headers: Content-Type\');
if ($_SERVER[\'REQUEST_METHOD\'] === \'OPTIONS\') { exit; }
';
}

// ── Handle form submission ────────────────────────────────────────────
$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'create') {

    // Collect inputs
    $clientSlug    = preg_replace('/[^a-z0-9_]/', '', strtolower(trim($_POST['client_slug']   ?? '')));
    $libraryName   = trim($_POST['library_name']   ?? '');
    $ownerName     = trim($_POST['owner_name']      ?? '');
    $phone         = trim($_POST['phone']           ?? '');
    $email         = trim($_POST['email']           ?? '');
    $addr          = trim($_POST['addr']            ?? '');
    $adminUser     = preg_replace('/[^a-zA-Z0-9_]/', '', trim($_POST['admin_username'] ?? 'admin'));
    $adminPass     = trim($_POST['admin_password']  ?? '');
    $dbSuffix      = preg_replace('/[^a-z0-9_]/', '', strtolower(trim($_POST['db_suffix'] ?? $clientSlug)));
    $dbUser        = trim($_POST['db_user']         ?? '');
    $dbPass        = trim($_POST['db_pass']         ?? '');
    $copyFrom      = trim($_POST['copy_from']       ?? '');

    // Validate
    if (!$clientSlug)  $errors[] = 'Client folder name is required (e.g. rajlibrary)';
    if (!$libraryName) $errors[] = 'Library name is required';
    if (!$adminPass || strlen($adminPass) < 6) $errors[] = 'Admin password must be at least 6 characters';
    if (!$dbSuffix)    $errors[] = 'Database name suffix is required';
    if (!$dbUser)      $errors[] = 'Database username is required';
    if (!$dbPass)      $errors[] = 'Database password is required';

    // Build full DB name (cPanel prefix_suffix format)
    $cpanelPrefix = explode('_', MASTER_ROOT_USER)[0];
    $fullDbName   = $cpanelPrefix . '_' . $dbSuffix;
    $fullDbUser   = $cpanelPrefix . '_' . preg_replace('/[^a-z0-9_]/', '', strtolower($dbUser));

    if (empty($errors)) {
        $log = [];
        try {
            // 1. Connect as root
            $rootPdo = new PDO(
                'mysql:host=' . MASTER_HOST . ';charset=utf8mb4',
                MASTER_ROOT_USER,
                MASTER_ROOT_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $log[] = ['ok', 'Connected to MySQL server'];

            // 2. Create database
            $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `{$fullDbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $log[] = ['ok', "Database created: <strong>{$fullDbName}</strong>"];

            // 3. Create DB user and grant permissions
            // Try creating user (may already exist)
            try {
                $rootPdo->exec("CREATE USER '{$fullDbUser}'@'localhost' IDENTIFIED BY '{$dbPass}'");
                $log[] = ['ok', "Database user created: <strong>{$fullDbUser}</strong>"];
            } catch (Exception $e) {
                // User might already exist — update password
                $rootPdo->exec("ALTER USER '{$fullDbUser}'@'localhost' IDENTIFIED BY '{$dbPass}'");
                $log[] = ['warn', "User already exists — password updated: <strong>{$fullDbUser}</strong>"];
            }
            $rootPdo->exec("GRANT ALL PRIVILEGES ON `{$fullDbName}`.* TO '{$fullDbUser}'@'localhost'");
            $rootPdo->exec("FLUSH PRIVILEGES");
            $log[] = ['ok', "Permissions granted to <strong>{$fullDbUser}</strong> on <strong>{$fullDbName}</strong>"];

            // 4. Connect to new database and run schema
            $clientPdo = new PDO(
                "mysql:host=" . MASTER_HOST . ";dbname={$fullDbName};charset=utf8mb4",
                MASTER_ROOT_USER,
                MASTER_ROOT_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            $passHash = password_hash($adminPass, PASSWORD_BCRYPT);
            $schema   = getSchema($libraryName, $phone, $email, $addr, $adminUser, $passHash);

            // Execute schema statement by statement
            $statements = array_filter(array_map('trim', explode(';', $schema)));
            foreach ($statements as $sql) {
                if (!empty($sql)) $clientPdo->exec($sql);
            }
            $log[] = ['ok', 'All database tables created and seeded'];

            // 5. Copy ERP files
            $sourcePath = BASE_PATH . '/' . ($copyFrom ?: 'library-1');
            $destPath   = BASE_PATH . '/' . $clientSlug;

            if (!is_dir($sourcePath)) {
                $log[] = ['warn', "Source folder not found: <strong>{$sourcePath}</strong> — skipping file copy. Copy files manually."];
            } elseif (is_dir($destPath)) {
                $log[] = ['warn', "Folder <strong>{$destPath}</strong> already exists — skipping file copy to avoid overwrite."];
            } else {
                // Recursive copy
                function copyDir($src, $dst) {
                    mkdir($dst, 0755, true);
                    foreach (scandir($src) as $item) {
                        if ($item === '.' || $item === '..') continue;
                        $s = $src . '/' . $item;
                        $d = $dst . '/' . $item;
                        is_dir($s) ? copyDir($s, $d) : copy($s, $d);
                    }
                }
                copyDir($sourcePath, $destPath);
                $log[] = ['ok', "Files copied from <strong>{$sourcePath}</strong> to <strong>{$destPath}</strong>"];
            }

            // 6. Write db.php for new client
            $dbPhpPath = $destPath . '/includes/db.php';
            if (is_dir(dirname($dbPhpPath))) {
                file_put_contents($dbPhpPath, generateDbPhp(MASTER_HOST, $fullDbName, $fullDbUser, $dbPass));
                $log[] = ['ok', "Database config written to <strong>{$clientSlug}/includes/db.php</strong>"];
            } else {
                $log[] = ['warn', "Could not write db.php — folder not found. Write it manually (details below)."];
            }

            // 7. Build result summary
            $result = [
                'success'      => true,
                'log'          => $log,
                'clientSlug'   => $clientSlug,
                'libraryName'  => $libraryName,
                'ownerName'    => $ownerName,
                'loginUrl'     => 'https://' . BASE_DOMAIN . '/' . $clientSlug . '/',
                'adminUser'    => $adminUser,
                'adminPass'    => $adminPass,
                'dbName'       => $fullDbName,
                'dbUser'       => $fullDbUser,
                'dbPass'       => $dbPass,
                'dbPhp'        => generateDbPhp(MASTER_HOST, $fullDbName, $fullDbUser, $dbPass),
            ];

        } catch (Exception $e) {
            $result = ['success' => false, 'error' => $e->getMessage(), 'log' => $log ?? []];
        }
    }
}

// ── Helpers ──────────────────────────────────────────────────────────
function randomPass($len = 12) {
    $chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789@#$!';
    return substr(str_shuffle(str_repeat($chars, 4)), 0, $len);
}
$suggestPass = randomPass();
$suggestSlug = 'client' . date('md');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>OPTMS ERP — New Client Setup</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',sans-serif;background:#f0ede8;min-height:100vh;padding:30px 16px;color:#2c2825}
.wrap{max-width:780px;margin:auto}
.card{background:#fff;border-radius:14px;padding:28px 32px;margin-bottom:20px;box-shadow:0 2px 16px rgba(0,0,0,.08)}
h1{font-size:22px;font-weight:700;color:#4a7c6f;margin-bottom:4px}
.sub{font-size:13px;color:#8a8078;margin-bottom:24px}
h2{font-size:15px;font-weight:600;color:#2c2825;margin:20px 0 12px;padding-bottom:8px;border-bottom:1px solid #ede9e3}
h2:first-child{margin-top:0}
.row{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px}
.row.full{grid-template-columns:1fr}
.field{display:flex;flex-direction:column;gap:5px}
label{font-size:11px;font-weight:600;color:#5a534c;text-transform:uppercase;letter-spacing:.5px}
input,select{padding:9px 12px;border:1px solid #d8d3cc;border-radius:8px;font-size:13px;color:#2c2825;background:#faf8f5;transition:border .2s}
input:focus,select:focus{border-color:#4a7c6f;outline:none;background:#fff}
.hint{font-size:11px;color:#8a8078;margin-top:3px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:11px 22px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;border:none;transition:all .2s}
.btn-primary{background:#4a7c6f;color:#fff}.btn-primary:hover{background:#3d6b5f;transform:translateY(-1px)}
.btn-copy{background:#e4dfd8;color:#5a534c;padding:6px 12px;font-size:11px;border-radius:5px;cursor:pointer;border:none}
.btn-copy:hover{background:#d8d3cc}
.warning{background:#fff8e6;border:1px solid #f0c040;border-radius:8px;padding:12px 16px;font-size:12px;color:#7a5c00;margin-bottom:20px}
.warning strong{color:#5a4000}
/* Log */
.log-item{display:flex;align-items:flex-start;gap:8px;padding:7px 0;border-bottom:1px solid #f0ede8;font-size:13px}
.log-item:last-child{border-bottom:none}
.log-ok{color:#3a7d5e}.log-warn{color:#c47d2b}.log-err{color:#c0444f}
/* Result card */
.result-ok{background:linear-gradient(135deg,#f0fff8,#e8f7f1);border:1px solid rgba(37,211,102,.25)}
.result-err{background:#fff5f5;border:1px solid #ffc0c0}
.cred-box{background:#1a1a2e;color:#e0e6f0;border-radius:10px;padding:18px 20px;font-family:monospace;font-size:13px;line-height:1.9;position:relative;margin:10px 0}
.cred-box .key{color:#7dd3b0}.cred-box .val{color:#fbbf24}
.copy-all{position:absolute;top:10px;right:10px;background:#2d3561;color:#7dd3b0;border:1px solid #3d4571;border-radius:5px;padding:4px 10px;font-size:11px;cursor:pointer;font-family:sans-serif}
.copy-all:hover{background:#3d4571}
.url-box{background:#4a7c6f;color:#fff;border-radius:8px;padding:14px 18px;font-size:15px;font-weight:600;text-align:center;margin:16px 0;letter-spacing:.3px}
.url-box a{color:#fff}
.step-badge{display:inline-block;background:#4a7c6f;color:#fff;border-radius:50%;width:22px;height:22px;font-size:11px;font-weight:700;text-align:center;line-height:22px;margin-right:6px;flex-shrink:0}
.next-steps{background:#f8f9ff;border:1px solid #e0e4f0;border-radius:10px;padding:16px 18px}
.next-step{display:flex;align-items:flex-start;gap:10px;padding:8px 0;border-bottom:1px solid #eaecf8;font-size:13px}
.next-step:last-child{border-bottom:none}
@media(max-width:580px){.row{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="wrap">

<?php if ($result && $result['success']): ?>
<!-- ═══ SUCCESS RESULT ═══ -->
<div class="card result-ok">
  <h1>✅ Client Setup Complete!</h1>
  <p class="sub">Library ERP is ready for <strong><?= htmlspecialchars($result['libraryName']) ?></strong></p>

  <!-- Setup log -->
  <div style="margin-bottom:20px">
    <?php foreach ($result['log'] as [$type, $msg]): ?>
    <div class="log-item">
      <span class="log-<?= $type ?>">
        <?= $type==='ok' ? '✓' : ($type==='warn' ? '⚠' : '✗') ?>
      </span>
      <span><?= $msg ?></span>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Login URL -->
  <div class="url-box">
    🌐 Client Login URL: <a href="<?= $result['loginUrl'] ?>" target="_blank"><?= $result['loginUrl'] ?></a>
  </div>

  <!-- Credentials -->
  <h2>📋 Client Credentials — Send These to Client</h2>
  <div class="cred-box" id="credBox">
    <button class="copy-all" onclick="copyCreds()">Copy All</button>
    <div><span class="key">Library Name  : </span><span class="val"><?= htmlspecialchars($result['libraryName']) ?></span></div>
    <div><span class="key">Login URL     : </span><span class="val"><?= $result['loginUrl'] ?></span></div>
    <div><span class="key">Username      : </span><span class="val"><?= htmlspecialchars($result['adminUser']) ?></span></div>
    <div><span class="key">Password      : </span><span class="val"><?= htmlspecialchars($result['adminPass']) ?></span></div>
    <div style="margin-top:8px;border-top:1px solid #2d3561;padding-top:8px">
    <span class="key" style="color:#888">Database      : </span><span style="color:#888"><?= $result['dbName'] ?></span></div>
    <div><span class="key" style="color:#888">DB User       : </span><span style="color:#888"><?= $result['dbUser'] ?></span></div>
    <div><span class="key" style="color:#888">DB Pass       : </span><span style="color:#888"><?= htmlspecialchars($result['dbPass']) ?></span></div>
  </div>

  <!-- db.php manual copy -->
  <details style="margin-top:14px">
    <summary style="cursor:pointer;font-size:12px;color:#5a534c;font-weight:600">
      📄 db.php content (if file copy failed — paste manually)
    </summary>
    <textarea readonly style="width:100%;margin-top:8px;padding:12px;border-radius:8px;border:1px solid #d8d3cc;font-family:monospace;font-size:11px;background:#f8f8f8;height:160px"><?= htmlspecialchars($result['dbPhp']) ?></textarea>
  </details>

  <!-- Next steps -->
  <h2>🚀 Next Steps</h2>
  <div class="next-steps">
    <div class="next-step">
      <span class="step-badge">1</span>
      <div><strong>Send login details to client</strong> — URL, username, password above</div>
    </div>
    <div class="next-step">
      <span class="step-badge">2</span>
      <div><strong>Setup WhatsApp</strong> — Client logs in → Settings → WhatsApp API → add Meta Phone ID + Token</div>
    </div>
    <div class="next-step">
      <span class="step-badge">3</span>
      <div><strong>Client customizes</strong> — Settings → Library Name, Address, Phone, Fee structure</div>
    </div>
    <div class="next-step">
      <span class="step-badge">4</span>
      <div><strong>Delete this setup file</strong> — Remove <code>setup_client.php</code> from your server for security!</div>
    </div>
  </div>

  <div style="margin-top:20px;display:flex;gap:10px;flex-wrap:wrap">
    <a href="<?= $result['loginUrl'] ?>" target="_blank" class="btn btn-primary">🔗 Open Client ERP</a>
    <a href="?key=<?= SETUP_SECRET ?>" class="btn" style="background:#e4dfd8;color:#5a534c">+ Setup Another Client</a>
  </div>
</div>

<script>
function copyCreds() {
  const text = `Library Name: <?= addslashes(htmlspecialchars($result['libraryName'])) ?>\nLogin URL: <?= $result['loginUrl'] ?>\nUsername: <?= addslashes($result['adminUser']) ?>\nPassword: <?= addslashes($result['adminPass']) ?>`;
  navigator.clipboard.writeText(text).then(() => {
    const btn = document.querySelector('.copy-all');
    btn.textContent = 'Copied!';
    setTimeout(() => btn.textContent = 'Copy All', 2000);
  });
}
</script>

<?php elseif ($result && !$result['success']): ?>
<!-- ═══ ERROR ═══ -->
<div class="card result-err">
  <h1>❌ Setup Failed</h1>
  <p style="color:#c0444f;margin:10px 0 16px;font-size:13px"><?= htmlspecialchars($result['error']) ?></p>
  <?php if (!empty($result['log'])): ?>
  <div style="margin-bottom:14px">
    <?php foreach ($result['log'] as [$type, $msg]): ?>
    <div class="log-item"><span class="log-<?=$type?>"><?=$type==='ok'?'✓':($type==='warn'?'⚠':'✗')?></span><span><?=$msg?></span></div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <a href="?key=<?= SETUP_SECRET ?>" class="btn btn-primary">← Try Again</a>
</div>

<?php else: ?>
<!-- ═══ SETUP FORM ═══ -->
<div class="card">
  <h1>📚 OPTMS ERP — New Client Setup</h1>
  <p class="sub">Fill this form once per client. Everything is created automatically.</p>

  <div class="warning">
    <strong>⚠ Security:</strong> Delete <code>setup_client.php</code> from your server after all clients are set up.
    Also make sure <code>MASTER_ROOT_PASS</code> is set correctly at the top of this file before running.
  </div>

  <?php if (!empty($errors)): ?>
  <div style="background:#fff5f5;border:1px solid #ffc0c0;border-radius:8px;padding:12px 16px;margin-bottom:20px">
    <?php foreach ($errors as $e): ?>
    <div style="color:#c0444f;font-size:13px;margin-bottom:4px">❌ <?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <form method="POST">
    <input type="hidden" name="step" value="create">

    <!-- Library Info -->
    <h2>🏫 Library Information</h2>
    <div class="row">
      <div class="field">
        <label>Library Name *</label>
        <input name="library_name" placeholder="Raj Study Library" value="<?= htmlspecialchars($_POST['library_name'] ?? '') ?>" required>
      </div>
      <div class="field">
        <label>Owner / Contact Name</label>
        <input name="owner_name" placeholder="Rajesh Kumar" value="<?= htmlspecialchars($_POST['owner_name'] ?? '') ?>">
      </div>
    </div>
    <div class="row">
      <div class="field">
        <label>Phone / WhatsApp</label>
        <input name="phone" placeholder="+91 98765 43210" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Email</label>
        <input name="email" type="email" placeholder="raj@library.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>
    </div>
    <div class="row full">
      <div class="field">
        <label>Address</label>
        <input name="addr" placeholder="123 Main Street, City, State - 000000" value="<?= htmlspecialchars($_POST['addr'] ?? '') ?>">
      </div>
    </div>

    <!-- Folder & URL -->
    <h2>🌐 URL & Folder</h2>
    <div class="row">
      <div class="field">
        <label>Client Folder Name *</label>
        <input name="client_slug" placeholder="<?= $suggestSlug ?>" value="<?= htmlspecialchars($_POST['client_slug'] ?? '') ?>" pattern="[a-z0-9_]+" required>
        <span class="hint">Lowercase, no spaces. URL will be: <strong><?= BASE_DOMAIN ?>/<span id="slugPreview"><?= $suggestSlug ?></span>/</strong></span>
      </div>
      <div class="field">
        <label>Copy Files From (existing ERP folder)</label>
        <input name="copy_from" placeholder="library-1" value="<?= htmlspecialchars($_POST['copy_from'] ?? 'library-1') ?>">
        <span class="hint">Name of your master ERP folder in public_html</span>
      </div>
    </div>

    <!-- Admin Login -->
    <h2>🔐 Admin Login for Client</h2>
    <div class="row">
      <div class="field">
        <label>Admin Username *</label>
        <input name="admin_username" placeholder="admin" value="<?= htmlspecialchars($_POST['admin_username'] ?? 'admin') ?>" required>
      </div>
      <div class="field">
        <label>Admin Password * <button type="button" class="btn-copy" onclick="document.querySelector('[name=admin_password]').value='<?= $suggestPass ?>'">Use Suggested</button></label>
        <input name="admin_password" placeholder="Min 6 characters" value="<?= htmlspecialchars($_POST['admin_password'] ?? '') ?>" required>
        <span class="hint">Suggested: <code><?= $suggestPass ?></code></span>
      </div>
    </div>

    <!-- Database -->
    <h2>🗄 Database Configuration</h2>
    <div class="row">
      <div class="field">
        <label>Database Name Suffix *</label>
        <input name="db_suffix" placeholder="<?= $suggestSlug ?>" value="<?= htmlspecialchars($_POST['db_suffix'] ?? '') ?>" pattern="[a-z0-9_]+" required>
        <span class="hint">Full DB name will be: <strong><?= explode('_', MASTER_ROOT_USER)[0] ?>_<span id="dbPreview"><?= $suggestSlug ?></span></strong></span>
      </div>
      <div class="field">
        <label>Database Username *</label>
        <input name="db_user" placeholder="<?= $suggestSlug ?>user" value="<?= htmlspecialchars($_POST['db_user'] ?? '') ?>" pattern="[a-z0-9_]+" required>
        <span class="hint">Full user: <strong><?= explode('_', MASTER_ROOT_USER)[0] ?>_<span id="userPreview"><?= $suggestSlug ?>user</span></strong></span>
      </div>
    </div>
    <div class="row">
      <div class="field">
        <label>Database Password *</label>
        <input name="db_pass" placeholder="Strong password" value="<?= htmlspecialchars($_POST['db_pass'] ?? '') ?>" required>
        <span class="hint">Used only internally — client never sees this</span>
      </div>
      <div class="field" style="justify-content:flex-end;padding-bottom:4px">
        <label>&nbsp;</label>
        <button type="button" class="btn-copy" style="padding:9px 14px;font-size:12px" onclick="
          var p='<?= randomPass(14) ?>';
          document.querySelector('[name=db_pass]').value=p;
        ">Generate DB Password</button>
      </div>
    </div>

    <div style="margin-top:24px;display:flex;gap:12px;align-items:center">
      <button type="submit" class="btn btn-primary" style="font-size:14px;padding:13px 28px">
        🚀 Create Client Library
      </button>
      <span style="font-size:12px;color:#8a8078">This creates the database, tables, copies files and configures everything automatically.</span>
    </div>
  </form>
</div>

<script>
// Live preview of slug/db names
document.querySelector('[name=client_slug]').addEventListener('input', function() {
  const v = this.value.toLowerCase().replace(/[^a-z0-9_]/g,'');
  this.value = v;
  document.getElementById('slugPreview').textContent = v || '<?= $suggestSlug ?>';
  if (!document.querySelector('[name=db_suffix]').value) {
    document.getElementById('dbPreview').textContent = v || '<?= $suggestSlug ?>';
  }
  if (!document.querySelector('[name=db_user]').value) {
    document.getElementById('userPreview').textContent = (v||'<?= $suggestSlug ?>') + 'user';
  }
  if (!document.querySelector('[name=db_suffix]').value) {
    document.querySelector('[name=db_suffix]').placeholder = v;
  }
});
document.querySelector('[name=db_suffix]').addEventListener('input', function() {
  const v = this.value.toLowerCase().replace(/[^a-z0-9_]/g,'');
  this.value = v;
  document.getElementById('dbPreview').textContent = v || '...';
});
document.querySelector('[name=db_user]').addEventListener('input', function() {
  const v = this.value.toLowerCase().replace(/[^a-z0-9_]/g,'');
  this.value = v;
  document.getElementById('userPreview').textContent = v ? v : '...';
});
</script>

<?php endif; ?>

<div style="text-align:center;font-size:11px;color:#8a8078;margin-top:10px">
  OPTMS Tech ERP · Client Setup Tool · <strong style="color:#c0444f">Delete this file after use!</strong>
</div>

</div>
</body>
</html>
