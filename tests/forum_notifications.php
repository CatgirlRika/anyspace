<?php
require_once __DIR__ . '/../core/forum/post.php';
require_once __DIR__ . '/../core/forum/notifications.php';

$dbFile = __DIR__ . '/forum_test.db';
@unlink($dbFile);
$dsn = 'sqlite:' . $dbFile;
putenv('DB_DSN=' . $dsn);

$conn = new PDO($dsn);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
global $conn;

$conn->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT)');
$conn->exec('CREATE TABLE forum_topics (id INTEGER PRIMARY KEY AUTOINCREMENT, locked INTEGER DEFAULT 0)');
$conn->exec('CREATE TABLE forum_posts (id INTEGER PRIMARY KEY AUTOINCREMENT, topic_id INTEGER, user_id INTEGER, body TEXT, created_at DATETIME)');
$conn->exec('CREATE TABLE notifications (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, post_id INTEGER, is_read INTEGER DEFAULT 0, created_at DATETIME)');

$conn->exec("INSERT INTO users (username) VALUES ('owner'), ('replier'), ('mentioned')");
$conn->exec("INSERT INTO forum_topics (id, locked) VALUES (1,0)");
$conn->exec("INSERT INTO forum_posts (topic_id, user_id, body, created_at) VALUES (1,1,'first',datetime('now'))");

forum_add_post(1,2,'reply to @mentioned');

$ownerCount = notifications_unread_count(1);
$mentionCount = notifications_unread_count(3);
if ($ownerCount === 1 && $mentionCount === 1) {
    echo "Notifications created\n";
} else {
    echo "Notification creation failed: owner=$ownerCount mention=$mentionCount\n";
    exit(1);
}

notifications_mark_all_read(1);
notifications_mark_all_read(3);

if (notifications_unread_count(1) === 0 && notifications_unread_count(3) === 0) {
    echo "Notifications marked read\n";
} else {
    echo "Notification mark read failed\n";
    exit(1);
}

unlink($dbFile);
?>
