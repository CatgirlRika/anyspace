<?php

function forum_add_reaction(int $post_id, int $user_id, string $reaction): void {
    global $conn;
    $driver = $conn->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'sqlite') {
        $sql = 'INSERT INTO post_reactions (post_id, user_id, reaction) VALUES (:pid, :uid, :react) '
             . 'ON CONFLICT(post_id, user_id) DO UPDATE SET reaction = :react';
    } else {
        $sql = 'INSERT INTO post_reactions (post_id, user_id, reaction) VALUES (:pid, :uid, :react) '
             . 'ON DUPLICATE KEY UPDATE reaction = :react';
    }
    $stmt = $conn->prepare($sql);
    $stmt->execute([':pid' => $post_id, ':uid' => $user_id, ':react' => $reaction]);
}

function forum_remove_reaction(int $post_id, int $user_id): void {
    global $conn;
    $stmt = $conn->prepare('DELETE FROM post_reactions WHERE post_id = :pid AND user_id = :uid');
    $stmt->execute([':pid' => $post_id, ':uid' => $user_id]);
}

function forum_get_reaction_counts(int $post_id): array {
    global $conn;
    $stmt = $conn->prepare('SELECT reaction, COUNT(*) as count FROM post_reactions WHERE post_id = :pid GROUP BY reaction');
    $stmt->execute([':pid' => $post_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $counts = [];
    foreach ($rows as $row) {
        $counts[$row['reaction']] = (int)$row['count'];
    }
    return $counts;
}

function forum_get_user_reaction(int $post_id, int $user_id): ?string {
    global $conn;
    $stmt = $conn->prepare('SELECT reaction FROM post_reactions WHERE post_id = :pid AND user_id = :uid');
    $stmt->execute([':pid' => $post_id, ':uid' => $user_id]);
    $reaction = $stmt->fetchColumn();
    return $reaction !== false ? $reaction : null;
}

?>
