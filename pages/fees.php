<?php 
require_once '../includes/db.php';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="content">
  <div class="sec-hd">
    <div><div class="sec-t">Fee Management</div></div>
    <button class="btn bp" onclick="openM('mCollectFee')">Collect Fee</button>
  </div>

  <div class="stats-grid">
    <!-- Fee stats cards -->
  </div>

  <div class="panel">
    <table>
      <thead><tr><th>Student</th><th>Net Fee</th><th>Paid</th><th>Balance</th><th>Status</th></tr></thead>
      <tbody id="feeTable"></tbody>
    </table>
  </div>
</div>

<script src="../assets/js/fees.js"></script>
<?php require_once '../includes/footer.php'; ?>