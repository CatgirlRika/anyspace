<?php

require_once(__DIR__ . '/topic.php');
require_once(__DIR__ . '/../helper.php');
require_once(__DIR__ . '/notifications.php');
require_once(__DIR__ . '/subscriptions.php');
require_once(__DIR__ . '/mod_log.php');
require_once(__DIR__ . '/word_filter.php');
require_once(__DIR__ . '/forum.php');
require_once(__DIR__ . '/permissions.php');
require_once(__DIR__ . '/rate_limit.php');
require_once(__DIR__ . '/audit_log.php');
require_once(__DIR__ . '/../../lib/upload.php');

function forum_add_post(int $topic_id, int $user_id, string $body)
{
    global $conn;
    
    // Input validation
    if ($topic_id <= 0 || $user_id <= 0) {
        return ['error' => 'Invalid topic or user ID'];
    }
    
    if (empty(trim($body))) {
        return ['error' => 'Post body cannot be empty'];
    }
    
    // Rate limiting check (except for moderators and admins)
    $role = forum_user_role();
    if ($role === 'member' || $role === 'guest') {
        if (forum_rate_limit_exceeded($user_id)) {
            $remaining = forum_rate_limit_time_remaining($user_id);
            forum_log_security_event('rate_limit_exceeded', $user_id, "Topic: $topic_id, Remaining: {$remaining}s");
            return ['error' => 'Rate limit exceeded. Please wait ' . $remaining . ' seconds before posting again.'];
        }
    }
    
    // Check if topic exists and is not locked
    $stmt = $conn->prepare('SELECT locked, forum_id FROM forum_topics WHERE id = :id');
    $stmt->execute([':id' => $topic_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        return ['error' => 'Topic not found'];
    }
    
    $locked = (int)$result['locked'];
    $forum_id = (int)$result['forum_id'];
    if ($locked === 1) {
        return ['error' => 'Topic is locked'];
    }
    $matches = isFiltered($body);
    $sanitizedBody = validateContentHTML($body);
    if (!empty($matches)) {
        $insert = $conn->prepare('INSERT INTO forum_posts (topic_id, user_id, body, created_at, deleted) VALUES (:tid, :uid, :body, CURRENT_TIMESTAMP, 1)');
        $insert->execute([':tid' => $topic_id, ':uid' => $user_id, ':body' => $sanitizedBody]);
        $postId = (int)$conn->lastInsertId();
        
        // Log filtered post
        forum_log_security_event('filtered_post_created', $user_id, "Post: $postId, Topic: $topic_id, Filtered words: " . implode(', ', $matches));
        
        // Clear cache when post is added
        forum_clear_stats_cache($forum_id);
        forum_clear_stats_cache(0); // Clear global cache too
        return ['warning' => 'Post contains filtered words', 'filtered' => $matches];
    }
    $insert = $conn->prepare('INSERT INTO forum_posts (topic_id, user_id, body, created_at) VALUES (:tid, :uid, :body, CURRENT_TIMESTAMP)');
    $insert->execute([':tid' => $topic_id, ':uid' => $user_id, ':body' => $sanitizedBody]);
    $postId = (int)$conn->lastInsertId();

    // Log successful post creation
    forum_log_activity('post_created', $user_id, "Post: $postId, Topic: $topic_id, Forum: $forum_id");

    // Clear cache when post is added
    forum_clear_stats_cache($forum_id);
    forum_clear_stats_cache(0); // Clear global cache too

    // Send notifications to relevant users
    forum_send_post_notifications($topic_id, $user_id, $postId, $sanitizedBody);

    return ['success' => true, 'id' => $postId];
}

/**
 * Send notifications for a new forum post
 * @param int $topic_id Topic ID
 * @param int $user_id User who created the post
 * @param int $postId Post ID
 * @param string $body Post content for @ mention parsing
 */
function forum_send_post_notifications($topic_id, $user_id, $postId, $body) {
    global $conn;
    $notified = [];

    // Notify topic owner
    $ownerStmt = $conn->prepare('SELECT user_id FROM forum_posts WHERE topic_id = :tid ORDER BY id ASC LIMIT 1');
    $ownerStmt->execute([':tid' => $topic_id]);
    $topicOwner = (int)$ownerStmt->fetchColumn();
    if ($topicOwner && $topicOwner !== $user_id) {
        notifications_add($topicOwner, $postId);
        $notified[] = $topicOwner;
    }

    // Notify mentioned users
    if (preg_match_all('/@([A-Za-z0-9_]+)/', $body, $matches)) {
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

    // Notify subscribers
    $subStmt = $conn->prepare('SELECT user_id FROM topic_subscriptions WHERE topic_id = :tid');
    $subStmt->execute([':tid' => $topic_id]);
    $subs = $subStmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($subs as $uid) {
        $uid = (int)$uid;
        if ($uid !== $user_id && !in_array($uid, $notified, true)) {
            notifications_add($uid, $postId);
            $notified[] = $uid;
        }
    }
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


function forum_get_attachments(int $post_id): array {
    global $conn;
    $stmt = $conn->prepare('SELECT id, path, mime_type, uploaded_at FROM attachments WHERE post_id = :pid');
    $stmt->execute([':pid' => $post_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function forum_delete_attachment(int $attachment_id): void {
    global $conn;
    $stmt = $conn->prepare('SELECT path FROM attachments WHERE id = :id');
    $stmt->execute([':id' => $attachment_id]);
    $path = $stmt->fetchColumn();
    if ($path) {
        @unlink(__DIR__ . '/../../public/' . $path);
        $del = $conn->prepare('DELETE FROM attachments WHERE id = :id');
        $del->execute([':id' => $attachment_id]);
    }
}

function uploadAttachment(int $post_id, array $file): bool {
    $uploadDir = __DIR__ . '/../../public/uploads/forum/';
    $result = upload_file($file, $uploadDir);
    if (!$result) {
        return false;
    }
    global $conn;
    $stmt = $conn->prepare('INSERT INTO attachments (post_id, path, mime_type, uploaded_at) VALUES (:pid, :path, :mime, NOW())');
    $stmt->execute([
        ':pid' => $post_id,
        ':path' => 'uploads/forum/' . $result['name'],
        ':mime' => $result['mime']
    ]);
    return true;
}
?>
