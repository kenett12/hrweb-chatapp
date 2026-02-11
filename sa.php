<?php
// CONFIG: set your DB credentials
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'chat-app'; // replace with your database

// Connect to MySQL
$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n");
}

// Prompt for superadmin details
echo "Enter username: ";
$username = trim(fgets(STDIN));

echo "Enter email: ";
$email = trim(fgets(STDIN));

echo "Enter nickname: ";
$nickname = trim(fgets(STDIN));

echo "Enter password: ";
$password = trim(fgets(STDIN));

// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Check if superadmin already exists
$sqlCheck = "SELECT * FROM users WHERE email = ?";
$stmt = $conn->prepare($sqlCheck);
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "Error: A superadmin with this email already exists.\n";
    exit;
}

// Insert superadmin
$sqlInsert = "INSERT INTO users (username, email, password, nickname, role, status, created_at, updated_at) VALUES (?, ?, ?, ?, 'superadmin', 'active', NOW(), NOW())";
$stmt = $conn->prepare($sqlInsert);
$stmt->bind_param('ssss', $username, $email, $hashedPassword, $nickname);
if ($stmt->execute()) {
    echo "Superadmin account created successfully!\n";
} else {
    echo "Error: " . $stmt->error . "\n";
}

$conn->close();
