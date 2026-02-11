<?php
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'chat-app';

$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Disable FK checks
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

// Truncate table
$conn->query("TRUNCATE TABLE users");

// Re-enable FK checks
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

echo "Table truncated successfully!";
$conn->close();
