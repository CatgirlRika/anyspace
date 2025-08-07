<?php
require __DIR__ . "/../../core/conn.php";
require_once __DIR__ . "/../../core/settings.php";
require_once __DIR__ . "/../../core/forum/category.php";
require_once __DIR__ . "/../../core/forum/forum.php";
require_once __DIR__ . "/../../core/forum/permissions.php";

$pageCSS = "../static/css/forum.css";
$categories = forum_get_categories();
?>
<?php require __DIR__ . "/../header.php"; ?>
<div class="simple-container">
    <h1>Forums</h1>
    <form class="forum-search" action="search.php" method="get">
        <input type="text" name="q">
        <button type="submit">Search</button>
    </form>
    <?php foreach ($categories as $cat): ?>
        <h2><?= htmlspecialchars($cat['name']) ?></h2>
    <?php endforeach; ?>
</div>
<?php require __DIR__ . "/../footer.php"; ?>
