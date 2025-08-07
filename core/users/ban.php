<?php
require_once __DIR__ . '/../forum/mod_log.php';

function banUser(int $user_id, string $until): void {
    global $conn;
    $stmt = $conn->prepare('UPDATE users SET banned_until = :until WHERE id = :id');
    $stmt->execute([
        ':until' => $until,
        ':id' => $user_id
    ]);

    $mid = $_SESSION['userId'] ?? 0;
    logModAction($mid, 'ban', 'user', $user_id);
}

function unbanUser(int $user_id): void {
    global $conn;
    $stmt = $conn->prepare('UPDATE users SET banned_until = NULL WHERE id = :id');
    $stmt->execute([
        ':id' => $user_id
    ]);

    $mid = $_SESSION['userId'] ?? 0;
    logModAction($mid, 'unban', 'user', $user_id);
}

?>
