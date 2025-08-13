<?php
// Regression test for friend search functionality.

$dbFile = __DIR__ . '/friend_search.db';
@unlink($dbFile);
$dsn = 'sqlite:' . $dbFile;
putenv('DB_DSN=' . $dsn);

$conn = new PDO($dsn);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
global $conn;

$conn->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, username TEXT)');
$conn->exec('CREATE TABLE friends (id INTEGER PRIMARY KEY AUTOINCREMENT, sender INTEGER, receiver INTEGER, status TEXT)');
$conn->exec("INSERT INTO users (id, username) VALUES (1, 'alice'), (2, 'bob'), (3, 'bobby'), (4, 'charlie')");
$conn->exec("INSERT INTO friends (sender, receiver, status) VALUES (1, 2, 'ACCEPTED'), (3, 1, 'ACCEPTED'), (1, 4, 'PENDING')");

require_once __DIR__ . '/../core/site/friend.php';

$results = searchFriends($conn, 1, 'bo');
$friendIds = [];
foreach ($results as $row) {
    $friendIds[] = ($row['sender'] == 1) ? $row['receiver'] : $row['sender'];
}
sort($friendIds);

if ($friendIds === [2, 3]) {
    echo "Friend search works\n";
} else {
    echo "Friend search failed\n";
    unlink($dbFile);
    exit(1);
}

unlink($dbFile);
