<?php
require("../core/conn.php");
require_once("../core/settings.php");
require("../core/site/page.php");

?>
<?php require("header.php"); ?>

<div class="simple-container">
    <h1>About <?= SITE_NAME ?></h1>
    <br>
    <?= get_page_content('about'); ?>
</div>

<?php require("footer.php"); ?>