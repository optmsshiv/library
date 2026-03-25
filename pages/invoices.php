<?php 
require_once '../includes/db.php';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
$page_css = 'invoices.css';
?>

<div class="content">
  <div class="sec-hd">
    <div><div class="sec-t">Invoices</div></div>
    <button class="btn bp" onclick="openM('mGenInv')">+ Generate Invoice</button>
  </div>

  <div class="panel">
    <div class="tw">
      <table>
        <thead>
          <tr><th>Invoice #</th><th>Student</th><th>Amount</th><th>Date</th><th>Status</th><th>Action</th></tr>
        </thead>
        <tbody id="invTable"></tbody>
      </table>
    </div>
  </div>
</div>

<script src="../assets/js/invoices.js"></script>
<?php require_once '../includes/footer.php'; ?>