<?php 
require_once '../includes/db.php';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
$page_css = 'analytics.css';
?>

<div class="content">
  <div class="sec-hd">
    <div><div class="sec-t">Analytics</div></div>
  </div>

  <div class="g3" id="analCards"></div>

  <div class="g2">
    <div class="panel">
      <div class="ph"><div class="pt">Monthly Revenue</div></div>
      <div class="pb">
        <div id="revChart" style="display:flex;align-items:flex-end;gap:8px;height:140px"></div>
      </div>
    </div>
    <div class="panel">
      <div class="ph"><div class="pt">Batch Occupancy</div></div>
      <div class="pb" id="batchAnal"></div>
    </div>
  </div>
</div>

<script src="../assets/js/analytics.js"></script>
<?php require_once '../includes/footer.php'; ?>