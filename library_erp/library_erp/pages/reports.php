<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
requireLogin();

$db = getDB();

$totalRevenue  = $db->query("SELECT SUM(amount) FROM fees")->fetchColumn() ?: 0;
$totalExpenses = $db->query("SELECT SUM(amount) FROM expenses")->fetchColumn() ?: 0;
$netProfit     = $totalRevenue - $totalExpenses;
$totalFine     = $db->query("SELECT SUM(fine_paid) FROM transactions")->fetchColumn() ?: 0;
$totalStudents = $db->query("SELECT COUNT(*) FROM students WHERE status='active'")->fetchColumn();
$paidStudents  = $db->query("SELECT COUNT(*) FROM students WHERE fee_status='paid' AND status='active'")->fetchColumn();

// Monthly breakdown
$monthly = $db->query("
    SELECT m.month,
           IFNULL(f.collected,0) AS collected,
           IFNULL(e.spent,0) AS spent
    FROM (
        SELECT DATE_FORMAT(d,'%b %Y') AS month, DATE_FORMAT(d,'%Y-%m') AS ym
        FROM (
            SELECT DATE_SUB(CURDATE(), INTERVAL n MONTH) AS d
            FROM (SELECT 0 n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) nums
        ) months
    ) m
    LEFT JOIN (SELECT DATE_FORMAT(payment_date,'%Y-%m') AS ym, SUM(amount) AS collected FROM fees GROUP BY ym) f ON m.ym=f.ym
    LEFT JOIN (SELECT DATE_FORMAT(expense_date,'%Y-%m') AS ym, SUM(amount) AS spent FROM expenses GROUP BY ym) e ON m.ym=e.ym
    ORDER BY m.ym ASC
")->fetchAll();

$currentPage = 'reports';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="sec-hd">
  <div><div class="sec-t">Reports</div><div class="sec-s">Financial summary</div></div>
  <button class="btn bg" onclick="window.print()">🖨️ Print</button>
</div>

<div class="stats-grid">
  <div class="sc" style="--ca:var(--em)"><div class="s-lb">Total Revenue</div><div class="s-vl">₹<?= number_format($totalRevenue) ?></div><div class="s-mt">All time fee collections</div></div>
  <div class="sc" style="--ca:var(--ro)"><div class="s-lb">Total Expenses</div><div class="s-vl">₹<?= number_format($totalExpenses) ?></div><div class="s-mt">All time expenses</div></div>
  <div class="sc" style="--ca:<?= $netProfit >= 0 ? 'var(--em)' : 'var(--ro)' ?>"><div class="s-lb">Net Profit</div><div class="s-vl" style="color:<?= $netProfit >= 0 ? 'var(--em)' : 'var(--ro)' ?>">₹<?= number_format(abs($netProfit)) ?></div><div class="s-mt"><?= $netProfit >= 0 ? 'Profit' : 'Loss' ?></div></div>
  <div class="sc" style="--ca:var(--gd)"><div class="s-lb">Fine Collected</div><div class="s-vl">₹<?= number_format($totalFine) ?></div><div class="s-mt">Late return fines</div></div>
</div>

<div class="panel">
  <div class="ph"><div class="pt">📅 Monthly Breakdown</div></div>
  <div class="tw"><table>
    <thead><tr><th>Month</th><th>Fee Collected</th><th>Expenses</th><th>Net</th></tr></thead>
    <tbody>
      <?php foreach (array_reverse($monthly) as $m):
        $net = $m['collected'] - $m['spent'];
      ?>
      <tr>
        <td style="font-weight:600"><?= $m['month'] ?></td>
        <td style="font-family:var(--fm);color:var(--em)">₹<?= number_format($m['collected']) ?></td>
        <td style="font-family:var(--fm);color:var(--ro)">₹<?= number_format($m['spent']) ?></td>
        <td style="font-family:var(--fm);font-weight:700;color:<?= $net>=0?'var(--em)':'var(--ro)' ?>">
          <?= $net >= 0 ? '+' : '' ?>₹<?= number_format(abs($net)) ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
</div>

<div class="g2">
  <div class="panel">
    <div class="ph"><div class="pt">👨‍🎓 Student Fee Status</div></div>
    <div style="padding:16px">
      <?php
      $feeStatus = $db->query("SELECT fee_status, COUNT(*) as cnt FROM students WHERE status='active' GROUP BY fee_status")->fetchAll();
      $total = array_sum(array_column($feeStatus, 'cnt'));
      foreach ($feeStatus as $fs):
        $pct = $total > 0 ? round($fs['cnt']/$total*100) : 0;
        $colors = ['paid'=>'var(--em)','partial'=>'var(--or)','pending'=>'var(--gd)','overdue'=>'var(--ro)'];
        $color = $colors[$fs['fee_status']] ?? 'var(--ac)';
      ?>
      <div style="margin-bottom:10px">
        <div style="display:flex;justify-content:space-between;margin-bottom:4px;font-size:12px">
          <span style="text-transform:capitalize;font-weight:500"><?= $fs['fee_status'] ?></span>
          <span style="font-family:var(--fm)"><?= $fs['cnt'] ?> (<?= $pct ?>%)</span>
        </div>
        <div class="prg"><div class="prf" style="width:<?= $pct ?>%;background:<?= $color ?>"></div></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="panel">
    <div class="ph"><div class="pt">📖 Book Status</div></div>
    <div style="padding:16px">
      <?php
      $bookStats = $db->query("SELECT SUM(total_copies) AS total, SUM(available_copies) AS available FROM books")->fetch();
      $issued = ($bookStats['total'] ?? 0) - ($bookStats['available'] ?? 0);
      $issuedPct = $bookStats['total'] > 0 ? round($issued/$bookStats['total']*100) : 0;
      ?>
      <div style="margin-bottom:10px">
        <div style="display:flex;justify-content:space-between;margin-bottom:4px;font-size:12px"><span>Total Books</span><span style="font-family:var(--fm);font-weight:700"><?= number_format($bookStats['total'] ?? 0) ?></span></div>
        <div style="display:flex;justify-content:space-between;margin-bottom:4px;font-size:12px"><span>Available</span><span style="font-family:var(--fm);color:var(--em);font-weight:700"><?= number_format($bookStats['available'] ?? 0) ?></span></div>
        <div style="display:flex;justify-content:space-between;margin-bottom:10px;font-size:12px"><span>Currently Issued</span><span style="font-family:var(--fm);color:var(--or);font-weight:700"><?= number_format($issued) ?></span></div>
        <div class="prg"><div class="prf" style="width:<?= $issuedPct ?>%;background:var(--or)"></div></div>
        <div style="font-size:10px;color:var(--tx3);margin-top:4px"><?= $issuedPct ?>% of books are issued</div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
