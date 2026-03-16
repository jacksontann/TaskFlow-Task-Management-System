<?php
/**
 * Database Configuration
 * 
 * SETUP INSTRUCTIONS:
 * 1. Copy this file and rename it to: database.php
 * 2. Update the credentials below with your own MySQL details
 * 3. Make sure you have created the 'taskflow' database first
 *    by running: database/schema.sql in MySQL Workbench
 */

$host = 'localhost';
$port = '3306';
$dbname = 'taskflow';
$username = 'root';           // <-- Change to your MySQL username
$password = 'YOUR_PASSWORD';  // <-- Change to your MySQL password
$charset = 'utf8mb4';

// Build DSN (Data Source Name)
$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";

// PDO options for security and convenience
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // Throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // Fetch as associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                    // Use real prepared statements
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // Show a friendly error message (hide details in production)
    die("Database connection failed: " . $e->getMessage());
}
