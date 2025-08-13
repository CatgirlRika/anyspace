<?php
// Integration test for forum search functionality.

$dbFile = __DIR__ . '/forum_search.db';
@unlink($dbFile);
$dsn = 'sqlite:' . $dbFile;
putenv('DB_DSN=' . $dsn);

$conn = new PDO($dsn);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$conn->exec('CREATE TABLE forums (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
$conn->exec('CREATE TABLE forum_topics (id INTEGER PRIMARY KEY AUTOINCREMENT, forum_id INTEGER, title TEXT)');
$conn->exec('CREATE TABLE forum_posts (id INTEGER PRIMARY KEY AUTOINCREMENT, topic_id INTEGER, body TEXT)');
$conn->exec('CREATE TABLE forum_permissions (forum_id INTEGER, role TEXT, can_view INTEGER, can_post INTEGER, can_moderate INTEGER)');
$conn->exec("INSERT INTO forums (id, name) VALUES (1, 'Public'), (2, 'Private')");
$conn->exec("INSERT INTO forum_topics (forum_id, title) VALUES (1, 'Hello World')");
$conn->exec("INSERT INTO forum_posts (topic_id, body) VALUES (1, 'First post body')");
$conn->exec("INSERT INTO forum_topics (forum_id, title) VALUES (2, 'Another Topic')");
$conn->exec("INSERT INTO forum_posts (topic_id, body) VALUES (2, 'Something else')");
$conn->exec("INSERT INTO forum_permissions (forum_id, role, can_view, can_post, can_moderate) VALUES (1, 'guest', 1, 0, 0)");
$conn->exec("INSERT INTO forum_permissions (forum_id, role, can_view, can_post, can_moderate) VALUES (2, 'guest', 0, 0, 0)");

$siteName = 'AnySpace';
$domainName = 'example.com';
$adminUser = 1;

global $conn;

$_GET['q'] = 'Hello';
ob_start();
require __DIR__ . '/../public/forum/search.php';
$output = ob_get_clean();

$_GET['q'] = 'Another';
ob_start();
require __DIR__ . '/../public/forum/search.php';
$output2 = ob_get_clean();

if (strpos($output, 'Hello World') !== false && strpos($output2, 'Another Topic') === false) {
    echo "Forum search works\n";
} else {
    echo "Forum search failed\n";
    exit(1);
}

unlink($dbFile);
