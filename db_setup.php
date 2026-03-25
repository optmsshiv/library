<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

$error = "";
$info = "";
$step = 1;

$lockFile = __DIR__ . '/install.lock';

// 🚫 Stop if installed
if (file_exists($lockFile)) {
    die("⚠️ Installer locked. Delete install.lock to reinstall.");
}

// 🔐 Secure install key
$INSTALL_KEY = getenv('INSTALL_KEY') ?: "CHANGE_THIS_KEY";

if (!isset($_GET['key']) || $_GET['key'] !== $INSTALL_KEY) {
    die("⛔ Unauthorized Access");
}

// 🔐 CSRF
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

function clean($data){
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// STEP 1
if (isset($_POST['test_db'])) {

    if ($_POST['csrf'] !== $_SESSION['csrf']) {
        die("Invalid CSRF token");
    }

    $host = clean($_POST['host']);
    $user = clean($_POST['user']);
    $pass = $_POST['pass'];
    $dbname = clean($_POST['dbname']);

    $conn = @new mysqli($host, $user, $pass, $dbname);

    if ($conn->connect_error) {
        $error = "❌ Connection failed: " . $conn->connect_error;
    } else {
        $_SESSION['db'] = compact('host','user','pass','dbname');
        $step = 2;
    }
}

// STEP 2
if (isset($_POST['create_admin'])) {

    if ($_POST['csrf'] !== $_SESSION['csrf']) {
        die("Invalid CSRF token");
    }

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

            // Run schema if needed
            $tableCheck = $conn->query("SHOW TABLES LIKE 'admins'");

            if ($tableCheck->num_rows == 0) {
                $sqlPath = __DIR__ . '/sql/schema.sql';

                if (file_exists($sqlPath)) {
                    $sql = file_get_contents($sqlPath);

                    if (!empty($sql)) {
                        if (!$conn->multi_query($sql)) {
                            die("❌ Schema error: " . $conn->error);
                        }

                        do {
                            if ($result = $conn->store_result()) {
                                $result->free();
                            }
                        } while ($conn->more_results() && $conn->next_result());
                    }
                }
            }

            $name = clean($_POST['admin_name']);
            $email = clean($_POST['admin_email']);
            $password = $_POST['admin_password'];

            if (!$name || !$email || !$password) {
                $error = "All fields required.";
                $step = 2;
            } elseif (strlen($password) < 6) {
                $error = "Password must be 6+ characters.";
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

                    if ($stmt->execute()) {

                        $config = "<?php
                                    define('DB_HOST','{$db['host']}');
                                    define('DB_USER','{$db['user']}');
                                    define('DB_PASS','{$db['pass']}');
                                    define('DB_NAME','{$db['dbname']}');
                                    ?>";

                        file_put_contents(__DIR__.'/config.php', $config);
                        chmod(__DIR__.'/config.php', 0640);

                        file_put_contents($lockFile, 'installed');

                        session_destroy();
                        $step = 3;

                    } else {
                        $error = "Insert failed: " . $stmt->error;
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
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family: 'Inter', 'Segoe UI', sans-serif;
}

body{
    background: linear-gradient(135deg,#667eea,#764ba2);
    height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
}

/* Glass Card */
.card{
    background: rgba(255,255,255,0.85);
    backdrop-filter: blur(12px);
    padding:40px;
    width:100%;
    max-width:460px;
    border-radius:20px;
    box-shadow:0 25px 80px rgba(0,0,0,0.25);
    animation: fadeIn 0.6s ease;
}

/* Header */
h1{
    text-align:center;
    font-size:26px;
    font-weight:700;
}

.sub{
    text-align:center;
    color:#666;
    margin-bottom:25px;
}

/* Steps Indicator */
.steps{
    display:flex;
    justify-content:space-between;
    margin-bottom:25px;
}

.step{
    flex:1;
    text-align:center;
    font-size:12px;
    position:relative;
    color:#aaa;
}

.step.active{
    color:#667eea;
    font-weight:600;
}

.step::after{
    content:'';
    position:absolute;
    top:10px;
    right:-50%;
    width:100%;
    height:2px;
    background:#ddd;
}

.step:last-child::after{
    display:none;
}

.step.active::after{
    background:#667eea;
}

/* Inputs */
.form-group{
    margin-bottom:15px;
}

input{
    width:100%;
    padding:13px;
    border-radius:10px;
    border:1px solid #ddd;
    outline:none;
    transition:0.3s;
}

input:focus{
    border-color:#667eea;
    box-shadow:0 0 8px rgba(102,126,234,0.3);
}

/* Button */
button{
    width:100%;
    padding:13px;
    background:linear-gradient(135deg,#667eea,#5a67d8);
    color:#fff;
    border:none;
    border-radius:10px;
    font-size:15px;
    cursor:pointer;
    transition:0.3s;
}

button:hover{
    transform:translateY(-2px);
    box-shadow:0 8px 20px rgba(0,0,0,0.2);
}

/* Loader */
button.loading{
    opacity:0.7;
    pointer-events:none;
}

button.loading::after{
    content:'⏳ Processing...';
}

/* Messages */
.msg {
    padding: 12px 14px;
    margin-bottom: 15px;
    border-radius: 10px;
    font-size: 14px;
    animation: fadeIn 0.4s ease;
}

.success {
    background: linear-gradient(135deg, #d4f8e8, #e0ffe5);
    color: #0a7d2c;
    border-left: 5px solid #28a745;
}

.error {
    background: #ffe0e0;
    color: #d8000c;
    border-left: 5px solid #ff4d4d;
}

/* Success Box */
.success-box{
    text-align:center;
    padding:25px;
    border-radius:15px;
    background:linear-gradient(135deg,#e0ffe5,#d4f8e8);
    box-shadow:0 0 20px rgba(40,167,69,0.3);
}

a.btn{
    display:inline-block;
    margin-top:15px;
    padding:12px 25px;
    background:linear-gradient(135deg,#28a745,#218838);
    color:#fff;
    border-radius:8px;
    text-decoration:none;
    transition:0.3s;
}

a.btn:hover{
    transform:translateY(-2px);
    box-shadow:0 8px 20px rgba(0,0,0,0.2);
}

/* Footer */
.footer{
    text-align:center;
    margin-top:20px;
    font-size:12px;
    color:#888;
}

/* Animation */
@keyframes fadeIn{
    from{opacity:0;transform:translateY(15px);}
    to{opacity:1;transform:translateY(0);}
}
</style>
</head>

<body>

<div class="card">
<h1>🚀 OPTMS ERP</h1>
<div class="steps">
    <div class="step <?= $step==1?'active':'' ?>">Database</div>
    <div class="step <?= $step==2?'active':'' ?>">Admin</div>
    <div class="step <?= $step==3?'active':'' ?>">Finish</div>
</div>
<p class="sub">Secure Installer</p>

<?php if ($installed): ?>
<div class="msg success glow" style="padding:20px;">⚠️ Already Installed</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="msg error"><?= $error ?></div>
<?php endif; ?>

<?php if (!empty($info)): ?>
<div class="msg success glow" style="padding:20px;"><?= $info ?></div>
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
<div class="success-box">
    <h2>🎉 Installation Complete</h2>
    <p>Your ERP is ready to use</p>
    <a class="btn" href="login.php">Go to Dashboard →</a>
</div>
<?php endif; ?>

<div class="footer">OPTMS Tech © <?= date('Y') ?></div>
</div>


<script>
document.querySelectorAll("form").forEach(form=>{
    form.addEventListener("submit",()=>{
        let btn = form.querySelector("button");
        btn.classList.add("loading");
        btn.innerText = "Processing...";
    });
});
</script>

</body>
</html>