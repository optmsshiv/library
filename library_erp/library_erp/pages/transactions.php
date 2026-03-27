<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
requireLogin();

$db = getDB();

// Handle issue book
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'issue_book') {
    $studentId = (int)$_POST['student_id'];
    $bookId    = (int)$_POST['book_id'];
    $issueDate = $_POST['issue_date'] ?? date('Y-m-d');
    $dueDate   = $_POST['due_date'] ?? date('Y-m-d', strtotime('+7 days'));

    $code = 'TX-' . date('Ymd') . '-' . rand(100, 999);

    $db->prepare("INSERT INTO transactions (transaction_code, student_id, book_id, issue_date, due_date, status, issued_by) VALUES (?,?,?,?,?,'issued',?)")
       ->execute([$code, $studentId, $bookId, $issueDate, $dueDate, $_SESSION['user_id']]);
    $db->prepare("UPDATE books SET available_copies = available_copies - 1 WHERE id = ? AND available_copies > 0")->execute([$bookId]);

    logActivity("Issued book ID $bookId to student ID $studentId", 'transaction');
    $_SESSION['toast'] = ['msg' => 'Book issued! Code: '.$code, 'type' => 'ok'];
    header('Location: /pages/transactions.php');
    exit;
}

// Handle return book
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'return_book') {
    $txId      = (int)$_POST['tx_id'];
    $returnDate = $_POST['return_date'] ?? date('Y-m-d');
    $fine      = (float)$_POST['fine'] ?? 0;

    $tx = $db->prepare("SELECT * FROM transactions WHERE id = ? AND status != 'returned'");
    $tx->execute([$txId]);
    $tx = $tx->fetch();

    if ($tx) {
        $db->prepare("UPDATE transactions SET status='returned', return_date=?, fine_amount=?, fine_paid=? WHERE id=?")
           ->execute([$returnDate, $fine, $fine, $txId]);
        $db->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE id = ?")->execute([$tx['book_id']]);

        logActivity("Returned book transaction ID $txId", 'transaction', $txId);
        $_SESSION['toast'] = ['msg' => 'Book returned successfully!', 'type' => 'ok'];
    }
    header('Location: /pages/transactions.php');
    exit;
}

// Fetch transactions
$filter = $_GET['filter'] ?? 'all';
$where  = ['1=1'];
$params = [];
if ($filter === 'issued')   { $where[] = "t.status = 'issued'"; }
if ($filter === 'overdue')  { $where[] = "t.status = 'overdue' OR (t.status='issued' AND t.due_date < CURDATE())"; }
if ($filter === 'returned') { $where[] = "t.status = 'returned'"; }
$whereSQL = implode(' AND ', $where);

$transactions = $db->prepare("
    SELECT t.*, s.first_name, s.last_name, s.student_code, bk.title AS book_title, bk.book_code,
           DATEDIFF(CURDATE(), t.due_date) AS days_overdue
    FROM transactions t
    JOIN students s ON t.student_id = s.id
    JOIN books bk ON t.book_id = bk.id
    WHERE $whereSQL
    ORDER BY t.created_at DESC LIMIT 100
");
$transactions->execute($params);
$transactions = $transactions->fetchAll();

// Stats
$issued   = $db->query("SELECT COUNT(*) FROM transactions WHERE status='issued'")->fetchColumn();
$overdue  = $db->query("SELECT COUNT(*) FROM transactions WHERE status='overdue' OR (status='issued' AND due_date < CURDATE())")->fetchColumn();
$returned = $db->query("SELECT COUNT(*) FROM transactions WHERE status='returned'")->fetchColumn();
$fineTotal= $db->query("SELECT SUM(fine_paid) FROM transactions")->fetchColumn() ?: 0;

$students = $db->query("SELECT id, first_name, last_name, student_code FROM students WHERE status='active' ORDER BY first_name")->fetchAll();
$books    = $db->query("SELECT id, title, book_code, available_copies FROM books WHERE available_copies > 0 ORDER BY title")->fetchAll();
$activeTransactions = $db->query("SELECT t.id, t.transaction_code, s.first_name, s.last_name, bk.title, t.due_date FROM transactions t JOIN students s ON t.student_id=s.id JOIN books bk ON t.book_id=bk.id WHERE t.status='issued'")->fetchAll();

$finePerDay = $db->query("SELECT setting_value FROM settings WHERE setting_key='fine_per_day'")->fetchColumn() ?: 5;

$currentPage = 'transactions';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="sec-hd">
  <div>
    <div class="sec-t">Issue & Returns</div>
    <div class="sec-s"><?= count($transactions) ?> transactions</div>
  </div>
  <div style="display:flex;gap:7px">
    <button class="btn bp" onclick="document.getElementById('issueModal').classList.add('open')">📤 Issue Book</button>
    <button class="btn bg" onclick="document.getElementById('returnModal').classList.add('open')">📩 Return Book</button>
  </div>
</div>

<div class="stats-grid" style="grid-template-columns:repeat(4,1fr)">
  <div class="sc" style="--ca:var(--vi)"><div class="s-lb">Issued</div><div class="s-vl"><?= $issued ?></div></div>
  <div class="sc" style="--ca:var(--ro)"><div class="s-lb">Overdue</div><div class="s-vl"><?= $overdue ?></div></div>
  <div class="sc" style="--ca:var(--em)"><div class="s-lb">Returned</div><div class="s-vl"><?= $returned ?></div></div>
  <div class="sc" style="--ca:var(--gd)"><div class="s-lb">Fine Collected</div><div class="s-vl">₹<?= number_format($fineTotal) ?></div></div>
</div>

<div style="display:flex;gap:7px;margin-bottom:12px">
  <?php foreach (['all','issued','overdue','returned'] as $f): ?>
  <a href="?filter=<?= $f ?>" class="btn <?= $filter===$f?'bp':'bg' ?>" style="text-decoration:none"><?= ucfirst($f) ?></a>
  <?php endforeach; ?>
</div>

<div class="panel"><div class="tw"><table>
  <thead><tr><th>Student</th><th>Book</th><th>Issue Date</th><th>Due Date</th><th>Return Date</th><th>Fine</th><th>Status</th><th>Action</th></tr></thead>
  <tbody>
    <?php foreach ($transactions as $tx):
      $isOverdue = ($tx['status'] === 'issued' && strtotime($tx['due_date']) < time());
      $stCls = $tx['status'] === 'returned' ? 'tpd' : ($isOverdue ? 'tod' : 'tac');
      $stLabel = $tx['status'] === 'returned' ? 'Returned' : ($isOverdue ? 'Overdue' : 'Issued');
      $calcFine = $isOverdue ? max(0, $tx['days_overdue']) * $finePerDay : 0;
    ?>
    <tr>
      <td>
        <div style="font-weight:600;font-size:12px"><?= htmlspecialchars($tx['first_name'].' '.$tx['last_name']) ?></div>
        <div style="font-size:10px;color:var(--tx3);font-family:var(--fm)"><?= $tx['student_code'] ?></div>
      </td>
      <td>
        <div style="font-weight:600;font-size:12px"><?= htmlspecialchars($tx['book_title']) ?></div>
        <div style="font-size:10px;color:var(--tx3);font-family:var(--fm)"><?= $tx['book_code'] ?> · <?= $tx['transaction_code'] ?></div>
      </td>
      <td style="font-size:11.5px;font-family:var(--fm)"><?= date('d M Y', strtotime($tx['issue_date'])) ?></td>
      <td style="font-size:11.5px;font-family:var(--fm);color:<?= $isOverdue?'var(--ro)':'var(--tx2)' ?>">
        <?= date('d M Y', strtotime($tx['due_date'])) ?>
        <?php if ($isOverdue): ?><div style="font-size:10px;color:var(--ro)"><?= $tx['days_overdue'] ?> days late</div><?php endif; ?>
      </td>
      <td style="font-size:11.5px;font-family:var(--fm)"><?= $tx['return_date'] ? date('d M Y', strtotime($tx['return_date'])) : '—' ?></td>
      <td style="font-family:var(--fm);color:var(--ro)"><?= ($tx['fine_amount'] > 0 || $calcFine > 0) ? '₹'.number_format($tx['fine_amount'] ?: $calcFine) : '—' ?></td>
      <td><span class="tag <?= $stCls ?>"><?= $stLabel ?></span></td>
      <td>
        <?php if ($tx['status'] !== 'returned'): ?>
        <button class="btn bg" style="font-size:10px;padding:3px 7px"
                onclick="openReturn(<?= $tx['id'] ?>, '<?= htmlspecialchars($tx['book_title'], ENT_QUOTES) ?>', <?= $calcFine ?>)">
          📩 Return
        </button>
        <?php else: ?>
        <span style="font-size:11px;color:var(--tx3)">Closed</span>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($transactions)): ?>
    <tr><td colspan="8"><div class="empty"><div class="ei">🔄</div><div class="et">No transactions yet</div></div></td></tr>
    <?php endif; ?>
  </tbody>
</table></div></div>

<!-- ISSUE MODAL -->
<div class="mo" id="issueModal">
  <div class="md">
    <div class="mh"><span class="mt">📤 Issue Book</span><button class="mc" onclick="document.getElementById('issueModal').classList.remove('open')">✕</button></div>
    <form method="POST">
      <input type="hidden" name="action" value="issue_book">
      <div class="mb">
        <div class="fg">
          <div class="fgi full"><label>Student *</label>
            <select name="student_id" required>
              <option value="">-- Select Student --</option>
              <?php foreach ($students as $s): ?>
              <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['first_name'].' '.$s['last_name'].' ('.$s['student_code'].')') ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="fgi full"><label>Book *</label>
            <select name="book_id" required>
              <option value="">-- Select Book --</option>
              <?php foreach ($books as $b): ?>
              <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['title'].' ('.$b['book_code'].') — '.$b['available_copies'].' left') ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="fgi"><label>Issue Date</label><input type="date" name="issue_date" value="<?= date('Y-m-d') ?>"></div>
          <div class="fgi"><label>Due Date</label><input type="date" name="due_date" value="<?= date('Y-m-d', strtotime('+7 days')) ?>"></div>
        </div>
      </div>
      <div class="mf"><button type="button" class="btn bg" onclick="document.getElementById('issueModal').classList.remove('open')">Cancel</button><button type="submit" class="btn bp">📤 Issue</button></div>
    </form>
  </div>
</div>

<!-- RETURN MODAL -->
<div class="mo" id="returnModal">
  <div class="md">
    <div class="mh"><span class="mt">📩 Return Book</span><button class="mc" onclick="document.getElementById('returnModal').classList.remove('open')">✕</button></div>
    <form method="POST">
      <input type="hidden" name="action" value="return_book">
      <div class="mb">
        <div class="fg">
          <div class="fgi full"><label>Transaction *</label>
            <select name="tx_id" id="returnTxSelect" required onchange="updateFine(this)">
              <option value="">-- Select Issued Book --</option>
              <?php foreach ($activeTransactions as $atx): ?>
              <option value="<?= $atx['id'] ?>" data-due="<?= $atx['due_date'] ?>">
                <?= htmlspecialchars($atx['transaction_code'].' — '.$atx['first_name'].' '.$atx['last_name'].' · '.$atx['title']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="fgi"><label>Return Date</label><input type="date" name="return_date" value="<?= date('Y-m-d') ?>"></div>
          <div class="fgi"><label>Fine Amount (₹)</label><input type="number" name="fine" id="returnFine" value="0" min="0"></div>
        </div>
      </div>
      <div class="mf"><button type="button" class="btn bg" onclick="document.getElementById('returnModal').classList.remove('open')">Cancel</button><button type="submit" class="btn bp">📩 Return</button></div>
    </form>
  </div>
</div>

<script>
const FINE_PER_DAY = <?= $finePerDay ?>;
function openReturn(id, title, fine) {
  document.getElementById('returnTxSelect').value = id;
  document.getElementById('returnFine').value = fine;
  document.getElementById('returnModal').classList.add('open');
}
function updateFine(sel) {
  const opt = sel.options[sel.selectedIndex];
  const due = opt.dataset.due;
  if (due) {
    const days = Math.max(0, Math.floor((Date.now() - new Date(due)) / 86400000));
    document.getElementById('returnFine').value = days * FINE_PER_DAY;
  }
}
document.querySelectorAll('.mo').forEach(m => m.addEventListener('click', e => { if(e.target===m) m.classList.remove('open'); }));
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
