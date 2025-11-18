<?php
global $pdo;

// Read from environment variables (Docker secrets)
$host = getenv('DB_HOST') ?: 'mysql';
$db = getenv('DB_NAME') ?: 'insurance_db';
$user = getenv('DB_USER') ?: 'insurance_user';

// Try Docker secrets first, then fall back to env variable
if (file_exists('/run/secrets/db_password')) {
    $dbPassword = trim(file_get_contents('/run/secrets/db_password'));
} else {
    $dbPassword = getenv('DB_PASSWORD') ?: 'changeme';
}

$dsn = "mysql:host={$host};dbname={$db};charset=UTF8";

try {
    // Create a new PDO instance with error handling enabled
    $pdo = new PDO($dsn, $user, $dbPassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Enable exception handling
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC // Default fetch mode
    ]);
} catch (PDOException $e) {
    // Handle connection errors
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please contact administrator.");
}
?>
