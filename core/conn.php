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
    require_once("config.php");
    $dsn = "mysql:host=$host;dbname=$dbname";
}

try {
    $conn = new PDO($dsn, $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: ", $e->getMessage();
}
?>
