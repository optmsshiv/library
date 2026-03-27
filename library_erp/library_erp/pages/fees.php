<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
requireLogin();

$db = getDB();

// Handle fee collection POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'collect_fee') {
    $studentId   = (int)$_POST['student_id'];
    $amount      = (float)$_POST['amount'];
    $mode        = $_POST['payment_mode'] ?? 'cash';
    $notes       = trim($_POST['notes'] ?? '');

    if ($studentId && $amount > 0) {
        // Generate receipt no
        $receiptNo = 'RCP-' . date('Ymd') . '-' . rand(1000, 9999);

        $db->prepare("INSERT INTO fees (student_id, amount, payment_mode, receipt_no, notes, collected_by) VALUES (?,?,?,?,?,?)")
           ->execute([$studentId, $amount, $mode, $receiptNo, $notes, $_SESSION['user_id']]);

        // Update student paid_fee
        $db->prepare("UPDATE students SET paid_fee = paid_fee + ? WHERE id = ?")->execute([$amount, $studentId]);

        // Update fee_status
        $student = $db->prepare("SELECT net_fee, paid_fee FROM students WHERE id = ?");
        $student->execute([$studentId]);
        $s = $student->fetch();
        if ($s) {
            $newStatus = ($s['paid_fee'] >= $s['net_fee']) ? 'paid' : 'partial';
            $db->prepare("UPDATE students SET fee_status = ? WHERE id = ?")->execute([$newStatus, $studentId]);
        }

        logActivity("Fee collected: ₹$amount (Receipt: $receiptNo)", 'student', $studentId);
        $_SESSION['toast'] = ['msg' => "₹$amount collected! Receipt: $receiptNo", 'type' => 'ok'];
        header('Location: /pages/fees.php');
        exit;
    }
}

// Fee summary stats
$collected  = $db->query("SELECT SUM(amount) FROM fees WHERE MONTH(payment_date)=MONTH(NOW())")->fetchColumn() ?: 0;
$totalPend  = $db->query("SELECT SUM(net_fee - paid_fee) FROM students WHERE fee_status IN ('pending','partial') AND status='active'")->fetchColumn() ?: 0;
$overdue    = $db->query("SELECT SUM(net_fee - paid_fee) FROM students WHERE fee_status='overdue' AND status='active'")->fetchColumn() ?: 0;
$partial    = $db->query("SELECT COUNT(*) FROM students WHERE fee_status='partial' AND status='active'")->fetchColumn() ?: 0;

// Pending fee list
$feeList = $db->query("
    SELECT s.*, b.name AS batch_name, (s.net_fee - s.paid_fee) AS balance
    FROM students s
    LEFT JOIN batches b ON s.batch_id = b.id
    WHERE s.fee_status != 'paid' AND s.status = 'active'
    ORDER BY s.due_date ASC, balance DESC
    LIMIT 50
")->fetchAll();

// Recent collections
$recentCollections = $db->query("
    SELECT f.*, s.first_name, s.last_name, s.student_code
    FROM fees f
    JOIN students s ON f.student_id = s.id
    ORDER BY f.created_at DESC LIMIT 20
")->fetchAll();

$allStudents = $db->query("SELECT id, first_name, last_name, student_code, net_fee, paid_fee FROM students WHERE status='active' ORDER BY first_name")->fetchAll();

$preselected = (int)($_GET['student_id'] ?? 0);

$currentPage = 'fees';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="sec-hd">
  <div><div class="sec-t">Fee Management</div></div>
  <div style="display:flex;gap:7px;flex-wrap:wrap">
    <button class="btn bp" onclick="document.getElementById('collectFeeModal').classList.add('open')">💳 Collect Fee</button>
    <a href="/pages/whatsapp.php?template=fee_reminder" class="btn bwa" style="font-size:11px;text-decoration:none">💬 WA Reminders</a>
  </div>
</div>

<div class="stats-grid">
  <div class="sc" style="--ca:var(--em)">
    <div class="s-ic" style="background:rgba(58,125,94,.1)">✅</div>
    <div class="s-lb">Collected (This Month)</div>
    <div class="s-vl">₹<?= number_format($collected) ?></div>
  </div>
  <div class="sc" style="--ca:var(--sk)">
    <div class="s-ic" style="background:rgba(58,122,176,.1)">◑</div>
    <div class="s-lb">Partial Payments</div>
    <div class="s-vl"><?= $partial ?></div>
    <div class="s-mt">students</div>
  </div>
  <div class="sc" style="--ca:var(--gd)">
    <div class="s-ic" style="background:rgba(196,125,43,.1)">⏳</div>
    <div class="s-lb">Pending</div>
    <div class="s-vl">₹<?= number_format($totalPend) ?></div>
  </div>
  <div class="sc" style="--ca:var(--ro)">
    <div class="s-ic" style="background:rgba(192,68,79,.1)">🚨</div>
    <div class="s-lb">Overdue</div>
    <div class="s-vl">₹<?= number_format($overdue) ?></div>
  </div>
</div>

<div class="g2">
  <!-- Pending Fee List -->
  <div>
    <div class="sec-hd"><div class="sec-t">Pending Collections</div></div>
    <div class="panel">
      <div class="tw"><table>
        <thead><tr><th>Student</th><th>Batch</th><th>Net Fee</th><th>Paid</th><th>Balance</th><th>Status</th><th>Due</th><th>Action</th></tr></thead>
        <tbody>
          <?php foreach ($feeList as $s):
            $stCls = ['partial'=>'tor','pending'=>'tpn','overdue'=>'tod'][$s['fee_status']] ?? 'tpn';
          ?>
          <tr class="<?= $s['fee_status']==='overdue'?'fee-due-row':($s['fee_status']==='partial'?'fee-partial-row':'') ?>">
            <td>
              <div class="si">
                <div class="sav" style="background:linear-gradient(135deg,var(--ac),var(--vi))">
                  <?= strtoupper(substr($s['first_name'],0,1).substr($s['last_name']??'',0,1)) ?>
                </div>
                <div>
                  <div style="font-weight:600;font-size:12px"><?= htmlspecialchars($s['first_name'].' '.$s['last_name']) ?></div>
                  <div style="font-size:10px;color:var(--tx3);font-family:var(--fm)"><?= $s['student_code'] ?></div>
                </div>
              </div>
            </td>
            <td style="font-size:11px"><?= htmlspecialchars($s['batch_name'] ?? '—') ?></td>
            <td style="font-family:var(--fm)">₹<?= number_format($s['net_fee']) ?></td>
            <td style="font-family:var(--fm);color:var(--em)">₹<?= number_format($s['paid_fee']) ?></td>
            <td><span class="fee-bal-badge">₹<?= number_format($s['balance']) ?></span></td>
            <td><span class="tag <?= $stCls ?>"><?= ucfirst($s['fee_status']) ?></span></td>
            <td style="font-size:11px;font-family:var(--fm)"><?= $s['due_date'] ? date('d M', strtotime($s['due_date'])) : '—' ?></td>
            <td>
              <button class="btn bp" style="font-size:10px;padding:3px 8px"
                onclick="openCollect(<?= $s['id'] ?>, '<?= htmlspecialchars($s['first_name'].' '.$s['last_name'], ENT_QUOTES) ?>', <?= $s['balance'] ?>)">
                💳 Collect
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($feeList)): ?>
          <tr><td colspan="8"><div class="empty"><div class="ei">✅</div><div class="et">All fees collected!</div></div></td></tr>
          <?php endif; ?>
        </tbody>
      </table></div>
    </div>
  </div>

  <!-- Recent Collections -->
  <div>
    <div class="sec-hd"><div class="sec-t">Recent Collections</div></div>
    <div class="panel" style="padding:14px">
      <?php foreach ($recentCollections as $fc): ?>
      <div class="fi">
        <div class="fd2" style="background:var(--em)"></div>
        <div class="fn2">
          <?= htmlspecialchars($fc['first_name'].' '.$fc['last_name']) ?>
          <div class="fsb"><?= $fc['receipt_no'] ?> · <?= date('d M Y', strtotime($fc['payment_date'])) ?> · <?= ucfirst($fc['payment_mode']) ?></div>
        </div>
        <div class="fa" style="color:var(--em)">₹<?= number_format($fc['amount']) ?></div>
      </div>
      <?php endforeach; ?>
      <?php if (empty($recentCollections)): ?>
      <div class="empty"><div class="ei">💰</div><div class="et">No collections yet</div></div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- COLLECT FEE MODAL -->
<div class="mo" id="collectFeeModal">
  <div class="md">
    <div class="mh">
      <span class="mt">💳 Collect Fee</span>
      <button class="mc" onclick="document.getElementById('collectFeeModal').classList.remove('open')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="collect_fee">
      <div class="mb">
        <div class="fg">
          <div class="fgi full">
            <label>Student *</label>
            <select name="student_id" id="cfStudent" required onchange="updateBalance(this)">
              <option value="">-- Select Student --</option>
              <?php foreach ($allStudents as $s): ?>
              <option value="<?= $s['id'] ?>" data-balance="<?= $s['net_fee']-$s['paid_fee'] ?>"
                      <?= $preselected===$s['id']?'selected':'' ?>>
                <?= htmlspecialchars($s['first_name'].' '.$s['last_name'].' ('.$s['student_code'].')') ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="fgi full">
            <label>Outstanding Balance</label>
            <input type="text" id="cfBalance" readonly style="background:var(--sf3);color:var(--ro);font-weight:700;font-family:var(--fm)">
          </div>
          <div class="fgi">
            <label>Amount (₹) *</label>
            <input type="number" name="amount" id="cfAmount" min="1" required>
          </div>
          <div class="fgi">
            <label>Payment Mode</label>
            <select name="payment_mode">
              <option value="cash">Cash</option>
              <option value="upi">UPI</option>
              <option value="online">Online Transfer</option>
              <option value="card">Card</option>
              <option value="bank">Bank Transfer</option>
            </select>
          </div>
          <div class="fgi full">
            <label>Notes</label>
            <textarea name="notes" rows="2" placeholder="Optional payment notes..."></textarea>
          </div>
        </div>
      </div>
      <div class="mf">
        <button type="button" class="btn bg" onclick="document.getElementById('collectFeeModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn bp">✅ Collect Fee</button>
      </div>
    </form>
  </div>
</div>

<script>
function openCollect(id, name, balance) {
  document.getElementById('cfStudent').value = id;
  updateBalance(document.getElementById('cfStudent'));
  document.getElementById('cfAmount').value = balance;
  document.getElementById('collectFeeModal').classList.add('open');
}
function updateBalance(sel) {
  const opt = sel.options[sel.selectedIndex];
  const bal = opt.dataset.balance || 0;
  document.getElementById('cfBalance').value = '₹' + parseFloat(bal).toLocaleString('en-IN');
  document.getElementById('cfAmount').value = Math.max(0, parseFloat(bal));
}
document.getElementById('collectFeeModal').addEventListener('click', e => { if(e.target===e.currentTarget) e.currentTarget.classList.remove('open'); });
<?php if ($preselected): ?>
document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('collectFeeModal').classList.add('open');
  updateBalance(document.getElementById('cfStudent'));
});
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
