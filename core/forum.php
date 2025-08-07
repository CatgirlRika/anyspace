<?php
require_once(__DIR__.'/helper.php'); // ensure login_check available

function forum_user_role() {
    // Determine the current user's role
    if (!isset($_SESSION['userId'])) {
        return 'guest';
    }
    if (defined('ADMIN_USER') && $_SESSION['userId'] == ADMIN_USER) {
        return 'admin';
    }
    return 'member';
}

function forum_fetch_permissions($forum_id) {
    global $conn;
    $role = forum_user_role();
    $stmt = $conn->prepare('SELECT can_view, can_post, can_moderate FROM forum_permissions WHERE forum_id = :fid AND role = :role');
    $stmt->execute([':fid' => $forum_id, ':role' => $role]);
    $perm = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$perm) {
        return ['can_view' => 0, 'can_post' => 0, 'can_moderate' => 0];
    }
    return $perm;
}

function forum_require_permission($forum_id, $flag) {
    $perms = forum_fetch_permissions($forum_id);
    $allowed = isset($perms[$flag]) ? (int)$perms[$flag] : 0;
    if (!$allowed) {
        if (!isset($_SESSION['user'])) {
            login_check(); // redirects to login
        }
        header('HTTP/1.1 403 Forbidden');
        echo 'Forbidden';
        exit;
    }
    return true;
}
?>
