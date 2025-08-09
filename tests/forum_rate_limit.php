<?php
// Test for forum rate limiting functionality
require_once __DIR__ . '/../core/forum/post.php';
require_once __DIR__ . '/../core/forum/rate_limit.php';

session_start();

// Setup SQLite database in a temporary file and configure connection
$dbFile = __DIR__ . '/forum_rate_limit.db';
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
$conn->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, username TEXT)');
$conn->exec('CREATE TABLE topic_subscriptions (user_id INTEGER, topic_id INTEGER, PRIMARY KEY(user_id, topic_id))');
$conn->exec('CREATE TABLE notifications (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, post_id INTEGER, created_at TEXT DEFAULT CURRENT_TIMESTAMP, is_read INTEGER DEFAULT 0)');
$conn->exec('CREATE TABLE bad_words (word TEXT PRIMARY KEY)');
$conn->exec('CREATE TABLE forum_permissions (forum_id INTEGER, role TEXT, can_view INTEGER, can_post INTEGER, can_moderate INTEGER)');
$conn->exec('CREATE TABLE forum_moderators (forum_id INTEGER, user_id INTEGER)');

// Seed data
$conn->exec("INSERT INTO forum_categories (name, position) VALUES ('General', 1)");
$conn->exec("INSERT INTO forums (category_id, name, description, position) VALUES (1, 'Test Forum', 'Test forum for rate limiting', 1)");
$conn->exec("INSERT INTO users (id, username) VALUES (1, 'testuser')");
$conn->exec("INSERT INTO forum_topics (forum_id, title) VALUES (1, 'Test Topic')");
$conn->exec("INSERT INTO forum_permissions (forum_id, role, can_view, can_post, can_moderate) VALUES (1, 'member', 1, 1, 0)");

global $conn;

// Mock session for normal user
$_SESSION = ['userId' => 1, 'user' => 'testuser', 'rank' => 0];

echo "Testing forum rate limiting...\n";

// Test 1: Normal posting should work
echo "1. Testing normal post creation...\n";
$result1 = forum_add_post(1, 1, 'First post');
if (isset($result1['success']) && $result1['success']) {
    echo "   ✓ First post created successfully\n";
} else {
    echo "   ✗ First post failed: " . ($result1['error'] ?? 'Unknown error') . "\n";
    exit(1);
}

// Test 2: Second post should work
echo "2. Testing second post...\n";
$result2 = forum_add_post(1, 1, 'Second post');
if (isset($result2['success']) && $result2['success']) {
    echo "   ✓ Second post created successfully\n";
} else {
    echo "   ✗ Second post failed: " . ($result2['error'] ?? 'Unknown error') . "\n";
    exit(1);
}

// Test 3: Third post should work
echo "3. Testing third post...\n";
$result3 = forum_add_post(1, 1, 'Third post');
if (isset($result3['success']) && $result3['success']) {
    echo "   ✓ Third post created successfully\n";
} else {
    echo "   ✗ Third post failed: " . ($result3['error'] ?? 'Unknown error') . "\n";
    exit(1);
}

// Test 4: Fourth post should be rate limited
echo "4. Testing rate limit (fourth post)...\n";
$result4 = forum_add_post(1, 1, 'Fourth post - should be blocked');
if (isset($result4['error']) && strpos($result4['error'], 'Rate limit exceeded') !== false) {
    echo "   ✓ Rate limit correctly enforced\n";
} else {
    echo "   ✗ Rate limit failed: " . ($result4['error'] ?? 'Post was allowed when it should have been blocked') . "\n";
    exit(1);
}

// Test 5: Check rate limit time remaining
echo "5. Testing rate limit time remaining...\n";
$remaining = forum_rate_limit_time_remaining(1);
if ($remaining > 0) {
    echo "   ✓ Rate limit time remaining: {$remaining} seconds\n";
} else {
    echo "   ✗ Rate limit time remaining should be > 0\n";
    exit(1);
}

// Test 6: Admin/mod should bypass rate limit
echo "6. Testing admin bypass...\n";
$_SESSION['rank'] = 1; // Make user a global mod
$result5 = forum_add_post(1, 1, 'Admin post - should work despite rate limit');
if (isset($result5['success']) && $result5['success']) {
    echo "   ✓ Admin successfully bypassed rate limit\n";
} else {
    echo "   ✗ Admin bypass failed: " . ($result5['error'] ?? 'Unknown error') . "\n";
    exit(1);
}

echo "All rate limiting tests passed!\n";

// Cleanup
@unlink($dbFile);
?>