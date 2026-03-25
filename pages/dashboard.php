<?php 
require_once __DIR__ . '../includes/db.php';
require_once __DIR__ . '../includes/header.php';
require_once __DIR__ . '../includes/sidebar.php';

// Fetch dashboard data
$totalStudents = $conn->query("SELECT COUNT(*) FROM students")->fetch_row()[0];
$totalRevenue = $conn->query("SELECT SUM(paid_amt) FROM students")->fetch_row()[0] ?? 0;
$totalExpenses = $conn->query("SELECT SUM(amount) FROM expenses")->fetch_row()[0] ?? 0;
$overdueCount = $conn->query("SELECT COUNT(*) FROM students WHERE fee_status='overdue'")->fetch_row()[0];
$pendingCount = $conn->query("SELECT COUNT(*) FROM students WHERE fee_status IN ('pending','partial')")->fetch_row()[0];
?>

<div class="content">
  <div class="al-row" id="dashAlerts">
    <!-- Alerts populated by JS or PHP -->
  </div>

  <div class="stats-grid" id="dashStats">
    <!-- Stats cards will be populated by JS -->
  </div>

  <div class="qa-gr">
    <div class="qa-b" onclick="openM('mEnroll')"><div class="qa-ic" style="background:rgba(74,124,111,.12)">➕</div><div class="qa-lb">New<br>Enroll</div></div>
    <div class="qa-b" onclick="openM('mCollectFee')"><div class="qa-ic" style="background:rgba(58,125,94,.12)">💳</div><div class="qa-lb">Collect<br>Fee</div></div>
    <!-- Add other quick actions similarly -->
  </div>

  <!-- Batch Cards & Expense Tracker -->
  <div class="g2">
    <div id="dashBatchCards"></div>
    <div id="dashExpTracker"></div>
  </div>
</div>

<script src="../assets/js/dashboard.js"></script>
<?php require_once '../includes/footer.php'; ?>