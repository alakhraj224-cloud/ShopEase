<?php
require_once 'config.php';
if (isset($_SESSION['customer_id'])) { header('Location: index.php'); exit; }

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name     = trim($_POST['first_name'] ?? '');
    $last_name      = trim($_POST['last_name'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $phone          = trim($_POST['phone'] ?? '');
    $address        = trim($_POST['address'] ?? '');
    $user_id        = trim($_POST['user_id'] ?? '');
    $password       = $_POST['password'] ?? '';
    $confirm        = $_POST['confirm_password'] ?? '';
    $account_number = trim($_POST['account_number'] ?? '');
    $bank_name      = trim($_POST['bank_name'] ?? '');
    $account_type   = $_POST['account_type'] ?? 'Savings';

    // Validate
    if (!$first_name) $errors[] = 'First name is required.';
    if (!$last_name)  $errors[] = 'Last name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (strlen($user_id) < 4) $errors[] = 'User ID must be at least 4 characters.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';
    if (!$account_number) $errors[] = 'Bank account number is required.';
    if (!$bank_name) $errors[] = 'Bank name is required.';

    if (empty($errors)) {
        $db = getDB();

        // Check duplicate user_id or email
        $chk = $db->prepare("SELECT customer_id FROM CUSTOMER WHERE user_id=? OR email=?");
        $chk->bind_param('ss', $user_id, $email);
        $chk->execute();
        $chk->store_result();

        if ($chk->num_rows > 0) {
            $errors[] = 'User ID or Email already exists. Please choose another.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);

            // Insert customer
            $ins = $db->prepare("INSERT INTO CUSTOMER (user_id,password_hash,first_name,last_name,email,phone,address) VALUES (?,?,?,?,?,?,?)");
            $ins->bind_param('sssssss', $user_id, $hash, $first_name, $last_name, $email, $phone, $address);
            $ins->execute();
            $cid = $db->insert_id;

            // Insert bank account
            $bins = $db->prepare("INSERT INTO BANK_ACCOUNT (customer_id,account_number,bank_name,account_type,is_primary) VALUES (?,?,?,?,1)");
            $bins->bind_param('isss', $cid, $account_number, $bank_name, $account_type);
            $bins->execute();

            $success = 'Registration successful! You can now login.';
        }
        $db->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register — ShopEase</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">

<nav class="navbar">
  <div class="nav-inner">
    <a href="index.php" class="logo">Shop<span>Ease</span></a>
    <div class="nav-links">
      <a href="login.php" class="btn-nav">Login</a>
    </div>
  </div>
</nav>

<div class="auth-container">
  <div class="auth-box wide">
    <h2>Create Account</h2>
    <p class="auth-sub">Join ShopEase and start shopping today</p>

    <?php if ($success): ?>
      <div class="alert success"><?= $success ?> <a href="login.php">Login now →</a></div>
    <?php endif; ?>
    <?php if ($errors): ?>
      <div class="alert error">
        <?php foreach($errors as $e): ?><p>• <?= htmlspecialchars($e) ?></p><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="POST" class="auth-form">
      <div class="form-section-title">Personal Details</div>
      <div class="form-row">
        <div class="form-group">
          <label>First Name *</label>
          <input type="text" name="first_name" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label>Last Name *</label>
          <input type="text" name="last_name" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required>
        </div>
      </div>
      <div class="form-group">
        <label>Email Address *</label>
        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Phone</label>
          <input type="text" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Delivery Address</label>
          <input type="text" name="address" value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
        </div>
      </div>

      <div class="form-section-title">Login Credentials</div>
      <div class="form-row">
        <div class="form-group">
          <label>User ID *</label>
          <input type="text" name="user_id" value="<?= htmlspecialchars($_POST['user_id'] ?? '') ?>" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Password *</label>
          <input type="password" name="password" required>
        </div>
        <div class="form-group">
          <label>Confirm Password *</label>
          <input type="password" name="confirm_password" required>
        </div>
      </div>

      <div class="form-section-title">Bank Account Details</div>
      <div class="form-row">
        <div class="form-group">
          <label>Account Number *</label>
          <input type="text" name="account_number" value="<?= htmlspecialchars($_POST['account_number'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label>Bank Name *</label>
          <input type="text" name="bank_name" value="<?= htmlspecialchars($_POST['bank_name'] ?? '') ?>" required>
        </div>
      </div>
      <div class="form-group">
        <label>Account Type</label>
        <select name="account_type">
          <option value="Savings">Savings</option>
          <option value="Current">Current</option>
          <option value="Credit">Credit</option>
        </select>
      </div>

      <button type="submit" class="btn-submit">Create My Account</button>
      <p class="auth-switch">Already have an account? <a href="login.php">Login here</a></p>
    </form>
  </div>
</div>

</body>
</html>
