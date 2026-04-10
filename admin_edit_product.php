<?php
// admin_edit_product.php

// Start session
session_start();

// Include database connection
include 'db_connect.php';

// Check if the user is an admin
if(!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch product details from database
if ($product_id) {
    $query = "SELECT * FROM products WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
} else {
    die('Invalid product ID.');
}

// Update product details if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $description = $_POST['description'];

    $update_query = "UPDATE products SET name = ?, price = ?, description = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param('sdsi', $name, $price, $description, $product_id);
    $update_stmt->execute();

    header('Location: products.php?message=Product+updated+successfully');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
</head>
<body>
    <h1>Edit Product</h1>
    <form action="admin_edit_product.php?id=<?php echo $product_id; ?>" method="post">
        <label for="name">Product Name:</label>
        <input type="text" id="name" name="name" value="<?php echo htmlentities($product['name']); ?>" required><br>

        <label for="price">Price:</label>
        <input type="number" step="0.01" id="price" name="price" value="<?php echo $product['price']; ?>" required><br>

        <label for="description">Description:</label>
        <textarea id="description" name="description" required><?php echo htmlentities($product['description']); ?></textarea><br>

        <input type="submit" value="Update Product">
    </form>
</body>
</html>
