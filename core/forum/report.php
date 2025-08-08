<?php

require_once(__DIR__ . '/topic.php');

function submitReport(string $type, int $id, int $user_id, string $reason): void
{
    global $conn;
    $stmt = $conn->prepare('INSERT INTO reports (reported_id, type, reason, reporter_id, status) VALUES (:rid, :type, :reason, :reporter, "open")');
    $stmt->execute([
        ':rid' => $id,
        ':type' => $type,
        ':reason' => $reason,
        ':reporter' => $user_id
    ]);
}

function getOpenReports(): array
{
    global $conn;
    $stmt = $conn->prepare('SELECT * FROM reports WHERE status = "open" ORDER BY id ASC');
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function resolveReport(int $report_id, string $action_taken): void
{
    global $conn;
    $stmt = $conn->prepare('UPDATE reports SET status = :status WHERE id = :id');
    $stmt->execute([
        ':status' => $action_taken,
        ':id' => $report_id
    ]);
    forum_log_action("Report {$report_id} resolved with action {$action_taken}");
}

?>
