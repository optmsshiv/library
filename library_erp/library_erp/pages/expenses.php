<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
requireLogin();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_expense') {
    $title   = trim($_POST['title'] ?? '');
    $cat     = $_POST['category'] ?? 'other';
    $amount  = (float)$_POST['amount'];
    $date    = $_POST['expense_date'] ?? date('Y-m-d');
    $paidTo  = trim($_POST['paid_to'] ?? '');
    $mode    = $_POST['payment_mode'] ?? 'cash';
    $notes   = trim($_POST['notes'] ?? '');

    if ($title && $amount > 0) {
        $db->prepare("INSERT INTO expenses (title,category,amount,expense_date,paid_to,payment_mode,notes,added_by) VALUES (?,?,?,?,?,?,?,?)")
           ->execute([$title,$cat,$amount,$date,$paidTo,$mode,$notes,$_SESSION['user_id']]);
        logActivity("Added expense: $title ₹$amount", 'expense');
        $_SESSION['toast'] = ['msg' => "Expense '$title' added!", 'type' => 'ok'];
        header('Location: /pages/expenses.php'); exit;
    }
}

$month = $_GET['month'] ?? date('Y-m');
$expenses = $db->prepare("SELECT * FROM expenses WHERE DATE_FORMAT(expense_date,'%Y-%m')=? ORDER BY expense_date DESC");
$expenses->execute([$month]);
$expenses = $expenses->fetchAll();

$totalMonth = array_sum(array_column($expenses, 'amount'));
$byCategory = [];
foreach ($expenses as $e) {
    $byCategory[$e['category']] = ($byCategory[$e['category']] ?? 0) + $e['amount'];
}

$currentPage = 'expenses';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="sec-hd">
  <div><div class="sec-t">Expenses</div><div class="sec-s">₹<?= number_format($totalMonth) ?> this month</div></div>
  <div style="display:flex;gap:7px;align-items:center">
    <input type="month" value="<?= $month ?>" onchange="window.location='?month='+this.value" style="font-size:12px;padding:6px 9px">
    <button class="btn bp" onclick="document.getElementById('addExpenseModal').classList.add('open')">+ Add Expense</button>
  </div>
</div>

<div class="g2">
  <!-- Expense List -->
  <div>
    <?php foreach ($expenses as $exp):
      $catIcons = ['salary'=>'👤','rent'=>'🏠','utilities'=>'💡','supplies'=>'📦','maintenance'=>'🔧','other'=>'💸'];
      $icon = $catIcons[$exp['category']] ?? '💸';
    ?>
    <div class="ei2">
      <div class="eic" style="background:rgba(192,68,79,.1)"><?= $icon ?></div>
      <div style="flex:1">
        <div class="en2"><?= htmlspecialchars($exp['title']) ?></div>
        <div class="ed"><?= date('d M Y', strtotime($exp['expense_date'])) ?> · <?= ucfirst($exp['category']) ?> · <?= ucfirst($exp['payment_mode']) ?><?= $exp['paid_to'] ? ' → '.$exp['paid_to'] : '' ?></div>
      </div>
      <div class="ea ea-d">₹<?= number_format($exp['amount']) ?></div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($expenses)): ?>
    <div class="empty"><div class="ei">💸</div><div class="et">No expenses for this month</div></div>
    <?php endif; ?>
  </div>

  <!-- Category Summary -->
  <div>
    <div class="sec-hd"><div class="sec-t">By Category</div></div>
    <div class="panel" style="padding:16px">
      <?php foreach ($byCategory as $cat => $total):
        $pct = $totalMonth > 0 ? round($total/$totalMonth*100) : 0;
      ?>
      <div style="margin-bottom:12px">
        <div style="display:flex;justify-content:space-between;margin-bottom:4px;font-size:12px">
          <span style="font-weight:500;text-transform:capitalize"><?= $cat ?></span>
          <span style="font-family:var(--fm);font-weight:700">₹<?= number_format($total) ?> <span style="color:var(--tx3);font-weight:400">(<?= $pct ?>%)</span></span>
        </div>
        <div class="prg"><div class="prf" style="width:<?= $pct ?>%;background:linear-gradient(90deg,var(--ro),#e05565)"></div></div>
      </div>
      <?php endforeach; ?>
      <?php if (empty($byCategory)): ?>
      <div class="empty"><div class="et">No data</div></div>
      <?php endif; ?>
      <div style="border-top:1px solid var(--br);padding-top:10px;margin-top:10px;display:flex;justify-content:space-between;font-weight:700">
        <span>Total</span><span style="font-family:var(--fm)">₹<?= number_format($totalMonth) ?></span>
      </div>
    </div>
  </div>
</div>

<!-- ADD EXPENSE MODAL -->
<div class="mo" id="addExpenseModal">
  <div class="md">
    <div class="mh"><span class="mt">💸 Add Expense</span><button class="mc" onclick="document.getElementById('addExpenseModal').classList.remove('open')">✕</button></div>
    <form method="POST">
      <input type="hidden" name="action" value="add_expense">
      <div class="mb">
        <div class="fg">
          <div class="fgi full"><label>Title *</label><input type="text" name="title" required placeholder="e.g. Staff salary, Electricity bill"></div>
          <div class="fgi"><label>Category</label>
            <select name="category">
              <option value="salary">Salary</option><option value="rent">Rent</option>
              <option value="utilities">Utilities</option><option value="supplies">Supplies</option>
              <option value="maintenance">Maintenance</option><option value="other">Other</option>
            </select>
          </div>
          <div class="fgi"><label>Amount (₹) *</label><input type="number" name="amount" min="1" required></div>
          <div class="fgi"><label>Date</label><input type="date" name="expense_date" value="<?= date('Y-m-d') ?>"></div>
          <div class="fgi"><label>Paid To</label><input type="text" name="paid_to" placeholder="Person/Vendor name"></div>
          <div class="fgi"><label>Payment Mode</label>
            <select name="payment_mode">
              <option value="cash">Cash</option><option value="upi">UPI</option>
              <option value="online">Online</option><option value="bank">Bank</option>
            </select>
          </div>
          <div class="fgi full"><label>Notes</label><textarea name="notes" rows="2"></textarea></div>
        </div>
      </div>
      <div class="mf"><button type="button" class="btn bg" onclick="document.getElementById('addExpenseModal').classList.remove('open')">Cancel</button><button type="submit" class="btn bp">Add Expense</button></div>
    </form>
  </div>
</div>

<script>
document.getElementById('addExpenseModal').addEventListener('click', e => { if(e.target===e.currentTarget) e.currentTarget.classList.remove('open'); });
<?php if (isset($_GET['add'])): ?>document.addEventListener('DOMContentLoaded',()=>document.getElementById('addExpenseModal').classList.add('open'));<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
