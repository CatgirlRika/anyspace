<?php
require("../../../core/conn.php");
require_once("../../../core/settings.php");
require_once("../../../core/forum/mod_check.php");

forum_mod_check();

$conditions = [];
$params = [];
if (!empty($_GET['moderator'])) {
    $conditions[] = 'l.moderator_id = :mid';
    $params[':mid'] = (int)$_GET['moderator'];
}
if (!empty($_GET['date'])) {
    $conditions[] = 'DATE(l.timestamp) = :date';
    $params[':date'] = $_GET['date'];
}
$sql = 'SELECT l.*, u.username FROM mod_log l JOIN users u ON l.moderator_id = u.id';
if ($conditions) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}
$sql .= ' ORDER BY l.timestamp DESC LIMIT 100';
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageCSS = "../../static/css/forum.css";
?>
<?php require("../../header.php"); ?>
<div class="simple-container">
    <h1>Moderator Log</h1>
    <form method="get" style="margin-bottom:1em;">
        <label>Moderator ID: <input type="text" name="moderator" value="<?= htmlspecialchars($_GET['moderator'] ?? '') ?>"></label>
        <label>Date: <input type="date" name="date" value="<?= htmlspecialchars($_GET['date'] ?? '') ?>"></label>
        <button type="submit">Filter</button>
    </form>
    <?php if (empty($logs)): ?>
    <p>No log entries found.</p>
    <?php else: ?>
    <table class="forum-table">
        <tr><th>Time</th><th>Moderator</th><th>Action</th><th>Target</th></tr>
        <?php foreach ($logs as $log): ?>
        <tr>
            <td><?= htmlspecialchars($log['timestamp']) ?></td>
            <td><?= htmlspecialchars($log['username']) ?></td>
            <td><?= htmlspecialchars($log['action']) ?></td>
            <td><?= htmlspecialchars($log['target_type'] . ' #' . $log['target_id']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
</div>
<?php require("../../footer.php"); ?>
