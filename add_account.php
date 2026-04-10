<?php
// add_account.php
require_once 'config.php';
if (!isset($_SESSION['customer_id'])) { header('Location: login.php'); exit; }

$cid = $_SESSION['customer_id'];
$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account_number = trim($_POST['account_number'] ?? '');
    $bank_name      = trim($_POST['bank_name'] ?? '');
    $account_type   = $_POST['account_type'] ?? 'Savings';

    if (!$account_number || !$bank_name) {
        $error = 'All fields are required.';
    } else {
        $db = getDB();
        $ins = $db->prepare("INSERT INTO BANK_ACCOUNT (customer_id,account_number,bank_name,account_type,is_primary) VALUES (?,?,?,?,0)");
        $ins->bind_param('isss', $cid, $account_number, $bank_name, $account_type);
        if ($ins->execute()) {
            $msg = 'Bank account added successfully!';
        } else {
            $error = 'Account number already exists.';
        }
        $db->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Bank Account — ShopEase</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
<nav class="navbar">
  <div class="nav-inner">
    <a href="index.php" class="logo">Shop<span>Ease</span></a>
    <div class="nav-links">
      <a href="orders.php" class="btn-nav">← Back to Account</a>
    </div>
  </div>
</nav>
<div class="auth-container">
  <div class="auth-box">
    <h2>Add Bank Account</h2>
    <p class="auth-sub">Add another bank account to your profile</p>
    <?php if ($msg): ?><div class="alert success"><?= $msg ?> <a href="orders.php">Back to account →</a></div><?php endif; ?>
    <?php if ($error): ?><div class="alert error"><p><?= htmlspecialchars($error) ?></p></div><?php endif; ?>
    <form method="POST" class="auth-form">
      <div class="form-group">
        <label>Account Number *</label>
        <input type="text" name="account_number" required>
      </div>
      <div class="form-group">
        <label>Bank Name *</label>
        <input type="text" name="bank_name" required>
      </div>
      <div class="form-group">
        <label>Account Type</label>
        <select name="account_type">
          <option value="Savings">Savings</option>
          <option value="Current">Current</option>
          <option value="Credit">Credit</option>
        </select>
      </div>
      <button type="submit" class="btn-submit">Add Account</button>
    </form>
  </div>
</div>
</body>
</html>
