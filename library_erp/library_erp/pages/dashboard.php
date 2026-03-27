<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
requireLogin();

$db = getDB();

// Dashboard stats
$totalStudents  = $db->query("SELECT COUNT(*) FROM students WHERE status='active'")->fetchColumn();
$totalBooks     = $db->query("SELECT SUM(total_copies) FROM books")->fetchColumn() ?: 0;
$totalBatches   = $db->query("SELECT COUNT(*) FROM batches")->fetchColumn();
$feeCollected   = $db->query("SELECT SUM(amount) FROM fees WHERE MONTH(payment_date)=MONTH(NOW())")->fetchColumn() ?: 0;
$feePending     = $db->query("SELECT SUM(net_fee - paid_fee) FROM students WHERE fee_status != 'paid' AND status='active'")->fetchColumn() ?: 0;
$overdueBooks   = $db->query("SELECT COUNT(*) FROM transactions WHERE status='overdue'")->fetchColumn();
$presentToday   = $db->query("SELECT COUNT(*) FROM attendance WHERE date=CURDATE() AND status='present'")->fetchColumn();
$totalExpenses  = $db->query("SELECT SUM(amount) FROM expenses WHERE MONTH(expense_date)=MONTH(NOW())")->fetchColumn() ?: 0;

// Recent students
$recentStudents = $db->query("
    SELECT s.*, b.name AS batch_name
    FROM students s
    LEFT JOIN batches b ON s.batch_id = b.id
    ORDER BY s.created_at DESC LIMIT 8
")->fetchAll();

// Recent activities
$activities = $db->query("
    SELECT al.*, u.name AS user_name
    FROM activity_log al
    LEFT JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC LIMIT 10
")->fetchAll();

// Batch overview
$batches = $db->query("SELECT *, (occupied_seats/total_seats*100) AS pct FROM batches ORDER BY time_start")->fetchAll();

// Expense by category this month
$expenses = $db->query("
    SELECT category, SUM(amount) AS total
    FROM expenses
    WHERE MONTH(expense_date)=MONTH(NOW())
    GROUP BY category
    ORDER BY total DESC
")->fetchAll();

$currentPage = 'dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- DASHBOARD ALERTS -->
<?php
$alerts = [];
if ($overdueBooks > 0) $alerts[] = ['type'=>'al-d','icon'=>'🚨','title'=>"$overdueBooks Overdue Books",'body'=>'Books not returned on time. Send reminders.'];
if ($feePending > 50000) $alerts[] = ['type'=>'al-w','icon'=>'⚠️','title'=>'High Pending Fees','body'=>'₹'.number_format($feePending).' in pending fee collections.'];
if ($presentToday < $totalStudents * 0.7 && $totalStudents > 0) $alerts[] = ['type'=>'al-i','icon'=>'📋','title'=>'Low Attendance Today','body'=>"Only $presentToday of $totalStudents students marked present."];
?>
<?php if ($alerts): ?>
<div class="al-row">
  <?php foreach ($alerts as $al): ?>
  <div class="al-card <?= $al['type'] ?>">
    <div style="font-size:18px"><?= $al['icon'] ?></div>
    <div><div class="al-t"><?= $al['title'] ?></div><div class="al-b"><?= $al['body'] ?></div></div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- STATS GRID -->
<div class="stats-grid">
  <div class="sc" style="--ca:var(--ac)">
    <div class="s-ic" style="background:rgba(74,124,111,.1)">👨‍🎓</div>
    <div class="s-lb">Total Students</div>
    <div class="s-vl"><?= number_format($totalStudents) ?></div>
    <div class="s-mt">Active enrollments</div>
  </div>
  <div class="sc" style="--ca:var(--em)">
    <div class="s-ic" style="background:rgba(58,125,94,.1)">✅</div>
    <div class="s-lb">Fee Collected</div>
    <div class="s-vl">₹<?= number_format($feeCollected) ?></div>
    <div class="s-mt">This month</div>
  </div>
  <div class="sc" style="--ca:var(--gd)">
    <div class="s-ic" style="background:rgba(196,125,43,.1)">⏳</div>
    <div class="s-lb">Fee Pending</div>
    <div class="s-vl">₹<?= number_format($feePending) ?></div>
    <div class="s-mt">Across all students</div>
  </div>
  <div class="sc" style="--ca:var(--ro)">
    <div class="s-ic" style="background:rgba(192,68,79,.1)">🔄</div>
    <div class="s-lb">Overdue Books</div>
    <div class="s-vl"><?= $overdueBooks ?></div>
    <div class="s-mt">Need immediate action</div>
  </div>
  <div class="sc" style="--ca:var(--vi)">
    <div class="s-ic" style="background:rgba(124,92,191,.1)">📖</div>
    <div class="s-lb">Total Books</div>
    <div class="s-vl"><?= number_format($totalBooks) ?></div>
    <div class="s-mt">In catalog</div>
  </div>
  <div class="sc" style="--ca:var(--sk)">
    <div class="s-ic" style="background:rgba(58,122,176,.1)">🏫</div>
    <div class="s-lb">Batches</div>
    <div class="s-vl"><?= $totalBatches ?></div>
    <div class="s-mt">Active time slots</div>
  </div>
  <div class="sc" style="--ca:var(--or)">
    <div class="s-ic" style="background:rgba(230,126,34,.1)">📋</div>
    <div class="s-lb">Present Today</div>
    <div class="s-vl"><?= $presentToday ?></div>
    <div class="s-mt">Attendance marked</div>
  </div>
  <div class="sc" style="--ca:var(--ro)">
    <div class="s-ic" style="background:rgba(192,68,79,.1)">💸</div>
    <div class="s-lb">Expenses</div>
    <div class="s-vl">₹<?= number_format($totalExpenses) ?></div>
    <div class="s-mt">This month</div>
  </div>
</div>

<!-- QUICK ACTIONS -->
<div class="qa-gr">
  <a href="/pages/enroll.php" class="qa-b"><div class="qa-ic" style="background:rgba(74,124,111,.12)">➕</div><div class="qa-lb">New Enroll</div></a>
  <a href="/pages/fees.php" class="qa-b"><div class="qa-ic" style="background:rgba(58,125,94,.12)">💳</div><div class="qa-lb">Collect Fee</div></a>
  <a href="/pages/transactions.php?action=issue" class="qa-b"><div class="qa-ic" style="background:rgba(196,125,43,.12)">📤</div><div class="qa-lb">Issue Book</div></a>
  <a href="/pages/transactions.php?action=return" class="qa-b"><div class="qa-ic" style="background:rgba(124,92,191,.12)">📩</div><div class="qa-lb">Return Book</div></a>
  <a href="/pages/seats.php" class="qa-b"><div class="qa-ic" style="background:rgba(192,68,79,.12)">🪑</div><div class="qa-lb">Seat Booking</div></a>
  <a href="/pages/attendance.php" class="qa-b"><div class="qa-ic" style="background:rgba(58,122,176,.12)">📋</div><div class="qa-lb">Mark Attend.</div></a>
  <a href="/pages/expenses.php?add=1" class="qa-b"><div class="qa-ic" style="background:rgba(212,144,47,.12)">💸</div><div class="qa-lb">Add Expense</div></a>
  <a href="/pages/whatsapp.php" class="qa-b"><div class="qa-ic" style="background:rgba(37,211,102,.12)">💬</div><div class="qa-lb">WhatsApp</div></a>
</div>

<!-- ROW 1: Batches + Expenses -->
<div class="g2" style="margin-bottom:14px">
  <!-- Batch Cards -->
  <div>
    <div class="sec-hd">
      <div><div class="sec-t">Batch-wise Seat Availability</div><div class="sec-s">Live occupancy overview</div></div>
      <a href="/pages/seats.php" class="btn bg" style="font-size:11px;text-decoration:none">Manage →</a>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <?php foreach ($batches as $b):
        $pct = $b['total_seats'] > 0 ? round($b['occupied_seats'] / $b['total_seats'] * 100) : 0;
        $barClass = $pct >= 90 ? 'sf-r' : ($pct >= 70 ? 'sf-y' : 'sf-g');
        $stClass  = $b['status'] === 'full' ? 'bst-f' : ($pct >= 70 ? 'bst-n' : 'bst-o');
        $stLabel  = $b['status'] === 'full' ? 'Full' : ($pct >= 70 ? 'Filling' : 'Open');
      ?>
      <div class="panel" style="padding:12px 14px">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:7px">
          <div style="font-weight:600;font-size:12.5px"><?= htmlspecialchars($b['name']) ?></div>
          <span class="bst <?= $stClass ?>"><?= $stLabel ?></span>
        </div>
        <div style="font-size:11px;color:var(--tx3);font-family:var(--fm);margin-bottom:7px">
          <?= date('g:i A', strtotime($b['time_start'])) ?> – <?= date('g:i A', strtotime($b['time_end'])) ?>
        </div>
        <div class="sbar"><div class="sfill <?= $barClass ?>" style="width:<?= $pct ?>%"></div></div>
        <div style="display:flex;justify-content:space-between;font-size:10.5px;color:var(--tx3)">
          <span><?= $b['occupied_seats'] ?> / <?= $b['total_seats'] ?> seats</span>
          <span style="font-family:var(--fm)"><?= $pct ?>%</span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <!-- Expense Tracker -->
  <div>
    <div class="sec-hd">
      <div><div class="sec-t">Expense Tracker</div><div class="sec-s">Monthly outflows by category</div></div>
      <a href="/pages/expenses.php?add=1" class="btn bg" style="font-size:11px;text-decoration:none">+ Add</a>
    </div>
    <div class="panel">
      <?php if (empty($expenses)): ?>
      <div class="empty"><div class="ei">💸</div><div class="et">No expenses this month</div></div>
      <?php else: ?>
      <?php foreach ($expenses as $exp): ?>
      <div class="ei2">
        <div class="eic" style="background:rgba(192,68,79,.1)">💸</div>
        <div style="flex:1">
          <div class="en2"><?= ucfirst($exp['category']) ?></div>
        </div>
        <div class="ea ea-d">₹<?= number_format($exp['total']) ?></div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- ROW 2: Recent Students + Activities -->
<div class="g2">
  <!-- Recent Students -->
  <div>
    <div class="sec-hd">
      <div><div class="sec-t">Recent Students & Fee Status</div></div>
      <a href="/pages/students.php" class="btn bg" style="font-size:11px;text-decoration:none">All →</a>
    </div>
    <div class="panel">
      <div class="tw"><table>
        <thead><tr><th>Student</th><th>Batch</th><th>Seat</th><th>Net Fee</th><th>Paid</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($recentStudents as $s):
            $stCls = ['paid'=>'tpd','partial'=>'tor','pending'=>'tpn','overdue'=>'tod'][$s['fee_status']] ?? 'tpn';
          ?>
          <tr>
            <td>
              <div class="si">
                <div class="sav" style="background:linear-gradient(135deg,var(--ac),var(--vi))">
                  <?= strtoupper(substr($s['first_name'],0,1).substr($s['last_name']??'',0,1)) ?>
                </div>
                <div>
                  <div style="font-weight:600;font-size:12.5px"><?= htmlspecialchars($s['first_name'].' '.$s['last_name']) ?></div>
                  <div style="font-size:10px;color:var(--tx3);font-family:var(--fm)"><?= $s['student_code'] ?></div>
                </div>
              </div>
            </td>
            <td><?= htmlspecialchars($s['batch_name'] ?? '—') ?></td>
            <td style="font-family:var(--fm);font-size:11px"><?= htmlspecialchars($s['seat'] ?? '—') ?></td>
            <td style="font-family:var(--fm)">₹<?= number_format($s['net_fee']) ?></td>
            <td style="font-family:var(--fm)">₹<?= number_format($s['paid_fee']) ?></td>
            <td><span class="tag <?= $stCls ?>"><?= ucfirst($s['fee_status']) ?></span></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($recentStudents)): ?>
          <tr><td colspan="6"><div class="empty"><div class="ei">👨‍🎓</div><div class="et">No students yet</div></div></td></tr>
          <?php endif; ?>
        </tbody>
      </table></div>
    </div>
  </div>
  <!-- Activity Log -->
  <div>
    <div class="sec-hd"><div class="sec-t">Recent Activity</div></div>
    <div class="panel" style="padding:14px">
      <?php if (empty($activities)): ?>
      <div class="empty"><div class="ei">📜</div><div class="et">No activity yet</div></div>
      <?php else: ?>
      <?php foreach ($activities as $act): ?>
      <div class="act-it">
        <div class="act-d" style="background:rgba(74,124,111,.12)">⚡</div>
        <div>
          <div class="act-tx"><?= htmlspecialchars($act['action']) ?> <strong><?= htmlspecialchars($act['user_name'] ?? '') ?></strong></div>
          <div class="act-tm"><?= date('d M, g:i A', strtotime($act['created_at'])) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
