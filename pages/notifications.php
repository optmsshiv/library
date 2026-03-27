<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
requireLogin();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'mark_read') {
        $id = (int)$_POST['id'];
        $db->prepare("UPDATE notifications SET is_read=1 WHERE id=?")->execute([$id]);
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $db->prepare("DELETE FROM notifications WHERE id=?")->execute([$id]);
    } elseif ($action === 'clear_all') {
        $db->query("DELETE FROM notifications");
    } elseif ($action === 'mark_all_read') {
        $db->query("UPDATE notifications SET is_read=1");
    }
    header('Location: /pages/notifications.php'); exit;
}

$notifications = $db->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 100")->fetchAll();
$unread = count(array_filter($notifications, fn($n) => !$n['is_read']));

$currentPage = 'notifications';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="sec-hd">
  <div><div class="sec-t">Notifications</div><div class="sec-s" id="notifCount"><?= count($notifications) ?> total, <?= $unread ?> unread</div></div>
  <div style="display:flex;gap:7px">
    <form method="POST" style="display:inline"><input type="hidden" name="action" value="mark_all_read"><button type="submit" class="btn bg">✓ Mark All Read</button></form>
    <form method="POST" style="display:inline" onsubmit="return confirm('Clear all notifications?')"><input type="hidden" name="action" value="clear_all"><button type="submit" class="btn bd">🗑 Clear All</button></form>
  </div>
</div>

<div style="display:flex;flex-direction:column;gap:8px">
  <?php if (empty($notifications)): ?>
  <div class="empty"><div class="ei">🔔</div><div class="et">No notifications</div></div>
  <?php endif; ?>
  <?php foreach ($notifications as $n):
    $icons = ['warning'=>'⚠️','info'=>'ℹ️','success'=>'✅','error'=>'🚨'];
    $bgs   = ['warning'=>'rgba(196,125,43,.1)','info'=>'rgba(74,124,111,.1)','success'=>'rgba(58,125,94,.1)','error'=>'rgba(192,68,79,.1)'];
    $icon  = $icons[$n['type']] ?? 'ℹ️';
    $bg    = $bgs[$n['type']]   ?? 'rgba(74,124,111,.1)';
  ?>
  <div style="display:flex;gap:11px;padding:12px;background:<?= $n['is_read']?'transparent':'rgba(74,124,111,.04)' ?>;border:1px solid <?= $n['is_read']?'var(--br)':'rgba(74,124,111,.2)' ?>;border-radius:var(--r2)">
    <div style="width:32px;height:32px;border-radius:9px;background:<?= $bg ?>;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0"><?= $icon ?></div>
    <div style="flex:1">
      <div style="font-size:12.5px;font-weight:600;margin-bottom:2px"><?= htmlspecialchars($n['title']) ?></div>
      <div style="font-size:11.5px;color:var(--tx2)"><?= htmlspecialchars($n['message']) ?></div>
      <div style="font-size:10px;color:var(--tx3);font-family:var(--fm);margin-top:3px"><?= date('d M Y, g:i A', strtotime($n['created_at'])) ?></div>
    </div>
    <div style="display:flex;gap:5px;align-items:flex-start">
      <?php if (!$n['is_read']): ?>
      <form method="POST" style="display:inline"><input type="hidden" name="action" value="mark_read"><input type="hidden" name="id" value="<?= $n['id'] ?>"><button type="submit" class="btn bg" style="font-size:10px;padding:2px 7px">Read</button></form>
      <?php endif; ?>
      <form method="POST" style="display:inline"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $n['id'] ?>"><button type="submit" class="btn bg" style="font-size:10px;padding:2px 6px">✕</button></form>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
