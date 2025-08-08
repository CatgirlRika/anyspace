<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
 97xjcb-codex/create-messages-table-and-related-features
require_once("../../core/helper.php");
=======
 main
require("../../core/messages/pm.php");

login_check();

$userId = $_SESSION['userId'];
$messages = pm_outbox($userId);
?>
<?php require("../header.php"); ?>

<div class="simple-container">
    <h1>Outbox</h1>
    <p><a href="compose.php">Compose</a> | <a href="inbox.php">Inbox</a></p>
    <?php if (empty($messages)): ?>
        <p>No messages.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($messages as $msg): ?>
                <li>
                    <strong><?= htmlspecialchars($msg['subject']) ?></strong>
                    to <a href="../profile.php?id=<?= $msg['receiver_id'] ?>"><?= htmlspecialchars($msg['receiver']) ?></a>
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

