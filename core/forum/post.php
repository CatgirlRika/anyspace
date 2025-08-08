<?php

require_once(__DIR__ . '/topic.php');
require_once(__DIR__ . '/../helper.php');
require_once(__DIR__ . '/notifications.php');
require_once(__DIR__ . '/mod_log.php');
require_once(__DIR__ . '/word_filter.php');

function forum_add_post(int $topic_id, int $user_id, string $body)
{
    global $conn;
    $stmt = $conn->prepare('SELECT locked FROM forum_topics WHERE id = :id');
    $stmt->execute([':id' => $topic_id]);
    $locked = (int)$stmt->fetchColumn();
    if ($locked === 1) {
        return ['error' => 'Topic is locked'];
    }
    $matches = isFiltered($body);
    $sanitizedBody = validateContentHTML($body);
    if (!empty($matches)) {
        $insert = $conn->prepare('INSERT INTO forum_posts (topic_id, user_id, body, created_at, deleted) VALUES (:tid, :uid, :body, CURRENT_TIMESTAMP, 1)');
        $insert->execute([':tid' => $topic_id, ':uid' => $user_id, ':body' => $sanitizedBody]);
        return ['warning' => 'Post contains filtered words', 'filtered' => $matches];
    }
    $insert = $conn->prepare('INSERT INTO forum_posts (topic_id, user_id, body, created_at) VALUES (:tid, :uid, :body, CURRENT_TIMESTAMP)');
    $insert->execute([':tid' => $topic_id, ':uid' => $user_id, ':body' => $sanitizedBody]);
    $postId = (int)$conn->lastInsertId();

    $notified = [];

    $ownerStmt = $conn->prepare('SELECT user_id FROM forum_posts WHERE topic_id = :tid ORDER BY id ASC LIMIT 1');
    $ownerStmt->execute([':tid' => $topic_id]);
    $topicOwner = (int)$ownerStmt->fetchColumn();
    if ($topicOwner && $topicOwner !== $user_id) {
        notifications_add($topicOwner, $postId);
        $notified[] = $topicOwner;
    }

    if (preg_match_all('/@([A-Za-z0-9_]+)/', $sanitizedBody, $matches)) {
        $usernames = array_unique($matches[1]);
        if ($usernames) {
            $placeholders = implode(',', array_fill(0, count($usernames), '?'));
            $stmt = $conn->prepare('SELECT id FROM users WHERE username IN (' . $placeholders . ')');
            $stmt->execute($usernames);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $uid = (int)$row['id'];
                if ($uid !== $user_id && !in_array($uid, $notified, true)) {
                    notifications_add($uid, $postId);
                    $notified[] = $uid;
                }
            }
        }
    }

    return ['success' => true];
}

function forum_get_posts(int $topic_id, bool $include_deleted = false): array
{
    global $conn;
    $sql = 'SELECT p.id, p.user_id, p.body, p.created_at, p.deleted, u.username FROM forum_posts p JOIN users u ON p.user_id = u.id WHERE p.topic_id = :id';
    if (!$include_deleted) {
        $sql .= ' AND p.deleted = 0';
    }
    $sql .= ' ORDER BY p.created_at ASC';
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id' => $topic_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function post_soft_delete(int $post_id, int $by_user_id): void
{
    global $conn;
    $stmt = $conn->prepare('UPDATE forum_posts SET deleted = 1, deleted_by = :uid, deleted_at = NOW() WHERE id = :id');
    $stmt->execute([':id' => $post_id, ':uid' => $by_user_id]);
    forum_log_action("User {$by_user_id} deleted post {$post_id}");
    logModAction($by_user_id, 'delete', 'post', $post_id);
}

function post_restore(int $post_id, int $by_user_id): void
{
    global $conn;
    $stmt = $conn->prepare('UPDATE forum_posts SET deleted = 0, deleted_by = NULL, deleted_at = NULL WHERE id = :id');
    $stmt->execute([':id' => $post_id]);
    forum_log_action("User {$by_user_id} restored post {$post_id}");
    logModAction($by_user_id, 'restore', 'post', $post_id);
}

function post_get_quote(int $post_id): ?array
{
    global $conn;
    $stmt = $conn->prepare('SELECT p.body, u.username FROM forum_posts p JOIN users u ON p.user_id = u.id WHERE p.id = :id');
    $stmt->execute([':id' => $post_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }
    return [
        'username' => $row['username'],
        'body' => strip_tags($row['body']),
    ];
}

?>
