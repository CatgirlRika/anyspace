<?php
require("../../../core/conn.php");
require_once("../../../core/settings.php");
require_once("../../../core/forum/report.php");
require_once("../../../core/forum/mod_check.php");

forum_mod_check();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_id'], $_POST['action'])) {
    $rid = (int)$_POST['report_id'];
    $action = $_POST['action'];
    resolveReport($rid, $action);
    header('Location: reports.php');
    exit;
}

$reports = getOpenReports();

$pageCSS = "../../static/css/forum.css";
?>
<?php require("../../header.php"); ?>
<div class="simple-container">
    <h1>Open Reports</h1>
    <?php if (empty($reports)): ?>
    <p>No reports.</p>
    <?php else: ?>
    <table class="forum-table">
        <tr><th>ID</th><th>Type</th><th>Reported ID</th><th>Reason</th><th>Reporter</th><th>Actions</th></tr>
        <?php foreach ($reports as $r): ?>
        <tr>
            <td><?= $r['id'] ?></td>
            <td><?= htmlspecialchars($r['type']) ?></td>
            <td><?= $r['reported_id'] ?></td>
            <td><?= htmlspecialchars($r['reason']) ?></td>
            <td><?= $r['reporter_id'] ?></td>
            <td>
                <form method="post" style="display:inline">
                    <input type="hidden" name="report_id" value="<?= $r['id'] ?>">
                    <input type="hidden" name="action" value="keep">
                    <button type="submit">Keep</button>
                </form>
                <form method="post" style="display:inline">
                    <input type="hidden" name="report_id" value="<?= $r['id'] ?>">
                    <input type="hidden" name="action" value="edit">
                    <button type="submit">Edit</button>
                </form>
                <form method="post" style="display:inline">
                    <input type="hidden" name="report_id" value="<?= $r['id'] ?>">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit">Delete</button>
                </form>
                <form method="post" style="display:inline">
                    <input type="hidden" name="report_id" value="<?= $r['id'] ?>">
                    <input type="hidden" name="action" value="ban">
                    <button type="submit">Ban</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
</div>
<?php require("../../footer.php"); ?>
