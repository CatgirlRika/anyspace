<?php

// Allow test environments to provide a custom DSN via the DB_DSN
// environment variable. When present, configuration variables are
// not required and optional DB_USER/DB_PASS variables may supply
// credentials. Otherwise fall back to the default MySQL configuration
// loaded from config.php.
if (($dsn = getenv('DB_DSN')) !== false) {
    $username = getenv('DB_USER') ?: null;
    $password = getenv('DB_PASS') ?: null;
} else {
    $configFile = __DIR__ . "/config.php";
    if (!file_exists($configFile)) {
        die("Configuration file not found. Please create config.php from config.php.example");
    }
    
    require_once($configFile);
    
    // Validate required configuration variables
    if (!isset($host) || !isset($dbname)) {
        die("Missing required database configuration. Please check config.php");
    }
    
    $dsn = "mysql:host=$host;dbname=$dbname";
}

try {
    $conn = new PDO($dsn, $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set charset to UTF-8 for proper encoding
    if (strpos($dsn, 'mysql:') === 0) {
        $conn->exec("SET NAMES utf8");
    }
} catch (PDOException $e) {
    if (defined('DEBUG') && DEBUG) {
        error_log("Database connection failed: " . $e->getMessage());
        die("Connection failed: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
    } else {
        error_log("Database connection failed: " . $e->getMessage());
        die("Database connection failed. Please contact the administrator.");
    }
}
?>
