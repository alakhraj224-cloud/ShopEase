<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ShopEase — Online Retail</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
  <div class="nav-inner">
    <a href="index.php" class="logo">Shop<span>Ease</span></a>
    <div class="nav-links">
      <a href="catalogue.php">Catalogue</a>
      <?php if (isset($_SESSION['customer_id'])): ?>
        <a href="orders.php">My Orders</a>
        <a href="cart.php" class="cart-link">🛒 Cart <?php if(!empty($_SESSION['cart'])) echo '<span class="badge">'.array_sum($_SESSION['cart']).'</span>'; ?></a>
        <a href="logout.php" class="btn-nav">Logout</a>
      <?php else: ?>
        <a href="login.php" class="btn-nav">Login</a>
        <a href="register.php" class="btn-nav btn-accent">Register</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="hero-content">
    <p class="hero-tag">Premium Online Shopping</p>
    <h1>Discover Products<br><em>Worth Having</em></h1>
    <p class="hero-sub">From everyday essentials to luxury goods — all in one place.</p>
    <div class="hero-actions">
      <a href="catalogue.php" class="btn-primary">Browse Catalogue</a>
      <?php if (!isset($_SESSION['customer_id'])): ?>
      <a href="register.php" class="btn-outline">Create Account</a>
      <?php endif; ?>
    </div>
  </div>
  <div class="hero-visual">
    <div class="floating-card card1"><span>🛍️</span><p>Economy</p></div>
    <div class="floating-card card2"><span>⭐</span><p>Standard</p></div>
    <div class="floating-card card3"><span>💎</span><p>Premium</p></div>
    <div class="floating-card card4"><span>👑</span><p>Luxury</p></div>
  </div>
</section>

<!-- CATEGORIES -->
<section class="section">
  <div class="container">
    <h2 class="section-title">Shop by Class</h2>
    <div class="class-grid">
      <?php
      $db = getDB();
      $res = $db->query("SELECT * FROM ITEM_CLASS ORDER BY min_price");
      $icons = ['Economy'=>'🛒','Standard'=>'⭐','Premium'=>'💎','Luxury'=>'👑'];
      $colors = ['Economy'=>'#e8f5e9','Standard'=>'#e3f2fd','Premium'=>'#f3e5f5','Luxury'=>'#fff8e1'];
      while($row = $res->fetch_assoc()):
        $icon = $icons[$row['class_name']] ?? '📦';
        $color = $colors[$row['class_name']] ?? '#f5f5f5';
      ?>
      <a href="catalogue.php?class=<?= $row['class_id'] ?>" class="class-card" style="--card-color:<?= $color ?>">
        <div class="class-icon"><?= $icon ?></div>
        <h3><?= $row['class_name'] ?></h3>
        <p>₹<?= number_format($row['min_price'],0) ?><?= $row['max_price'] ? ' – ₹'.number_format($row['max_price'],0) : '+' ?></p>
      </a>
      <?php endwhile; $db->close(); ?>
    </div>
  </div>
</section>

<!-- FEATURED ITEMS -->
<section class="section section-dark">
  <div class="container">
    <h2 class="section-title light">Featured Products</h2>
    <div class="product-grid">
      <?php
      $db = getDB();
      $res = $db->query("SELECT i.*, ic.class_name FROM ITEM i JOIN ITEM_CLASS ic ON i.class_id=ic.class_id WHERE i.stock_quantity > 0 ORDER BY i.created_at DESC LIMIT 4");
      while($row = $res->fetch_assoc()):
      ?>
      <div class="product-card">
        <div class="product-badge"><?= htmlspecialchars($row['class_name']) ?></div>
        <div class="product-img">🛍️</div>
        <div class="product-info">
          <h4><?= htmlspecialchars($row['item_name']) ?></h4>
          <p class="product-desc"><?= htmlspecialchars(substr($row['description'],0,60)) ?>...</p>
          <div class="product-footer">
            <span class="price">₹<?= number_format($row['unit_price'],2) ?></span>
            <a href="catalogue.php" class="btn-small">View</a>
          </div>
        </div>
      </div>
      <?php endwhile; $db->close(); ?>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer class="footer">
  <div class="container">
    <p><strong>ShopEase</strong> — Online Retail Application &nbsp;|&nbsp; Database Project</p>
  </div>
</footer>

</body>
</html>
