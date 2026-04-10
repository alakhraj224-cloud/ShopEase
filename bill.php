<?php
// bill.php
require_once 'config.php';
if (!isset($_SESSION['customer_id'])) { header('Location: login.php'); exit; }

$bill_id = (int)($_GET['bill_id'] ?? 0);
$cid     = $_SESSION['customer_id'];
$db      = getDB();

// Fetch bill (only for this customer)
$st = $db->prepare("
    SELECT b.*, o.order_date, o.status, o.shipping_address,
           ba.bank_name, ba.account_number, ba.account_type,
           c.first_name, c.last_name, c.email, c.phone
    FROM BILL b
    JOIN `ORDER`      o  ON b.order_id   = o.order_id
    JOIN CUSTOMER     c  ON o.customer_id = c.customer_id
    JOIN BANK_ACCOUNT ba ON b.account_id = ba.account_id
    WHERE b.bill_id = ? AND o.customer_id = ?
");
$st->bind_param('ii', $bill_id, $cid);
$st->execute();
$bill = $st->get_result()->fetch_assoc();

if (!$bill) { echo 'Bill not found.'; exit; }

// Fetch order items
$oi = $db->prepare("
    SELECT oi.*, i.item_name, ic.class_name,
           IFNULL(d.discount_name,'No Discount') AS discount_name
    FROM ORDER_ITEM oi
    JOIN ITEM i ON oi.item_id = i.item_id
    JOIN ITEM_CLASS ic ON i.class_id = ic.class_id
    LEFT JOIN DISCOUNT d ON oi.discount_id = d.discount_id
    WHERE oi.order_id = ?
");
$oi->bind_param('i', $bill['order_id']);
$oi->execute();
$items = $oi->get_result()->fetch_all(MYSQLI_ASSOC);
$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bill #<?= $bill_id ?> — ShopEase</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar">
  <div class="nav-inner">
    <a href="index.php" class="logo">Shop<span>Ease</span></a>
    <div class="nav-links">
      <a href="catalogue.php">Catalogue</a>
      <a href="orders.php">My Orders</a>
      <a href="logout.php" class="btn-nav">Logout</a>
    </div>
  </div>
</nav>

<div class="container bill-container">
  <div class="bill-box">

    <!-- HEADER -->
    <div class="bill-header">
      <div>
        <h1 class="logo-text">Shop<span>Ease</span></h1>
        <p>Online Retail Application</p>
      </div>
      <div class="bill-meta">
        <h2>INVOICE</h2>
        <p>Bill # <strong><?= $bill_id ?></strong></p>
        <p>Order # <strong><?= $bill['order_id'] ?></strong></p>
        <p>Date: <?= date('d M Y, h:i A', strtotime($bill['bill_date'])) ?></p>
        <span class="status-badge <?= strtolower($bill['payment_status']) ?>"><?= $bill['payment_status'] ?></span>
      </div>
    </div>

    <!-- CUSTOMER INFO -->
    <div class="bill-info-grid">
      <div class="bill-info-block">
        <h4>Billed To</h4>
        <p><strong><?= htmlspecialchars($bill['first_name'].' '.$bill['last_name']) ?></strong></p>
        <p><?= htmlspecialchars($bill['email']) ?></p>
        <p><?= htmlspecialchars($bill['phone'] ?? '') ?></p>
      </div>
      <div class="bill-info-block">
        <h4>Ship To</h4>
        <p><?= htmlspecialchars($bill['shipping_address']) ?></p>
      </div>
      <div class="bill-info-block">
        <h4>Payment Method</h4>
        <p><strong><?= htmlspecialchars($bill['bank_name']) ?></strong></p>
        <p>A/C: <?= htmlspecialchars($bill['account_number']) ?></p>
        <p><?= $bill['account_type'] ?> Account</p>
      </div>
    </div>

    <!-- ITEMS TABLE -->
    <table class="bill-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Item</th>
          <th>Class</th>
          <th>Qty</th>
          <th>Unit Price</th>
          <th>Discount</th>
          <th>Line Total</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($items as $i => $item): ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td><?= htmlspecialchars($item['item_name']) ?></td>
          <td><span class="badge-small"><?= $item['class_name'] ?></span></td>
          <td><?= $item['quantity'] ?></td>
          <td>₹<?= number_format($item['unit_price_at_order'],2) ?></td>
          <td><?= $item['discount_amount'] > 0 ? '- ₹'.number_format($item['discount_amount'],2).' ('.$item['discount_name'].')' : '—' ?></td>
          <td><strong>₹<?= number_format($item['line_total'],2) ?></strong></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <!-- TOTALS -->
    <div class="bill-totals">
      <div class="totals-row"><span>Subtotal</span><span>₹<?= number_format($bill['subtotal'],2) ?></span></div>
      <div class="totals-row"><span>GST (18%)</span><span>₹<?= number_format($bill['tax_amount'],2) ?></span></div>
      <div class="totals-row grand"><span>TOTAL AMOUNT</span><span>₹<?= number_format($bill['total_amount'],2) ?></span></div>
    </div>

    <div class="bill-actions">
      <a href="orders.php" class="btn-primary">View All Orders</a>
      <a href="catalogue.php" class="btn-outline">Continue Shopping</a>
      <button onclick="window.print()" class="btn-outline">🖨️ Print Bill</button>
    </div>

  </div>
</div>

<footer class="footer">
  <div class="container"><p><strong>ShopEase</strong> — Thank you for shopping with us!</p></div>
</footer>
</body>
</html>
