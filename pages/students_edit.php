<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
requireLogin();

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

$student = $db->prepare("SELECT s.*, b.name AS batch_name FROM students s LEFT JOIN batches b ON s.batch_id=b.id WHERE s.id=?");
$student->execute([$id]);
$student = $student->fetch();

if (!$student) { header('Location: /pages/students.php'); exit; }

$batches = $db->query("SELECT * FROM batches ORDER BY time_start")->fetchAll();
$errors  = [];

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
    $status   = $_POST['status'] ?? 'active';
    $notes    = trim($_POST['notes'] ?? '');

    if (!$fname) $errors[] = 'First name is required.';
    if (empty($errors)) {
        $db->prepare("UPDATE students SET first_name=?,last_name=?,email=?,phone=?,batch_id=?,seat=?,student_type=?,base_fee=?,discount=?,net_fee=?,due_date=?,status=?,notes=? WHERE id=?")
           ->execute([$fname,$lname,$email,$phone,$batchId,$seat,$type,$baseFee,$discount,$netFee,$dueDate,$status,$notes,$id]);
        logActivity("Updated student: $fname $lname", 'student', $id);
        $_SESSION['toast'] = ['msg' => 'Student updated!', 'type' => 'ok'];
        header('Location: /pages/students.php'); exit;
    }
}

$currentPage = 'students';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="sec-hd">
  <div><div class="sec-t">Edit Student</div><div class="sec-s"><?= htmlspecialchars($student['first_name'].' '.$student['last_name']) ?></div></div>
  <a href="/pages/students.php" class="btn bg" style="text-decoration:none">← Back</a>
</div>

<?php if ($errors): ?>
<div class="alert alert-error" style="margin-bottom:14px"><?php foreach ($errors as $e): ?><div>❌ <?= htmlspecialchars($e) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<div class="panel" style="max-width:720px">
  <div class="ph"><div class="pt">Student Details — <?= htmlspecialchars($student['student_code']) ?></div></div>
  <form method="POST" style="padding:20px">
    <div class="fg">
      <div class="fgi"><label>First Name *</label><input type="text" name="first_name" value="<?= htmlspecialchars($student['first_name']) ?>" required></div>
      <div class="fgi"><label>Last Name</label><input type="text" name="last_name" value="<?= htmlspecialchars($student['last_name'] ?? '') ?>"></div>
      <div class="fgi"><label>Phone</label><input type="tel" name="phone" value="<?= htmlspecialchars($student['phone'] ?? '') ?>"></div>
      <div class="fgi"><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($student['email'] ?? '') ?>"></div>
      <div class="fgi"><label>Batch</label>
        <select name="batch_id">
          <option value="">-- No Batch --</option>
          <?php foreach ($batches as $b): ?>
          <option value="<?= $b['id'] ?>" <?= $student['batch_id']==$b['id']?'selected':'' ?>><?= htmlspecialchars($b['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="fgi"><label>Seat No.</label><input type="text" name="seat" value="<?= htmlspecialchars($student['seat'] ?? '') ?>"></div>
      <div class="fgi"><label>Type</label>
        <select name="student_type">
          <?php foreach (['regular','part-time','online'] as $t): ?>
          <option value="<?= $t ?>" <?= $student['student_type']===$t?'selected':'' ?>><?= ucfirst($t) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="fgi"><label>Status</label>
        <select name="status">
          <option value="active" <?= $student['status']==='active'?'selected':'' ?>>Active</option>
          <option value="inactive" <?= $student['status']==='inactive'?'selected':'' ?>>Inactive</option>
        </select>
      </div>
      <div class="fgi"><label>Full Fee (₹)</label><input type="number" name="base_fee" value="<?= $student['base_fee'] ?>" min="0" oninput="calcNet()"></div>
      <div class="fgi"><label>Discount (₹)</label><input type="number" name="discount" id="discFee" value="<?= $student['discount'] ?>" min="0" oninput="calcNet()"></div>
      <div class="fgi"><label>Net Fee (₹)</label><input type="number" name="net_fee" id="netFeeDisp" value="<?= $student['net_fee'] ?>" readonly style="background:var(--sf3);font-weight:700"></div>
      <div class="fgi"><label>Due Date</label><input type="date" name="due_date" value="<?= $student['due_date'] ?? '' ?>"></div>
      <div class="fgi full"><label>Notes</label><textarea name="notes"><?= htmlspecialchars($student['notes'] ?? '') ?></textarea></div>
    </div>
    <div style="display:flex;gap:9px;margin-top:16px">
      <button type="submit" class="btn bp">💾 Save Changes</button>
      <a href="/pages/students.php" class="btn bg" style="text-decoration:none">Cancel</a>
    </div>
  </form>
</div>
<script>
function calcNet() {
  const base = parseFloat(document.querySelector('[name=base_fee]').value) || 0;
  const disc = parseFloat(document.getElementById('discFee').value) || 0;
  document.getElementById('netFeeDisp').value = Math.max(0, base - disc);
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
