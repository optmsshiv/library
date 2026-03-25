<?php 
require_once '../includes/db.php';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="content">
  <div class="sec-hd">
    <div>
      <div class="sec-t">All Students</div>
      <div class="sec-s" id="stuCount2">0 student(s)</div>
    </div>
    <div style="display:flex;gap:7px">
      <input placeholder="Search…" id="stuSrchInp" oninput="stuSrch(this.value)">
      <button class="btn bp" onclick="openM('mEnroll')">+ Enroll</button>
    </div>
  </div>

  <div class="panel">
    <div class="tw">
      <table>
        <thead>
          <tr>
            <th>Student</th><th>Batch</th><th>Seat</th><th>Net Fee</th><th>Paid</th><th>Balance</th><th>Status</th><th>Action</th>
          </tr>
        </thead>
        <tbody id="stuTable"></tbody>
      </table>
    </div>
  </div>
</div>

<script src="../assets/js/students.js"></script>
<?php require_once '../includes/footer.php'; ?>