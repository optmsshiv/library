<?php
// ═══ SHARED FOOTER ═══
?>
  </div><!-- .content -->
</div><!-- .main -->

<!-- TOAST CONTAINER -->
<div class="toast-wrap" id="toastWrap"></div>

<script src="/assets/js/app.js"></script>
<script>
  document.getElementById('todayChip').textContent = new Date().toLocaleDateString('en-IN', {month:'long', year:'numeric'});
  <?php if (!empty($_SESSION['toast'])): ?>
  toast(<?= json_encode($_SESSION['toast']['msg']) ?>, <?= json_encode($_SESSION['toast']['type']) ?>);
  <?php unset($_SESSION['toast']); ?>
  <?php endif; ?>
</script>
</body>
</html>
