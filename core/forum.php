<?php
require_once(__DIR__.'/helper.php');
require_once(__DIR__.'/forum/permissions.php');

function forum_get_user_settings($user_id) {
    global $conn;
    $stmt = $conn->prepare('SELECT background_image_url, background_color, text_color, accent_color FROM forum_user_settings WHERE user_id = :uid');
    $stmt->execute([':uid' => $user_id]);
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$settings) {
        $settings = ['background_image_url' => '', 'background_color' => '', 'text_color' => '', 'accent_color' => ''];
    }
    return $settings;
}

function forum_save_user_settings($user_id, $bg_url, $bg_color, $text_color, $accent_color = null) {
    global $conn;
    $stmt = $conn->prepare('INSERT INTO forum_user_settings (user_id, background_image_url, background_color, text_color, accent_color) VALUES (:uid, :bg_url, :bg_color, :text_color, :accent_color) ON DUPLICATE KEY UPDATE background_image_url = VALUES(background_image_url), background_color = VALUES(background_color), text_color = VALUES(text_color), accent_color = VALUES(accent_color)');
    $stmt->execute([
        ':uid' => $user_id,
        ':bg_url' => $bg_url,
        ':bg_color' => $bg_color,
        ':text_color' => $text_color,
        ':accent_color' => $accent_color
    ]);
}
?>
