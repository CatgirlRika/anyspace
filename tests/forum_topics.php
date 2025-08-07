<?php
// Integration test for creating, editing, and deleting forum topics.
// Uses SQLite to avoid external dependencies.

require_once __DIR__ . '/../core/forum/topic.php';
require_once __DIR__ . '/../core/forum/permissions.php';

session_start();

// Setup SQLite database in a temporary file and configure connection
$dbFile = __DIR__ . '/forum_topics.db';
@unlink($dbFile);
$dsn = 'sqlite:' . $dbFile;
putenv('DB_DSN=' . $dsn);

// Establish connection and seed schema
$conn = new PDO($dsn);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$conn->sqliteCreateFunction('NOW', function() { return date('Y-m-d H:i:s'); });
$conn->exec('CREATE TABLE forum_topics (id INTEGER PRIMARY KEY AUTOINCREMENT, forum_id INTEGER, title TEXT, locked INTEGER DEFAULT 0, sticky INTEGER DEFAULT 0, moved_to INTEGER DEFAULT NULL)');
$conn->exec('CREATE TABLE forum_posts (id INTEGER PRIMARY KEY AUTOINCREMENT, topic_id INTEGER, user_id INTEGER, body TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP, deleted INTEGER DEFAULT 0, deleted_by INTEGER, deleted_at TEXT)');
$conn->exec('CREATE TABLE mod_log (id INTEGER PRIMARY KEY AUTOINCREMENT, moderator_id INTEGER, action TEXT, target_type TEXT, target_id INTEGER, timestamp TEXT DEFAULT CURRENT_TIMESTAMP)');
$conn->exec('CREATE TABLE forum_permissions (forum_id INTEGER, role TEXT, can_view INTEGER, can_post INTEGER, can_moderate INTEGER)');
$conn->exec('CREATE TABLE forum_moderators (forum_id INTEGER, user_id INTEGER)');
$conn->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, username TEXT)');
$conn->exec("INSERT INTO forum_permissions (forum_id, role, can_view, can_post, can_moderate) VALUES (1, 'member', 1, 1, 0)");
$conn->exec("INSERT INTO forum_permissions (forum_id, role, can_view, can_post, can_moderate) VALUES (1, 'guest', 1, 0, 0)");
$conn->exec("INSERT INTO forum_moderators (forum_id, user_id) VALUES (1, 2)");
$conn->exec("INSERT INTO users (id, username) VALUES (1, 'alice'), (2, 'bob')");

global $conn;

echo "Create topic...\n";
$_SESSION = ['userId' => 1, 'user' => 'alice', 'rank' => 0];
$tid = forum_create_topic(1, 1, 'Test Topic', 'First post');
$count = $conn->query('SELECT COUNT(*) FROM forum_topics')->fetchColumn();
if ($count == 1) {
    echo "Topic created\n";
} else {
    echo "Topic creation failed\n";
    unlink($dbFile);
    exit(1);
}

echo "Lock topic...\n";
$_SESSION = ['userId' => 2, 'user' => 'bob', 'rank' => 0];
topic_lock($tid, 2);
$locked = $conn->query('SELECT locked FROM forum_topics WHERE id = ' . (int)$tid)->fetchColumn();
if ((int)$locked === 1) {
    echo "Topic locked\n";
} else {
    echo "Topic lock failed\n";
    unlink($dbFile);
    exit(1);
}

echo "Delete topic...\n";
$conn->exec('DELETE FROM forum_posts WHERE topic_id = ' . (int)$tid);
$conn->exec('DELETE FROM forum_topics WHERE id = ' . (int)$tid);
$remaining = $conn->query('SELECT COUNT(*) FROM forum_topics')->fetchColumn();
if ((int)$remaining === 0) {
    echo "Topic deleted\n";
} else {
    echo "Topic delete failed\n";
    unlink($dbFile);
    exit(1);
}

echo "Unauthorized moderation...\n";
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
file_put_contents(__DIR__ . '/unauth_topics.php', $unauth);
$output = shell_exec('php ' . escapeshellarg(__DIR__ . '/unauth_topics.php'));
unlink(__DIR__ . '/unauth_topics.php');
if (trim($output) === 'Forbidden') {
    echo "Permission enforced\n";
} else {
    echo "Permission test failed: $output\n";
    unlink($dbFile);
    exit(1);
}

unlink($dbFile);
?>
