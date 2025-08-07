<?php

function forum_get_forums_by_category(int $category_id): array {
    global $conn;
    $stmt = $conn->prepare('SELECT id, name, description, position FROM forums WHERE category_id = :cid ORDER BY position, id');
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

function forum_create_forum(int $category_id, string $name, string $description, int $position = 0): int {
    global $conn;
    $stmt = $conn->prepare('INSERT INTO forums (category_id, name, description, position) VALUES (:cid, :name, :descr, :pos)');
    $stmt->execute([':cid' => $category_id, ':name' => $name, ':descr' => $description, ':pos' => $position]);
    return (int)$conn->lastInsertId();
}

function forum_update_forum(int $id, int $category_id, string $name, string $description, int $position): void {
    global $conn;
    $stmt = $conn->prepare('UPDATE forums SET category_id = :cid, name = :name, description = :descr, position = :pos WHERE id = :id');
    $stmt->execute([':cid' => $category_id, ':name' => $name, ':descr' => $description, ':pos' => $position, ':id' => $id]);
}

function forum_delete_forum(int $id): void {
    global $conn;
    $stmt = $conn->prepare('DELETE FROM forums WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

?>
