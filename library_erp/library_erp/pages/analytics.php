<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
requireLogin();

$db = getDB();

// Basic analytics data
$monthlyFees = $db->query("
    SELECT DATE_FORMAT(payment_date,'%b %Y') AS month, SUM(amount) AS total
    FROM fees
    GROUP BY DATE_FORMAT(payment_date,'%Y-%m')
    ORDER BY MIN(payment_date) DESC LIMIT 6
")->fetchAll();

$batchStats = $db->query("
    SELECT b.name, b.total_seats, b.occupied_seats,
           COUNT(s.id) AS students,
           SUM(CASE WHEN s.fee_status='paid' THEN 1 ELSE 0 END) AS paid_count
    FROM batches b
    LEFT JOIN students s ON s.batch_id = b.id AND s.status='active'
    GROUP BY b.id ORDER BY b.time_start
")->fetchAll();

$currentPage = 'analytics';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="sec-hd">
  <div><div class="sec-t">Analytics</div><div class="sec-s">Library performance overview</div></div>
</div>

<div class="g2">
  <!-- Monthly Collection -->
  <div class="panel">
    <div class="ph"><div class="pt">📈 Monthly Fee Collection</div></div>
    <div style="padding:16px">
      <?php foreach (array_reverse($monthlyFees) as $mf): ?>
      <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--br)">
        <span style="font-size:12.5px;color:var(--tx2)"><?= $mf['month'] ?></span>
        <span style="font-family:var(--fm);font-weight:700;color:var(--em)">₹<?= number_format($mf['total']) ?></span>
      </div>
      <?php endforeach; ?>
      <?php if (empty($monthlyFees)): ?>
      <div class="empty"><div class="ei">📊</div><div class="et">No data yet</div></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Batch Stats -->
  <div class="panel">
    <div class="ph"><div class="pt">🏫 Batch Performance</div></div>
    <div style="padding:16px">
      <?php foreach ($batchStats as $b):
        $pct = $b['total_seats'] > 0 ? round($b['occupied_seats']/$b['total_seats']*100) : 0;
        $paidPct = $b['students'] > 0 ? round($b['paid_count']/$b['students']*100) : 0;
      ?>
      <div style="margin-bottom:14px">
        <div style="display:flex;justify-content:space-between;margin-bottom:4px">
          <span style="font-weight:600;font-size:12.5px"><?= htmlspecialchars($b['name']) ?></span>
          <span style="font-size:11px;color:var(--tx3)"><?= $b['students'] ?> students · <?= $paidPct ?>% paid</span>
        </div>
        <div class="sbar"><div class="sfill sf-g" style="width:<?= $pct ?>%"></div></div>
        <div style="font-size:10px;color:var(--tx3)"><?= $b['occupied_seats'] ?>/<?= $b['total_seats'] ?> seats (<?= $pct ?>%)</div>
      </div>
      <?php endforeach; ?>
      <?php if (empty($batchStats)): ?>
      <div class="empty"><div class="ei">🏫</div><div class="et">No batch data</div></div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
