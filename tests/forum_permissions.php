<?php
require_once __DIR__.'/../core/forum/permissions.php';

session_start();
ob_start();

// Use an in-memory SQLite database for testing
$conn = new PDO('sqlite::memory:');
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$conn->exec('CREATE TABLE forum_permissions (forum_id INTEGER, role TEXT, can_view INTEGER, can_post INTEGER, can_moderate INTEGER)');
$conn->exec('CREATE TABLE forum_moderators (forum_id INTEGER, user_id INTEGER)');
$conn->exec("INSERT INTO forum_permissions (forum_id, role, can_view, can_post, can_moderate) VALUES (1, 'member', 1, 1, 0)");
$conn->exec('INSERT INTO forum_moderators (forum_id, user_id) VALUES (1, 4)');

echo "Global moderator test...\n";
$_SESSION = ['userId' => 2, 'rank' => 1, 'user' => 'gm'];
forum_require_permission(1, 'can_moderate');
echo "OK\n";

echo "Forum moderator test...\n";
$_SESSION = ['userId' => 4, 'rank' => 0, 'user' => 'fm'];
forum_require_permission(1, 'can_moderate');
echo "OK\n";

echo "Forbidden user test...\n";
$_SESSION = ['userId' => 5, 'rank' => 0, 'user' => 'user'];
forum_require_permission(1, 'can_moderate');
echo "This line will not be reached\n";

