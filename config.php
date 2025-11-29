<?php
// config.php - Shared configuration and database connection
session_start();

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$dbname = 'extra_class_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // No return statement - $pdo is now available to any file that includes this config
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>