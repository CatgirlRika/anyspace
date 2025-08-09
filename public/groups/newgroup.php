<?php
    require("../../core/conn.php");
    require_once("../../core/settings.php");
    require_once("../../core/site/user.php");
?>
<!DOCTYPE html>
<html>
    <head>
        <link rel="stylesheet" href="../static/css/header.css">
        <link rel="stylesheet" href="../static/css/base.css">
    </head>
    <body>
        <?php
            require("../../core/components/navbar.php");
        ?>
        <div class="container">
            <?php
                if(@$_POST) {
                    $text = str_replace(PHP_EOL, "<br>", $_POST['desc']);
                    $name = htmlspecialchars($_POST['groupname']);
                    
                    $stmt = $conn->prepare("INSERT INTO `groups` (name, description, author, date) VALUES (?, ?, ?, datetime('now'))");
                    $stmt->execute([$name, $text, $_SESSION['user']]);
                    $group_id = $conn->lastInsertId();
                    
                    // Add creator as first member
                    $stmt = $conn->prepare("INSERT INTO `group_memberships` (group_id, username, role) VALUES (?, ?, 'owner')");
                    $stmt->execute([$group_id, $_SESSION['user']]);
                    
                    $stmt = $conn->prepare("UPDATE users SET currentgroup = ? WHERE username = ?");
                    $stmt->execute([$name, $_SESSION['user']]);
                    
                    header("Location: viewgroup.php?id=" . $group_id);
                    exit;             
                }
            ?>
            <form method="post" enctype="multipart/form-data">
    <?= csrf_token_input(); ?>
                <input required placeholder="Name" size="90" type="text" name="groupname"><br>
				<textarea required rows="10" cols="68" placeholder="Description" name="desc"></textarea><br>
				<input name="submit" type="submit" value="Create"> <small>max limit: 500 characters</small>
            </form>
        </div>
    </body>
</html>
