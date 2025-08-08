<?php

function forum_get_all_forums(): array {
    global $conn;
    $stmt = $conn->query('SELECT * FROM forums ORDER BY position, id');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function forum_get_forums_by_category(int $category_id): array {
    global $conn;
    $stmt = $conn->prepare('SELECT id, name, description, position, parent_forum_id FROM forums WHERE category_id = :cid ORDER BY position, id');
    $stmt->execute([':cid' => $category_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function forum_get_forum(int $forum_id): ?array {
    global $conn;
    $stmt = $conn->prepare('SELECT id, category_id, name, description FROM forums WHERE id = :id');
    $stmt->execute([':id' => $forum_id]);
    $forum = $stmt->fetch(PDO::FETCH_ASSOC);
    return $forum ?: null;
}

function forum_create_forum(int $category_id, string $name, string $description, int $position = 0, ?int $parent_forum_id = null): int {
    global $conn;
    $stmt = $conn->prepare('INSERT INTO forums (category_id, parent_forum_id, name, description, position) VALUES (:cid, :pid, :name, :descr, :pos)');
    $stmt->execute([':cid' => $category_id, ':pid' => $parent_forum_id, ':name' => $name, ':descr' => $description, ':pos' => $position]);
    return (int)$conn->lastInsertId();
}

function forum_update_forum(int $id, int $category_id, string $name, string $description, int $position, ?int $parent_forum_id = null): void {
    global $conn;
    $stmt = $conn->prepare('UPDATE forums SET category_id = :cid, parent_forum_id = :pid, name = :name, description = :descr, position = :pos WHERE id = :id');
    $stmt->execute([':cid' => $category_id, ':pid' => $parent_forum_id, ':name' => $name, ':descr' => $description, ':pos' => $position, ':id' => $id]);
}

function forum_delete_forum(int $id): void {
    global $conn;

    // delete child forums first
    $childStmt = $conn->prepare('SELECT id FROM forums WHERE parent_forum_id = :id');
    $childStmt->execute([':id' => $id]);
    $children = $childStmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($children as $childId) {
        forum_delete_forum((int)$childId);
    }

    // remove posts and topics belonging to this forum
    $postDel = $conn->prepare('DELETE FROM forum_posts WHERE topic_id IN (SELECT id FROM forum_topics WHERE forum_id = :id)');
    $postDel->execute([':id' => $id]);
    $topicDel = $conn->prepare('DELETE FROM forum_topics WHERE forum_id = :id');
    $topicDel->execute([':id' => $id]);

    // remove moderator assignments and permissions
    $modDel = $conn->prepare('DELETE FROM forum_moderators WHERE forum_id = :id');
    $modDel->execute([':id' => $id]);
    $permDel = $conn->prepare('DELETE FROM forum_permissions WHERE forum_id = :id');
    $permDel->execute([':id' => $id]);

    // finally delete the forum itself
    $stmt = $conn->prepare('DELETE FROM forums WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

?>
