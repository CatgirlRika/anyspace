<?php
// Regression test for private messages: sending and reading.

session_start();
require_once __DIR__ . '/../core/messages/pm.php';

$dbFile = __DIR__ . '/messages_pm.db';
@unlink($dbFile);
$dsn = 'sqlite:' . $dbFile;
putenv('DB_DSN=' . $dsn);

$conn = new PDO($dsn);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
global $conn;

$conn->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, username TEXT)');
$conn->exec('CREATE TABLE messages (id INTEGER PRIMARY KEY AUTOINCREMENT, sender_id INTEGER, receiver_id INTEGER, subject TEXT, body TEXT, sent_at TEXT DEFAULT CURRENT_TIMESTAMP, read_at TEXT DEFAULT NULL)');
$conn->exec("INSERT INTO users (id, username) VALUES (1, 'alice'), (2, 'bob')");

echo "Send message...\n";
pm_send(1, 2, 'Hi', 'Hello Bob');
$inbox = pm_inbox(2);
if (count($inbox) !== 1 || $inbox[0]['subject'] !== 'Hi') {
    echo "Send failed\n";
    unlink($dbFile);
    exit(1);
}
echo "Message received\n";

echo "Mark read...\n";
pm_mark_read($inbox[0]['id'], 2);
$read = $conn->query('SELECT read_at FROM messages WHERE id = ' . (int)$inbox[0]['id'])->fetchColumn();
if ($read) {
    echo "Message read\n";
} else {
    echo "Read failed\n";
    unlink($dbFile);
    exit(1);
}

unlink($dbFile);
?>

