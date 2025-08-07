<?php

function forum_log_action(string $message): void {
    $logFile = __DIR__ . '/../../admin_logs.txt';
    $entry = date('c') . ' ' . $message . PHP_EOL;
    file_put_contents($logFile, $entry, FILE_APPEND);
}

function topic_lock(int $topic_id, int $by_user_id): void {
    global $conn;
    $stmt = $conn->prepare('UPDATE forum_topics SET locked = 1 WHERE id = :id');
    $stmt->execute([':id' => $topic_id]);
    forum_log_action("User {$by_user_id} locked topic {$topic_id}");
}

function topic_unlock(int $topic_id, int $by_user_id): void {
    global $conn;
    $stmt = $conn->prepare('UPDATE forum_topics SET locked = 0 WHERE id = :id');
    $stmt->execute([':id' => $topic_id]);
    forum_log_action("User {$by_user_id} unlocked topic {$topic_id}");
}

?>
