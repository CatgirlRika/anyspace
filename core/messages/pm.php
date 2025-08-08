<?php
require_once __DIR__ . '/../helper.php';

function pm_send(int $sender_id, int $receiver_id, string $subject, string $body): int
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

    $stmt = $conn->prepare('INSERT INTO messages (sender_id, receiver_id, subject, body, sent_at) VALUES (:sid, :rid, :sub, :body, CURRENT_TIMESTAMP)');
    $stmt->execute([':sid' => $sender_id, ':rid' => $receiver_id, ':sub' => $cleanSubject, ':body' => $cleanBody]);
    return (int)$conn->lastInsertId();
}

function pm_inbox(int $user_id, int $limit = 20, int $offset = 0, ?string $keyword = null): array
{
    global $conn;
    $sql = 'SELECT m.id, m.sender_id, m.receiver_id, m.subject, m.body, m.sent_at, m.read_at, u.username AS sender
            FROM messages m JOIN users u ON m.sender_id = u.id
            WHERE m.receiver_id = :uid AND m.receiver_deleted = 0';
    if ($keyword !== null && $keyword !== '') {
        $sql .= ' AND (m.subject LIKE :kw OR m.body LIKE :kw)';
    }
    $sql .= ' ORDER BY m.sent_at DESC LIMIT :lim OFFSET :off';
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':uid', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    if ($keyword !== null && $keyword !== '') {
        $stmt->bindValue(':kw', '%' . $keyword . '%', PDO::PARAM_STR);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function pm_outbox(int $user_id, int $limit = 20, int $offset = 0, ?string $keyword = null): array
{
    global $conn;
    $sql = 'SELECT m.id, m.sender_id, m.receiver_id, m.subject, m.body, m.sent_at, m.read_at, u.username AS receiver
            FROM messages m JOIN users u ON m.receiver_id = u.id
            WHERE m.sender_id = :uid AND m.sender_deleted = 0';
    if ($keyword !== null && $keyword !== '') {
        $sql .= ' AND (m.subject LIKE :kw OR m.body LIKE :kw)';
    }
    $sql .= ' ORDER BY m.sent_at DESC LIMIT :lim OFFSET :off';
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':uid', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    if ($keyword !== null && $keyword !== '') {
        $stmt->bindValue(':kw', '%' . $keyword . '%', PDO::PARAM_STR);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

