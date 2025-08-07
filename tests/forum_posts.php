<?php
// Integration test for creating, editing, and deleting forum posts.
// Uses SQLite to avoid external dependencies.

require_once __DIR__ . '/../core/forum/topic.php';
require_once __DIR__ . '/../core/forum/post.php';
require_once __DIR__ . '/../core/forum/permissions.php';

session_start();

// Setup SQLite database in a temporary file and configure connection
$dbFile = __DIR__ . '/forum_posts.db';
@unlink($dbFile);
$dsn = 'sqlite:' . $dbFile;
putenv('DB_DSN=' . $dsn);

// Establish connection and seed schema
$conn = new PDO($dsn);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$conn->sqliteCreateFunction('NOW', function() { return date('Y-m-d H:i:s'); });
$conn->exec('CREATE TABLE forum_topics (id INTEGER PRIMARY KEY AUTOINCREMENT, forum_id INTEGER, title TEXT, locked INTEGER DEFAULT 0, sticky INTEGER DEFAULT 0, moved_to INTEGER DEFAULT NULL)');
$conn->exec('CREATE TABLE forum_posts (id INTEGER PRIMARY KEY AUTOINCREMENT, topic_id INTEGER, user_id INTEGER, body TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP, deleted INTEGER DEFAULT 0, deleted_by INTEGER, deleted_at TEXT)');
$conn->exec('CREATE TABLE forum_permissions (forum_id INTEGER, role TEXT, can_view INTEGER, can_post INTEGER, can_moderate INTEGER)');
$conn->exec('CREATE TABLE forum_moderators (forum_id INTEGER, user_id INTEGER)');
$conn->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, username TEXT)');
$conn->exec("INSERT INTO forum_permissions (forum_id, role, can_view, can_post, can_moderate) VALUES (1, 'member', 1, 1, 0)");
$conn->exec("INSERT INTO forum_permissions (forum_id, role, can_view, can_post, can_moderate) VALUES (1, 'guest', 1, 0, 0)");
$conn->exec("INSERT INTO forum_moderators (forum_id, user_id) VALUES (1, 2)");
$conn->exec("INSERT INTO users (id, username) VALUES (1, 'alice'), (2, 'bob')");

global $conn;

// Create a topic which also seeds the first post
$_SESSION = ['userId' => 1, 'user' => 'alice', 'rank' => 0];
$tid = forum_create_topic(1, 1, 'Topic', 'First post');

// Add a second post

echo "Add post...\n";
$add = forum_add_post($tid, 1, 'Second post');
$count = $conn->query('SELECT COUNT(*) FROM forum_posts WHERE topic_id = ' . (int)$tid)->fetchColumn();
if ($count == 2) {
    echo "Post added\n";
} else {
    echo "Post add failed\n";
    unlink($dbFile);
    exit(1);
}

// Edit the second post

echo "Edit post...\n";
$conn->exec("UPDATE forum_posts SET body = 'Edited post' WHERE id = 2");
$body = $conn->query('SELECT body FROM forum_posts WHERE id = 2')->fetchColumn();
if ($body === 'Edited post') {
    echo "Post edited\n";
} else {
    echo "Post edit failed\n";
    unlink($dbFile);
    exit(1);
}

// Delete the post as moderator

echo "Delete post...\n";
$_SESSION = ['userId' => 2, 'user' => 'bob', 'rank' => 0];
post_soft_delete(2, 2);
$deleted = $conn->query('SELECT deleted FROM forum_posts WHERE id = 2')->fetchColumn();
if ((int)$deleted === 1) {
    echo "Post deleted\n";
} else {
    echo "Post delete failed\n";
    unlink($dbFile);
    exit(1);
}

// Unauthorized delete attempt

echo "Unauthorized delete attempt...\n";
$unauth = <<<'PHP'
<?php
session_start();
$dsn = getenv('DB_DSN');
$conn = new PDO($dsn);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
global $conn;
require __DIR__ . '/../core/forum/permissions.php';
$_SESSION = ['userId' => 1, 'user' => 'alice', 'rank' => 0];
forum_require_permission(1, 'can_moderate');
echo "ALLOWED";
PHP;
file_put_contents(__DIR__ . '/unauth_posts.php', $unauth);
$output = shell_exec('php ' . escapeshellarg(__DIR__ . '/unauth_posts.php'));
unlink(__DIR__ . '/unauth_posts.php');
if (trim($output) === 'Forbidden') {
    echo "Permission enforced\n";
} else {
    echo "Permission test failed: $output\n";
    unlink($dbFile);
    exit(1);
}

unlink($dbFile);
?>
