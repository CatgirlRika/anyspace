<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
require_once("../../core/site/user.php");

login_check();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['groupname'] ?? '');
    $desc = trim($_POST['desc'] ?? '');

    if ($name === '' || $desc === '') {
        $error = 'All fields are required.';
    } elseif (mb_strlen($name) > 100 || mb_strlen($desc) > 500) {
        $error = 'Group name or description too long.';
    } else {
        $name = strip_tags($name);
        $desc = validateContentHTML($desc);

        $stmt = $conn->prepare("SELECT COUNT(*) FROM `groups` WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'A group with that name already exists.';
        } else {
            $stmt = $conn->prepare("INSERT INTO `groups` (name, description, author, date) VALUES (?, ?, ?, CURRENT_TIMESTAMP)");
            $stmt->execute([$name, $desc, $_SESSION['user']]);
            $group_id = $conn->lastInsertId();

            $stmt = $conn->prepare("INSERT INTO `group_memberships` (group_id, username, role) VALUES (?, ?, 'owner')");
            $stmt->execute([$group_id, $_SESSION['user']]);

            $stmt = $conn->prepare("UPDATE users SET currentgroup = ? WHERE username = ?");
            $stmt->execute([$name, $_SESSION['user']]);

            header("Location: viewgroup.php?id=" . $group_id);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html>
    <head>
        <link rel="stylesheet" href="../static/css/header.css">
        <link rel="stylesheet" href="../static/css/base.css">
    </head>
    <body>
        <?php require("../../core/components/navbar.php"); ?>
        <div class="container">
            <?php if ($error) { echo "<div class='error'>" . htmlspecialchars($error) . "</div>"; } ?>
            <form method="post">
                <?= csrf_token_input(); ?>
                <input required placeholder="Name" size="90" type="text" name="groupname" maxlength="100"><br>
                <textarea required rows="10" cols="68" placeholder="Description" name="desc" maxlength="500"></textarea><br>
                <input name="submit" type="submit" value="Create"> <small>max limit: 500 characters</small>
            </form>
        </div>
    </body>
</html>
