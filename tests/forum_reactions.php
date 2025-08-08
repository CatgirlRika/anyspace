<?php
// Test for forum post reactions
require_once __DIR__ . '/../core/forum/reactions.php';

// Setup SQLite database
$dbFile = __DIR__ . '/forum_reactions.db';
@unlink($dbFile);
$dsn = 'sqlite:' . $dbFile;
putenv('DB_DSN=' . $dsn);

$conn = new PDO($dsn);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$conn->exec('CREATE TABLE forum_posts (id INTEGER PRIMARY KEY AUTOINCREMENT)');
$conn->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT)');
$conn->exec('CREATE TABLE post_reactions (post_id INTEGER, user_id INTEGER, reaction TEXT, UNIQUE(post_id, user_id))');

global $conn;

// Seed one post and users
$conn->exec("INSERT INTO forum_posts DEFAULT VALUES");
$conn->exec("INSERT INTO users (id) VALUES (1), (2)");

// Add reactions
forum_add_reaction(1, 1, 'like');
forum_add_reaction(1, 1, 'love'); // update existing
forum_add_reaction(1, 2, 'like');

$counts = forum_get_reaction_counts(1);
if (($counts['love'] ?? 0) === 1 && ($counts['like'] ?? 0) === 1) {
    echo "Counts correct\n";
} else {
    echo "Count check failed\n";
    unlink($dbFile);
    exit(1);
}

// Remove reaction
forum_remove_reaction(1, 1);
$counts = forum_get_reaction_counts(1);
if (($counts['like'] ?? 0) === 1 && !isset($counts['love'])) {
    echo "Remove works\n";
} else {
    echo "Remove failed\n";
    unlink($dbFile);
    exit(1);
}

unlink($dbFile);
?>
