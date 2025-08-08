<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
require_once("../../core/helper.php");
require("../../core/messages/pm.php");

login_check();

$userId = $_SESSION['userId'];

if (isset($_GET['mark'])) {
    pm_mark_read((int)$_GET['mark'], $userId);
    header('Location: inbox.php');
    exit;
}

$messages = pm_inbox($userId);
?>
<?php require("../header.php"); ?>

<div class="simple-container">
    <h1>Inbox</h1>
    <p><a href="compose.php">Compose</a> | <a href="outbox.php">Outbox</a></p>
    <?php if (empty($messages)): ?>
        <p>No messages.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($messages as $msg): ?>
                <li>
                    <strong><a href="?mark=<?= $msg['id'] ?>"><?= htmlspecialchars($msg['subject']) ?></a></strong>
                    from <a href="../profile.php?id=<?= $msg['sender_id'] ?>"><?= htmlspecialchars($msg['sender']) ?></a>
                    on <?= htmlspecialchars($msg['sent_at']) ?>
                    <?php if (empty($msg['read_at'])): ?>
                        <em>(unread)</em>
                    <?php endif; ?>
                    <div><?= nl2br(htmlspecialchars($msg['body'])) ?></div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<?php require("../footer.php"); ?>

