<?php
require_once __DIR__ . '/../helper.php';

function pm_send(int $sender_id, int $receiver_id, string $subject, string $body, ?int $parent_id = null): int
{
    global $conn;
    $cleanSubject = trim(strip_tags($subject));
    if (mb_strlen($cleanSubject) === 0 || mb_strlen($cleanSubject) > 255) {
        throw new InvalidArgumentException('Subject must be between 1 and 255 characters.');
    }

    $cleanBody = validateContentHTML($body);
    if (mb_strlen(trim(strip_tags($cleanBody))) === 0) {
        throw new InvalidArgumentException('Message body cannot be empty.');
    }

    $threadId = null;
    if ($parent_id !== null) {
        $stmt = $conn->prepare('SELECT thread_id FROM messages WHERE id = :pid');
        $stmt->execute([':pid' => $parent_id]);
        $threadId = $stmt->fetchColumn() ?: null;
    }

    $stmt = $conn->prepare('INSERT INTO messages (sender_id, receiver_id, subject, body, thread_id, sent_at) VALUES (:sid, :rid, :sub, :body, :tid, CURRENT_TIMESTAMP)');
    $stmt->execute([':sid' => $sender_id, ':rid' => $receiver_id, ':sub' => $cleanSubject, ':body' => $cleanBody, ':tid' => $threadId ?? 0]);
    $id = (int)$conn->lastInsertId();
    if ($threadId === null) {
        $threadId = $id;
        $upd = $conn->prepare('UPDATE messages SET thread_id = :tid WHERE id = :id');
        $upd->execute([':tid' => $threadId, ':id' => $id]);
    }
    return $id;
}

function pm_inbox(int $user_id, int $limit = 20, int $offset = 0, ?string $keyword = null): array
{
    global $conn;
    $params = [':uid' => $user_id];
    $sql = 'SELECT m.id, m.thread_id, m.sender_id, m.receiver_id, m.subject, m.body, m.sent_at, m.read_at, su.username AS sender, ru.username AS receiver
            FROM messages m
            JOIN users su ON m.sender_id = su.id
            JOIN users ru ON m.receiver_id = ru.id
            WHERE m.thread_id IN (SELECT thread_id FROM messages WHERE receiver_id = :uid AND receiver_deleted = 0)
              AND ((m.sender_id = :uid AND m.sender_deleted = 0) OR (m.receiver_id = :uid AND m.receiver_deleted = 0))';
    if ($keyword !== null && $keyword !== '') {
        $sql .= ' AND (m.subject LIKE :kw OR m.body LIKE :kw)';
        $params[':kw'] = '%' . $keyword . '%';
    }
    $sql .= ' ORDER BY m.thread_id, m.sent_at ASC';
    $stmt = $conn->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $threads = [];
    foreach ($rows as $row) {
        $tid = $row['thread_id'];
        if (!isset($threads[$tid])) {
            $threads[$tid] = ['thread_id' => $tid, 'messages' => []];
        }
        $threads[$tid]['messages'][] = $row;
    }
    $threads = array_values($threads);
    usort($threads, function ($a, $b) {
        return strcmp(end($b['messages'])['sent_at'], end($a['messages'])['sent_at']);
    });
    $threads = array_slice($threads, $offset, $limit);
    return $threads;
}

function pm_outbox(int $user_id, int $limit = 20, int $offset = 0, ?string $keyword = null): array
{
    global $conn;
    $params = [':uid' => $user_id];
    $sql = 'SELECT m.id, m.thread_id, m.sender_id, m.receiver_id, m.subject, m.body, m.sent_at, m.read_at, su.username AS sender, ru.username AS receiver
            FROM messages m
            JOIN users su ON m.sender_id = su.id
            JOIN users ru ON m.receiver_id = ru.id
            WHERE m.thread_id IN (SELECT thread_id FROM messages WHERE sender_id = :uid AND sender_deleted = 0)
              AND ((m.sender_id = :uid AND m.sender_deleted = 0) OR (m.receiver_id = :uid AND m.receiver_deleted = 0))';
    if ($keyword !== null && $keyword !== '') {
        $sql .= ' AND (m.subject LIKE :kw OR m.body LIKE :kw)';
        $params[':kw'] = '%' . $keyword . '%';
    }
    $sql .= ' ORDER BY m.thread_id, m.sent_at ASC';
    $stmt = $conn->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $threads = [];
    foreach ($rows as $row) {
        $tid = $row['thread_id'];
        if (!isset($threads[$tid])) {
            $threads[$tid] = ['thread_id' => $tid, 'messages' => []];
        }
        $threads[$tid]['messages'][] = $row;
    }
    $threads = array_values($threads);
    usort($threads, function ($a, $b) {
        return strcmp(end($b['messages'])['sent_at'], end($a['messages'])['sent_at']);
    });
    $threads = array_slice($threads, $offset, $limit);
    return $threads;
}

function pm_mark_read(int $message_id, int $user_id): void
{
    global $conn;
    $stmt = $conn->prepare('UPDATE messages SET read_at = CURRENT_TIMESTAMP WHERE id = :id AND receiver_id = :uid AND receiver_deleted = 0 AND read_at IS NULL');
    $stmt->execute([':id' => $message_id, ':uid' => $user_id]);
}

function pm_unread_count(int $user_id): int
{
    global $conn;
    $stmt = $conn->prepare('SELECT COUNT(*) FROM messages WHERE receiver_id = :uid AND receiver_deleted = 0 AND read_at IS NULL');
    $stmt->execute([':uid' => $user_id]);
    return (int)$stmt->fetchColumn();
}

function pm_delete(int $message_id, int $user_id): void
{
    global $conn;
    $stmt = $conn->prepare('SELECT sender_id, receiver_id, sender_deleted, receiver_deleted FROM messages WHERE id = :id');
    $stmt->execute([':id' => $message_id]);
    $msg = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$msg) {
        return;
    }

    if ((int)$msg['sender_id'] === $user_id) {
        if ((int)$msg['receiver_deleted'] === 1) {
            $del = $conn->prepare('DELETE FROM messages WHERE id = :id');
            $del->execute([':id' => $message_id]);
        } else {
            $upd = $conn->prepare('UPDATE messages SET sender_deleted = 1 WHERE id = :id');
            $upd->execute([':id' => $message_id]);
        }
    } elseif ((int)$msg['receiver_id'] === $user_id) {
        if ((int)$msg['sender_deleted'] === 1) {
            $del = $conn->prepare('DELETE FROM messages WHERE id = :id');
            $del->execute([':id' => $message_id]);
        } else {
            $upd = $conn->prepare('UPDATE messages SET receiver_deleted = 1 WHERE id = :id');
            $upd->execute([':id' => $message_id]);
        }
    }
}

?>

