<?php

function getUserById(int $user_id) {
    global $conn;
    $stmt = $conn->prepare('SELECT id, username, rank, banned_until FROM users WHERE id = :id');
    $stmt->execute([':id' => $user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function isUserBanned(int $user_id): bool {
    $info = getUserById($user_id);
    if (!$info || empty($info['banned_until'])) {
        return false;
    }
    return strtotime($info['banned_until']) > time();
}

function fetchBannedUntil(int $user_id) {
    $info = getUserById($user_id);
    return $info ? $info['banned_until'] : null;
}

?>
