<?php

require_once(__DIR__ . '/../helper.php');

function createPoll(int $topic_id, string $question, array $options) {
    global $conn;
    $options = array_values(array_filter(array_map('trim', $options), fn($o) => $o !== ''));
    if ($question === '' || count($options) < 2) {
        return false;
    }
    $stmt = $conn->prepare('INSERT INTO polls (topic_id, question, options, locked) VALUES (:tid, :question, :opts, 0)');
    $stmt->execute([
        ':tid' => $topic_id,
        ':question' => strip_tags($question),
        ':opts' => json_encode($options)
    ]);
    return (int)$conn->lastInsertId();
}

function votePoll(int $poll_id, int $user_id, int $option_index) {
    global $conn;
    $stmt = $conn->prepare('SELECT 1 FROM poll_votes WHERE poll_id = :pid AND user_id = :uid');
    $stmt->execute([':pid' => $poll_id, ':uid' => $user_id]);
    if ($stmt->fetch()) {
        return false; // already voted
    }
    $stmt = $conn->prepare('INSERT INTO poll_votes (poll_id, user_id, option_index) VALUES (:pid, :uid, :opt)');
    $stmt->execute([':pid' => $poll_id, ':uid' => $user_id, ':opt' => $option_index]);
    $conn->prepare('UPDATE polls SET locked = 1 WHERE id = :pid AND locked = 0')
         ->execute([':pid' => $poll_id]);
    return true;
}

function getPollResults(int $poll_id): array {
    global $conn;
    $stmt = $conn->prepare('SELECT question, options FROM polls WHERE id = :pid');
    $stmt->execute([':pid' => $poll_id]);
    $poll = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$poll) {
        return [];
    }
    $options = json_decode($poll['options'], true) ?: [];
    $counts = array_fill(0, count($options), 0);
    $voteStmt = $conn->prepare('SELECT option_index, COUNT(*) as votes FROM poll_votes WHERE poll_id = :pid GROUP BY option_index');
    $voteStmt->execute([':pid' => $poll_id]);
    foreach ($voteStmt as $row) {
        $idx = (int)$row['option_index'];
        if (isset($counts[$idx])) {
            $counts[$idx] = (int)$row['votes'];
        }
    }
    $results = [];
    foreach ($options as $i => $opt) {
        $results[] = ['option' => $opt, 'votes' => $counts[$i] ?? 0];
    }
    return $results;
}

?>

