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
$conn->exec('CREATE TABLE messages (id INTEGER PRIMARY KEY AUTOINCREMENT, sender_id INTEGER, receiver_id INTEGER, subject TEXT, body TEXT, sent_at TEXT DEFAULT CURRENT_TIMESTAMP, read_at TEXT DEFAULT NULL, sender_deleted INTEGER DEFAULT 0, receiver_deleted INTEGER DEFAULT 0)');
$conn->exec('CREATE INDEX idx_messages_receiver ON messages(receiver_id, receiver_deleted, sent_at)');
$conn->exec('CREATE INDEX idx_messages_sender ON messages(sender_id, sender_deleted, sent_at)');
$conn->exec("INSERT INTO users (id, username) VALUES (1, 'alice'), (2, 'bob')");

echo "Send message...\n";
$messageId = pm_send(1, 2, 'Hi', 'Hello Bob');
$inbox = pm_inbox(2);
$outbox = pm_outbox(1);
if (count($inbox) !== 1 || $inbox[0]['subject'] !== 'Hi') {
    echo "Inbox failed\n";
    unlink($dbFile);
    exit(1);
}
if (count($outbox) !== 1 || $outbox[0]['subject'] !== 'Hi') {
    echo "Outbox failed\n";
    unlink($dbFile);
    exit(1);
}
echo "Message received\n";

$unreadBefore = pm_unread_count(2);
if ($unreadBefore !== 1) {
    echo "Unread count before read incorrect\n";
    unlink($dbFile);
    exit(1);
}

echo "Mark read...\n";
pm_mark_read($messageId, 2);
$read = $conn->query('SELECT read_at FROM messages WHERE id = ' . (int)$messageId)->fetchColumn();
if ($read) {
    echo "Message read\n";
} else {
    echo "Read failed\n";
    unlink($dbFile);
    exit(1);
}
$unreadAfter = pm_unread_count(2);
if ($unreadAfter !== 0) {
    echo "Unread count after read incorrect\n";
    unlink($dbFile);
    exit(1);
}
echo "Unread count updated\n";

echo "Delete message (receiver)...\n";
pm_delete($messageId, 2);
if (pm_inbox(2)) {
    echo "Inbox delete failed\n";
    unlink($dbFile);
    exit(1);
}
if (count(pm_outbox(1)) !== 1) {
    echo "Outbox altered after receiver delete\n";
    unlink($dbFile);
    exit(1);
}
echo "Receiver delete OK\n";

echo "Delete message (sender)...\n";
pm_delete($messageId, 1);
if (pm_outbox(1)) {
    echo "Outbox delete failed\n";
    unlink($dbFile);
    exit(1);
}
$count = $conn->query('SELECT COUNT(*) FROM messages')->fetchColumn();
if ($count != 0) {
    echo "Message not fully removed\n";
    unlink($dbFile);
    exit(1);
}
echo "Sender delete OK\n";

echo "Validate inputs...\n";
try {
    pm_send(1, 2, '', 'Body');
    echo "Empty subject allowed\n";
    unlink($dbFile);
    exit(1);
} catch (InvalidArgumentException $e) {
    // expected
}
try {
    pm_send(1, 2, str_repeat('a', 256), 'Body');
    echo "Long subject allowed\n";
    unlink($dbFile);
    exit(1);
} catch (InvalidArgumentException $e) {
    // expected
}
try {
    pm_send(1, 2, 'Sub', '');
    echo "Empty body allowed\n";
    unlink($dbFile);
    exit(1);
} catch (InvalidArgumentException $e) {
    // expected
}
echo "Validation passed\n";

echo "Pagination and search...\n";
pm_send(1, 2, 'First', 'Body one');
pm_send(1, 2, 'Second keyword', 'Body two');
pm_send(1, 2, 'Third', 'Body keyword three');
$page1 = pm_inbox(2, 2, 0);
$page2 = pm_inbox(2, 2, 2);
if (count($page1) !== 2 || count($page2) !== 1) {
    echo "Pagination failed\n";
    unlink($dbFile);
    exit(1);
}
$filteredInbox = pm_inbox(2, 10, 0, 'keyword');
if (count($filteredInbox) !== 2) {
    echo "Inbox search failed\n";
    unlink($dbFile);
    exit(1);
}
$filteredOutbox = pm_outbox(1, 10, 0, 'keyword');
if (count($filteredOutbox) !== 2) {
    echo "Outbox search failed\n";
    unlink($dbFile);
    exit(1);
}
echo "Pagination and search passed\n";

unlink($dbFile);
?>

