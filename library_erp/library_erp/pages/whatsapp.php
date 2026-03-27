<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
requireLogin();

$db = getDB();
$waNum = $db->query("SELECT setting_value FROM settings WHERE setting_key='whatsapp_number'")->fetchColumn() ?: '';
$students = $db->query("SELECT id, first_name, last_name, phone, fee_status, net_fee, paid_fee FROM students WHERE status='active' ORDER BY first_name")->fetchAll();

$currentPage = 'whatsapp';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="sec-hd">
  <div><div class="sec-t">WhatsApp Communication</div><div class="sec-s">Send fee reminders and messages</div></div>
</div>

<div class="g2">
  <!-- Templates -->
  <div>
    <div class="sec-hd"><div class="sec-t">Message Templates</div></div>
    <div style="display:flex;flex-direction:column;gap:10px">
      <?php
      $templates = [
        ['icon'=>'💳','label'=>'Fee Reminder','desc'=>'Send fee due reminder','tmpl'=>"Dear {name},\nYour library fee of ₹{balance} is due. Please pay at the earliest.\n\nThank you,\nOPTMS Tech Library"],
        ['icon'=>'🔴','label'=>'Overdue Alert','desc'=>'Alert for overdue fees','tmpl'=>"Dear {name},\nYour fee payment of ₹{balance} is OVERDUE. Please clear immediately to continue using library services.\n\nOPTMS Tech Library"],
        ['icon'=>'📖','label'=>'Book Return','desc'=>'Book return reminder','tmpl'=>"Dear {name},\nKindly return the issued book(s) to avoid late fine charges.\n\nOPTMS Tech Library"],
        ['icon'=>'✅','label'=>'Fee Received','desc'=>'Payment confirmation','tmpl'=>"Dear {name},\nWe have received your fee payment. Thank you!\n\nOPTMS Tech Library"],
      ];
      foreach ($templates as $t): ?>
      <div class="wa-panel" style="padding:14px;cursor:pointer" onclick="setTemplate(`<?= addslashes($t['tmpl']) ?>`)">
        <div style="font-size:18px;margin-bottom:5px"><?= $t['icon'] ?></div>
        <div style="font-size:13px;font-weight:600;margin-bottom:2px"><?= $t['label'] ?></div>
        <div style="font-size:11px;color:var(--tx3)"><?= $t['desc'] ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Send Panel -->
  <div>
    <div class="sec-hd"><div class="sec-t">Send Message</div></div>
    <div class="panel" style="padding:18px">
      <div style="display:flex;flex-direction:column;gap:12px">
        <div class="fgi">
          <label>Student</label>
          <select id="waStudent" onchange="updateWaPreview()">
            <option value="">-- Select Student --</option>
            <?php foreach ($students as $s): ?>
            <option value="<?= $s['id'] ?>" data-phone="<?= htmlspecialchars($s['phone']) ?>"
                    data-name="<?= htmlspecialchars($s['first_name'].' '.$s['last_name']) ?>"
                    data-balance="<?= $s['net_fee']-$s['paid_fee'] ?>">
              <?= htmlspecialchars($s['first_name'].' '.$s['last_name']) ?> (<?= ucfirst($s['fee_status']) ?>)
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="fgi">
          <label>Message</label>
          <textarea id="waMessage" rows="5" placeholder="Select a template or type your message..."></textarea>
        </div>
        <div class="wa-preview" id="waPreview" style="display:none"></div>
        <button class="btn bwa" onclick="sendWA()">💬 Send on WhatsApp</button>
      </div>
    </div>

    <!-- Bulk Reminders -->
    <div class="panel" style="padding:18px;margin-top:0">
      <div style="font-family:var(--fd);font-size:14px;margin-bottom:14px">📣 Bulk Reminders</div>
      <?php
      $pending = array_filter($students, fn($s) => in_array($s['fee_status'], ['pending','partial','overdue']));
      ?>
      <p style="font-size:12px;color:var(--tx2);margin-bottom:12px"><?= count($pending) ?> students with pending fees</p>
      <div style="display:flex;flex-direction:column;gap:7px">
        <?php foreach (array_slice(array_values($pending), 0, 8) as $s):
          $balance = $s['net_fee'] - $s['paid_fee'];
          $phone = preg_replace('/[^0-9]/', '', $s['phone']);
          $msg = urlencode("Dear {$s['first_name']},\nYour library fee balance is ₹".number_format($balance).". Please pay soon.\n\nOPTMS Tech Library");
        ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 10px;background:var(--sf2);border-radius:var(--r2)">
          <div>
            <div style="font-weight:600;font-size:12px"><?= htmlspecialchars($s['first_name'].' '.$s['last_name']) ?></div>
            <div style="font-size:10px;color:var(--tx3)">₹<?= number_format($balance) ?> pending · <?= ucfirst($s['fee_status']) ?></div>
          </div>
          <a href="https://wa.me/91<?= $phone ?>?text=<?= $msg ?>" target="_blank" class="btn bwa" style="font-size:10px;padding:3px 9px;text-decoration:none">💬</a>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<script>
function setTemplate(tmpl) {
  document.getElementById('waMessage').value = tmpl;
  updateWaPreview();
}
function updateWaPreview() {
  const sel = document.getElementById('waStudent');
  const opt = sel.options[sel.selectedIndex];
  let msg = document.getElementById('waMessage').value;
  if (opt && opt.value) {
    msg = msg.replace(/{name}/g, opt.dataset.name || '')
             .replace(/{balance}/g, '₹' + parseFloat(opt.dataset.balance || 0).toLocaleString('en-IN'));
  }
  const preview = document.getElementById('waPreview');
  if (msg) { preview.textContent = msg; preview.style.display = 'block'; }
  else preview.style.display = 'none';
}
function sendWA() {
  const sel = document.getElementById('waStudent');
  const opt = sel.options[sel.selectedIndex];
  if (!opt || !opt.value) { toast('Please select a student', 'er'); return; }
  let msg = document.getElementById('waMessage').value;
  if (!msg) { toast('Please enter a message', 'er'); return; }
  msg = msg.replace(/{name}/g, opt.dataset.name || '')
           .replace(/{balance}/g, '₹' + parseFloat(opt.dataset.balance || 0).toLocaleString('en-IN'));
  const phone = opt.dataset.phone.replace(/[^0-9]/g, '');
  window.open('https://wa.me/91' + phone + '?text=' + encodeURIComponent(msg), '_blank');
}
document.getElementById('waMessage').addEventListener('input', updateWaPreview);
</script>

<style>
.wa-panel { background:linear-gradient(135deg,rgba(37,211,102,.07),rgba(18,140,126,.05)); border:1px solid rgba(37,211,102,.22); border-radius:var(--r); transition:all .2s; }
.wa-panel:hover { border-color:var(--wa); transform:translateY(-1px); }
.wa-preview { background:#e3f7d5; border-radius:12px 12px 0 12px; padding:12px 14px; font-size:12px; line-height:1.6; color:#1a1a1a; white-space:pre-line; border:1px solid rgba(37,211,102,.2); }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
