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
    <?php foreach ($categories as $cat): ?>
        <h2><?= htmlspecialchars($cat['name']) ?></h2>

    <?php endforeach; ?>
</div>
<?php require __DIR__ . "/../footer.php"; ?>
