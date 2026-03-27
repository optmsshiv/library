<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
requireLogin();

$db = getDB();

// Handle add/edit staff
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_staff' || $action === 'edit_staff') {
        $id    = (int)($_POST['staff_id'] ?? 0);
        $name  = trim($_POST['name'] ?? '');
        $uname = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $role  = $_POST['role'] ?? 'librarian';
        $pass  = trim($_POST['password'] ?? '');
        $perms = json_encode($_POST['perms'] ?? []);

        if ($action === 'add_staff' && $name && $uname && $email) {
            $hash = password_hash($pass ?: 'staff123', PASSWORD_DEFAULT);
            $db->prepare("INSERT INTO users (name,username,email,phone,role,password,permissions) VALUES (?,?,?,?,?,?,?)")
               ->execute([$name,$uname,$email,$phone,$role,$hash,$perms]);
            logActivity("Added staff: $name");
            $_SESSION['toast'] = ['msg' => "$name added successfully!", 'type' => 'ok'];
        } elseif ($action === 'edit_staff' && $id) {
            if ($pass) {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $db->prepare("UPDATE users SET name=?,username=?,email=?,phone=?,role=?,password=?,permissions=? WHERE id=?")
                   ->execute([$name,$uname,$email,$phone,$role,$hash,$perms,$id]);
            } else {
                $db->prepare("UPDATE users SET name=?,username=?,email=?,phone=?,role=?,permissions=? WHERE id=?")
                   ->execute([$name,$uname,$email,$phone,$role,$perms,$id]);
            }
            logActivity("Updated staff: $name", 'user', $id);
            $_SESSION['toast'] = ['msg' => "$name updated!", 'type' => 'ok'];
        }
        header('Location: /pages/staff.php'); exit;
    }

    if ($action === 'delete_staff') {
        $id = (int)$_POST['staff_id'];
        if ($id !== $_SESSION['user_id']) {
            $db->prepare("UPDATE users SET status='inactive' WHERE id=?")->execute([$id]);
            $_SESSION['toast'] = ['msg' => 'Staff deactivated.', 'type' => 'wn'];
        }
        header('Location: /pages/staff.php'); exit;
    }
}

$staffList = $db->query("SELECT * FROM users WHERE status='active' ORDER BY role, name")->fetchAll();

$allPerms = [
    ['key'=>'manage_students','label'=>'Manage Students'],
    ['key'=>'manage_books','label'=>'Manage Books'],
    ['key'=>'manage_fees','label'=>'Manage Fees'],
    ['key'=>'view_reports','label'=>'View Reports'],
    ['key'=>'manage_staff','label'=>'Manage Staff'],
    ['key'=>'manage_settings','label'=>'Settings'],
    ['key'=>'issue_books','label'=>'Issue/Return Books'],
];

$currentPage = 'staff';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="sec-hd">
  <div><div class="sec-t">Staff & Users</div><div class="sec-s"><?= count($staffList) ?> active users</div></div>
  <button class="btn bp" onclick="document.getElementById('addStaffModal').classList.add('open')">+ Add Staff</button>
</div>

<div class="panel"><div class="tw"><table>
  <thead><tr><th>Staff</th><th>Role</th><th>Email</th><th>Phone</th><th>Permissions</th><th>Status</th><th>Last Login</th><th>Action</th></tr></thead>
  <tbody>
    <?php foreach ($staffList as $i => $sf):
      $perms = json_decode($sf['permissions'] ?? '{}', true) ?: [];
      $pc    = count(array_filter($perms));
      $roleCls = ['admin'=>'tpd','librarian'=>'tac','accountant'=>'tpn','receptionist'=>'tis','staff'=>'trt'][$sf['role']] ?? 'trt';
    ?>
    <tr>
      <td>
        <div class="si">
          <div class="sav" style="background:linear-gradient(135deg,var(--ac),var(--vi))">
            <?= strtoupper(substr($sf['name'],0,2)) ?>
          </div>
          <div>
            <div style="font-weight:600;font-size:12.5px"><?= htmlspecialchars($sf['name']) ?></div>
            <div style="font-size:10px;color:var(--tx3);font-family:var(--fm)">@<?= htmlspecialchars($sf['username']) ?></div>
          </div>
        </div>
      </td>
      <td><span class="tag <?= $roleCls ?>" style="text-transform:capitalize"><?= $sf['role'] ?></span></td>
      <td style="font-size:12px"><?= htmlspecialchars($sf['email']) ?></td>
      <td style="font-family:var(--fm);font-size:11px"><?= htmlspecialchars($sf['phone'] ?? '—') ?></td>
      <td><span style="font-family:var(--fm);font-size:11px"><?= $pc ?>/<?= count($allPerms) ?> perms</span></td>
      <td><span class="tag tpd">Active</span></td>
      <td style="font-size:11px;font-family:var(--fm)"><?= $sf['last_login'] ? date('d M Y g:i A', strtotime($sf['last_login'])) : 'Never' ?></td>
      <td>
        <div style="display:flex;gap:4px">
          <button class="btn bg" style="font-size:10px;padding:3px 7px"
                  onclick='editStaff(<?= htmlspecialchars(json_encode($sf), ENT_QUOTES) ?>)'>✏</button>
          <?php if ($sf['id'] !== $_SESSION['user_id']): ?>
          <form method="POST" style="display:inline" onsubmit="return confirm('Deactivate this staff?')">
            <input type="hidden" name="action" value="delete_staff">
            <input type="hidden" name="staff_id" value="<?= $sf['id'] ?>">
            <button type="submit" class="btn bd" style="font-size:10px;padding:3px 6px">✕</button>
          </form>
          <?php endif; ?>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($staffList)): ?>
    <tr><td colspan="8"><div class="empty"><div class="ei">👥</div><div class="et">No staff</div></div></td></tr>
    <?php endif; ?>
  </tbody>
</table></div></div>

<!-- ADD/EDIT STAFF MODAL -->
<div class="mo" id="addStaffModal">
  <div class="md wide">
    <div class="mh">
      <span class="mt" id="staffModalTitle">➕ Add Staff</span>
      <button class="mc" onclick="closeStaffModal()">✕</button>
    </div>
    <form method="POST" id="staffForm">
      <input type="hidden" name="action" id="staffAction" value="add_staff">
      <input type="hidden" name="staff_id" id="staffId" value="0">
      <div class="mb">
        <div class="fg">
          <div class="fgi"><label>Full Name *</label><input type="text" name="name" id="sfName" required></div>
          <div class="fgi"><label>Role *</label>
            <select name="role" id="sfRole" onchange="updateDefaultPerms()">
              <option value="librarian">Librarian</option>
              <option value="receptionist">Receptionist</option>
              <option value="accountant">Accountant</option>
              <option value="staff">Staff</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          <div class="fgi"><label>Email *</label><input type="email" name="email" id="sfEmail" required></div>
          <div class="fgi"><label>Phone</label><input type="tel" name="phone" id="sfPhone"></div>
          <div class="fgi"><label>Username *</label><input type="text" name="username" id="sfUsername" required></div>
          <div class="fgi"><label>Password <span style="color:var(--tx3);font-weight:400">(leave blank to keep)</span></label><input type="password" name="password" placeholder="Min 6 characters"></div>
        </div>
        <div class="sdiv" style="margin-top:14px">Permissions</div>
        <div id="permList">
          <?php foreach ($allPerms as $p): ?>
          <div class="perm-row">
            <div style="font-size:13px;font-weight:500"><?= $p['label'] ?></div>
            <label class="toggle-wrap">
              <input type="checkbox" name="perms[<?= $p['key'] ?>]" value="1" class="toggle-inp perm-<?= $p['key'] ?>">
              <span class="toggle-sl"></span>
            </label>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="mf">
        <button type="button" class="btn bg" onclick="closeStaffModal()">Cancel</button>
        <button type="submit" class="btn bp" id="staffSaveBtn">Add Staff</button>
      </div>
    </form>
  </div>
</div>

<script>
const ROLE_PERMS = {
  admin:        {manage_students:true,manage_books:true,manage_fees:true,view_reports:true,manage_staff:true,manage_settings:true,issue_books:true},
  librarian:    {manage_students:true,manage_books:true,manage_fees:false,view_reports:true,manage_staff:false,manage_settings:false,issue_books:true},
  accountant:   {manage_students:false,manage_books:false,manage_fees:true,view_reports:true,manage_staff:false,manage_settings:false,issue_books:false},
  receptionist: {manage_students:true,manage_books:false,manage_fees:false,view_reports:false,manage_staff:false,manage_settings:false,issue_books:false},
  staff:        {manage_students:false,manage_books:false,manage_fees:false,view_reports:false,manage_staff:false,manage_settings:false,issue_books:true},
};
function updateDefaultPerms() {
  const role = document.getElementById('sfRole').value;
  const d = ROLE_PERMS[role] || {};
  Object.keys(d).forEach(k => {
    const el = document.querySelector('.perm-' + k);
    if (el) el.checked = !!d[k];
  });
}
function editStaff(sf) {
  document.getElementById('staffModalTitle').textContent = '✏ Edit Staff';
  document.getElementById('staffSaveBtn').textContent = 'Save Changes';
  document.getElementById('staffAction').value = 'edit_staff';
  document.getElementById('staffId').value = sf.id;
  document.getElementById('sfName').value = sf.name;
  document.getElementById('sfRole').value = sf.role;
  document.getElementById('sfEmail').value = sf.email;
  document.getElementById('sfPhone').value = sf.phone || '';
  document.getElementById('sfUsername').value = sf.username;
  const perms = JSON.parse(sf.permissions || '{}');
  document.querySelectorAll('#permList input[type=checkbox]').forEach(cb => {
    const key = cb.name.replace('perms[','').replace(']','');
    cb.checked = !!perms[key];
  });
  document.getElementById('addStaffModal').classList.add('open');
}
function closeStaffModal() {
  document.getElementById('addStaffModal').classList.remove('open');
  document.getElementById('staffModalTitle').textContent = '➕ Add Staff';
  document.getElementById('staffSaveBtn').textContent = 'Add Staff';
  document.getElementById('staffAction').value = 'add_staff';
  document.getElementById('staffId').value = '0';
  document.getElementById('staffForm').reset();
}
document.getElementById('addStaffModal').addEventListener('click', e => { if(e.target===e.currentTarget) closeStaffModal(); });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
