<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
requireLogin();

$db = getDB();
$search = trim($_GET['q'] ?? '');
$filter = $_GET['filter'] ?? 'all';
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build query
$where  = ["s.status='active'"];
$params = [];
if ($search) {
    $where[]  = "(s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_code LIKE ? OR s.phone LIKE ?)";
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like, $like]);
}
if ($filter !== 'all') {
    $where[]  = "s.fee_status = ?";
    $params[] = $filter;
}
$whereSQL = implode(' AND ', $where);

$total = $db->prepare("SELECT COUNT(*) FROM students s WHERE $whereSQL");
$total->execute($params);
$totalRows = $total->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

$stmt = $db->prepare("
    SELECT s.*, b.name AS batch_name
    FROM students s
    LEFT JOIN batches b ON s.batch_id = b.id
    WHERE $whereSQL
    ORDER BY s.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$students = $stmt->fetchAll();

$currentPage = 'students';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="sec-hd">
  <div>
    <div class="sec-t">All Students</div>
    <div class="sec-s"><?= $totalRows ?> students found</div>
  </div>
  <div style="display:flex;gap:7px;align-items:center;flex-wrap:wrap">
    <form method="GET" style="display:contents">
      <input name="q" placeholder="Search…" style="width:130px;font-size:11.5px" value="<?= htmlspecialchars($search) ?>">
      <input type="hidden" name="filter" value="<?= $filter ?>">
    </form>
    <div class="tabs">
      <?php foreach (['all','paid','partial','pending','overdue'] as $f): ?>
      <a href="?filter=<?= $f ?>&q=<?= urlencode($search) ?>" class="tab <?= $filter===$f?'active':'' ?>"><?= ucfirst($f) ?></a>
      <?php endforeach; ?>
    </div>
    <a href="/pages/enroll.php" class="btn bp" style="text-decoration:none">+ Enroll</a>
    <a href="/pages/whatsapp.php" class="btn bwa" style="font-size:11px;text-decoration:none">💬 Bulk Msg</a>
  </div>
</div>

<div class="panel">
  <div class="tw"><table>
    <thead>
      <tr>
        <th>Student</th><th>Batch</th><th>Seat</th><th>Type</th>
        <th>Full Fee</th><th>Discount</th><th>Net Fee</th><th>Paid</th>
        <th>Balance</th><th>Status</th><th>Due Date</th><th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($students as $s):
        $balance = $s['net_fee'] - $s['paid_fee'];
        $stCls   = ['paid'=>'tpd','partial'=>'tor','pending'=>'tpn','overdue'=>'tod'][$s['fee_status']] ?? 'tpn';
        $rowCls  = $s['fee_status'] === 'overdue' ? 'fee-due-row' : ($s['fee_status'] === 'partial' ? 'fee-partial-row' : '');
      ?>
      <tr class="<?= $rowCls ?>">
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
        <td><span class="tag tac" style="text-transform:capitalize"><?= $s['student_type'] ?></span></td>
        <td style="font-family:var(--fm)">₹<?= number_format($s['base_fee']) ?></td>
        <td style="font-family:var(--fm);color:var(--or)">
          <?= $s['discount'] > 0 ? '-₹'.number_format($s['discount']) : '—' ?>
        </td>
        <td style="font-family:var(--fm);font-weight:600">₹<?= number_format($s['net_fee']) ?></td>
        <td style="font-family:var(--fm);color:var(--em)">₹<?= number_format($s['paid_fee']) ?></td>
        <td>
          <?php if ($balance > 0): ?>
          <span class="fee-bal-badge">₹<?= number_format($balance) ?></span>
          <?php else: ?>
          <span style="color:var(--em);font-size:11px">✅</span>
          <?php endif; ?>
        </td>
        <td><span class="tag <?= $stCls ?>"><?= ucfirst($s['fee_status']) ?></span></td>
        <td style="font-size:11px;font-family:var(--fm)">
          <?= $s['due_date'] ? date('d M Y', strtotime($s['due_date'])) : '—' ?>
        </td>
        <td>
          <div style="display:flex;gap:4px">
            <a href="/pages/students_edit.php?id=<?= $s['id'] ?>" class="btn bg" style="font-size:10px;padding:3px 7px;text-decoration:none">✏</a>
            <a href="/pages/fees.php?student_id=<?= $s['id'] ?>" class="btn bp" style="font-size:10px;padding:3px 7px;text-decoration:none">💳</a>
            <a href="/api/students.php?action=whatsapp&id=<?= $s['id'] ?>" class="btn bwa" style="font-size:10px;padding:3px 6px;text-decoration:none" target="_blank">💬</a>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($students)): ?>
      <tr><td colspan="12"><div class="empty"><div class="ei">👨‍🎓</div><div class="et">No students found</div></div></td></tr>
      <?php endif; ?>
    </tbody>
  </table></div>
  <!-- PAGINATION -->
  <div class="pag">
    <span class="pag-i">Showing <?= count($students) ?> of <?= $totalRows ?> students</span>
    <div class="pag-b">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
      <a href="?page=<?= $i ?>&filter=<?= $filter ?>&q=<?= urlencode($search) ?>"
         class="pb2 <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
      <?php endfor; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
