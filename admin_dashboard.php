<?php
// admin_dashboard.php

// Include database connection
include('db_connect.php');

// Fetch statistics from the database
totalSales = 10000; // Example data
totalOrders = 250;  // Example data
totalCustomers = 150; // Example data

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css"> <!-- Link to an external CSS file -->
    <title>Admin Dashboard</title>
</head>
<body>
    <header>
        <h1>Admin Dashboard</h1>
        <nav>
            <ul>
                <li><a href="view_orders.php">View Orders</a></li>
                <li><a href="manage_products.php">Manage Products</a></li>
                <li><a href="customer_queries.php">Customer Queries</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <section class="statistics">
            <h2>Statistics</h2>
            <p>Total Sales: <?php echo $totalSales; ?></p>
            <p>Total Orders: <?php echo $totalOrders; ?></p>
            <p>Total Customers: <?php echo $totalCustomers; ?></p>
        </section>
        <section class="quick-actions">
            <h2>Quick Actions</h2>
            <button onclick="window.location.href='add_product.php'">Add Product</button>
            <button onclick="window.location.href='view_customers.php'">View Customers</button>
            <button onclick="window.location.href='process_orders.php'">Process Orders</button>
        </section>
    </main>
    <footer>
        <p>&copy; <?php echo date("Y"); ?> ShopEase. All rights reserved.</p>
    </footer>
</body>
</html>