<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
requireLogin();

$db = getDB();

// Handle add batch
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_batch') {
    $name   = trim($_POST['name'] ?? '');
    $start  = $_POST['time_start'] ?? '';
    $end    = $_POST['time_end'] ?? '';
    $total  = (int)($_POST['total_seats'] ?? 0);
    $fee    = (float)($_POST['fee'] ?? 0);
    if ($name && $total > 0) {
        $db->prepare("INSERT INTO batches (name,time_start,time_end,total_seats,fee,status) VALUES (?,?,?,?,'open','open')")
           ->execute([$name,$start,$end,$total,$fee]);
        logActivity("Added batch: $name");
        $_SESSION['toast'] = ['msg' => "Batch '$name' created!", 'type' => 'ok'];
    }
    header('Location: /pages/seats.php'); exit;
}

// Handle allocate seat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'alloc_seat') {
    $stuId  = (int)$_POST['student_id'];
    $batchId= (int)$_POST['batch_id'];
    $seat   = trim($_POST['seat'] ?? '');
    if ($stuId && $batchId && $seat) {
        $db->prepare("UPDATE students SET seat=?, batch_id=? WHERE id=?")->execute([$seat,$batchId,$stuId]);
        $db->prepare("UPDATE batches SET occupied_seats = (SELECT COUNT(*) FROM students WHERE batch_id=? AND seat IS NOT NULL AND seat != '') WHERE id=?")
           ->execute([$batchId,$batchId]);
        logActivity("Allocated seat $seat to student ID $stuId");
        $_SESSION['toast'] = ['msg' => "Seat $seat allocated!", 'type' => 'ok'];
    }
    header('Location: /pages/seats.php'); exit;
}

$batches = $db->query("SELECT * FROM batches ORDER BY time_start")->fetchAll();
$totalSeats    = array_sum(array_column($batches, 'total_seats'));
$occupiedSeats = array_sum(array_column($batches, 'occupied_seats'));
$vacantSeats   = $totalSeats - $occupiedSeats;

// Students by batch for seat map
$studentsByBatch = [];
$rows = $db->query("SELECT s.*, b.name AS batch_name FROM students s JOIN batches b ON s.batch_id=b.id WHERE s.status='active' ORDER BY s.seat")->fetchAll();
foreach ($rows as $r) { $studentsByBatch[$r['batch_id']][] = $r; }

$allStudents = $db->query("SELECT id, first_name, last_name, student_code FROM students WHERE status='active' ORDER BY first_name")->fetchAll();

$currentPage = 'seats';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="sec-hd">
  <div><div class="sec-t">Seat Allocation</div><div class="sec-s">Batch seat map with fee status highlight</div></div>
  <div style="display:flex;gap:7px">
    <button class="btn bp" onclick="document.getElementById('addBatchModal').classList.add('open')">+ Add Batch</button>
    <button class="btn bg" onclick="document.getElementById('allocSeatModal').classList.add('open')">Allocate Seat</button>
  </div>
</div>

<div class="stats-grid" style="grid-template-columns:repeat(3,1fr)">
  <div class="sc" style="--ca:var(--ac)"><div class="s-ic" style="background:rgba(74,124,111,.1)">🏠</div><div class="s-lb">Total Seats</div><div class="s-vl"><?= $totalSeats ?></div></div>
  <div class="sc" style="--ca:var(--em)"><div class="s-ic" style="background:rgba(58,125,94,.1)">✅</div><div class="s-lb">Vacant</div><div class="s-vl"><?= $vacantSeats ?></div></div>
  <div class="sc" style="--ca:var(--ro)"><div class="s-ic" style="background:rgba(192,68,79,.1)">🔴</div><div class="s-lb">Occupied</div><div class="s-vl"><?= $occupiedSeats ?></div></div>
</div>

<!-- Legend -->
<div style="display:flex;gap:12px;flex-wrap:wrap;font-size:10.5px;color:var(--tx3);margin-bottom:14px">
  <div style="display:flex;align-items:center;gap:5px"><div style="width:14px;height:11px;border-radius:2px;background:rgba(58,125,94,.15);border:1px solid rgba(58,125,94,.4)"></div>Vacant</div>
  <div style="display:flex;align-items:center;gap:5px"><div style="width:14px;height:11px;border-radius:2px;background:rgba(192,68,79,.15);border:1px solid rgba(192,68,79,.4)"></div>Occupied (Paid)</div>
  <div style="display:flex;align-items:center;gap:5px"><div style="width:14px;height:11px;border-radius:2px;background:rgba(230,126,34,.25);border:1px solid rgba(230,126,34,.5)"></div>Fee Pending ⚠</div>
  <div style="display:flex;align-items:center;gap:5px"><div style="width:14px;height:11px;border-radius:2px;background:rgba(192,68,79,.3);border:1px solid rgba(192,68,79,.7)"></div>Overdue 🚨</div>
</div>

<div class="g2">
  <?php foreach ($batches as $b):
    $pct = $b['total_seats'] > 0 ? round($b['occupied_seats']/$b['total_seats']*100) : 0;
    $barClass = $pct >= 90 ? 'sf-r' : ($pct >= 70 ? 'sf-y' : 'sf-g');
    $stuMap = [];
    foreach ($studentsByBatch[$b['id']] ?? [] as $s) { $stuMap[$s['seat']] = $s; }
  ?>
  <div class="panel" style="padding:14px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
      <div>
        <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($b['name']) ?></div>
        <div style="font-size:10.5px;color:var(--tx3);font-family:var(--fm)"><?= date('g:i A', strtotime($b['time_start'])) ?> – <?= date('g:i A', strtotime($b['time_end'])) ?> · ₹<?= number_format($b['fee']) ?>/month</div>
      </div>
      <span style="font-size:10px;font-weight:700;padding:3px 8px;border-radius:20px;font-family:var(--fm);background:rgba(58,125,94,.12);color:var(--em)">
        <?= $b['occupied_seats'] ?>/<?= $b['total_seats'] ?>
      </span>
    </div>
    <div class="sbar" style="margin-bottom:10px"><div class="sfill <?= $barClass ?>" style="width:<?= $pct ?>%"></div></div>
    <div class="seat-visual">
      <?php for ($i = 1; $i <= $b['total_seats']; $i++):
        $seatLabel = 'S'.$i;
        $stu = $stuMap[$seatLabel] ?? null;
        if ($stu) {
          if ($stu['fee_status'] === 'overdue') $cls = 'seat-overdue';
          elseif (in_array($stu['fee_status'], ['pending','partial'])) $cls = 'seat-due';
          else $cls = 'seat-occ';
          $tooltip = htmlspecialchars($stu['first_name'].' '.$stu['last_name']);
        } else {
          $cls = 'seat-vac';
          $tooltip = 'Vacant';
        }
      ?>
      <div class="seat-cell <?= $cls ?>" title="<?= $seatLabel ?>: <?= $tooltip ?>">
        <?= $seatLabel ?>
        <div class="seat-tooltip"><?= $seatLabel ?>: <?= $tooltip ?></div>
      </div>
      <?php endfor; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ADD BATCH MODAL -->
<div class="mo" id="addBatchModal">
  <div class="md">
    <div class="mh"><span class="mt">🏫 Add Batch</span><button class="mc" onclick="document.getElementById('addBatchModal').classList.remove('open')">✕</button></div>
    <form method="POST">
      <input type="hidden" name="action" value="add_batch">
      <div class="mb">
        <div class="fg">
          <div class="fgi full"><label>Batch Name *</label><input type="text" name="name" required placeholder="e.g. Morning, Evening"></div>
          <div class="fgi"><label>Start Time</label><input type="time" name="time_start"></div>
          <div class="fgi"><label>End Time</label><input type="time" name="time_end"></div>
          <div class="fgi"><label>Total Seats *</label><input type="number" name="total_seats" min="1" required></div>
          <div class="fgi"><label>Monthly Fee (₹)</label><input type="number" name="fee" min="0" value="0"></div>
        </div>
      </div>
      <div class="mf"><button type="button" class="btn bg" onclick="document.getElementById('addBatchModal').classList.remove('open')">Cancel</button><button type="submit" class="btn bp">Create Batch</button></div>
    </form>
  </div>
</div>

<!-- ALLOC SEAT MODAL -->
<div class="mo" id="allocSeatModal">
  <div class="md">
    <div class="mh"><span class="mt">🪑 Allocate Seat</span><button class="mc" onclick="document.getElementById('allocSeatModal').classList.remove('open')">✕</button></div>
    <form method="POST">
      <input type="hidden" name="action" value="alloc_seat">
      <div class="mb">
        <div class="fg">
          <div class="fgi full"><label>Student *</label>
            <select name="student_id" required>
              <option value="">-- Select Student --</option>
              <?php foreach ($allStudents as $s): ?>
              <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['first_name'].' '.$s['last_name'].' ('.$s['student_code'].')') ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="fgi"><label>Batch *</label>
            <select name="batch_id" required>
              <option value="">-- Select Batch --</option>
              <?php foreach ($batches as $b): ?>
              <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="fgi"><label>Seat No. *</label><input type="text" name="seat" required placeholder="e.g. S12"></div>
        </div>
      </div>
      <div class="mf"><button type="button" class="btn bg" onclick="document.getElementById('allocSeatModal').classList.remove('open')">Cancel</button><button type="submit" class="btn bp">Allocate</button></div>
    </form>
  </div>
</div>

<script>
document.querySelectorAll('.mo').forEach(m => m.addEventListener('click', e => { if(e.target===m) m.classList.remove('open'); }));
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
