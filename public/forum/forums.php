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

    <?php endforeach; ?>
</div>
<?php require("../footer.php"); ?>
