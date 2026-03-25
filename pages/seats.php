<?php 
require_once '../includes/db.php';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="content">
  <div class="sec-hd">
    <div><div class="sec-t">Seat Allocation</div></div>
    <button class="btn bp" onclick="openM('mAddBatch')">+ Add Batch</button>
  </div>

  <div class="stats-grid" style="grid-template-columns:repeat(3,1fr)">
    <div class="sc"><div class="s-vl" id="st-total">0</div><div class="s-lb">Total Seats</div></div>
    <div class="sc"><div class="s-vl" id="st-vacant">0</div><div class="s-lb">Vacant</div></div>
    <div class="sc"><div class="s-vl" id="st-occupied">0</div><div class="s-lb">Occupied</div></div>
  </div>

  <div id="batchGrid" class="g2"></div>
</div>

<script src="../assets/js/seats.js"></script>
<?php require_once '../includes/footer.php'; ?>