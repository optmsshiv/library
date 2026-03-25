<?php 
require_once '../includes/db.php';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
$page_css = 'expenses.css';
?>

<div class="content">
  <div class="sec-hd">
    <div><div class="sec-t">Expenses</div></div>
    <button class="btn bp" onclick="openM('mExpense')">+ Add Expense</button>
  </div>

  <div class="stats-grid" style="grid-template-columns:repeat(3,1fr)">
    <div class="sc" style="--ca:var(--ro)">
      <div class="s-lb">Total Expenses</div>
      <div class="s-vl" id="ex-t">₹0</div>
    </div>
    <div class="sc" style="--ca:var(--em)">
      <div class="s-lb">Net Profit</div>
      <div class="s-vl" id="ex-p">₹0</div>
    </div>
    <div class="sc" style="--ca:var(--ac)">
      <div class="s-lb">Revenue</div>
      <div class="s-vl" id="ex-r">₹0</div>
    </div>
  </div>

  <div class="panel">
    <div class="ph">
      <div class="pt">Expense Records</div>
      <select id="exCatF" onchange="renderExp()">
        <option value="all">All Categories</option>
        <option>Utilities</option>
        <option>Staff</option>
        <option>Maintenance</option>
        <option>Supplies</option>
        <option>Books</option>
      </select>
    </div>
    <div class="pb" id="expList" style="display:flex;flex-direction:column;gap:8px"></div>
  </div>
</div>

<script src="../assets/js/expenses.js"></script>
<?php require_once '../includes/footer.php'; ?>