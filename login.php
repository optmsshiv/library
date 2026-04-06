<?php
session_start();
// Already logged in? Go to dashboard
if (!empty($_SESSION['staff_id'])) {
    header('Location: index');
    exit;
}

// Pre-fill username from "Remember Me" cookie
$remembered_username = '';
if (!empty($_COOKIE['optms_remember'])) {
    $remembered_username = htmlspecialchars(strip_tags($_COOKIE['optms_remember']));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/includes/db.php';
    $username    = trim($_POST['username'] ?? '');
    $password    = $_POST['password'] ?? '';
    $remember_me = !empty($_POST['remember_me']);

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

            // Handle Remember Me cookie (30 days)
            if ($remember_me) {
                setcookie('optms_remember', $username, [
                    'expires'  => time() + (30 * 24 * 60 * 60),
                    'path'     => '/',
                    'httponly' => true,
                    'samesite' => 'Strict',
                ]);
            } else {
                // Clear cookie if user unchecked it
                setcookie('optms_remember', '', ['expires' => time() - 3600, 'path' => '/']);
            }

            header('Location: index');
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

        /* ── Loading bar ── */
        .progress-bar{
            height:3px;
            background:var(--sf3);
            overflow:hidden;
            opacity:0;
            transition:opacity .2s;
        }
        .progress-bar.active{ opacity:1; }
        .progress-fill{
            height:100%;
            width:0%;
            background:linear-gradient(90deg, var(--ac), var(--ac2), #7c5cbf);
            border-radius:0 2px 2px 0;
            transition:width .4s ease;
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

        /* forgot password link */
        .forgot-row{
            display:flex;justify-content:space-between;align-items:center;
            margin-top:1px;margin-bottom:14px;
        }
        .forgot-link{
            font-size:11.5px;color:var(--ac);
            background:none;border:none;cursor:pointer;
            font-family:var(--fb);font-weight:500;
            text-decoration:underline;text-underline-offset:3px;
            padding:0;transition:color .2s;
        }
        .forgot-link:hover{color:var(--ac2);}

        /* Remember Me checkbox */
        .remember-label{
            display:flex;align-items:center;gap:7px;
            cursor:pointer;user-select:none;
            font-size:11.5px;color:var(--tx2);font-weight:500;
        }
        .remember-label input[type=checkbox]{
            display:none;
        }
        .custom-cb{
            width:16px;height:16px;flex-shrink:0;
            border:1.5px solid var(--br2);
            border-radius:4px;
            background:var(--sf2);
            display:flex;align-items:center;justify-content:center;
            transition:border-color .2s, background .2s, box-shadow .2s;
            position:relative;
        }
        .custom-cb svg{
            width:9px;height:9px;
            stroke:#fff;stroke-width:2.5;stroke-linecap:round;stroke-linejoin:round;
            fill:none;opacity:0;transform:scale(.5);
            transition:opacity .15s, transform .15s;
        }
        .remember-label input[type=checkbox]:checked ~ .custom-cb{
            background:var(--ac);border-color:var(--ac);
            box-shadow:0 0 0 3px rgba(74,124,111,.15);
        }
        .remember-label input[type=checkbox]:checked ~ .custom-cb svg{
            opacity:1;transform:scale(1);
        }

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
            display:flex;align-items:center;justify-content:center;gap:8px;
            position:relative;overflow:hidden;
        }
        .btn-login:hover{transform:translateY(-1px);box-shadow:0 4px 16px rgba(74,124,111,.35);}
        .btn-login:active{transform:translateY(0);}
        .btn-login:disabled{opacity:.7;cursor:not-allowed;transform:none;}

        /* spinner inside button */
        .btn-spinner{
            width:14px;height:14px;
            border:2px solid rgba(255,255,255,.4);
            border-top-color:#fff;
            border-radius:50%;
            animation:spin .7s linear infinite;
            display:none;
            flex-shrink:0;
        }
        @keyframes spin{to{transform:rotate(360deg)}}
        .btn-login.loading .btn-spinner{display:block;}
        .btn-login.loading .btn-label{opacity:.85;}

        .login-foot{
            text-align:center;margin-top:20px;
            font-size:11px;color:var(--tx3);font-family:var(--fm);
        }

        /* ── Forgot Password Modal ── */
        .modal-overlay{
            position:fixed;inset:0;
            background:rgba(44,40,37,.45);
            backdrop-filter:blur(4px);
            display:flex;align-items:center;justify-content:center;
            z-index:100;padding:16px;
            opacity:0;pointer-events:none;
            transition:opacity .25s;
        }
        .modal-overlay.open{opacity:1;pointer-events:all;}
        .modal{
            background:var(--sf);
            border:1px solid var(--br);
            border-radius:16px;
            box-shadow:var(--sh2);
            width:100%;max-width:360px;
            overflow:hidden;
            transform:translateY(20px) scale(.97);
            transition:transform .25s ease;
        }
        .modal-overlay.open .modal{transform:translateY(0) scale(1);}
        .modal-head{
            padding:20px 22px 16px;
            border-bottom:1px solid var(--br);
            display:flex;align-items:flex-start;justify-content:space-between;
        }
        .modal-head-text .modal-title{
            font-family:var(--fd);font-size:18px;color:var(--tx);margin-bottom:3px;
        }
        .modal-head-text .modal-desc{
            font-size:11.5px;color:var(--tx3);line-height:1.5;
        }
        .modal-close{
            background:none;border:none;cursor:pointer;
            font-size:18px;color:var(--tx3);line-height:1;
            padding:2px 4px;border-radius:4px;
            transition:color .2s, background .2s;
            margin-left:12px;flex-shrink:0;
        }
        .modal-close:hover{color:var(--tx);background:var(--sf3);}
        .modal-body{padding:20px 22px 22px;}
        .modal-body .fgi{margin-bottom:14px;}

        /* step indicators */
        .modal-steps{display:flex;gap:6px;margin-bottom:18px;}
        .modal-step{
            flex:1;height:3px;border-radius:2px;
            background:var(--sf3);
            transition:background .3s;
        }
        .modal-step.done{background:var(--ac);}
        .modal-step.active{background:linear-gradient(90deg,var(--ac),var(--ac2));}

        .modal-btn{
            width:100%;padding:9px;
            background:linear-gradient(135deg,var(--ac),var(--ac2));
            color:#fff;border:none;border-radius:var(--r2);
            font-size:13px;font-weight:600;font-family:var(--fb);
            cursor:pointer;transition:all .2s;
            box-shadow:0 2px 8px rgba(74,124,111,.22);
            display:flex;align-items:center;justify-content:center;gap:7px;
        }
        .modal-btn:hover{transform:translateY(-1px);box-shadow:0 4px 14px rgba(74,124,111,.32);}
        .modal-btn:disabled{opacity:.65;cursor:not-allowed;transform:none;}

        .info-box{
            background:rgba(74,124,111,.07);
            border:1px solid rgba(74,124,111,.2);
            border-radius:var(--r2);
            padding:10px 12px;
            color:var(--ac);font-size:12px;
            margin-bottom:14px;line-height:1.5;
        }
        .success-box{
            text-align:center;padding:10px 0 4px;
        }
        .success-icon{font-size:36px;margin-bottom:10px;}
        .success-title{font-family:var(--fd);font-size:17px;color:var(--tx);margin-bottom:6px;}
        .success-msg{font-size:12.5px;color:var(--tx3);line-height:1.6;}
    </style>
</head>
<body>
<div class="login-wrap">
    <div class="login-card">
        <!-- Loading bar at top of card -->
        <div class="progress-bar" id="progressBar">
            <div class="progress-fill" id="progressFill"></div>
        </div>

        <div class="login-head">
            <div class="logo-ic">📚</div>
            <div class="login-title">OPTMS Library</div>
            <div class="login-sub">Staff Portal · ERP v6</div>
        </div>
        <div class="login-body">
            <?php if ($error): ?>
                <div class="error-box">⚠️ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST" autocomplete="on" id="loginForm">
                <div class="fgi">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username"
                           placeholder="Enter your username"
                           value="<?= htmlspecialchars($_POST['username'] ?? $remembered_username) ?>"
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
                <!-- Remember Me + Forgot password row -->
                <div class="forgot-row">
                    <label class="remember-label">
                        <input type="checkbox" name="remember_me" id="rememberMe" <?= $remembered_username ? 'checked' : '' ?>>
                        <span class="custom-cb">
              <svg viewBox="0 0 10 8"><polyline points="1,4 4,7 9,1"/></svg>
            </span>
                        Remember me
                    </label>
                    <button type="button" class="forgot-link" onclick="openForgot()">Forgot password?</button>
                </div>
                <button type="submit" class="btn-login" id="loginBtn">
                    <span class="btn-spinner"></span>
                    <span class="btn-label">Sign In →</span>
                </button>
            </form>
        </div>
    </div>
    <div class="login-foot">OPTMS Tech Study Library · Madhepura, Bihar</div>
</div>

<!-- ── Forgot Password Modal ── -->
<div class="modal-overlay" id="forgotOverlay" onclick="handleOverlayClick(event)">
    <div class="modal" id="forgotModal">
        <div class="modal-head">
            <div class="modal-head-text">
                <div class="modal-title" id="modalTitle">Forgot Password</div>
                <div class="modal-desc" id="modalDesc">Enter your registered username to reset your password.</div>
            </div>
            <button class="modal-close" onclick="closeForgot()" title="Close">✕</button>
        </div>
        <div class="modal-body">
            <!-- Step indicators -->
            <div class="modal-steps">
                <div class="modal-step active" id="step1ind"></div>
                <div class="modal-step" id="step2ind"></div>
                <div class="modal-step" id="step3ind"></div>
            </div>

            <!-- Step 1: Enter username -->
            <div id="fpStep1">
                <div class="fgi">
                    <label for="fpUsername">Username</label>
                    <input type="text" id="fpUsername" placeholder="Enter your username" autocomplete="off">
                </div>
                <button class="modal-btn" onclick="fpStep1Next()">Continue →</button>
            </div>

            <!-- Step 2: Enter registered email -->
            <div id="fpStep2" style="display:none">
                <div class="info-box">📋 Enter the email address associated with this account and we'll send a reset link.</div>
                <div class="fgi">
                    <label for="fpEmail">Registered Email</label>
                    <input type="email" id="fpEmail" placeholder="staff@optms.edu.in" autocomplete="off">
                </div>
                <button class="modal-btn" id="fpSendBtn" onclick="fpStep2Send()">Send Reset Link</button>
            </div>

            <!-- Step 3: Success -->
            <div id="fpStep3" style="display:none">
                <div class="success-box">
                    <div class="success-icon">✅</div>
                    <div class="success-title">Check Your Email</div>
                    <div class="success-msg">A password reset link has been sent to your registered email. It will expire in <strong>30 minutes</strong>.<br><br>If you don't see it, check your spam folder or contact the admin.</div>
                </div>
                <br>
                <button class="modal-btn" onclick="closeForgot()" style="background:var(--sf3);color:var(--tx);box-shadow:none;">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    /* ── Password toggle ── */
    function togglePw() {
        const inp = document.getElementById('password');
        inp.type = inp.type === 'password' ? 'text' : 'password';
    }

    /* ── Login loading bar ── */
    const loginForm   = document.getElementById('loginForm');
    const loginBtn    = document.getElementById('loginBtn');
    const progressBar = document.getElementById('progressBar');
    const progressFill= document.getElementById('progressFill');

    loginForm.addEventListener('submit', function(e) {
        // Basic HTML5 validation first
        if (!loginForm.checkValidity()) return;

        // Activate loading state
        loginBtn.disabled = true;
        loginBtn.classList.add('loading');
        loginBtn.querySelector('.btn-label').textContent = 'Signing in…';

        progressBar.classList.add('active');

        // Animate progress in stages
        let pct = 0;
        const stages = [
            {target: 30, delay: 80},
            {target: 60, delay: 250},
            {target: 85, delay: 500},
            {target: 95, delay: 900},
        ];
        stages.forEach(({target, delay}) => {
            setTimeout(() => {
                progressFill.style.width = target + '%';
            }, delay);
        });
        // Let the form submit naturally — page will reload/redirect
    });

    /* ── Forgot Password Modal ── */
    let fpCurrentStep = 1;

    function openForgot() {
        resetForgotModal();
        document.getElementById('forgotOverlay').classList.add('open');
        setTimeout(() => document.getElementById('fpUsername').focus(), 280);
    }
    function closeForgot() {
        document.getElementById('forgotOverlay').classList.remove('open');
    }
    function handleOverlayClick(e) {
        if (e.target === document.getElementById('forgotOverlay')) closeForgot();
    }

    function setStep(n) {
        fpCurrentStep = n;
        document.getElementById('fpStep1').style.display = n===1 ? '' : 'none';
        document.getElementById('fpStep2').style.display = n===2 ? '' : 'none';
        document.getElementById('fpStep3').style.display = n===3 ? '' : 'none';

        ['step1ind','step2ind','step3ind'].forEach((id, i) => {
            const el = document.getElementById(id);
            el.className = 'modal-step';
            if (i+1 < n)      el.classList.add('done');
            else if (i+1 === n) el.classList.add('active');
        });

        const titles = ['Forgot Password','Verify Identity','Email Sent'];
        const descs  = [
            'Enter your registered username to reset your password.',
            'Confirm your registered email address.',
            'A reset link has been dispatched to your inbox.'
        ];
        document.getElementById('modalTitle').textContent = titles[n-1];
        document.getElementById('modalDesc').textContent  = descs[n-1];
    }

    function fpStep1Next() {
        const val = document.getElementById('fpUsername').value.trim();
        if (!val) {
            document.getElementById('fpUsername').focus();
            document.getElementById('fpUsername').style.borderColor = 'var(--ro)';
            setTimeout(() => document.getElementById('fpUsername').style.borderColor = '', 1200);
            return;
        }
        setStep(2);
        setTimeout(() => document.getElementById('fpEmail').focus(), 100);
    }

    async function fpStep2Send() {
    const email = document.getElementById('fpEmail').value.trim();
    const username = document.getElementById('fpUsername').value.trim();
    const emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    if (!emailOk) {
        document.getElementById('fpEmail').focus();
        document.getElementById('fpEmail').style.borderColor = 'var(--ro)';
        setTimeout(() => document.getElementById('fpEmail').style.borderColor = '', 1200);
        return;
    }
    const btn = document.getElementById('fpSendBtn');
    btn.disabled = true;
    btn.textContent = 'Sending…';

    try {
        const res = await fetch('/api/index.php?action=forgot_password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, email })
        });
        const data = await res.json();
        if (data.error) {
            btn.disabled = false;
            btn.textContent = 'Send Reset Link';

    // Show error message below the email input
    let errEl = document.getElementById('fpEmailErr');
    if (!errEl) {
        errEl = document.createElement('div');
        errEl.id = 'fpEmailErr';
        errEl.style.cssText = 'color:var(--ro);font-size:11.5px;margin-top:5px;padding:8px 10px;background:rgba(192,68,79,.08);border:1px solid rgba(192,68,79,.25);border-radius:6px;';
        document.getElementById('fpEmail').parentNode.appendChild(errEl);
                  }
                  errEl.textContent = '⚠ ' + (typeof data.error === 'string' ? data.error : 'The email address does not match our records.');
                  document.getElementById('fpEmail').style.borderColor = 'var(--ro)';
                  document.getElementById('fpEmail').focus();
                  setTimeout(() => {
                      document.getElementById('fpEmail').style.borderColor = '';
                      if (errEl) errEl.remove();
                  }, 4000);
        } else {
            setStep(3);
        }
    } catch(e) {
        btn.disabled = false;
        btn.textContent = 'Send Reset Link';
    }
   }

    function resetForgotModal() {
        document.getElementById('fpUsername').value = '';
        document.getElementById('fpEmail').value    = '';
        document.getElementById('fpSendBtn').disabled = false;
        document.getElementById('fpSendBtn').textContent = 'Send Reset Link';
        setStep(1);
    }

    /* Close modal on Escape */
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeForgot();
    });
</script>
</body>
</html>