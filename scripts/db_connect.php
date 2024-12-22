<?php
global $pdo;

$host = 'localhost';
$db = 'insurance_db';
$user = 'root';
$dbPassword = 'Oprogr@mowanie24';

$dsn = "mysql:host=localhost;dbname=insurance_db;charset=UTF8";

try {
    // Create a new PDO instance with error handling enabled
    $pdo = new PDO($dsn, $user, $dbPassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Enable exception handling
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC // Default fetch mode
    ]);
} catch (PDOException $e) {
    var_dump($e->getMessage());
    // Handle connection errors
    die("Database connection failed: " . $e->getMessage());


}
?>