<?php
// ============================================================
//  DATABASE CONFIGURATION
//  File: config.php
// ============================================================

define('DB_HOST', 'localhost:3307');
define('DB_USER', 'root');
define('DB_PASS', '');           // XAMPP default has no password
define('DB_NAME', 'online_retail');

function getDB() {
    $conn = new mysqli('localhost:3307', 'root', '', 'online_retail');
    if ($conn->connect_error) {
        die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
