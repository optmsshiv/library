<?php 
require_once '../includes/db.php';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="content">
  <div class="sec-hd">
    <div><div class="sec-t">Books Catalog</div></div>
    <button class="btn bp" onclick="openM('mAddBook')">+ Add Book</button>
  </div>

  <div class="panel">
    <table>
      <thead><tr><th>Book</th><th>Author</th><th>Available</th><th>Action</th></tr></thead>
      <tbody id="bkTable"></tbody>
    </table>
  </div>
</div>

<script src="../assets/js/books.js"></script>
<?php require_once '../includes/footer.php'; ?>