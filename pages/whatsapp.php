<?php 
require_once '../includes/db.php';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="content">
  <div class="sec-hd">
    <div><div class="sec-t">WhatsApp Messaging</div></div>
  </div>

  <div class="panel wa-panel">
    <div class="pb">
      <h3>Message Templates</h3>
      <!-- Templates grid populated by JS -->
    </div>
  </div>

  <div class="g2">
    <div class="panel">
      <div class="pb">
        <textarea id="wa-msg" rows="8"></textarea>
        <button class="btn bwa" onclick="waSend()">Send via WhatsApp</button>
      </div>
    </div>
  </div>
</div>

<script src="../assets/js/whatsapp.js"></script>
<?php require_once '../includes/footer.php'; ?>