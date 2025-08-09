<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
require_once("../../core/site/user.php");

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

if((int)$_GET['id']) {
    $group_id = (int)$_GET['id'];
    
    // Get group name for display
    $stmt = $conn->prepare("SELECT * FROM `groups` WHERE id = ?");
    $stmt->execute([$group_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        $groupname = $result['name'];
        
        // Check if user is already a member
        $stmt = $conn->prepare("SELECT * FROM `group_memberships` WHERE group_id = ? AND username = ?");
        $stmt->execute([$group_id, $_SESSION['user']]);
        $membership_result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$membership_result) {
            // Add user to group
            $stmt = $conn->prepare("INSERT INTO `group_memberships` (group_id, username) VALUES (?, ?)");
            $stmt->execute([$group_id, $_SESSION['user']]);
            
            // Also update the currentgroup field for backwards compatibility
            $stmt = $conn->prepare("UPDATE users SET currentgroup = ? WHERE username = ?");
            $stmt->execute([$groupname, $_SESSION['user']]);
            
            header("Location: viewgroup.php?id=" . $group_id . "&msg=joined");
        } else {
            header("Location: viewgroup.php?id=" . $group_id . "&msg=already_member");
        }
    } else {
        header("Location: groups.php?msg=group_not_found");
    }
} else {
    header("Location: groups.php");
}
?>