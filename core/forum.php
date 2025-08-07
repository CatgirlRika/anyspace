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
    if (isset($_SESSION['rank']) && $_SESSION['rank'] == 1) {
        return 'global_mod';
    }
    return 'member';
}

function is_forum_moderator($forum_id, $user_id) {
    global $conn;
    $stmt = $conn->prepare('SELECT 1 FROM forum_moderators WHERE forum_id = :fid AND user_id = :uid LIMIT 1');
    $stmt->execute([':fid' => $forum_id, ':uid' => $user_id]);
    return (bool) $stmt->fetchColumn();
}

function forum_fetch_permissions($forum_id) {
    global $conn;
    $role = forum_user_role();
    $user_id = $_SESSION['userId'] ?? null;
    if ($role === 'global_mod' || ($user_id && is_forum_moderator($forum_id, $user_id))) {
        return ['can_view' => 1, 'can_post' => 1, 'can_moderate' => 1];
    }
    $stmt = $conn->prepare('SELECT can_view, can_post, can_moderate FROM forum_permissions WHERE forum_id = :fid AND role = :role');
    $stmt->execute([':fid' => $forum_id, ':role' => $role]);
    $perm = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$perm) {
        return ['can_view' => 0, 'can_post' => 0, 'can_moderate' => 0];
    }
    return $perm;
}

function forum_require_permission($forum_id, $flag) {
    $role = forum_user_role();
    $user_id = $_SESSION['userId'] ?? null;
    if ($role === 'global_mod' || ($user_id && is_forum_moderator($forum_id, $user_id))) {
        return true;
    }
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

function forum_get_user_settings($user_id) {
    global $conn;
    $stmt = $conn->prepare('SELECT background_image_url, background_color, text_color FROM forum_user_settings WHERE user_id = :uid');
    $stmt->execute([':uid' => $user_id]);
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$settings) {
        $settings = ['background_image_url' => '', 'background_color' => '', 'text_color' => ''];
    }
    return $settings;
}

function forum_save_user_settings($user_id, $bg_url, $bg_color, $text_color) {
    global $conn;
    $stmt = $conn->prepare('INSERT INTO forum_user_settings (user_id, background_image_url, background_color, text_color) VALUES (:uid, :bg_url, :bg_color, :text_color) ON DUPLICATE KEY UPDATE background_image_url = VALUES(background_image_url), background_color = VALUES(background_color), text_color = VALUES(text_color)');
    $stmt->execute([
        ':uid' => $user_id,
        ':bg_url' => $bg_url,
        ':bg_color' => $bg_color,
        ':text_color' => $text_color
    ]);
}
?>
