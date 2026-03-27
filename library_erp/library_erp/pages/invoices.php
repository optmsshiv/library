<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
requireLogin();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'gen_invoice') {
    $studentId = (int)$_POST['student_id'];
    $amount    = (float)$_POST['amount'];
    $discount  = (float)$_POST['discount'];
    $netAmount = $amount - $discount;
    $dueDate   = $_POST['due_date'] ?? null;
    $notes     = trim($_POST['notes'] ?? '');

    $invNo = 'INV-'.date('Ymd').'-'.rand(100,999);
    $db->prepare("INSERT INTO invoices (invoice_no,student_id,amount,discount,net_amount,due_date,notes,created_by) VALUES (?,?,?,?,?,?,?,?)")
       ->execute([$invNo,$studentId,$amount,$discount,$netAmount,$dueDate,$notes,$_SESSION['user_id']]);
    logActivity("Generated invoice $invNo", 'invoice');
    $_SESSION['toast'] = ['msg' => "Invoice $invNo generated!", 'type' => 'ok'];
    header('Location: /pages/invoices.php'); exit;
}

$invoices = $db->query("
    SELECT i.*, s.first_name, s.last_name, s.student_code
    FROM invoices i
    JOIN students s ON i.student_id = s.id
    ORDER BY i.created_at DESC LIMIT 50
")->fetchAll();

$allStudents = $db->query("SELECT id, first_name, last_name, student_code, net_fee FROM students WHERE status='active' ORDER BY first_name")->fetchAll();

$currentPage = 'invoices';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="sec-hd">
  <div><div class="sec-t">Invoices</div><div class="sec-s"><?= count($invoices) ?> invoices generated</div></div>
  <button class="btn bp" onclick="document.getElementById('genInvModal').classList.add('open')">+ Generate Invoice</button>
</div>

<div class="panel"><div class="tw"><table>
  <thead><tr><th>Invoice No</th><th>Student</th><th>Amount</th><th>Discount</th><th>Net</th><th>Due Date</th><th>Status</th><th>Action</th></tr></thead>
  <tbody>
    <?php foreach ($invoices as $inv):
      $stCls = ['paid'=>'tpd','unpaid'=>'tpn','partial'=>'tor','cancelled'=>'tod'][$inv['status']] ?? 'tpn';
    ?>
    <tr>
      <td style="font-family:var(--fm);font-weight:600;font-size:11px"><?= $inv['invoice_no'] ?></td>
      <td>
        <div style="font-weight:600;font-size:12px"><?= htmlspecialchars($inv['first_name'].' '.$inv['last_name']) ?></div>
        <div style="font-size:10px;color:var(--tx3);font-family:var(--fm)"><?= $inv['student_code'] ?></div>
      </td>
      <td style="font-family:var(--fm)">₹<?= number_format($inv['amount']) ?></td>
      <td style="font-family:var(--fm);color:var(--or)"><?= $inv['discount']>0?'-₹'.number_format($inv['discount']):'—' ?></td>
      <td style="font-family:var(--fm);font-weight:700">₹<?= number_format($inv['net_amount']) ?></td>
      <td style="font-size:11px;font-family:var(--fm)"><?= $inv['due_date']?date('d M Y',strtotime($inv['due_date'])):'—' ?></td>
      <td><span class="tag <?= $stCls ?>"><?= ucfirst($inv['status']) ?></span></td>
      <td><button class="btn bg" style="font-size:10px;padding:3px 7px" onclick="window.print()">🖨</button></td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($invoices)): ?>
    <tr><td colspan="8"><div class="empty"><div class="ei">🧾</div><div class="et">No invoices yet</div></div></td></tr>
    <?php endif; ?>
  </tbody>
</table></div></div>

<!-- GEN INVOICE MODAL -->
<div class="mo" id="genInvModal">
  <div class="md">
    <div class="mh"><span class="mt">🧾 Generate Invoice</span><button class="mc" onclick="document.getElementById('genInvModal').classList.remove('open')">✕</button></div>
    <form method="POST">
      <input type="hidden" name="action" value="gen_invoice">
      <div class="mb">
        <div class="fg">
          <div class="fgi full"><label>Student *</label>
            <select name="student_id" required onchange="setInvAmt(this)">
              <option value="">-- Select --</option>
              <?php foreach ($allStudents as $s): ?>
              <option value="<?= $s['id'] ?>" data-fee="<?= $s['net_fee'] ?>"><?= htmlspecialchars($s['first_name'].' '.$s['last_name'].' ('.$s['student_code'].')') ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="fgi"><label>Amount (₹)</label><input type="number" name="amount" id="invAmt" min="0"></div>
          <div class="fgi"><label>Discount (₹)</label><input type="number" name="discount" value="0" min="0"></div>
          <div class="fgi"><label>Due Date</label><input type="date" name="due_date"></div>
          <div class="fgi full"><label>Notes</label><textarea name="notes" rows="2"></textarea></div>
        </div>
      </div>
      <div class="mf"><button type="button" class="btn bg" onclick="document.getElementById('genInvModal').classList.remove('open')">Cancel</button><button type="submit" class="btn bp">Generate</button></div>
    </form>
  </div>
</div>
<script>
function setInvAmt(sel) {
  const opt = sel.options[sel.selectedIndex];
  document.getElementById('invAmt').value = opt.dataset.fee || 0;
}
document.getElementById('genInvModal').addEventListener('click', e => { if(e.target===e.currentTarget) e.currentTarget.classList.remove('open'); });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
