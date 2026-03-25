<?php 
require_once '../includes/db.php';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="content">
  <div class="sec-hd">
    <div><div class="sec-t">Attendance</div></div>
    <select id="attBatchF" onchange="renderAtt()"></select>
    <button class="btn bp" onclick="saveAtt()">Save</button>
  </div>

  <div class="panel">
    <table>
      <thead><tr><th>Student</th><th>Batch</th><th>Status</th><th>Action</th></tr></thead>
      <tbody id="attTable"></tbody>
    </table>
  </div>
</div>

<script src="../assets/js/attendance.js"></script>
<?php require_once '../includes/footer.php'; ?>