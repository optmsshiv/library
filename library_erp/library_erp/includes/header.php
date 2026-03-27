<?php
// ═══ SHARED HEADER / SIDEBAR ═══
requireLogin();
$user = currentUser();

// Determine active page from URL
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Nav badge counts
try {
    $db = getDB();
    $feePending   = $db->query("SELECT COUNT(*) FROM students WHERE fee_status != 'paid' AND status='active'")->fetchColumn();
    $bookOverdue  = $db->query("SELECT COUNT(*) FROM transactions WHERE status='overdue'")->fetchColumn();
    $unreadNotif  = $db->query("SELECT COUNT(*) FROM notifications WHERE is_read=0")->fetchColumn();
    $absentToday  = $db->query("SELECT COUNT(*) FROM attendance WHERE date=CURDATE() AND status='absent'")->fetchColumn();
} catch (Exception $e) {
    $feePending = $bookOverdue = $unreadNotif = $absentToday = 0;
}

$nav = [
    'Overview' => [
        ['page'=>'dashboard','icon'=>'⊞','label'=>'Dashboard'],
        ['page'=>'analytics','icon'=>'📊','label'=>'Analytics'],
    ],
    'Students' => [
        ['page'=>'students','icon'=>'👨‍🎓','label'=>'All Students'],
        ['page'=>'enroll',  'icon'=>'➕','label'=>'Enroll Student'],
        ['page'=>'seats',   'icon'=>'🪑','label'=>'Seat Allocation'],
        ['page'=>'attendance','icon'=>'📋','label'=>'Attendance','badge'=>$absentToday,'badgeClass'=>''],
    ],
    'Books' => [
        ['page'=>'books',        'icon'=>'📖','label'=>'Books Catalog'],
        ['page'=>'transactions', 'icon'=>'🔄','label'=>'Issue / Returns','badge'=>$bookOverdue,'badgeClass'=>''],
    ],
    'Finance' => [
        ['page'=>'fees',     'icon'=>'💰','label'=>'Fee Management','badge'=>$feePending,'badgeClass'=>''],
        ['page'=>'invoices', 'icon'=>'🧾','label'=>'Invoices'],
        ['page'=>'expenses', 'icon'=>'💸','label'=>'Expenses'],
        ['page'=>'reports',  'icon'=>'📈','label'=>'Reports'],
    ],
    'Communication' => [
        ['page'=>'whatsapp','icon'=>'💬','label'=>'WhatsApp','badge'=>'New','badgeClass'=>'wa'],
    ],
    'Admin' => [
        ['page'=>'staff',         'icon'=>'👥','label'=>'Staff & Users'],
        ['page'=>'notifications', 'icon'=>'🔔','label'=>'Notifications','badge'=>$unreadNotif,'badgeClass'=>''],
        ['page'=>'settings',      'icon'=>'⚙️','label'=>'Settings'],
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>OPTMS Tech ERP v6 — <?= ucfirst($currentPage) ?></title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=DM+Sans:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/global.css">
<link rel="stylesheet" href="/assets/css/<?= $currentPage ?>.css">
</head>
<body>

<!-- SIDEBAR -->
<div class="sb">
  <div class="sb-logo">
    <div class="logo-row">
      <div class="logo-ic">📚</div>
      <div>
        <div class="logo-tx">OPTMS Tech</div>
        <div class="logo-sb">ERP v6.0</div>
      </div>
    </div>
  </div>
  <nav class="sb-nav">
    <?php foreach ($nav as $section => $items): ?>
    <div class="ns">
      <div class="nl"><?= $section ?></div>
      <?php foreach ($items as $item): ?>
      <a href="/pages/<?= $item['page'] ?>.php" class="ni <?= $currentPage === $item['page'] ? 'active' : '' ?>">
        <span class="ni-ic"><?= $item['icon'] ?></span>
        <?= htmlspecialchars($item['label']) ?>
        <?php if (!empty($item['badge'])): ?>
        <span class="nbadge <?= $item['badgeClass'] ?? '' ?>"><?= $item['badge'] ?></span>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
  </nav>
  <div class="sb-foot">
    <div class="u-card">
      <div class="u-av"><?= strtoupper(substr($user['name'] ?? 'A', 0, 2)) ?></div>
      <div>
        <div class="u-nm"><?= htmlspecialchars($user['name'] ?? 'Admin') ?></div>
        <div class="u-rl"><?= htmlspecialchars(ucfirst($user['role'] ?? 'admin')) ?></div>
      </div>
      <a href="/logout.php" title="Logout" class="logout-btn">⏻</a>
    </div>
  </div>
</div>

<!-- MAIN -->
<div class="main">
  <div class="topbar">
    <div class="pg-title" id="topTitle"><?= ucfirst($currentPage) ?></div>
    <form method="GET" action="/pages/students.php" class="srch">
      <span style="color:var(--tx3);font-size:12px">🔍</span>
      <input name="q" placeholder="Search students, books…" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
    </form>
    <div style="display:flex;align-items:center;gap:9px">
      <div class="chip chip-tl">📅 <span id="todayChip"></span></div>
      <a href="/pages/whatsapp.php" class="btn bwa" style="gap:5px;font-size:11px;text-decoration:none">💬 WhatsApp</a>
      <a href="/pages/notifications.php" class="nb-btn">🔔
        <?php if ($unreadNotif > 0): ?><div class="nd"></div><?php endif; ?>
      </a>
      <a href="/pages/enroll.php" class="btn bp" style="text-decoration:none">+ Enroll</a>
    </div>
  </div>
  <div class="content">
