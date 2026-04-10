<?php
require_once 'config.php';
if (isset($_SESSION['customer_id'])) { header('Location: index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id  = trim($_POST['user_id'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($user_id && $password) {
        $db = getDB();
        $stmt = $db->prepare("SELECT customer_id, password_hash, first_name FROM CUSTOMER WHERE user_id = ?");
        $stmt->bind_param('s', $user_id);
        $stmt->execute();
        $stmt->bind_result($cid, $hash, $fname);
        $stmt->fetch();

        if ($cid && password_verify($password, $hash)) {
            $_SESSION['customer_id'] = $cid;
            $_SESSION['first_name']  = $fname;
            $_SESSION['user_id']     = $user_id;
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid User ID or Password.';
        }
        $db->close();
    } else {
        $error = 'Please enter your User ID and Password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — ShopEase</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">

<nav class="navbar">
  <div class="nav-inner">
    <a href="index.php" class="logo">Shop<span>Ease</span></a>
    <div class="nav-links">
      <a href="register.php" class="btn-nav btn-accent">Register</a>
    </div>
  </div>
</nav>

<div class="auth-container">
  <div class="auth-box">
    <h2>Welcome Back</h2>
    <p class="auth-sub">Login to your ShopEase account</p>

    <?php if ($error): ?>
      <div class="alert error"><p><?= htmlspecialchars($error) ?></p></div>
    <?php endif; ?>

    <form method="POST" class="auth-form">
      <div class="form-group">
        <label>User ID</label>
        <input type="text" name="user_id" value="<?= htmlspecialchars($_POST['user_id'] ?? '') ?>" required autofocus>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" required>
      </div>
      <button type="submit" class="btn-submit">Login</button>
      <p class="auth-switch">Don't have an account? <a href="register.php">Register here</a></p>
    </form>

    <div class="demo-hint">
      <p>🔑 Demo accounts (password: <strong>Pass@123</strong>)</p>
      <p>User IDs: john_doe / jane_smith / raj_kumar</p>
    </div>
  </div>
</div>

</body>
</html>
