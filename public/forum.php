<?php
require("../core/conn.php");
require_once("../core/settings.php");
require_once("../core/forum.php");

$forumId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
forum_require_permission($forumId, 'can_view');

$pageCSS = "static/css/forum.css";

?>
<?php require("header.php"); ?>

<div class="simple-container">
    <h1>Coming Soon!</h1>


</div>

<?php require("footer.php"); ?>