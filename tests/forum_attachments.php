<?php
// Integration test for forum post attachments
require_once __DIR__ . '/../core/forum/topic.php';
require_once __DIR__ . '/../core/forum/post.php';

session_start();

$dbFile = __DIR__ . '/forum_attachments.db';
@unlink($dbFile);
$dsn = 'sqlite:' . $dbFile;
putenv('DB_DSN=' . $dsn);

$conn = new PDO($dsn);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$conn->sqliteCreateFunction('NOW', function() { return date('Y-m-d H:i:s'); });
$conn->exec('CREATE TABLE forum_topics (id INTEGER PRIMARY KEY AUTOINCREMENT, forum_id INTEGER, title TEXT, locked INTEGER DEFAULT 0, sticky INTEGER DEFAULT 0, moved_to INTEGER DEFAULT NULL)');
$conn->exec('CREATE TABLE forum_posts (id INTEGER PRIMARY KEY AUTOINCREMENT, topic_id INTEGER, user_id INTEGER, body TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP, deleted INTEGER DEFAULT 0, deleted_by INTEGER, deleted_at TEXT)');
$conn->exec('CREATE TABLE attachments (id INTEGER PRIMARY KEY AUTOINCREMENT, post_id INTEGER, path TEXT, mime_type TEXT, uploaded_at TEXT)');
$conn->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, username TEXT)');
$conn->exec("INSERT INTO users (id, username) VALUES (1, 'alice')");

global $conn;

$topicId = forum_create_topic(1, 1, 'Attachment Topic', 'Body');
$postId = (int)$conn->query('SELECT id FROM forum_posts LIMIT 1')->fetchColumn();

$tmp = tempnam(sys_get_temp_dir(), 'att');
file_put_contents($tmp, 'hello');
$file = ['name' => 'test.txt', 'tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK, 'size' => filesize($tmp)];

echo "Upload attachment...\n";
uploadAttachment($postId, $file);
$count = $conn->query('SELECT COUNT(*) FROM attachments WHERE post_id = ' . $postId)->fetchColumn();
if ($count == 1) {
    echo "Attachment uploaded\n";
} else {
    echo "Attachment upload failed\n";
    unlink($dbFile);
    exit(1);
}

echo "Retrieve attachment...\n";
$atts = forum_get_attachments($postId);
if (count($atts) === 1 && file_exists(__DIR__ . '/../public/' . $atts[0]['path'])) {
    echo "Attachment retrieved\n";
} else {
    echo "Attachment retrieval failed\n";
    unlink($dbFile);
    exit(1);
}

echo "Delete attachment...\n";
forum_delete_attachment($atts[0]['id']);
$remaining = $conn->query('SELECT COUNT(*) FROM attachments WHERE post_id = ' . $postId)->fetchColumn();
if ($remaining == 0 && !file_exists(__DIR__ . '/../public/' . $atts[0]['path'])) {
    echo "Attachment deleted\n";
} else {
    echo "Attachment deletion failed\n";
    unlink($dbFile);
    exit(1);
}

unlink($dbFile);
?>
