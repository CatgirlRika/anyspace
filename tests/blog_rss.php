<?php
// Integration test for blog RSS feed endpoint

// Setup SQLite database and configure connection
$dbFile = __DIR__ . '/blog_rss_test.db';
@unlink($dbFile);
$dsn = 'sqlite:' . $dbFile;
putenv('DB_DSN=' . $dsn);

// Establish connection and seed data
$conn = new PDO($dsn);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$conn->exec('CREATE TABLE blogs (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, text TEXT, author INTEGER, category INTEGER, date TEXT)');
$conn->exec("INSERT INTO blogs (title, text, author, category, date) VALUES ('Test entry', 'Content', 1, 1, datetime('now'))");

// Settings variables expected by settings.php
$siteName = 'AnySpace';
$domainName = 'example.com';
$adminUser = 1;

ob_start();
require __DIR__ . '/../public/blog/rss.php';
$output = ob_get_clean();

if (strpos($output, '<rss') !== false && strpos($output, '<channel>') !== false) {
    echo "RSS feed output OK\n";
} else {
    echo "RSS feed output failed\n";
    exit(1);
}

// Cleanup temporary database
unlink($dbFile);

