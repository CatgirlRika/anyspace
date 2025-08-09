<?php
require("../../core/conn.php");
require("../../core/settings.php");
login_check();

// Redirect to the main groups page
header("Location: groups.php");
exit;
?>