<?php

function subscribeTopic(int $user_id, int $topic_id): bool {
    global $conn;
    $check = $conn->prepare('SELECT COUNT(*) FROM topic_subscriptions WHERE user_id = :uid AND topic_id = :tid');
    $check->execute([':uid' => $user_id, ':tid' => $topic_id]);
    if ((int)$check->fetchColumn() > 0) {
        $del = $conn->prepare('DELETE FROM topic_subscriptions WHERE user_id = :uid AND topic_id = :tid');
        $del->execute([':uid' => $user_id, ':tid' => $topic_id]);
        return false; // unsubscribed
    }
    $ins = $conn->prepare('INSERT INTO topic_subscriptions (user_id, topic_id) VALUES (:uid, :tid)');
    $ins->execute([':uid' => $user_id, ':tid' => $topic_id]);
    return true; // subscribed
}

function getUserSubscriptions(int $user_id): array {
    global $conn;
    $stmt = $conn->prepare('SELECT t.id, t.title FROM topic_subscriptions s JOIN forum_topics t ON s.topic_id = t.id WHERE s.user_id = :uid');
    $stmt->execute([':uid' => $user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>
