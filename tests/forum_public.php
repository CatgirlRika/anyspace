<?php
// Integration test for public forum listing page.
// Uses SQLite to avoid external dependencies.

// Setup SQLite database in a temporary file and configure connection
$dbFile = __DIR__ . '/forum_test.db';
@unlink($dbFile);
$dsn = 'sqlite:' . $dbFile;
putenv('DB_DSN=' . $dsn);

// Establish connection and seed data
$conn = new PDO($dsn);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$conn->exec('CREATE TABLE forum_categories (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, position INTEGER)');
$conn->exec("INSERT INTO forum_categories (name, position) VALUES ('General', 1)");

// Settings variables expected by settings.php
$siteName = 'AnySpace';
$domainName = 'example.com';
$adminUser = 1;

global $conn;

// Capture output of the index page, which in turn loads the forums list
ob_start();
require __DIR__ . '/../public/forum/index.php';
$output = ob_get_clean();

// Simple assertions
if (strpos($output, '<h1>Forums</h1>') !== false && strpos($output, 'General') !== false) {
    echo "Forums page displays categories\n";
} else {
    echo "Forums page test failed\n";
    exit(1);
}

// Cleanup temporary database
unlink($dbFile);

