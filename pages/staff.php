<?php 
require_once '../includes/db.php';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
$page_css = 'staff.css';
?>

<div class="content">
  <div class="sec-hd">
    <div>
      <div class="sec-t">Staff & Users</div>
      <div class="sec-s" id="staffCount">0 staff</div>
    </div>
    <button class="btn bp" onclick="openM('mAddStaff')">+ Add Staff</button>
  </div>

  <div class="panel">
    <div class="tw">
      <table>
        <thead>
          <tr><th>Staff</th><th>Role</th><th>Email</th><th>Phone</th><th>Status</th><th>Action</th></tr>
        </thead>
        <tbody id="staffTable"></tbody>
      </table>
    </div>
  </div>
</div>

<script src="../assets/js/staff.js"></script>
<?php require_once '../includes/footer.php'; ?>