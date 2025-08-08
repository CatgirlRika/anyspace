<?php
// Test for moderator logging on topic lock action
require_once __DIR__ . '/../core/forum/topic.php';
require_once __DIR__ . '/../core/forum/mod_log.php';

session_start();

$dbFile = __DIR__ . '/forum_mod_log.db';
@unlink($dbFile);
$dsn = 'sqlite:' . $dbFile;
putenv('DB_DSN=' . $dsn);

$conn = new PDO($dsn);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$conn->sqliteCreateFunction('NOW', function() { return date('Y-m-d H:i:s'); });
$conn->exec('CREATE TABLE forum_topics (id INTEGER PRIMARY KEY AUTOINCREMENT, forum_id INTEGER, title TEXT, locked INTEGER DEFAULT 0, sticky INTEGER DEFAULT 0, moved_to INTEGER DEFAULT NULL)');
$conn->exec('CREATE TABLE mod_log (id INTEGER PRIMARY KEY AUTOINCREMENT, moderator_id INTEGER, action TEXT, target_type TEXT, target_id INTEGER, timestamp TEXT DEFAULT CURRENT_TIMESTAMP)');

global $conn;

// seed topic
$conn->exec("INSERT INTO forum_topics (forum_id, title, locked, sticky) VALUES (1, 'Test', 0, 0)");

// perform action

echo "Lock topic...\n";
topic_lock(1, 42);

$count = $conn->query("SELECT COUNT(*) FROM mod_log WHERE moderator_id = 42 AND action = 'lock' AND target_type = 'topic' AND target_id = 1")->fetchColumn();
if ((int)$count === 1) {
    echo "Log entry recorded\n";
} else {
    echo "Log entry missing\n";
    unlink($dbFile);
    exit(1);
}

unlink($dbFile);
?>
