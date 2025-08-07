<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
require_once("../../core/forum/category.php");
require_once("../../core/forum/forum.php");
require_once("../../core/forum/permissions.php");

$pageCSS = "../static/css/forum.css";
$categories = forum_get_categories();
?>
<?php require("../header.php"); ?>
<div class="simple-container">
    <h1>Forums</h1>
    <?php foreach ($categories as $cat): ?>
        <h2><?= htmlspecialchars($cat['name']) ?></h2>
        <ul class="forum-list">
            <?php foreach (forum_get_forums_by_category($cat['id']) as $forum): ?>
                <?php $perm = forum_fetch_permissions($forum['id']); if (!$perm['can_view']) continue; ?>
                <li>
                    <a href="topic.php?id=<?= $forum['id'] ?>"><?= htmlspecialchars($forum['name']) ?></a>
                    - <?= htmlspecialchars($forum['description']) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endforeach; ?>
</div>
<?php require("../footer.php"); ?>
