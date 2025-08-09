<?php
// Test for forum improvements: quick reply, topic preview, and statistics caching
require_once __DIR__ . '/../core/forum/topic.php';
require_once __DIR__ . '/../core/forum/post.php';
require_once __DIR__ . '/../core/forum/forum.php';
require_once __DIR__ . '/../core/forum/permissions.php';

session_start();

// Setup SQLite database in a temporary file and configure connection
$dbFile = __DIR__ . '/forum_improvements.db';
@unlink($dbFile);
$dsn = 'sqlite:' . $dbFile;
putenv('DB_DSN=' . $dsn);

// Establish connection and seed schema
$conn = new PDO($dsn);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$conn->sqliteCreateFunction('NOW', function() { return date('Y-m-d H:i:s'); });

// Create tables
$conn->exec('CREATE TABLE forum_categories (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, position INTEGER)');
$conn->exec('CREATE TABLE forums (id INTEGER PRIMARY KEY AUTOINCREMENT, category_id INTEGER, parent_forum_id INTEGER, name TEXT, description TEXT, position INTEGER)');
$conn->exec('CREATE TABLE forum_topics (id INTEGER PRIMARY KEY AUTOINCREMENT, forum_id INTEGER, title TEXT, locked INTEGER DEFAULT 0, sticky INTEGER DEFAULT 0, moved_to INTEGER DEFAULT NULL)');
$conn->exec('CREATE TABLE forum_posts (id INTEGER PRIMARY KEY AUTOINCREMENT, topic_id INTEGER, user_id INTEGER, body TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP, deleted INTEGER DEFAULT 0, deleted_by INTEGER, deleted_at TEXT)');
$conn->exec('CREATE TABLE mod_log (id INTEGER PRIMARY KEY AUTOINCREMENT, moderator_id INTEGER, action TEXT, target_type TEXT, target_id INTEGER, timestamp TEXT DEFAULT CURRENT_TIMESTAMP)');
$conn->exec('CREATE TABLE forum_permissions (forum_id INTEGER, role TEXT, can_view INTEGER, can_post INTEGER, can_moderate INTEGER)');
$conn->exec('CREATE TABLE forum_moderators (forum_id INTEGER, user_id INTEGER)');
$conn->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, username TEXT)');
$conn->exec('CREATE TABLE topic_subscriptions (user_id INTEGER, topic_id INTEGER, PRIMARY KEY(user_id, topic_id))');
$conn->exec('CREATE TABLE notifications (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, post_id INTEGER, created_at TEXT DEFAULT CURRENT_TIMESTAMP, is_read INTEGER DEFAULT 0)');

// Seed data
$conn->exec("INSERT INTO forum_categories (name, position) VALUES ('General', 1)");
$conn->exec("INSERT INTO forums (category_id, name, description, position) VALUES (1, 'Test Forum', 'Test forum for improvements', 1)");
$conn->exec("INSERT INTO forum_permissions (forum_id, role, can_view, can_post, can_moderate) VALUES (1, 'member', 1, 1, 0)");
$conn->exec("INSERT INTO users (id, username) VALUES (1, 'alice'), (2, 'bob')");

global $conn;

// Mock session
$_SESSION = ['userId' => 1, 'user' => 'alice', 'rank' => 0];

echo "Testing forum improvements...\n";

// Test 1: Topic preview function
echo "1. Testing topic preview function...\n";
$tid = forum_create_topic(1, 1, 'Test Topic', 'This is the first post in the topic. It should be returned as a preview with some longer text to test truncation functionality.');
if (is_array($tid)) {
    echo "Failed to create topic\n";
    exit(1);
}

$preview = forum_get_topic_preview($tid);
if ($preview && strpos($preview['body'], 'This is the first post') !== false && strlen($preview['body']) <= 153) {
    echo "   Topic preview works correctly (preview: " . substr($preview['body'], 0, 50) . "...)\n";
} else {
    echo "   Topic preview failed\n";
    exit(1);
}

// Test 2: Statistics caching
echo "2. Testing statistics caching...\n";
$stats1 = forum_get_cached_stats(1);
if ($stats1['topics'] == 1 && $stats1['posts'] == 1) {
    echo "   Statistics computed correctly (topics: {$stats1['topics']}, posts: {$stats1['posts']})\n";
} else {
    echo "   Statistics computation failed\n";
    exit(1);
}

// Add another post and test cache clearing
$add_result = forum_add_post($tid, 2, 'Second post');
if (isset($add_result['success'])) {
    $stats2 = forum_get_cached_stats(1);
    if ($stats2['posts'] == 2) {
        echo "   Statistics cache cleared correctly on new post (posts: {$stats2['posts']})\n";
    } else {
        echo "   Statistics cache not cleared properly\n";
        exit(1);
    }
} else {
    echo "   Failed to add second post\n";
    exit(1);
}

// Test 3: Global statistics
echo "3. Testing global statistics...\n";
$global_stats = forum_get_cached_stats(0);
if ($global_stats['topics'] == 1 && $global_stats['posts'] == 2 && $global_stats['active_users'] == 2) {
    echo "   Global statistics work correctly (topics: {$global_stats['topics']}, posts: {$global_stats['posts']}, users: {$global_stats['active_users']})\n";
} else {
    echo "   Global statistics failed\n";
    exit(1);
}

echo "All forum improvement tests passed!\n";

// Cleanup
unlink($dbFile);
?>