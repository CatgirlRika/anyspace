<?php
require_once __DIR__.'/../core/forum/category.php';
require_once __DIR__.'/../core/forum/forum.php';
require_once __DIR__.'/../core/forum/topic.php';
require_once __DIR__.'/../core/forum/post.php';

// Use an in-memory SQLite database for testing
$conn = new PDO('sqlite::memory:');
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create test tables
$conn->exec('CREATE TABLE forum_categories (id INTEGER PRIMARY KEY, name TEXT, position INTEGER)');
$conn->exec('CREATE TABLE forums (id INTEGER PRIMARY KEY, category_id INTEGER, parent_forum_id INTEGER, name TEXT, description TEXT, position INTEGER)');
$conn->exec('CREATE TABLE forum_topics (id INTEGER PRIMARY KEY, forum_id INTEGER, title TEXT, locked INTEGER DEFAULT 0, sticky INTEGER DEFAULT 0, moved_to INTEGER)');
$conn->exec('CREATE TABLE forum_posts (id INTEGER PRIMARY KEY, topic_id INTEGER, user_id INTEGER, body TEXT, created_at TEXT, deleted INTEGER DEFAULT 0, deleted_by INTEGER, deleted_at TEXT)');
$conn->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, username TEXT, email TEXT, password TEXT, date TEXT, bio TEXT, interests TEXT, css TEXT, music TEXT, pfp TEXT, currentgroup TEXT, status TEXT, private INTEGER, views INTEGER, lastactive TEXT, lastlogon TEXT, rank INTEGER DEFAULT 0)');

// Insert test data
$conn->exec("INSERT INTO forum_categories (id, name, position) VALUES (1, 'Test Category', 1)");
$conn->exec("INSERT INTO forums (id, category_id, name, description, position) VALUES (1, 1, 'Test Forum', 'Test Description', 1)");
$conn->exec("INSERT INTO users (id, username, email) VALUES (1, 'testuser', 'test@example.com')");
$conn->exec("INSERT INTO forum_topics (id, forum_id, title) VALUES (1, 1, 'Test Topic')");
$conn->exec("INSERT INTO forum_posts (id, topic_id, user_id, body, created_at) VALUES (1, 1, 1, 'Test post content', datetime('now'))");
$conn->exec("INSERT INTO forum_posts (id, topic_id, user_id, body, created_at) VALUES (2, 1, 1, 'Another test post', datetime('now'))");

echo "Testing forum statistics...\n";

// Test forum stats function
$stats = forum_get_forum_stats(1);
echo "Forum stats retrieved\n";
assert($stats['topic_count'] == 1, 'Topic count should be 1');
assert($stats['post_count'] == 2, 'Post count should be 2');
assert($stats['last_post'] !== null, 'Last post should exist');
assert($stats['last_post']['username'] == 'testuser', 'Last post author should be testuser');

echo "Testing forums with stats...\n";

// Test forums with stats function
$forums = forum_get_forums_with_stats_by_category(1);
echo "Forums with stats retrieved\n";
assert(count($forums) == 1, 'Should have 1 forum');
assert($forums[0]['topic_count'] == 1, 'Forum topic count should be 1');
assert($forums[0]['post_count'] == 2, 'Forum post count should be 2');

echo "Testing recent topics...\n";

// Test recent topics function
$recent = forum_get_recent_topics(5);
echo "Recent topics retrieved\n";
assert(count($recent) == 1, 'Should have 1 recent topic');
assert($recent[0]['title'] == 'Test Topic', 'Recent topic title should match');
assert($recent[0]['username'] == 'testuser', 'Recent topic author should be testuser');

echo "Testing total stats...\n";

// Test total stats function
$stats = forum_get_total_stats();
echo "Total stats retrieved\n";
assert($stats['total_topics'] == 1, 'Should have 1 total topic');
assert($stats['total_posts'] == 2, 'Should have 2 total posts');
assert($stats['total_members'] == 1, 'Should have 1 total member');
assert($stats['newest_member'] == 'testuser', 'Newest member should be testuser');

echo "Testing online users...\n";

// Test online users function - this will return empty array in test as no lastactive is set
$online = forum_get_online_users(15);
echo "Online users retrieved\n";
assert(is_array($online), 'Online users should return an array');

echo "All forum statistics tests passed!\n";
?>