<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
requireLogin();

$db = getDB();
$search   = trim($_GET['q'] ?? '');
$category = $_GET['cat'] ?? 'all';
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 20;
$offset   = ($page - 1) * $perPage;

// Handle add book POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_book') {
    $title    = trim($_POST['title'] ?? '');
    $author   = trim($_POST['author'] ?? '');
    $isbn     = trim($_POST['isbn'] ?? '');
    $cat      = trim($_POST['category'] ?? '');
    $shelf    = trim($_POST['shelf'] ?? '');
    $copies   = (int)($_POST['copies'] ?? 1);
    $price    = (float)($_POST['price'] ?? 0);
    $desc     = trim($_POST['description'] ?? '');

    if ($title) {
        $lastCode = $db->query("SELECT book_code FROM books ORDER BY id DESC LIMIT 1")->fetchColumn();
        $num      = $lastCode ? ((int)substr($lastCode, 3) + 1) : 1001;
        $code     = 'BK-' . str_pad($num, 4, '0', STR_PAD_LEFT);

        $stmt = $db->prepare("INSERT INTO books (book_code,title,author,isbn,category,shelf,total_copies,available_copies,price,description) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$code,$title,$author,$isbn,$cat,$shelf,$copies,$copies,$price,$desc]);
        logActivity("Added book: $title", 'book', $db->lastInsertId());
        $_SESSION['toast'] = ['msg' => "Book '$title' added!", 'type' => 'ok'];
        header('Location: /pages/books.php');
        exit;
    }
}

// Build query
$where  = ['1=1'];
$params = [];
if ($search) {
    $where[]  = "(b.title LIKE ? OR b.author LIKE ? OR b.book_code LIKE ?)";
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like]);
}
if ($category !== 'all') {
    $where[]  = "b.category = ?";
    $params[] = $category;
}
$whereSQL = implode(' AND ', $where);

$totalRows  = $db->prepare("SELECT COUNT(*) FROM books b WHERE $whereSQL");
$totalRows->execute($params);
$totalRows  = $totalRows->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

$stmt = $db->prepare("SELECT * FROM books b WHERE $whereSQL ORDER BY b.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$books = $stmt->fetchAll();

$categories = $db->query("SELECT DISTINCT category FROM books WHERE category IS NOT NULL AND category != '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

$currentPage = 'books';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="sec-hd">
  <div>
    <div class="sec-t">Books Catalog</div>
    <div class="sec-s"><?= $totalRows ?> books in library</div>
  </div>
  <div style="display:flex;gap:7px;align-items:center;flex-wrap:wrap">
    <form method="GET" style="display:contents">
      <input name="q" placeholder="Search title, author…" style="width:150px;font-size:11.5px" value="<?= htmlspecialchars($search) ?>">
      <select name="cat" onchange="this.form.submit()" style="font-size:12px;padding:6px 9px">
        <option value="all">All Categories</option>
        <?php foreach ($categories as $c): ?>
        <option value="<?= htmlspecialchars($c) ?>" <?= $category===$c?'selected':'' ?>><?= htmlspecialchars($c) ?></option>
        <?php endforeach; ?>
      </select>
    </form>
    <button class="btn bp" onclick="document.getElementById('addBookModal').classList.add('open')">+ Add Book</button>
  </div>
</div>

<div class="panel">
  <div class="tw"><table>
    <thead>
      <tr><th>Book</th><th>Author</th><th>ISBN</th><th>Category</th><th>Copies</th><th>Available</th><th>Shelf</th><th>Status</th><th>Action</th></tr>
    </thead>
    <tbody>
      <?php foreach ($books as $bk):
        $avail = $bk['available_copies'] > 0 ? 'tpd' : 'tod';
        $availLabel = $bk['available_copies'] > 0 ? 'Available' : 'All Issued';
      ?>
      <tr>
        <td>
          <div style="font-weight:600;font-size:12.5px"><?= htmlspecialchars($bk['title']) ?></div>
          <div style="font-size:10px;color:var(--tx3);font-family:var(--fm)"><?= $bk['book_code'] ?></div>
        </td>
        <td><?= htmlspecialchars($bk['author'] ?? '—') ?></td>
        <td style="font-family:var(--fm);font-size:11px"><?= htmlspecialchars($bk['isbn'] ?? '—') ?></td>
        <td><?= htmlspecialchars($bk['category'] ?? '—') ?></td>
        <td style="text-align:center;font-family:var(--fm)"><?= $bk['total_copies'] ?></td>
        <td style="text-align:center;font-weight:700;font-family:var(--fm);color:<?= $bk['available_copies']>0?'var(--em)':'var(--ro)' ?>">
          <?= $bk['available_copies'] ?>
        </td>
        <td style="font-family:var(--fm)"><?= htmlspecialchars($bk['shelf'] ?? '—') ?></td>
        <td><span class="tag <?= $avail ?>"><?= $availLabel ?></span></td>
        <td>
          <div style="display:flex;gap:4px">
            <a href="/pages/transactions.php?action=issue&book_id=<?= $bk['id'] ?>" class="btn bp" style="font-size:10px;padding:3px 7px;text-decoration:none">📤 Issue</a>
            <button class="btn bg" style="font-size:10px;padding:3px 7px" onclick="deleteBook(<?= $bk['id'] ?>, '<?= htmlspecialchars($bk['title'], ENT_QUOTES) ?>')">✕</button>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($books)): ?>
      <tr><td colspan="9"><div class="empty"><div class="ei">📖</div><div class="et">No books found</div></div></td></tr>
      <?php endif; ?>
    </tbody>
  </table></div>
  <div class="pag">
    <span class="pag-i">Showing <?= count($books) ?> of <?= $totalRows ?></span>
    <div class="pag-b">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
      <a href="?page=<?= $i ?>&cat=<?= urlencode($category) ?>&q=<?= urlencode($search) ?>" class="pb2 <?= $i===$page?'active':'' ?>"><?= $i ?></a>
      <?php endfor; ?>
    </div>
  </div>
</div>

<!-- ADD BOOK MODAL -->
<div class="mo" id="addBookModal">
  <div class="md">
    <div class="mh">
      <span class="mt">📖 Add New Book</span>
      <button class="mc" onclick="document.getElementById('addBookModal').classList.remove('open')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add_book">
      <div class="mb">
        <div class="fg">
          <div class="fgi full"><label>Title *</label><input type="text" name="title" required></div>
          <div class="fgi"><label>Author</label><input type="text" name="author"></div>
          <div class="fgi"><label>ISBN</label><input type="text" name="isbn"></div>
          <div class="fgi"><label>Category</label>
            <select name="category">
              <option value="">-- Select --</option>
              <option>Academic</option><option>Self-Help</option><option>Fiction</option>
              <option>Science</option><option>History</option><option>Technology</option>
            </select>
          </div>
          <div class="fgi"><label>Shelf Location</label><input type="text" name="shelf" placeholder="e.g. A-3"></div>
          <div class="fgi"><label>Total Copies</label><input type="number" name="copies" value="1" min="1"></div>
          <div class="fgi"><label>Price (₹)</label><input type="number" name="price" value="0" min="0"></div>
          <div class="fgi full"><label>Description</label><textarea name="description" rows="2"></textarea></div>
        </div>
      </div>
      <div class="mf"><button type="button" class="btn bg" onclick="document.getElementById('addBookModal').classList.remove('open')">Cancel</button><button type="submit" class="btn bp">Add Book</button></div>
    </form>
  </div>
</div>

<script>
document.querySelector('.mo').addEventListener('click', e => { if(e.target === e.currentTarget) e.currentTarget.classList.remove('open'); });
function deleteBook(id, title) {
  if (!confirm('Delete book: ' + title + '?')) return;
  fetch('/api/books.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'action=delete&id='+id})
    .then(r=>r.json()).then(d=>{ if(d.success){toast('Book deleted','ok');location.reload();}else{toast(d.error,'er');} });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
