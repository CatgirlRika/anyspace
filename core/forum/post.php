<?php

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

?>
