<?php 
require_once '../includes/db.php';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
$page_css = 'reports.css';
?>

<div class="content">
  <div class="sec-hd">
    <div><div class="sec-t">Reports</div></div>
  </div>

  <div class="g3">
    <div class="panel" onclick="genReport('monthly')">
      <div class="pb" style="text-align:center;padding:30px">
        <div style="font-size:48px;margin-bottom:10px">📄</div>
        <div style="font-weight:600">Monthly Summary</div>
        <button class="btn bp" style="margin-top:15px">Generate</button>
      </div>
    </div>
    <div class="panel" onclick="genReport('fee')">
      <div class="pb" style="text-align:center;padding:30px">
        <div style="font-size:48px;margin-bottom:10px">💰</div>
        <div style="font-weight:600">Fee Report</div>
        <button class="btn bp" style="margin-top:15px">Generate</button>
      </div>
    </div>
    <div class="panel" onclick="genReport('books')">
      <div class="pb" style="text-align:center;padding:30px">
        <div style="font-size:48px;margin-bottom:10px">📚</div>
        <div style="font-weight:600">Book Inventory</div>
        <button class="btn bp" style="margin-top:15px">Generate</button>
      </div>
    </div>
    <!-- Add more report cards similarly -->
  </div>

  <div class="panel" id="rptOut" style="display:none">
    <div class="ph">
      <div class="pt" id="rptTitle">Report</div>
      <button class="btn bg" onclick="window.print()">🖨 Print</button>
    </div>
    <div class="pb" id="rptBody"></div>
  </div>
</div>

<script src="../assets/js/reports.js"></script>
<?php require_once '../includes/footer.php'; ?>