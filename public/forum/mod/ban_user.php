<?php
require("../../../core/conn.php");
require_once("../../../core/settings.php");
require_once("../../../core/forum/mod_check.php");
require_once("../../../core/users/ban.php");

forum_mod_check();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = (int)$_POST['user_id'];
    $until = trim($_POST['ban_until'] ?? '');
    if ($until !== '') {
        banUser($uid, $until);
        $message = "User $uid banned until $until";
    } else {
        unbanUser($uid);
        $message = "User $uid unbanned";
    }
}

$pageCSS = "../../static/css/forum.css";
?>
<?php require("../../header.php"); ?>
<div class="simple-container">
    <h1>Ban User</h1>
    <form method="post">
    <?= csrf_token_input(); ?>
        <label>User ID: <input type="number" name="user_id" required></label><br>
        <label>Ban Until (YYYY-MM-DD HH:MM:SS): <input type="text" name="ban_until"></label><br>
        <button type="submit">Submit</button>
    </form>
    <?php if ($message): ?>
    <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
</div>
<?php require("../../footer.php"); ?>

