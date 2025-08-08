<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
require_once("../../core/helper.php");
require("../../core/messages/pm.php");

login_check();

$userId = $_SESSION['userId'];
$limit = 20;
$page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : null;

if (isset($_GET['mark'])) {
    pm_mark_read((int)$_GET['mark'], $userId);
    header('Location: inbox.php');
    exit;
}

if (isset($_GET['del'])) {
    pm_delete((int)$_GET['del'], $userId);
    header('Location: inbox.php');
    exit;
}
$messages = pm_inbox($userId, $limit, $offset, $search);
?>
<?php require("../header.php"); ?>

<div class="simple-container">
    <h1>Inbox</h1>
    <p><a href="compose.php">Compose</a> | <a href="outbox.php">Outbox</a></p>
    <form method="get" action="inbox.php">
        <input type="text" name="search" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Search" />
        <button type="submit">Go</button>
    </form>
    <?php if (empty($messages)): ?>
        <p>No messages.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($messages as $msg): ?>
                <li>
                    <strong><a href="?mark=<?= $msg['id'] ?>&search=<?= urlencode($search ?? '') ?>&page=<?= $page ?>"><?= htmlspecialchars($msg['subject']) ?></a></strong>
                    from <a href="../profile.php?id=<?= $msg['sender_id'] ?>"><?= htmlspecialchars($msg['sender']) ?></a>
                    on <?= htmlspecialchars($msg['sent_at']) ?>
                    <?php if (empty($msg['read_at'])): ?>
                        <em>(unread)</em>
                    <?php endif; ?>
                    <a href="?del=<?= $msg['id'] ?>&search=<?= urlencode($search ?? '') ?>&page=<?= $page ?>" onclick="return confirm('Delete this message?');">Delete</a>
                    <div><?= nl2br(htmlspecialchars($msg['body'])) ?></div>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search ?? '') ?>">Previous</a>
            <?php endif; ?>
            <?php if (count($messages) === $limit): ?>
                <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search ?? '') ?>">Next</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require("../footer.php"); ?>

