<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
requireLogin();

$db = getDB();
$batches = $db->query("SELECT * FROM batches WHERE status != 'closed' ORDER BY time_start")->fetchAll();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname    = trim($_POST['first_name'] ?? '');
    $lname    = trim($_POST['last_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $batchId  = (int)($_POST['batch_id'] ?? 0);
    $seat     = trim($_POST['seat'] ?? '');
    $type     = $_POST['student_type'] ?? 'regular';
    $baseFee  = (float)($_POST['base_fee'] ?? 0);
    $discount = (float)($_POST['discount'] ?? 0);
    $netFee   = $baseFee - $discount;
    $dueDate  = $_POST['due_date'] ?? null;
    $notes    = trim($_POST['notes'] ?? '');

    if (!$fname) $errors[] = 'First name is required.';
    if (!$phone) $errors[] = 'Phone number is required.';
    if (!$batchId) $errors[] = 'Please select a batch.';

    if (empty($errors)) {
        // Generate student code
        $lastCode = $db->query("SELECT student_code FROM students ORDER BY id DESC LIMIT 1")->fetchColumn();
        $num      = $lastCode ? ((int)substr($lastCode, 3) + 1) : 1001;
        $code     = 'STU' . str_pad($num, 4, '0', STR_PAD_LEFT);

        $stmt = $db->prepare("
            INSERT INTO students
            (student_code, first_name, last_name, email, phone, batch_id, seat, student_type,
             base_fee, discount, net_fee, paid_fee, fee_status, due_date, notes, enroll_date)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,0,'pending',?,?,CURDATE())
        ");
        $stmt->execute([$code,$fname,$lname,$email,$phone,$batchId,$seat,$type,$baseFee,$discount,$netFee,$dueDate,$notes]);
        $newId = $db->lastInsertId();

        // Update batch seat count
        $db->prepare("UPDATE batches SET occupied_seats = occupied_seats + 1 WHERE id = ?")->execute([$batchId]);

        logActivity("Enrolled new student: $fname $lname", 'student', $newId);
        $_SESSION['toast'] = ['msg' => "$fname $lname enrolled successfully! Code: $code", 'type' => 'ok'];
        header('Location: /pages/students.php');
        exit;
    }
}

$currentPage = 'enroll';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="sec-hd">
  <div><div class="sec-t">Enroll New Student</div><div class="sec-s">Fill in the details below</div></div>
  <a href="/pages/students.php" class="btn bg" style="text-decoration:none">← Back</a>
</div>

<?php if ($errors): ?>
<div class="alert alert-error" style="margin-bottom:14px">
  <?php foreach ($errors as $e): ?><div>❌ <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<div class="panel" style="max-width:720px">
  <div class="ph"><div class="pt">Student Information</div></div>
  <form method="POST" class="mb" style="padding:20px">
    <div class="fg">
      <div class="fgi">
        <label>First Name *</label>
        <input type="text" name="first_name" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>
      </div>
      <div class="fgi">
        <label>Last Name</label>
        <input type="text" name="last_name" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
      </div>
      <div class="fgi">
        <label>Phone *</label>
        <input type="tel" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required>
      </div>
      <div class="fgi">
        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>
      <div class="fgi">
        <label>Batch *</label>
        <select name="batch_id" required onchange="updateFee(this)">
          <option value="">-- Select Batch --</option>
          <?php foreach ($batches as $b): ?>
          <option value="<?= $b['id'] ?>" data-fee="<?= $b['fee'] ?>"
                  <?= (($_POST['batch_id'] ?? '') == $b['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($b['name']) ?> (<?= date('g:i A', strtotime($b['time_start'])) ?> – <?= date('g:i A', strtotime($b['time_end'])) ?>)
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="fgi">
        <label>Seat No.</label>
        <input type="text" name="seat" value="<?= htmlspecialchars($_POST['seat'] ?? '') ?>" placeholder="e.g. A1">
      </div>
      <div class="fgi">
        <label>Student Type</label>
        <select name="student_type">
          <option value="regular">Regular</option>
          <option value="part-time">Part-time</option>
          <option value="online">Online</option>
        </select>
      </div>
      <div class="fgi">
        <label>Due Date</label>
        <input type="date" name="due_date" value="<?= $_POST['due_date'] ?? '' ?>">
      </div>
      <div class="fgi">
        <label>Full Fee (₹)</label>
        <input type="number" name="base_fee" id="baseFee" value="<?= $_POST['base_fee'] ?? 0 ?>" min="0" oninput="calcNet()">
      </div>
      <div class="fgi">
        <label>Discount (₹)</label>
        <input type="number" name="discount" id="discFee" value="<?= $_POST['discount'] ?? 0 ?>" min="0" oninput="calcNet()">
      </div>
      <div class="fgi">
        <label>Net Fee (₹)</label>
        <input type="number" name="net_fee" id="netFeeDisp" value="<?= $_POST['net_fee'] ?? 0 ?>" readonly style="background:var(--sf3);font-weight:700">
      </div>
      <div class="fgi full">
        <label>Notes</label>
        <textarea name="notes" placeholder="Optional notes about the student..."><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
      </div>
    </div>
    <div style="display:flex;gap:9px;margin-top:16px">
      <button type="submit" class="btn bp">✅ Enroll Student</button>
      <a href="/pages/students.php" class="btn bg" style="text-decoration:none">Cancel</a>
    </div>
  </form>
</div>

<script>
function updateFee(sel) {
  const opt = sel.options[sel.selectedIndex];
  const fee = opt.dataset.fee || 0;
  document.getElementById('baseFee').value = fee;
  calcNet();
}
function calcNet() {
  const base = parseFloat(document.getElementById('baseFee').value) || 0;
  const disc = parseFloat(document.getElementById('discFee').value) || 0;
  document.getElementById('netFeeDisp').value = Math.max(0, base - disc);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
