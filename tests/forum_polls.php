<?php
// Integration test for poll creation and voting logic.

session_start();
require_once __DIR__ . '/../core/forum/topic.php';
require_once __DIR__ . '/../core/forum/polls.php';

$dbFile = __DIR__ . '/forum_polls.db';
@unlink($dbFile);
$dsn = 'sqlite:' . $dbFile;
putenv('DB_DSN=' . $dsn);

$conn = new PDO($dsn);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$conn->sqliteCreateFunction('NOW', function() { return date('Y-m-d H:i:s'); });
$conn->exec('CREATE TABLE forum_topics (id INTEGER PRIMARY KEY AUTOINCREMENT, forum_id INTEGER, title TEXT, locked INTEGER DEFAULT 0, sticky INTEGER DEFAULT 0, moved_to INTEGER DEFAULT NULL)');
$conn->exec('CREATE TABLE forum_posts (id INTEGER PRIMARY KEY AUTOINCREMENT, topic_id INTEGER, user_id INTEGER, body TEXT, created_at TEXT)');
$conn->exec('CREATE TABLE polls (id INTEGER PRIMARY KEY AUTOINCREMENT, topic_id INTEGER, question TEXT, options TEXT, locked INTEGER DEFAULT 0)');
$conn->exec('CREATE TABLE poll_votes (poll_id INTEGER, user_id INTEGER, option_index INTEGER, UNIQUE(poll_id, user_id))');
$conn->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, username TEXT)');
$conn->exec("INSERT INTO users (id, username) VALUES (1, 'alice'), (2, 'bob')");

global $conn;

echo "Create poll...\n";
$_SESSION = ['userId' => 1];
$tid = forum_create_topic(1, 1, 'Poll Topic', 'Body');
$pid = createPoll($tid, 'Best color?', ['Red', 'Blue']);
$count = $conn->query('SELECT COUNT(*) FROM polls')->fetchColumn();
if ($count == 1) {
    echo "Poll created\n";
} else {
    echo "Poll creation failed\n";
    unlink($dbFile);
    exit(1);
}

echo "Vote once per user...\n";
$v1 = votePoll($pid, 1, 0);
$v2 = votePoll($pid, 1, 1);
if ($v1 && !$v2) {
    echo "Vote limited\n";
} else {
    echo "Vote limit failed\n";
    unlink($dbFile);
    exit(1);
}

echo "Poll locks after votes...\n";
$locked = $conn->query('SELECT locked FROM polls WHERE id = ' . (int)$pid)->fetchColumn();
if ((int)$locked === 1) {
    echo "Poll locked\n";
} else {
    echo "Poll did not lock\n";
    unlink($dbFile);
    exit(1);
}

echo "Vote by second user...\n";
votePoll($pid, 2, 1);
$results = getPollResults($pid);
if ($results[0]['votes'] == 1 && $results[1]['votes'] == 1) {
    echo "Results tallied\n";
} else {
    echo "Results incorrect\n";
    unlink($dbFile);
    exit(1);
}

unlink($dbFile);
?>

