<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
require_once("../../core/forum/post.php");
require_once("../../core/forum/permissions.php");
require_once("../../core/helper.php");

$postId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $conn->prepare('SELECT p.topic_id, p.user_id, p.body, t.forum_id FROM forum_posts p JOIN forum_topics t ON p.topic_id = t.id WHERE p.id = :id');
$stmt->execute([':id' => $postId]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$post) {
    echo 'Post not found';
    exit;
}

forum_require_permission((int)$post['forum_id'], 'can_view');
$perms = forum_fetch_permissions((int)$post['forum_id']);
$can_moderate = !empty($perms['can_moderate']);
$can_edit = isset($_SESSION['userId']) && ($_SESSION['userId'] == $post['user_id'] || $can_moderate);
if (!$can_edit) {
    echo 'Forbidden';
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    login_check();
    $body = $_POST['body'] ?? '';
    $result = forum_edit_post($postId, (int)$_SESSION['userId'], $body);
    if (isset($result['error'])) {
        $error = $result['error'];
    } else {
        if (!empty($_POST['delete_attachments'])) {
            foreach ((array)$_POST['delete_attachments'] as $aid) {
                forum_delete_attachment((int)$aid);
            }
        }
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            uploadAttachment($postId, $_FILES['attachment']);
        }
        header('Location: post.php?id=' . (int)$post['topic_id']);
        exit;
    }
}

$attachments = forum_get_attachments($postId);
$pageCSS = "../static/css/forum.css";
?>
<?php require("../header.php"); ?>
<div class="simple-container">
    <h1>Edit Post</h1>
    <?php if ($error): ?><div class="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data">
    <?= csrf_token_input(); ?>
        <textarea name="body" aria-label="Post message"><?= htmlspecialchars($post['body']) ?></textarea>
        <input type="file" name="attachment" aria-label="Attachment">
        <?php foreach ($attachments as $att): ?>
        <div>
            <a href="../<?= htmlspecialchars($att['path']) ?>" aria-label="Attachment">Attachment</a>
            <label><input type="checkbox" name="delete_attachments[]" value="<?= $att['id'] ?>"> Delete</label>
        </div>
        <?php endforeach; ?>
        <button type="submit" aria-label="Save post" role="button">Save</button>
    </form>
</div>
<?php require("../footer.php"); ?>
