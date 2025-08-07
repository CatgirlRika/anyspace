<?php
require_once __DIR__.'/../core/forum/forum.php';

// in-memory database for testing
$conn = new PDO('sqlite::memory:');
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
global $conn;

$conn->exec('CREATE TABLE forums (id INTEGER PRIMARY KEY, category_id INTEGER, parent_forum_id INTEGER)');
$conn->exec('CREATE TABLE forum_topics (id INTEGER PRIMARY KEY, forum_id INTEGER)');
$conn->exec('CREATE TABLE forum_posts (id INTEGER PRIMARY KEY, topic_id INTEGER)');
$conn->exec('CREATE TABLE forum_moderators (forum_id INTEGER, user_id INTEGER)');
$conn->exec('CREATE TABLE forum_permissions (forum_id INTEGER, role TEXT, can_view INTEGER, can_post INTEGER, can_moderate INTEGER)');

// seed forum with a child, topics, posts, moderator and permissions
$conn->exec("INSERT INTO forums (id, category_id, parent_forum_id) VALUES (1,1,NULL)");
$conn->exec("INSERT INTO forums (id, category_id, parent_forum_id) VALUES (2,1,1)");
$conn->exec("INSERT INTO forum_topics (id, forum_id) VALUES (1,1)");
$conn->exec("INSERT INTO forum_topics (id, forum_id) VALUES (2,2)");
$conn->exec("INSERT INTO forum_posts (id, topic_id) VALUES (1,1)");
$conn->exec("INSERT INTO forum_posts (id, topic_id) VALUES (2,2)");
$conn->exec("INSERT INTO forum_moderators (forum_id, user_id) VALUES (1,10)");
$conn->exec("INSERT INTO forum_permissions (forum_id, role, can_view, can_post, can_moderate) VALUES (1,'member',1,1,0)");

forum_delete_forum(1);

$tables = ['forums','forum_topics','forum_posts','forum_moderators','forum_permissions'];
$remaining = [];
foreach ($tables as $t) {
    $remaining[$t] = $conn->query("SELECT COUNT(*) FROM $t")->fetchColumn();
}

if (array_sum($remaining) === 0) {
    echo "Deletion cascade OK\n";
} else {
    echo 'Deletion cascade failed: ' . json_encode($remaining) . "\n";
}

