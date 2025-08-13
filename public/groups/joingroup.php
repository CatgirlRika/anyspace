<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
require_once("../../core/site/user.php");

login_check();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['group_id'])) {
    $group_id = (int)$_POST['group_id'];

    $stmt = $conn->prepare("SELECT name FROM `groups` WHERE id = ?");
    $stmt->execute([$group_id]);
    $group = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($group) {
        $stmt = $conn->prepare("SELECT 1 FROM `group_memberships` WHERE group_id = ? AND username = ?");
        $stmt->execute([$group_id, $_SESSION['user']]);
        if (!$stmt->fetch()) {
            $stmt = $conn->prepare("INSERT INTO `group_memberships` (group_id, username) VALUES (?, ?)");
            $stmt->execute([$group_id, $_SESSION['user']]);

            $stmt = $conn->prepare("UPDATE users SET currentgroup = ? WHERE username = ?");
            $stmt->execute([$group['name'], $_SESSION['user']]);

            header("Location: viewgroup.php?id=" . $group_id . "&msg=joined");
            exit;
        }
        header("Location: viewgroup.php?id=" . $group_id . "&msg=already_member");
        exit;
    }
}

header("Location: groups.php?msg=group_not_found");
exit;
?>
