<?php
require_once __DIR__ . '/../forum/notifications.php';
if (isset($_SESSION['user'])) {
    $count = notifications_unread_count($_SESSION['user']['id']);
    echo '<a href="/notifications.php">Notifications (' . (int)$count . ')</a> | ';
}
?>
