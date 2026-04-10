<?php
require_once 'config.php';
if (!isset($_SESSION['customer_id'])) { header('Location: login.php'); exit; }

$db = getDB();
$cid = $_SESSION['customer_id'];

// Initialize cart
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $iid = (int)($_POST['item_id'] ?? 0);
        $qty = (int)($_POST['qty'] ?? 1);
        if ($iid > 0 && $qty > 0) {
            if (isset($_SESSION['cart'][$iid])) {
                $_SESSION['cart'][$iid]['qty'] += $qty;
            } else {
                $_SESSION['cart'][$iid] = ['qty' => $qty, 'discount_id' => null];
            }
            $msg = 'Item added to cart!';
        }
    }

    if ($action === 'remove') {
        $iid = (int)($_POST['item_id'] ?? 0);
        unset($_SESSION['cart'][$iid]);
    }

    if ($action === 'update_discount') {
        $iid = (int)($_POST['item_id'] ?? 0);
        $did = !empty($_POST['discount_id']) ? (int)$_POST['discount_id'] : null;
        if (isset($_SESSION['cart'][$iid])) {
            $_SESSION['cart'][$iid]['discount_id'] = $did;
        }
    }

    if ($action === 'checkout' && !empty($_SESSION['cart'])) {
        $account_id       = (int)($_POST['account_id'] ?? 0);
        $shipping_address = trim($_POST['shipping_address'] ?? '');

        if (!$account_id || !$shipping_address) {
            $msg = 'Please select a bank account and enter shipping address.';
        } else {
            // Create order
            $ins = $db->prepare("INSERT INTO `ORDER` (customer_id, shipping_address) VALUES (?,?)");
            $ins->bind_param('is', $cid, $shipping_address);
            $ins->execute();
            $order_id = $db->insert_id;
            $ins->close();

            $subtotal = 0;

            foreach ($_SESSION['cart'] as $iid => $c) {
                $qty = $c['qty'];
                $did = $c['discount_id'];

                // Get price
                $pr = $db->prepare("SELECT unit_price FROM ITEM WHERE item_id=?");
                $pr->bind_param('i', $iid);
                $pr->execute();
                $result = $pr->get_result();
                $row_price = $result->fetch_assoc();
                $uprice = $row_price['unit_price'];
                $pr->close();

                $disc_amt = 0;
                if ($did) {
                    $dr = $db->prepare("SELECT discount_type, discount_value FROM DISCOUNT WHERE discount_id=?");
                    $dr->bind_param('i', $did);
                    $dr->execute();
                    $dresult = $dr->get_result();
                    $drow = $dresult->fetch_assoc();
                    $dr->close();

                    if ($drow) {
                        $disc_amt = ($drow['discount_type'] === 'PERCENTAGE')
                            ? ($uprice * $qty) * ($drow['discount_value'] / 100)
                            : $drow['discount_value'];
                    }
                }

                $line_total = ($uprice * $qty) - $disc_amt;
                $subtotal  += $line_total;

                $oi = $db->prepare("INSERT INTO ORDER_ITEM (order_id,item_id,discount_id,quantity,unit_price_at_order,discount_amount,line_total) VALUES (?,?,?,?,?,?,?)");
                $oi->bind_param('iiidddd', $order_id, $iid, $did, $qty, $uprice, $disc_amt, $line_total);
                $oi->execute();
                $oi->close();

                // Reduce stock
                $st = $db->prepare("UPDATE ITEM SET stock_quantity = stock_quantity - ? WHERE item_id = ?");
                $st->bind_param('ii', $qty, $iid);
                $st->execute();
                $st->close();
            }

            $tax   = round($subtotal * 0.18, 2);
            $total = $subtotal + $tax;

            $bill = $db->prepare("INSERT INTO BILL (order_id,account_id,subtotal,tax_amount,total_amount) VALUES (?,?,?,?,?)");
            $bill->bind_param('iiddd', $order_id, $account_id, $subtotal, $tax, $total);
            $bill->execute();
            $bill_id = $db->insert_id;
            $bill->close();

            $_SESSION['cart'] = [];
            $db->close();
            header("Location: bill.php?bill_id=$bill_id");
            exit;
        }
    }
}

// ─────────────────────────────────────────────
//  Load cart items for display
// ─────────────────────────────────────────────
$cart_items  = [];
$grand_total = 0;

if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $iid => $c) {
        $st = $db->prepare("SELECT i.*, ic.class_name FROM ITEM i JOIN ITEM_CLASS ic ON i.class_id=ic.class_id WHERE i.item_id=?");
        $st->bind_param('i', $iid);
        $st->execute();
        $row = $st->get_result()->fetch_assoc();
        $st->close();

        if ($row) {
            $row['cart_qty']    = $c['qty'];
            $row['discount_id'] = $c['discount_id'];
            $disc_amt = 0;

            if ($c['discount_id']) {
                $dr = $db->prepare("SELECT discount_type, discount_value FROM DISCOUNT WHERE discount_id=?");
                $dr->bind_param('i', $c['discount_id']);
                $dr->execute();
                $drow = $dr->get_result()->fetch_assoc();
                $dr->close();

                if ($drow) {
                    $disc_amt = ($drow['discount_type'] === 'PERCENTAGE')
                        ? ($row['unit_price'] * $c['qty']) * ($drow['discount_value'] / 100)
                        : $drow['discount_value'];
                }
            }

            $row['disc_amt']   = $disc_amt;
            $row['line_total'] = ($row['unit_price'] * $c['qty']) - $disc_amt;
            $grand_total      += $row['line_total'];
            $cart_items[]      = $row;
        }
    }
}

// Load accounts and discounts
$acc_st = $db->prepare("SELECT * FROM BANK_ACCOUNT WHERE customer_id = ?");
$acc_st->bind_param('i', $cid);
$acc_st->execute();
$accounts = $acc_st->get_result()->fetch_all(MYSQLI_ASSOC);
$acc_st->close();

$discounts = $db->query("SELECT * FROM DISCOUNT WHERE CURDATE() BETWEEN valid_from AND IFNULL(valid_to, CURDATE())")->fetch_all(MYSQLI_ASSOC);

$db->close();

$tax   = round($grand_total * 0.18, 2);
$total = $grand_total + $tax;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cart & Checkout — ShopEase</title>
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
      <a href="cart.php" class="cart-link active">🛒 Cart</a>
      <a href="logout.php" class="btn-nav">Logout</a>
    </div>
  </div>
</nav>

<div class="page-header">
  <div class="container"><h1>Shopping Cart</h1></div>
</div>

<div class="container cart-layout">

  <div class="cart-main">
    <?php if ($msg): ?>
      <div class="alert <?= strpos($msg,'select') !== false ? 'error' : 'success' ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <?php if (empty($cart_items)): ?>
      <div class="empty-state">
        <p>🛒 Your cart is empty. <a href="catalogue.php">Browse products</a></p>
      </div>
    <?php else: ?>
      <?php foreach($cart_items as $item): ?>
      <div class="cart-item">
        <div class="cart-item-info">
          <h4><?= htmlspecialchars($item['item_name']) ?></h4>
          <p class="badge-small"><?= htmlspecialchars($item['class_name']) ?></p>
          <p>Unit Price: <strong>₹<?= number_format($item['unit_price'],2) ?></strong> × <?= $item['cart_qty'] ?></p>
        </div>
        <div class="cart-item-discount">
          <form method="POST">
            <input type="hidden" name="action"   value="update_discount">
            <input type="hidden" name="item_id"  value="<?= $item['item_id'] ?>">
            <select name="discount_id" onchange="this.form.submit()" class="discount-select">
              <option value="">No Discount</option>
              <?php foreach($discounts as $d): ?>
              <option value="<?= $d['discount_id'] ?>" <?= $item['discount_id']==$d['discount_id']?'selected':'' ?>>
                <?= htmlspecialchars($d['discount_name']) ?> (<?= $d['discount_type']==='PERCENTAGE'?$d['discount_value'].'%':'₹'.$d['discount_value'] ?> OFF)
              </option>
              <?php endforeach; ?>
            </select>
          </form>
          <?php if ($item['disc_amt'] > 0): ?>
            <span class="discount-tag">- ₹<?= number_format($item['disc_amt'],2) ?></span>
          <?php endif; ?>
        </div>
        <div class="cart-item-total">
          <strong>₹<?= number_format($item['line_total'],2) ?></strong>
          <form method="POST">
            <input type="hidden" name="action"  value="remove">
            <input type="hidden" name="item_id" value="<?= $item['item_id'] ?>">
            <button type="submit" class="btn-remove">✕ Remove</button>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <?php if (!empty($cart_items)): ?>
  <aside class="cart-summary">
    <div class="summary-box">
      <h3>Order Summary</h3>
      <div class="summary-row"><span>Subtotal</span><span>₹<?= number_format($grand_total,2) ?></span></div>
      <div class="summary-row"><span>GST (18%)</span><span>₹<?= number_format($tax,2) ?></span></div>
      <div class="summary-row total-row"><span>Total</span><span>₹<?= number_format($total,2) ?></span></div>

      <form method="POST" class="checkout-form">
        <input type="hidden" name="action" value="checkout">
        <div class="form-group">
          <label>Shipping Address</label>
          <input type="text" name="shipping_address" placeholder="Enter delivery address" required>
        </div>
        <div class="form-group">
          <label>Pay with Bank Account</label>
          <select name="account_id" required>
            <option value="">Select account...</option>
            <?php foreach($accounts as $acc): ?>
            <option value="<?= $acc['account_id'] ?>">
              <?= htmlspecialchars($acc['bank_name']) ?> — <?= htmlspecialchars($acc['account_number']) ?> (<?= $acc['account_type'] ?>)
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn-submit">Place Order & Pay</button>
      </form>
    </div>
  </aside>
  <?php endif; ?>

</div>

<footer class="footer">
  <div class="container"><p><strong>ShopEase</strong> — Online Retail Application</p></div>
</footer>
</body>
</html>