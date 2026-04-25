<?php
// ══════════════════════════════════════════════════════════
//  pay.php — Student UPI Payment Page
//  Linked from WhatsApp: pay.php?token=XXXXX
// ══════════════════════════════════════════════════════════
error_reporting(0);
@ini_set('display_errors', '0');

require_once __DIR__ . '/includes/db.php';
$db = getDB();

// ── Auto-create payment_links table if missing ──
$db->exec("CREATE TABLE IF NOT EXISTS payment_links (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    token       VARCHAR(64)  NOT NULL UNIQUE,
    student_id  VARCHAR(32)  NOT NULL,
    amount      INT          NOT NULL,
    upi_id      VARCHAR(128) NOT NULL,
    note        VARCHAR(255) DEFAULT '',
    status      VARCHAR(16)  NOT NULL DEFAULT 'pending',
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    paid_at     TIMESTAMP    NULL,
    INDEX idx_token (token),
    INDEX idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Get token from URL ──
$token = trim($_GET['token'] ?? '');

if (!$token) {
    die(renderError('Invalid Link', 'No payment token found in this link. Please ask the library to resend your payment link.'));
}

// ── Look up token ──
$stmt = $db->prepare("SELECT pl.*, s.fname, s.lname, s.phone, s.id as student_id,
    s.net_fee, s.paid_amt, s.fee_status, b.name as batch_name
    FROM payment_links pl
    JOIN students s ON s.id = pl.student_id
    LEFT JOIN batches b ON b.id = s.batch_id
    WHERE pl.token = ? LIMIT 1");
$stmt->execute([$token]);
$link = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$link) {
    die(renderError('Link Not Found', 'This payment link is invalid or has expired. Please ask the library to generate a new link for you.'));
}

// ── Get library settings ──
$settings = $db->query("SELECT * FROM settings WHERE id=1")->fetch(PDO::FETCH_ASSOC);
$libName   = htmlspecialchars($settings['name']   ?? 'NAYI UDAAN LIBRARY');
$libPhone  = htmlspecialchars($settings['phone']  ?? '');
$libAddr   = htmlspecialchars($settings['addr']   ?? '');
$logoUrl   = $settings['logo_url'] ?? '';
$upiId     = $link['upi_id'];

// ── Payment details ──
$studentName = htmlspecialchars($link['fname'] . ' ' . $link['lname']);
$amount      = (int)$link['amount'];
$note        = urlencode('Fee-' . $link['student_id'] . '-' . ($link['note'] ?? 'Monthly'));
$alreadyPaid = ($link['status'] === 'paid');

// ── Build UPI deep links ──
$upiBase   = "upi://pay?pa={$upiId}&pn=" . urlencode($libName) . "&am={$amount}&tn={$note}&cu=INR";
$gpayLink  = "intent://pay?pa={$upiId}&pn=" . urlencode($libName) . "&am={$amount}&tn={$note}&cu=INR#Intent;scheme=upi;package=com.google.android.apps.nbu.paisa.user;end";
$phonepeLink = "intent://pay?pa={$upiId}&pn=" . urlencode($libName) . "&am={$amount}&tn={$note}&cu=INR#Intent;scheme=upi;package=com.phonepe.app;end";
$paytmLink = "intent://pay?pa={$upiId}&pn=" . urlencode($libName) . "&am={$amount}&tn={$note}&cu=INR#Intent;scheme=upi;package=net.one97.paytm;end";
$bhimLink  = "intent://pay?pa={$upiId}&pn=" . urlencode($libName) . "&am={$amount}&tn={$note}&cu=INR#Intent;scheme=upi;package=in.org.npci.upiapp;end";

// ── QR data (standard UPI string) ──
$qrData = "upi://pay?pa={$upiId}&pn=" . urlencode($libName) . "&am={$amount}&tn={$note}&cu=INR";

// ── Helper: render error page ──
function renderError($title, $msg) {
    return '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Payment Error</title>
    <style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:sans-serif;background:#f0f4fb;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:16px}.card{background:#fff;border-radius:16px;padding:32px;max-width:400px;width:100%;text-align:center;box-shadow:0 4px 24px rgba(0,0,0,.08)}.icon{font-size:48px;margin-bottom:16px}.title{font-size:20px;font-weight:700;color:#dc2626;margin-bottom:8px}.msg{font-size:14px;color:#64748b;line-height:1.6}</style>
    </head><body><div class="card"><div class="icon">⚠️</div><div class="title">' . htmlspecialchars($title) . '</div><div class="msg">' . htmlspecialchars($msg) . '</div></div></body></html>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<meta name="theme-color" content="#3d6ff0">
<title>Pay Fee — <?= $libName ?></title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;padding:16px;display:flex;align-items:flex-start;justify-content:center}
.wrap{width:100%;max-width:420px;padding-bottom:32px}

/* Header card */
.hdr{background:#fff;border-radius:20px;padding:24px;margin-bottom:12px;text-align:center;box-shadow:0 8px 32px rgba(0,0,0,.12)}
.lib-logo{width:56px;height:56px;border-radius:14px;object-fit:contain;margin-bottom:10px}
.lib-logo-placeholder{width:56px;height:56px;border-radius:14px;background:linear-gradient(135deg,#3d6ff0,#7c3aed);display:inline-flex;align-items:center;justify-content:center;font-size:22px;font-weight:700;color:#fff;margin-bottom:10px}
.lib-name{font-size:17px;font-weight:700;color:#0f172a;margin-bottom:2px}
.lib-addr{font-size:11px;color:#94a3b8}

/* Amount card */
.amt-card{background:#fff;border-radius:20px;padding:24px;margin-bottom:12px;box-shadow:0 8px 32px rgba(0,0,0,.12)}
.student-row{display:flex;align-items:center;gap:12px;margin-bottom:18px;padding-bottom:18px;border-bottom:1px solid #f1f5f9}
.s-av{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:700;color:#fff;flex-shrink:0;background:linear-gradient(135deg,#3d6ff0,#7c3aed)}
.s-name{font-size:15px;font-weight:700;color:#0f172a}
.s-id{font-size:11px;color:#94a3b8;font-family:monospace;margin-top:2px}
.s-batch{font-size:11px;color:#64748b;margin-top:2px}
.lbl{font-size:11px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.8px;margin-bottom:4px}
.amount{font-size:42px;font-weight:800;color:#0f172a;line-height:1}
.rupee{font-size:26px;vertical-align:top;margin-top:6px;display:inline-block;color:#64748b}
.upi-dest{margin-top:14px;padding:10px 14px;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0;display:flex;align-items:center;gap:8px}
.upi-icon{font-size:18px}
.upi-label{font-size:11px;color:#94a3b8}
.upi-val{font-size:13px;font-weight:600;color:#0f172a;font-family:monospace}

/* Already paid */
.paid-banner{background:#f0fdf4;border:2px solid #86efac;border-radius:16px;padding:20px;text-align:center;margin-bottom:12px}
.paid-icon{font-size:40px;margin-bottom:8px}
.paid-title{font-size:18px;font-weight:700;color:#166534}
.paid-sub{font-size:13px;color:#4ade80;margin-top:4px}

/* QR card */
.qr-card{background:#fff;border-radius:20px;padding:24px;margin-bottom:12px;text-align:center;box-shadow:0 8px 32px rgba(0,0,0,.12)}
.qr-title{font-size:13px;font-weight:600;color:#64748b;margin-bottom:14px}
#qrcode{display:inline-block;padding:12px;background:#fff;border-radius:12px;border:2px solid #e2e8f0}
#qrcode img,#qrcode canvas{display:block;border-radius:8px}
.qr-hint{font-size:11px;color:#94a3b8;margin-top:12px}

/* Pay buttons */
.pay-card{background:#fff;border-radius:20px;padding:20px;margin-bottom:12px;box-shadow:0 8px 32px rgba(0,0,0,.12)}
.pay-title{font-size:13px;font-weight:600;color:#64748b;margin-bottom:12px;text-align:center}
.pay-btns{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.pay-btn{display:flex;align-items:center;justify-content:center;gap:8px;padding:14px 10px;border-radius:12px;font-size:13px;font-weight:700;cursor:pointer;border:none;text-decoration:none;transition:transform .15s,box-shadow .15s}
.pay-btn:active{transform:scale(.97)}
.btn-gpay{background:#000;color:#fff}
.btn-phonepe{background:#5f259f;color:#fff}
.btn-paytm{background:#00baf2;color:#fff}
.btn-bhim{background:#ff6b35;color:#fff}
.btn-upi{background:linear-gradient(135deg,#3d6ff0,#7c3aed);color:#fff;grid-column:1/-1;padding:16px}
.btn-emoji{font-size:18px}

/* Notify card */
.notify-card{background:#fff;border-radius:20px;padding:20px;margin-bottom:12px;box-shadow:0 8px 32px rgba(0,0,0,.12);text-align:center}
.notify-title{font-size:13px;font-weight:600;color:#64748b;margin-bottom:6px}
.notify-sub{font-size:11px;color:#94a3b8;margin-bottom:14px}
.notify-btn{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:15px;border-radius:12px;background:#25d366;color:#fff;font-size:14px;font-weight:700;border:none;cursor:pointer;text-decoration:none;transition:background .15s}
.notify-btn:active{background:#128c7e}
.utr-row{display:flex;gap:8px;margin-top:10px}
.utr-input{flex:1;padding:11px 13px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;outline:none;font-family:monospace}
.utr-input:focus{border-color:#3d6ff0}
.utr-btn{padding:11px 16px;background:#3d6ff0;color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer}

/* Steps */
.steps-card{background:rgba(255,255,255,.15);border-radius:16px;padding:16px;margin-bottom:12px;backdrop-filter:blur(8px)}
.step{display:flex;align-items:flex-start;gap:10px;margin-bottom:10px}
.step:last-child{margin-bottom:0}
.step-num{width:22px;height:22px;border-radius:50%;background:#fff;color:#3d6ff0;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px}
.step-txt{font-size:12px;color:#fff;line-height:1.5}

/* Footer */
.footer{text-align:center;font-size:11px;color:rgba(255,255,255,.7);padding:8px 0}
</style>
</head>
<body>
<div class="wrap">

  <!-- Library Header -->
  <div class="hdr">
    <?php if ($logoUrl): ?>
      <img src="<?= htmlspecialchars($logoUrl) ?>" class="lib-logo" alt="Logo">
    <?php else: ?>
      <div class="lib-logo-placeholder">📚</div>
    <?php endif; ?>
    <div class="lib-name"><?= $libName ?></div>
    <?php if ($libAddr): ?><div class="lib-addr"><?= $libAddr ?></div><?php endif; ?>
  </div>

  <?php if ($alreadyPaid): ?>
  <!-- Already paid banner -->
  <div class="paid-banner">
    <div class="paid-icon">✅</div>
    <div class="paid-title">Payment Already Received</div>
    <div class="paid-sub">This fee has been marked as paid. Thank you!</div>
  </div>

  <?php else: ?>

  <!-- Amount Card -->
  <div class="amt-card">
    <div class="student-row">
      <div class="s-av"><?= strtoupper(mb_substr($link['fname'],0,1) . mb_substr($link['lname'],0,1)) ?></div>
      <div>
        <div class="s-name"><?= $studentName ?></div>
        <div class="s-id"><?= htmlspecialchars($link['student_id']) ?></div>
        <?php if ($link['batch_name']): ?><div class="s-batch">📚 <?= htmlspecialchars($link['batch_name']) ?></div><?php endif; ?>
      </div>
    </div>
    <div class="lbl">Amount to Pay</div>
    <div class="amount"><span class="rupee">₹</span><?= number_format($amount) ?></div>
    <div class="upi-dest">
      <span class="upi-icon">💳</span>
      <div>
        <div class="upi-label">Paying to</div>
        <div class="upi-val"><?= htmlspecialchars($upiId) ?></div>
      </div>
    </div>
  </div>

  <!-- QR Code -->
  <div class="qr-card">
    <div class="qr-title">📷 Scan with any UPI app</div>
    <div id="qrcode"></div>
    <div class="qr-hint">GPay · PhonePe · Paytm · BHIM · Any UPI app</div>
  </div>

  <!-- Pay Buttons -->
  <div class="pay-card">
    <div class="pay-title">Or tap to open your app directly</div>
    <div class="pay-btns">
      <a href="<?= $gpayLink ?>" class="pay-btn btn-gpay">
        <span class="btn-emoji">G</span> GPay
      </a>
      <a href="<?= $phonepeLink ?>" class="pay-btn btn-phonepe">
        <span class="btn-emoji">₱</span> PhonePe
      </a>
      <a href="<?= $paytmLink ?>" class="pay-btn btn-paytm">
        <span class="btn-emoji">P</span> Paytm
      </a>
      <a href="<?= $bhimLink ?>" class="pay-btn btn-bhim">
        <span class="btn-emoji">B</span> BHIM
      </a>
      <a href="<?= $upiBase ?>" class="pay-btn btn-upi">
        <span class="btn-emoji">📱</span> Open Any UPI App
      </a>
    </div>
  </div>

  <!-- Notify Admin -->
  <div class="notify-card">
    <div class="notify-title">✅ After paying, notify the library</div>
    <div class="notify-sub">Enter your UPI transaction ID (optional) and send via WhatsApp</div>
    <div class="utr-row">
      <input type="text" class="utr-input" id="utrInput" placeholder="Transaction ID (e.g. 401234567890)" maxlength="64">
      <button class="utr-btn" onclick="copyUTR()">Copy</button>
    </div>
    <div style="margin-top:10px">
      <a id="notifyBtn" class="notify-btn" href="#" onclick="sendNotify(event)">
        💬 Notify Library via WhatsApp
      </a>
    </div>
  </div>

  <!-- Steps guide -->
  <div class="steps-card">
    <div class="step"><div class="step-num">1</div><div class="step-txt">Scan the QR code above or tap an app button</div></div>
    <div class="step"><div class="step-num">2</div><div class="step-txt">Pay ₹<?= number_format($amount) ?> to <?= htmlspecialchars($upiId) ?></div></div>
    <div class="step"><div class="step-num">3</div><div class="step-txt">Enter the transaction ID and tap "Notify Library" — your fee status will be updated</div></div>
  </div>

  <?php endif; ?>

  <div class="footer"><?= $libName ?> · <?= $libPhone ?></div>
</div>

<script>
<?php if (!$alreadyPaid): ?>
// Generate QR code
new QRCode(document.getElementById('qrcode'), {
  text: <?= json_encode($qrData) ?>,
  width: 200,
  height: 200,
  colorDark: '#0f172a',
  colorLight: '#ffffff',
  correctLevel: QRCode.CorrectLevel.M
});

// Notify via WhatsApp
function sendNotify(e) {
  e.preventDefault();
  const utr = document.getElementById('utrInput').value.trim();
  const utrText = utr ? '\n\n🔖 Transaction ID: ' + utr : '';
  const msg = '✅ *Fee Payment Done*\n\nHello <?= $libName ?>,\n\n' +
    'I have paid my monthly fee.\n\n' +
    '👤 Name: <?= $studentName ?>\n' +
    '🆔 ID: <?= htmlspecialchars($link['student_id']) ?>\n' +
    '💰 Amount: ₹<?= number_format($amount) ?>' + utrText + '\n\n' +
    'Please update my fee status. 🙏';
  const phone = '<?= preg_replace('/\D/', '', $libPhone) ?>';
  const fullPhone = phone.length === 10 ? '91' + phone : phone;
  window.location.href = 'https://wa.me/' + fullPhone + '?text=' + encodeURIComponent(msg);
}

function copyUTR() {
  const val = document.getElementById('utrInput').value.trim();
  if (!val) { alert('Please enter a transaction ID first'); return; }
  navigator.clipboard?.writeText(val).then(() => alert('Copied!')).catch(() => {});
}
<?php endif; ?>
</script>
</body>
</html>
