<?php

function countOpenReports(): int {
    global $conn;
    $stmt = $conn->query('SELECT COUNT(*) FROM reports WHERE status = "open"');
    return (int)$stmt->fetchColumn();
}

function countUnresolvedPosts(): int {
    global $conn;
    $stmt = $conn->query('SELECT COUNT(*) FROM forum_posts WHERE deleted = 1');
    return (int)$stmt->fetchColumn();
}

function countActiveBans(): int {
    global $conn;
    $stmt = $conn->query('SELECT COUNT(*) FROM users WHERE banned_until IS NOT NULL AND banned_until > CURRENT_TIMESTAMP');
    return (int)$stmt->fetchColumn();
}

function getLatestLogEntries(int $limit = 5): array {
    global $conn;
    $stmt = $conn->prepare('SELECT l.*, u.username FROM mod_log l JOIN users u ON l.moderator_id = u.id ORDER BY l.timestamp DESC LIMIT :lim');
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>
