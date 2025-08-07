<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
require_once("../../core/forum/report.php");
require_once("../../core/helper.php");

login_check();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reason'], $_POST['type'], $_POST['id'])) {
    $type = $_POST['type'];
    $id = (int)$_POST['id'];
    $reason = trim($_POST['reason']);
    if ($type && $id && $reason !== '') {
        submitReport($type, $id, $_SESSION['userId'], $reason);
    }
    $back = $_SERVER['HTTP_REFERER'] ?? 'index.php';
    header('Location: ' . $back);
    exit;
}

$type = $_POST['type'] ?? '';
$id = (int)($_POST['id'] ?? 0);

$pageCSS = "../static/css/forum.css";
?>
<?php require("../header.php"); ?>
<div class="simple-container">
    <h1>Report</h1>
    <form method="post">
        <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
        <input type="hidden" name="id" value="<?= $id ?>">
        <textarea name="reason" aria-label="Report reason"></textarea>
        <button type="submit" role="button">Submit</button>
    </form>
</div>
<?php require("../footer.php"); ?>
