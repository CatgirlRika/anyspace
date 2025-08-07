<?php

function forum_get_categories(): array {
    global $conn;
    $stmt = $conn->query('SELECT id, name, position FROM forum_categories ORDER BY position, id');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function forum_create_category(string $name, int $position = 0): int {
    global $conn;
    $stmt = $conn->prepare('INSERT INTO forum_categories (name, position) VALUES (:name, :pos)');
    $stmt->execute([':name' => $name, ':pos' => $position]);
    return (int)$conn->lastInsertId();
}

function forum_update_category(int $id, string $name, int $position): void {
    global $conn;
    $stmt = $conn->prepare('UPDATE forum_categories SET name = :name, position = :pos WHERE id = :id');
    $stmt->execute([':name' => $name, ':pos' => $position, ':id' => $id]);
}

function forum_delete_category(int $id): void {
    global $conn;
    $stmt = $conn->prepare('DELETE FROM forum_categories WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

?>
