<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
require_once("../../core/forum/subscriptions.php");
require_once("../../core/helper.php");

login_check();

$subs = getUserSubscriptions($_SESSION['userId']);

$pageCSS = "../static/css/forum.css";
?>
<?php require("../header.php"); ?>
<div class="simple-container">
    <h1>My Subscriptions</h1>
    <?php if (empty($subs)): ?>
        <p>No subscriptions.</p>
    <?php else: ?>
        <ul>
        <?php foreach ($subs as $s): ?>
            <li><a href="post.php?id=<?= $s['id'] ?>" aria-label="View subscribed topic <?= htmlspecialchars($s['title']) ?>" role="link"><?= htmlspecialchars($s['title']) ?></a></li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
<?php require("../footer.php"); ?>
