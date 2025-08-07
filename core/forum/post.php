<?php

require_once(__DIR__ . '/topic.php');

function forum_add_post(int $topic_id, int $user_id, string $body)
{
    global $conn;
    $stmt = $conn->prepare('SELECT locked FROM forum_topics WHERE id = :id');
    $stmt->execute([':id' => $topic_id]);
    $locked = (int)$stmt->fetchColumn();
    if ($locked === 1) {
        return ['error' => 'Topic is locked'];
    }
    $insert = $conn->prepare('INSERT INTO forum_posts (topic_id, user_id, body, created_at) VALUES (:tid, :uid, :body, NOW())');
    $insert->execute([':tid' => $topic_id, ':uid' => $user_id, ':body' => $body]);
    return ['success' => true];
}

function post_soft_delete(int $post_id, int $by_user_id): void
{
    global $conn;
    $stmt = $conn->prepare('UPDATE forum_posts SET deleted = 1, deleted_by = :uid, deleted_at = NOW() WHERE id = :id');
    $stmt->execute([':id' => $post_id, ':uid' => $by_user_id]);
    forum_log_action("User {$by_user_id} deleted post {$post_id}");
}

function post_restore(int $post_id, int $by_user_id): void
{
    global $conn;
    $stmt = $conn->prepare('UPDATE forum_posts SET deleted = 0, deleted_by = NULL, deleted_at = NULL WHERE id = :id');
    $stmt->execute([':id' => $post_id]);
    forum_log_action("User {$by_user_id} restored post {$post_id}");
}

?>
