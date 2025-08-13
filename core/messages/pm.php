<?php
require_once __DIR__ . '/../helper.php';

function pm_send(int $sender_id, int $receiver_id, string $subject, string $body): int
{
    global $conn;
    $cleanSubject = trim(strip_tags($subject));
    $cleanBody = validateContentHTML($body);
    $stmt = $conn->prepare('INSERT INTO messages (sender_id, receiver_id, subject, body, sent_at) VALUES (:sid, :rid, :sub, :body, CURRENT_TIMESTAMP)');
    $stmt->execute([':sid' => $sender_id, ':rid' => $receiver_id, ':sub' => $cleanSubject, ':body' => $cleanBody]);
    return (int)$conn->lastInsertId();
}

function pm_inbox(int $user_id): array
{
    global $conn;
    $stmt = $conn->prepare('SELECT m.id, m.sender_id, m.receiver_id, m.subject, m.body, m.sent_at, m.read_at, u.username AS sender FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.receiver_id = :uid ORDER BY m.sent_at DESC');
    $stmt->execute([':uid' => $user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function pm_outbox(int $user_id): array
{
    global $conn;
    $stmt = $conn->prepare('SELECT m.id, m.sender_id, m.receiver_id, m.subject, m.body, m.sent_at, m.read_at, u.username AS receiver FROM messages m JOIN users u ON m.receiver_id = u.id WHERE m.sender_id = :uid ORDER BY m.sent_at DESC');
    $stmt->execute([':uid' => $user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function pm_mark_read(int $message_id, int $user_id): void
{
    global $conn;
    $stmt = $conn->prepare('UPDATE messages SET read_at = CURRENT_TIMESTAMP WHERE id = :id AND receiver_id = :uid AND read_at IS NULL');
    $stmt->execute([':id' => $message_id, ':uid' => $user_id]);
}

function pm_unread_count(int $user_id): int
{
    global $conn;
    try {
        $stmt = $conn->prepare('SELECT COUNT(*) FROM messages WHERE receiver_id = :uid AND read_at IS NULL');
        $stmt->execute([':uid' => $user_id]);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

?>

