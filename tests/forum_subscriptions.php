<?php
require_once __DIR__ . '/../core/forum/post.php';
require_once __DIR__ . '/../core/forum/notifications.php';
require_once __DIR__ . '/../core/forum/subscriptions.php';

$dbFile = __DIR__ . '/forum_test.db';
@unlink($dbFile);
$dsn = 'sqlite:' . $dbFile;
putenv('DB_DSN=' . $dsn);

$conn = new PDO($dsn);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
global $conn;

$conn->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT)');
$conn->exec('CREATE TABLE forum_topics (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, locked INTEGER DEFAULT 0)');
$conn->exec('CREATE TABLE forum_posts (id INTEGER PRIMARY KEY AUTOINCREMENT, topic_id INTEGER, user_id INTEGER, body TEXT, created_at DATETIME, deleted INTEGER DEFAULT 0, deleted_by INTEGER, deleted_at DATETIME)');
$conn->exec('CREATE TABLE notifications (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, post_id INTEGER, is_read INTEGER DEFAULT 0, created_at DATETIME)');
$conn->exec('CREATE TABLE topic_subscriptions (user_id INTEGER, topic_id INTEGER, PRIMARY KEY(user_id, topic_id))');

$conn->exec("INSERT INTO users (username) VALUES ('owner'), ('subscriber'), ('replier')");
$conn->exec("INSERT INTO forum_topics (title, locked) VALUES ('Test', 0)");
$conn->exec("INSERT INTO forum_posts (topic_id, user_id, body, created_at) VALUES (1,1,'first',datetime('now'))");

subscribeTopic(2,1);
$subs = getUserSubscriptions(2);
subscribeTopic(2,1);
$subs2 = getUserSubscriptions(2);

if (count($subs) === 1 && count($subs2) === 0) {
    echo "Subscription toggling works\n";
} else {
    echo "Subscription toggling failed\n";
    exit(1);
}

subscribeTopic(2,1);
forum_add_post(1,3,'reply');

if (notifications_unread_count(2) === 1) {
    echo "Subscription notification created\n";
} else {
    echo "Subscription notification failed\n";
    exit(1);
}

unlink($dbFile);
?>
