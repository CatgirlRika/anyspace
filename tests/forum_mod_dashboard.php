<?php
require_once __DIR__ . '/../core/forum/mod_dashboard.php';

session_start();

$dbFile = __DIR__ . '/forum_mod_dashboard.db';
@unlink($dbFile);
$dsn = 'sqlite:' . $dbFile;
putenv('DB_DSN=' . $dsn);

$conn = new PDO($dsn);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$conn->exec('CREATE TABLE reports (id INTEGER PRIMARY KEY AUTOINCREMENT, reported_id INTEGER, type TEXT, reason TEXT, reporter_id INTEGER, status TEXT)');
$conn->exec('CREATE TABLE forum_posts (id INTEGER PRIMARY KEY AUTOINCREMENT, topic_id INTEGER, user_id INTEGER, body TEXT, created_at TEXT, deleted INTEGER DEFAULT 0, deleted_by INTEGER DEFAULT NULL, deleted_at TEXT DEFAULT NULL)');
$conn->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, rank INTEGER, username TEXT, email TEXT, password TEXT, banned_until TEXT)');
$conn->exec('CREATE TABLE mod_log (id INTEGER PRIMARY KEY AUTOINCREMENT, moderator_id INTEGER, action TEXT, target_type TEXT, target_id INTEGER, timestamp TEXT DEFAULT CURRENT_TIMESTAMP)');
$conn->exec('CREATE TABLE messages (id INTEGER PRIMARY KEY AUTOINCREMENT, sender_id INTEGER, receiver_id INTEGER, subject TEXT, body TEXT, sent_at TEXT, read_at TEXT DEFAULT NULL, sender_deleted INTEGER DEFAULT 0, receiver_deleted INTEGER DEFAULT 0)');
$conn->exec('CREATE TABLE forum_user_settings (user_id INTEGER PRIMARY KEY, accent_color TEXT, background_color TEXT, text_color TEXT)');

global $conn;

// seed data
$conn->exec("INSERT INTO reports (reported_id, type, reason, reporter_id, status) VALUES (1, 'post', 'spam', 2, 'open'), (2, 'post', 'spam', 3, 'closed')");
$conn->exec("INSERT INTO forum_posts (topic_id, user_id, body, created_at, deleted) VALUES (1,1,'a',datetime('now'),0), (1,1,'b',datetime('now'),1)");
$hash = password_hash('secret', PASSWORD_DEFAULT);
$conn->exec("INSERT INTO users (username,email,password,rank,banned_until) VALUES ('mod','m@example.com','$hash',1,NULL), ('banned','b@example.com','$hash',0, datetime('now', '+1 hour'))");
$conn->exec("INSERT INTO mod_log (moderator_id, action, target_type, target_id, timestamp) VALUES (1,'ban','user',2,datetime('now')), (1,'delete','post',1,datetime('now'))");

$_SESSION = ['userId' => 1, 'user' => 'mod', 'rank' => 1];

$siteName = 'AnySpace';
$domainName = 'example.com';
$adminUser = 1;

ob_start();
require __DIR__ . '/../public/forum/mod/dashboard.php';
$output = ob_get_clean();

$ok = true;
$ok = $ok && strpos($output, 'Open Reports: 1') !== false;
$ok = $ok && strpos($output, 'Unresolved Posts: 1') !== false;
$ok = $ok && strpos($output, 'Active Bans: 1') !== false;
$ok = $ok && strpos($output, 'ban') !== false && strpos($output, 'delete') !== false;

if ($ok) {
    echo "Dashboard shows correct counts\n";
} else {
    echo "Dashboard incorrect\n";
    unlink($dbFile);
    exit(1);
}

unlink($dbFile);
?>
