<?php
function logModAction(int $moderator_id, string $action, string $target_type, int $target_id): void {
    global $conn;
    $stmt = $conn->prepare('INSERT INTO mod_log (moderator_id, action, target_type, target_id, timestamp) VALUES (:mid, :action, :ttype, :tid, CURRENT_TIMESTAMP)');
    $stmt->execute([
        ':mid' => $moderator_id,
        ':action' => $action,
        ':ttype' => $target_type,
        ':tid' => $target_id
    ]);
}
?>
