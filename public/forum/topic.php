<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
require_once("../../core/forum.php");
require_once("../../core/forum/topic.php");
require_once("../../core/forum/post.php");

$topicId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $conn->prepare('SELECT id, forum_id, title, locked FROM forum_topics WHERE id = :id');
$stmt->execute([':id' => $topicId]);
$topic = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$topic) {
    echo 'Topic not found';
    exit;
}

$perms = forum_fetch_permissions($topic['forum_id']);
$can_moderate = !empty($perms['can_moderate']);
$can_post = !empty($perms['can_post']);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['lock']) && $can_moderate) {
        topic_lock($topicId, $_SESSION['userId']);
        header('Location: topic.php?id=' . $topicId);
        exit;
    }
    if (isset($_POST['unlock']) && $can_moderate) {
        topic_unlock($topicId, $_SESSION['userId']);
        header('Location: topic.php?id=' . $topicId);
        exit;
    }
    if (isset($_POST['body'])) {
        forum_require_permission($topic['forum_id'], 'can_post');
        $result = forum_add_post($topicId, $_SESSION['userId'], $_POST['body']);
        if (isset($result['error'])) {
            $error = $result['error'];
        } else {
            header('Location: topic.php?id=' . $topicId);
            exit;
        }
    }
}

$pageCSS = "../static/css/forum.css";
?>
<?php require("../header.php"); ?>
<div class="simple-container">
    <h1><?= htmlspecialchars($topic['title']) ?></h1>

    <?php if ($can_moderate): ?>
    <form method="post" style="display:inline">
        <?php if ($topic['locked']): ?>
            <button type="submit" name="unlock">Unlock Topic</button>
        <?php else: ?>
            <button type="submit" name="lock">Lock Topic</button>
        <?php endif; ?>
    </form>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!$topic['locked'] && $can_post): ?>
    <form method="post">
        <textarea name="body"></textarea>
        <button type="submit">Post</button>
    </form>
    <?php elseif ($topic['locked']): ?>
        <p>This topic is locked.</p>
    <?php endif; ?>
</div>
<?php require("../footer.php"); ?>
