<?php
require_once 'config.php';
$db = getDB();

$class_filter = isset($_GET['class']) ? (int)$_GET['class'] : 0;
$search       = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "SELECT i.*, ic.class_name FROM ITEM i JOIN ITEM_CLASS ic ON i.class_id=ic.class_id WHERE i.stock_quantity > 0";
$params = [];
$types  = '';

if ($class_filter) {
    $sql .= " AND i.class_id = ?";
    $params[] = $class_filter;
    $types   .= 'i';
}
if ($search) {
    $sql .= " AND i.item_name LIKE ?";
    $like = "%$search%";
    $params[] = $like;
    $types   .= 's';
}
$sql .= " ORDER BY i.unit_price";

$stmt = $db->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$classes = $db->query("SELECT * FROM ITEM_CLASS ORDER BY min_price")->fetch_all(MYSQLI_ASSOC);
$discounts = $db->query("SELECT * FROM DISCOUNT WHERE CURDATE() BETWEEN valid_from AND IFNULL(valid_to, CURDATE())")->fetch_all(MYSQLI_ASSOC);
$db->close();

$icons = ['Economy'=>'🛒','Standard'=>'⭐','Premium'=>'💎','Luxury'=>'👑'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Catalogue — ShopEase</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar">
  <div class="nav-inner">
    <a href="index.php" class="logo">Shop<span>Ease</span></a>
    <div class="nav-links">
      <a href="catalogue.php">Catalogue</a>
      <?php if (isset($_SESSION['customer_id'])): ?>
        <a href="orders.php">My Orders</a>
        <a href="cart.php" class="cart-link">🛒 Cart <?php if(!empty($_SESSION['cart'])) echo '<span class="badge">'.count($_SESSION['cart']).'</span>'; ?></a>
        <a href="logout.php" class="btn-nav">Logout</a>
      <?php else: ?>
        <a href="login.php" class="btn-nav">Login</a>
        <a href="register.php" class="btn-nav btn-accent">Register</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<div class="page-header">
  <div class="container">
    <h1>Product Catalogue</h1>
    <p>Browse all products across every price class</p>
  </div>
</div>

<div class="container catalogue-layout">

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-block">
      <h3>Search</h3>
      <form method="GET">
        <?php if($class_filter): ?><input type="hidden" name="class" value="<?= $class_filter ?>"><?php endif; ?>
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search products..." class="search-input">
        <button type="submit" class="btn-submit small">Search</button>
      </form>
    </div>

    <div class="sidebar-block">
      <h3>Filter by Class</h3>
      <a href="catalogue.php<?= $search ? '?search='.urlencode($search) : '' ?>" class="filter-link <?= !$class_filter ? 'active' : '' ?>">All Products</a>
      <?php foreach($classes as $c): ?>
      <a href="catalogue.php?class=<?= $c['class_id'] ?><?= $search ? '&search='.urlencode($search) : '' ?>"
         class="filter-link <?= $class_filter == $c['class_id'] ? 'active' : '' ?>">
        <?= ($icons[$c['class_name']] ?? '📦') . ' ' . $c['class_name'] ?>
        <small>₹<?= number_format($c['min_price'],0) ?><?= $c['max_price'] ? '–₹'.number_format($c['max_price'],0) : '+' ?></small>
      </a>
      <?php endforeach; ?>
    </div>

    <?php if ($discounts): ?>
    <div class="sidebar-block discount-block">
      <h3>🏷️ Active Offers</h3>
      <?php foreach($discounts as $d): ?>
      <div class="discount-item">
        <strong><?= htmlspecialchars($d['discount_name']) ?></strong>
        <span><?= $d['discount_type']==='PERCENTAGE' ? $d['discount_value'].'% OFF' : '₹'.$d['discount_value'].' OFF' ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </aside>

  <!-- PRODUCTS -->
  <main class="catalogue-main">
    <div class="results-bar">
      <p><?= count($items) ?> product<?= count($items)!=1?'s':'' ?> found</p>
    </div>

    <?php if (empty($items)): ?>
      <div class="empty-state">
        <p>😕 No products found. <a href="catalogue.php">Clear filters</a></p>
      </div>
    <?php else: ?>
    <div class="product-grid">
      <?php foreach($items as $item): ?>
      <div class="product-card">
        <div class="product-badge"><?= htmlspecialchars($item['class_name']) ?></div>
        <div class="product-img"><?= $icons[$item['class_name']] ?? '📦' ?></div>
        <div class="product-info">
          <h4><?= htmlspecialchars($item['item_name']) ?></h4>
          <p class="product-desc"><?= htmlspecialchars(substr($item['description'] ?? '', 0, 70)) ?>...</p>
          <p class="stock-info">Stock: <?= $item['stock_quantity'] ?> units</p>
          <div class="product-footer">
            <span class="price">₹<?= number_format($item['unit_price'],2) ?></span>
            <?php if (isset($_SESSION['customer_id'])): ?>
              <form method="POST" action="cart.php" style="display:inline">
                <input type="hidden" name="action"  value="add">
                <input type="hidden" name="item_id" value="<?= $item['item_id'] ?>">
                <input type="number" name="qty" value="1" min="1" max="<?= $item['stock_quantity'] ?>" class="qty-input">
                <button type="submit" class="btn-small">Add 🛒</button>
              </form>
            <?php else: ?>
              <a href="login.php" class="btn-small">Login to Buy</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </main>
</div>

<footer class="footer">
  <div class="container"><p><strong>ShopEase</strong> — Online Retail Application</p></div>
</footer>

</body>
</html>
