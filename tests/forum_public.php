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
$conn->exec('CREATE TABLE forums (id INTEGER PRIMARY KEY AUTOINCREMENT, category_id INTEGER, parent_forum_id INTEGER, name TEXT, description TEXT, position INTEGER)');
$conn->exec('CREATE TABLE forum_topics (id INTEGER PRIMARY KEY AUTOINCREMENT, forum_id INTEGER, title TEXT, locked INTEGER DEFAULT 0)');
$conn->exec('CREATE TABLE forum_posts (id INTEGER PRIMARY KEY AUTOINCREMENT, topic_id INTEGER, user_id INTEGER, body TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP, deleted INTEGER DEFAULT 0)');
$conn->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, username TEXT)');
$conn->exec("INSERT INTO forum_categories (name, position) VALUES ('General', 1)");
$conn->exec("INSERT INTO forums (category_id, name, description, position) VALUES (1, 'Test Forum', 'A test forum', 1)");
$conn->exec("INSERT INTO users (id, username) VALUES (1, 'testuser')");

// Settings variables expected by settings.php
$siteName = 'AnySpace';
$domainName = 'example.com';
$adminUser = 1;

global $conn;
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

