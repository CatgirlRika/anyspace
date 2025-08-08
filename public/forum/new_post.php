<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
require_once("../../core/forum/post.php");
require_once("../../core/forum/permissions.php");
require_once("../../core/helper.php");

$topicId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $conn->prepare('SELECT forum_id FROM forum_topics WHERE id = :id');
$stmt->execute([':id' => $topicId]);
$forumId = (int)$stmt->fetchColumn();
if (!$forumId) {
    echo 'Topic not found';
    exit;
}

forum_require_permission($forumId, 'can_view');
$perms = forum_fetch_permissions($forumId);
$can_post = !empty($perms['can_post']);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    login_check();
    forum_require_permission($forumId, 'can_post');
    $body = $_POST['body'] ?? '';
    if ($body !== '') {
        $result = forum_add_post($topicId, $_SESSION['userId'], $body);
        if (isset($result['id']) && isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            uploadAttachment($result['id'], $_FILES['attachment']);
        }
        header('Location: post.php?id=' . $topicId);
        exit;
    } else {
        $error = 'Message cannot be empty.';
    }
}

$pageCSS = "../static/css/forum.css";
?>
<?php require("../header.php"); ?>
<div class="simple-container">
    <h1>New Post</h1>
    <?php if ($error): ?><div class="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($can_post): ?>
    <form method="post" enctype="multipart/form-data">
        <textarea name="body" aria-label="Post message"></textarea>
        <input type="file" name="attachment" aria-label="Attachment">
        <button type="submit" aria-label="Submit reply" role="button">Post</button>
    </form>
    <?php else: ?>
        <p>You do not have permission to post.</p>
    <?php endif; ?>
</div>
<?php require("../footer.php"); ?>
