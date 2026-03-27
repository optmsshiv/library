<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
requireLogin();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_settings') {
    $keys = ['library_name','fine_per_day','max_issue_days','whatsapp_number','currency','academic_year'];
    foreach ($keys as $key) {
        if (isset($_POST[$key])) {
            $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")
               ->execute([$key, trim($_POST[$key])]);
        }
    }
    logActivity('Updated settings');
    $_SESSION['toast'] = ['msg' => 'Settings saved!', 'type' => 'ok'];
    header('Location: /pages/settings.php'); exit;
}

$settings = [];
foreach ($db->query("SELECT setting_key, setting_value FROM settings")->fetchAll() as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Stats
$totalStudents  = $db->query("SELECT COUNT(*) FROM students WHERE status='active'")->fetchColumn();
$discountGiven  = $db->query("SELECT COUNT(*) FROM students WHERE discount > 0")->fetchColumn();
$totalDiscAmt   = $db->query("SELECT SUM(base_fee-net_fee) FROM students WHERE discount>0")->fetchColumn() ?: 0;
$totalBooks     = $db->query("SELECT SUM(total_copies) FROM books")->fetchColumn() ?: 0;
$activeTx       = $db->query("SELECT COUNT(*) FROM transactions WHERE status='issued'")->fetchColumn();
$totalBatches   = $db->query("SELECT COUNT(*) FROM batches")->fetchColumn();
$staffCount     = $db->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetchColumn();
$netProfit      = $db->query("SELECT (SELECT IFNULL(SUM(amount),0) FROM fees) - (SELECT IFNULL(SUM(amount),0) FROM expenses)")->fetchColumn() ?: 0;

$currentPage = 'settings';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="sec-hd">
  <div><div class="sec-t">Settings</div><div class="sec-s">Library configuration</div></div>
</div>

<div class="g2">
  <!-- Settings Form -->
  <div>
    <div class="panel">
      <div class="ph"><div class="pt">⚙️ General Settings</div></div>
      <form method="POST" style="padding:20px">
        <input type="hidden" name="action" value="save_settings">
        <div class="fg">
          <div class="fgi full">
            <label>Library Name</label>
            <input type="text" name="library_name" value="<?= htmlspecialchars($settings['library_name'] ?? 'OPTMS Tech Library') ?>">
          </div>
          <div class="fgi">
            <label>Fine Per Day (₹)</label>
            <input type="number" name="fine_per_day" value="<?= $settings['fine_per_day'] ?? 5 ?>" min="0">
          </div>
          <div class="fgi">
            <label>Max Issue Days</label>
            <input type="number" name="max_issue_days" value="<?= $settings['max_issue_days'] ?? 7 ?>" min="1">
          </div>
          <div class="fgi">
            <label>WhatsApp Number</label>
            <input type="text" name="whatsapp_number" value="<?= htmlspecialchars($settings['whatsapp_number'] ?? '') ?>" placeholder="91XXXXXXXXXX">
          </div>
          <div class="fgi">
            <label>Currency</label>
            <select name="currency">
              <option value="INR" <?= ($settings['currency']??'INR')==='INR'?'selected':'' ?>>INR (₹)</option>
              <option value="USD" <?= ($settings['currency']??'')==='USD'?'selected':'' ?>>USD ($)</option>
            </select>
          </div>
          <div class="fgi">
            <label>Academic Year</label>
            <input type="text" name="academic_year" value="<?= htmlspecialchars($settings['academic_year'] ?? '2024-25') ?>">
          </div>
        </div>
        <button type="submit" class="btn bp" style="margin-top:16px">💾 Save Settings</button>
      </form>
    </div>

    <!-- DB Config -->
    <div class="panel" style="margin-top:14px">
      <div class="ph"><div class="pt">🗄️ Database</div></div>
      <div style="padding:16px">
        <div style="display:flex;flex-direction:column;gap:8px;font-size:12.5px">
          <div style="display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid var(--br)">
            <span style="color:var(--tx2)">Host</span><span style="font-family:var(--fm)"><?= DB_HOST ?>:<?= DB_PORT ?></span>
          </div>
          <div style="display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid var(--br)">
            <span style="color:var(--tx2)">Database</span><span style="font-family:var(--fm)"><?= DB_NAME ?></span>
          </div>
          <div style="display:flex;justify-content:space-between;padding:7px 0">
            <span style="color:var(--tx2)">User</span><span style="font-family:var(--fm)"><?= DB_USER ?></span>
          </div>
        </div>
        <a href="/setup.php" class="btn bg" style="margin-top:12px;font-size:11px;text-decoration:none">🔧 Reconfigure DB</a>
      </div>
    </div>
  </div>

  <!-- System Stats -->
  <div>
    <div class="panel">
      <div class="ph"><div class="pt">📊 System Overview</div></div>
      <div style="padding:14px">
        <?php
        $stats = [
            ['Total Students', $totalStudents],
            ['Discounts Given', "$discountGiven students (₹".number_format($totalDiscAmt).")"],
            ['Total Books', number_format($totalBooks)],
            ['Active Transactions', $activeTx],
            ['Total Batches', $totalBatches],
            ['Staff Members', $staffCount],
            ['Net Profit/Loss', '₹'.number_format($netProfit)],
        ];
        foreach ($stats as $s): ?>
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--br)">
          <span style="font-size:12px;color:var(--tx2)"><?= $s[0] ?></span>
          <span style="font-weight:700;font-family:var(--fm)"><?= $s[1] ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Quick Links -->
    <div class="panel" style="margin-top:14px">
      <div class="ph"><div class="pt">🔗 Quick Links</div></div>
      <div style="padding:14px;display:flex;flex-direction:column;gap:8px">
        <a href="/pages/reports.php" class="btn bg" style="text-decoration:none">📈 View Reports</a>
        <a href="/pages/staff.php" class="btn bg" style="text-decoration:none">👥 Manage Staff</a>
        <a href="/setup.php" class="btn bg" style="text-decoration:none">🗄️ DB Setup</a>
        <a href="/logout.php" class="btn bd" style="text-decoration:none">⏻ Logout</a>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
