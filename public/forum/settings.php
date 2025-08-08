<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
require_once("../../core/forum.php");

login_check();

$userId = $_SESSION['userId'];
$settings = forum_get_user_settings($userId);
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bgUrl = trim($_POST['background_url'] ?? '');
    $bgColor = trim($_POST['background_color'] ?? '');
    $textColor = trim($_POST['text_color'] ?? '');

    if ($bgUrl && !filter_var($bgUrl, FILTER_VALIDATE_URL)) {
        $bgUrl = '';
    }
    if ($bgColor && !preg_match('/^#[0-9a-fA-F]{6}$/', $bgColor)) {
        $bgColor = '';
    }
    if ($textColor && !preg_match('/^#[0-9a-fA-F]{6}$/', $textColor)) {
        $textColor = '';
    }

    forum_save_user_settings($userId, $bgUrl, $bgColor, $textColor);
    $settings = ['background_image_url' => $bgUrl, 'background_color' => $bgColor, 'text_color' => $textColor];
    $message = "Settings saved.";
}

$pageCSS = "../static/css/forum.css";
require("../header.php");
?>
<div class="simple-container">
    <h1>Forum Settings</h1>
    <?php if (!empty($message)): ?>
    <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
    <form method="post">
    <?= csrf_token_input(); ?>
        <p>
            <label>Background Image URL:<br>
                <input type="text" name="background_url" value="<?= htmlspecialchars($settings['background_image_url'] ?? '') ?>">
            </label>
        </p>
        <p>
            <label>Background Color:<br>
                <input type="text" name="background_color" value="<?= htmlspecialchars($settings['background_color'] ?? '') ?>" placeholder="#ffffff">
            </label>
        </p>
        <p>
            <label>Text Color:<br>
                <input type="text" name="text_color" value="<?= htmlspecialchars($settings['text_color'] ?? '') ?>" placeholder="#000000">
            </label>
        </p>
        <p><button type="submit" aria-label="Save forum settings" role="button">Save</button></p>
    </form>
</div>
<?php require("../footer.php"); ?>
