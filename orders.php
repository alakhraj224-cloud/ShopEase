<?php
// orders.php
require_once 'config.php';
if (!isset($_SESSION['customer_id'])) { header('Location: login.php'); exit; }

$cid = $_SESSION['customer_id'];
$db  = getDB();

$orders = $db->query("
    SELECT o.order_id, o.order_date, o.status,
           COUNT(oi.order_item_id) AS item_count,
           b.bill_id, b.total_amount, b.payment_status
    FROM `ORDER` o
    JOIN ORDER_ITEM oi ON o.order_id = oi.order_id
    JOIN BILL b ON o.order_id = b.order_id
    WHERE o.customer_id = $cid
    GROUP BY o.order_id, o.order_date, o.status, b.bill_id, b.total_amount, b.payment_status
    ORDER BY o.order_date DESC
")->fetch_all(MYSQLI_ASSOC);

$customer = $db->query("SELECT * FROM CUSTOMER WHERE customer_id=$cid")->fetch_assoc();
$accounts = $db->query("SELECT * FROM BANK_ACCOUNT WHERE customer_id=$cid")->fetch_all(MYSQLI_ASSOC);
$db->close();

$status_colors = [
    'PENDING'=>'#f59e0b','CONFIRMED'=>'#3b82f6',
    'SHIPPED'=>'#8b5cf6','DELIVERED'=>'#10b981','CANCELLED'=>'#ef4444'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Orders — ShopEase</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar">
  <div class="nav-inner">
    <a href="index.php" class="logo">Shop<span>Ease</span></a>
    <div class="nav-links">
      <a href="catalogue.php">Catalogue</a>
      <a href="orders.php" class="active">My Orders</a>
      <a href="cart.php" class="cart-link">🛒 Cart</a>
      <a href="logout.php" class="btn-nav">Logout</a>
    </div>
  </div>
</nav>

<div class="page-header">
  <div class="container"><h1>My Account</h1></div>
</div>

<div class="container orders-layout">

  <!-- PROFILE SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-block profile-block">
      <div class="avatar">👤</div>
      <h3><?= htmlspecialchars($customer['first_name'].' '.$customer['last_name']) ?></h3>
      <p><?= htmlspecialchars($customer['email']) ?></p>
      <p class="user-id-tag">@<?= htmlspecialchars($customer['user_id']) ?></p>
    </div>
    <div class="sidebar-block">
      <h3>Bank Accounts</h3>
      <?php foreach($accounts as $acc): ?>
      <div class="account-card">
        <strong><?= htmlspecialchars($acc['bank_name']) ?></strong>
        <p><?= htmlspecialchars($acc['account_number']) ?></p>
        <span class="badge-small"><?= $acc['account_type'] ?></span>
        <?php if ($acc['is_primary']): ?><span class="primary-tag">Primary</span><?php endif; ?>
      </div>
      <?php endforeach; ?>
      <a href="add_account.php" class="btn-outline small" style="margin-top:12px;display:block;text-align:center">+ Add Bank Account</a>
    </div>
  </aside>

  <!-- ORDERS LIST -->
  <main class="orders-main">
    <h2>Order History</h2>
    <?php if (empty($orders)): ?>
      <div class="empty-state">
        <p>📦 No orders yet. <a href="catalogue.php">Start shopping</a></p>
      </div>
    <?php else: ?>
      <?php foreach($orders as $o): ?>
      <div class="order-card">
        <div class="order-card-header">
          <div>
            <span class="order-id">Order #<?= $o['order_id'] ?></span>
            <span class="order-date"><?= date('d M Y', strtotime($o['order_date'])) ?></span>
          </div>
          <div>
            <span class="order-status" style="background:<?= $status_colors[$o['status']] ?? '#888' ?>">
              <?= $o['status'] ?>
            </span>
            <span class="payment-status <?= strtolower($o['payment_status']) ?>">
              <?= $o['payment_status'] ?>
            </span>
          </div>
        </div>
        <div class="order-card-body">
          <p><?= $o['item_count'] ?> item<?= $o['item_count']!=1?'s':'' ?></p>
          <p class="order-total">₹<?= number_format($o['total_amount'],2) ?></p>
        </div>
        <div class="order-card-footer">
          <a href="bill.php?bill_id=<?= $o['bill_id'] ?>" class="btn-small">View Bill →</a>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </main>

</div>

<footer class="footer">
  <div class="container"><p><strong>ShopEase</strong> — Online Retail Application</p></div>
</footer>
</body>
</html>
