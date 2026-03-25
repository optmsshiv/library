<?php 
require_once '../includes/db.php';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
$page_css = 'settings.css';
?>

<div class="content">
  <div class="sec-hd">
    <div><div class="sec-t">Settings</div></div>
  </div>

  <div class="g2">
    <div class="panel">
      <div class="ph"><div class="pt">Library Information</div></div>
      <div class="pb">
        <div class="fg">
          <div class="fgi full"><label>Library Name</label><input id="s-name" value="OPTMS Tech Study Library"></div>
          <div class="fgi"><label>Phone / WhatsApp</label><input id="s-phone" value="+91 72820 71620"></div>
          <div class="fgi"><label>Email</label><input id="s-email" value="admin@optms.co.in"></div>
          <div class="fgi full"><label>Address</label><input id="s-addr" value="Madhepura, Bihar - 852113"></div>
          <div class="fgi"><label>Fine Per Day (₹)</label><input id="s-fine" type="number" value="5"></div>
          <div class="fgi"><label>Max Issue Days</label><input id="s-days" type="number" value="14"></div>
        </div>
        <button class="btn bp" onclick="saveSettings()" style="margin-top:15px">💾 Save Settings</button>
      </div>
    </div>

    <div class="panel">
      <div class="ph"><div class="pt">System Statistics</div></div>
      <div class="pb" id="setStats"></div>
    </div>
  </div>
</div>

<script src="../assets/js/settings.js"></script>
<?php require_once '../includes/footer.php'; ?>