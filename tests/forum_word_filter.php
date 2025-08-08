<?php
require_once __DIR__ . '/../core/forum/topic.php';
require_once __DIR__ . '/../core/forum/post.php';
require_once __DIR__ . '/../core/forum/permissions.php';
require_once __DIR__ . '/../core/forum/word_filter.php';

session_start();

$dbFile = __DIR__ . '/forum_word_filter.db';
@unlink($dbFile);
$dsn = 'sqlite:' . $dbFile;
putenv('DB_DSN=' . $dsn);

$conn = new PDO($dsn);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$conn->sqliteCreateFunction('NOW', function() { return date('Y-m-d H:i:s'); });
$conn->exec('CREATE TABLE forum_topics (id INTEGER PRIMARY KEY AUTOINCREMENT, forum_id INTEGER, title TEXT, locked INTEGER DEFAULT 0, sticky INTEGER DEFAULT 0, moved_to INTEGER DEFAULT NULL)');
$conn->exec('CREATE TABLE forum_posts (id INTEGER PRIMARY KEY AUTOINCREMENT, topic_id INTEGER, user_id INTEGER, body TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP, deleted INTEGER DEFAULT 0, deleted_by INTEGER, deleted_at TEXT)');
$conn->exec('CREATE TABLE mod_log (id INTEGER PRIMARY KEY AUTOINCREMENT, moderator_id INTEGER, action TEXT, target_type TEXT, target_id INTEGER, timestamp TEXT DEFAULT CURRENT_TIMESTAMP)');
$conn->exec('CREATE TABLE forum_permissions (forum_id INTEGER, role TEXT, can_view INTEGER, can_post INTEGER, can_moderate INTEGER)');
$conn->exec('CREATE TABLE forum_moderators (forum_id INTEGER, user_id INTEGER)');
$conn->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, username TEXT)');
$conn->exec('CREATE TABLE bad_words (id INTEGER PRIMARY KEY AUTOINCREMENT, word TEXT)');
$conn->exec("INSERT INTO bad_words (word) VALUES ('badword')");
$conn->exec("INSERT INTO forum_permissions (forum_id, role, can_view, can_post, can_moderate) VALUES (1, 'member', 1, 1, 0)");
$conn->exec("INSERT INTO forum_permissions (forum_id, role, can_view, can_post, can_moderate) VALUES (1, 'guest', 1, 0, 0)");
$conn->exec("INSERT INTO forum_moderators (forum_id, user_id) VALUES (1, 2)");
$conn->exec("INSERT INTO users (id, username) VALUES (1, 'alice'), (2, 'bob')");

global $conn;

$_SESSION = ['userId' => 1, 'user' => 'alice', 'rank' => 0];
$tid = forum_create_topic(1, 1, 'Topic', 'Clean first post');

echo "Filtering post...\n";
$result = forum_add_post($tid, 1, 'This post has badword in it');
$deleted = $conn->query('SELECT deleted FROM forum_posts WHERE id = 2')->fetchColumn();
if (isset($result['filtered']) && (int)$deleted === 1) {
    echo "Filtered\n";
    unlink($dbFile);
    exit(0);
}

echo "Filter failed\n";
unlink($dbFile);
exit(1);
?>
