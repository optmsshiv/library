<?php 
require_once '../includes/db.php';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="content">
  <div class="sec-hd">
    <div><div class="sec-t">Issue & Returns</div></div>
    <button class="btn bp" onclick="openM('mIssueBook')">Issue Book</button>
  </div>

  <div class="panel">
    <table>
      <thead><tr><th>Student</th><th>Book</th><th>Status</th><th>Action</th></tr></thead>
      <tbody id="txTable"></tbody>
    </table>
  </div>
</div>

<script src="../assets/js/transactions.js"></script>
<?php require_once '../includes/footer.php'; ?>