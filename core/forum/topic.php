<?php

require_once(__DIR__ . '/../helper.php');

function forum_log_action(string $message): void {
    $logFile = __DIR__ . '/../../admin_logs.txt';
    $entry = date('c') . ' ' . $message . PHP_EOL;
    file_put_contents($logFile, $entry, FILE_APPEND);
}

function topic_lock(int $topic_id, int $by_user_id): void {
    global $conn;
    $stmt = $conn->prepare('UPDATE forum_topics SET locked = 1 WHERE id = :id');
    $stmt->execute([':id' => $topic_id]);
    forum_log_action("User {$by_user_id} locked topic {$topic_id}");
}

function topic_unlock(int $topic_id, int $by_user_id): void {
    global $conn;
    $stmt = $conn->prepare('UPDATE forum_topics SET locked = 0 WHERE id = :id');
    $stmt->execute([':id' => $topic_id]);
    forum_log_action("User {$by_user_id} unlocked topic {$topic_id}");
}

function topic_sticky(int $topic_id, int $by_user_id): void {
    global $conn;
    $stmt = $conn->prepare('UPDATE forum_topics SET sticky = 1 WHERE id = :id');
    $stmt->execute([':id' => $topic_id]);
    forum_log_action("User {$by_user_id} stickied topic {$topic_id}");
}

function topic_unsticky(int $topic_id, int $by_user_id): void {
    global $conn;
    $stmt = $conn->prepare('UPDATE forum_topics SET sticky = 0 WHERE id = :id');
    $stmt->execute([':id' => $topic_id]);
    forum_log_action("User {$by_user_id} unstickied topic {$topic_id}");
}

function topic_move(int $topic_id, int $new_forum_id, int $by_user_id): void {
    global $conn;
    $conn->beginTransaction();
    try {
        $stmt = $conn->prepare('SELECT forum_id, title FROM forum_topics WHERE id = :id');
        $stmt->execute([':id' => $topic_id]);
        $topic = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$topic) {
            $conn->rollBack();
            return;
        }
        $old_forum_id = (int)$topic['forum_id'];

        $update = $conn->prepare('UPDATE forum_topics SET forum_id = :new_forum WHERE id = :id');
        $update->execute([':new_forum' => $new_forum_id, ':id' => $topic_id]);

        $placeholder = $conn->prepare('INSERT INTO forum_topics (forum_id, title, locked, sticky, moved_to) VALUES (:forum, :title, 1, 0, :moved_to)');
        $placeholder->execute([
            ':forum' => $old_forum_id,
            ':title' => $topic['title'],
            ':moved_to' => $topic_id
        ]);

        $conn->commit();
        forum_log_action("User {$by_user_id} moved topic {$topic_id} from forum {$old_forum_id} to forum {$new_forum_id}");
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
}

function forum_get_topics(int $forum_id): array {
    global $conn;
    $stmt = $conn->prepare('SELECT t.id, t.title, t.locked, t.sticky, t.moved_to, (SELECT COUNT(*) FROM forum_posts p WHERE p.topic_id = t.id) AS posts, (SELECT MAX(created_at) FROM forum_posts p WHERE p.topic_id = t.id) AS last_post FROM forum_topics t WHERE t.forum_id = :fid ORDER BY t.sticky DESC, t.id DESC');
    $stmt->execute([':fid' => $forum_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function forum_create_topic(int $forum_id, int $user_id, string $title, string $body): int {
    global $conn;
    $sanitizedTitle = strip_tags($title);
    $sanitizedBody = validateContentHTML($body);
    $conn->beginTransaction();
    $stmt = $conn->prepare('INSERT INTO forum_topics (forum_id, title) VALUES (:fid, :title)');
    $stmt->execute([':fid' => $forum_id, ':title' => $sanitizedTitle]);
    $topicId = (int)$conn->lastInsertId();
    $post = $conn->prepare('INSERT INTO forum_posts (topic_id, user_id, body, created_at) VALUES (:tid, :uid, :body, NOW())');
    $post->execute([':tid' => $topicId, ':uid' => $user_id, ':body' => $sanitizedBody]);
    $conn->commit();
    return $topicId;
}

?>
