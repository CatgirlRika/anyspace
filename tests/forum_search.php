<?php
// Integration test for forum search functionality.

$dbFile = __DIR__ . '/forum_search.db';
@unlink($dbFile);
$dsn = 'sqlite:' . $dbFile;
putenv('DB_DSN=' . $dsn);

$conn = new PDO($dsn);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$conn->exec('CREATE TABLE forum_topics (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT)');
$conn->exec('CREATE TABLE forum_posts (id INTEGER PRIMARY KEY AUTOINCREMENT, topic_id INTEGER, body TEXT)');
$conn->exec("INSERT INTO forum_topics (title) VALUES ('Hello World')");
$conn->exec("INSERT INTO forum_posts (topic_id, body) VALUES (1, 'First post body')");
$conn->exec("INSERT INTO forum_topics (title) VALUES ('Another Topic')");
$conn->exec("INSERT INTO forum_posts (topic_id, body) VALUES (2, 'Something else')");

$siteName = 'AnySpace';
$domainName = 'example.com';
$adminUser = 1;

global $conn;

$_GET['q'] = 'Hello';
ob_start();
require __DIR__ . '/../public/forum/search.php';
$output = ob_get_clean();

if (strpos($output, 'Hello World') !== false && strpos($output, 'Another Topic') === false) {
    echo "Forum search works\n";
} else {
    echo "Forum search failed\n";
    exit(1);
}

unlink($dbFile);
