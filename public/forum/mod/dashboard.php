<?php
require __DIR__ . "/../../../core/conn.php";
require_once __DIR__ . "/../../../core/settings.php";
require_once __DIR__ . "/../../../core/forum/mod_check.php";
require_once __DIR__ . "/../../../core/forum/mod_dashboard.php";

forum_mod_check();

$openReports = countOpenReports();
$unresolvedPosts = countUnresolvedPosts();
$activeBans = countActiveBans();
$latestLogs = getLatestLogEntries();

$pageCSS = "../../static/css/mod.css";
?>
<?php require __DIR__ . "/../../header.php"; ?>
<div class="mod-dashboard">
    <h1>Moderator Dashboard</h1>
    <div class="stats">
        <div class="stat">Open Reports: <?= $openReports ?></div>
        <div class="stat">Unresolved Posts: <?= $unresolvedPosts ?></div>
        <div class="stat">Active Bans: <?= $activeBans ?></div>
    </div>
    <h2>Latest Log Entries</h2>
    <?php if (empty($latestLogs)): ?>
    <p>No log entries.</p>
    <?php else: ?>
    <table class="forum-table">
        <tr><th>Time</th><th>Moderator</th><th>Action</th><th>Target</th></tr>
        <?php foreach ($latestLogs as $log): ?>
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
<?php require __DIR__ . "/../../footer.php"; ?>
