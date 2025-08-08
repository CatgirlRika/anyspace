<?php

function notifications_add(int $user_id, int $post_id): void {
    global $conn;
    $stmt = $conn->prepare('INSERT INTO notifications (user_id, post_id, created_at, is_read) VALUES (:uid, :pid, CURRENT_TIMESTAMP, 0)');
    $stmt->execute([':uid' => $user_id, ':pid' => $post_id]);
}

function notifications_get_unread(int $user_id): array {
    global $conn;
    $stmt = $conn->prepare('SELECT id, post_id, created_at FROM notifications WHERE user_id = :uid AND is_read = 0 ORDER BY id DESC');
    $stmt->execute([':uid' => $user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function notifications_mark_all_read(int $user_id): void {
    global $conn;
    $stmt = $conn->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = :uid');
    $stmt->execute([':uid' => $user_id]);
}

function notifications_unread_count(int $user_id): int {
    global $conn;
    $stmt = $conn->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0');
    $stmt->execute([':uid' => $user_id]);
    return (int)$stmt->fetchColumn();
}

?>
