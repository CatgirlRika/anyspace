<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
require_once("../../core/forum/topic.php");
require_once("../../core/forum/post.php");
require_once("../../core/forum/permissions.php");
require_once("../../core/helper.php");

$topicId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $conn->prepare('SELECT id, forum_id, title, locked, sticky FROM forum_topics WHERE id = :id');
$stmt->execute([':id' => $topicId]);
$topic = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$topic) {
    echo 'Topic not found';
    exit;
}

$forumId = (int)$topic['forum_id'];
forum_require_permission($forumId, 'can_view');

$perms = forum_fetch_permissions($forumId);
$can_post = !empty($perms['can_post']);
$can_moderate = !empty($perms['can_moderate']);
$error = '';
$prefill = '';

if (isset($_GET['quote'])) {
    $quote = post_get_quote((int)$_GET['quote']);
    if ($quote) {
        $prefill = "[quote={$quote['username']}]{$quote['body']}[/quote]\n";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    login_check();
    if ($can_moderate && isset($_POST['action'])) {
        forum_require_permission($forumId, 'can_moderate');
        switch ($_POST['action']) {
            case 'lock':
                topic_lock($topicId, $_SESSION['userId']);
                break;
            case 'unlock':
                topic_unlock($topicId, $_SESSION['userId']);
                break;
            case 'sticky':
                topic_sticky($topicId, $_SESSION['userId']);
                break;
            case 'unsticky':
                topic_unsticky($topicId, $_SESSION['userId']);
                break;
            case 'delete_post':
                $pid = (int)($_POST['post_id'] ?? 0);
                post_soft_delete($pid, $_SESSION['userId']);
                break;
            case 'restore_post':
                $pid = (int)($_POST['post_id'] ?? 0);
                post_restore($pid, $_SESSION['userId']);
                break;
        }
        header('Location: post.php?id=' . $topicId);
        exit;
    }

    forum_require_permission($topic['forum_id'], 'can_post');
    $body = $_POST['body'] ?? '';
    if ($body !== '') {
        $result = forum_add_post($topicId, $_SESSION['userId'], $body);
        if (isset($result['error'])) {
            $error = $result['error'];
        } else {
            header('Location: post.php?id=' . $topicId);
            exit;
        }
    } else {
        $error = 'Message cannot be empty.';
    }
}

$posts = forum_get_posts($topicId, $can_moderate);

$pageCSS = "../static/css/forum.css";
?>
<?php require("../header.php"); ?>
<div class="simple-container">
    <h1><?= htmlspecialchars($topic['title']) ?></h1>
    <?php if ($can_moderate): ?>
    <div class="mod-actions">
        <form method="post" style="display:inline">
            <input type="hidden" name="action" value="<?= $topic['locked'] ? 'unlock' : 'lock' ?>">
            <button type="submit"><?= $topic['locked'] ? 'Unlock' : 'Lock' ?></button>
        </form>
        <form method="post" style="display:inline">
            <input type="hidden" name="action" value="<?= $topic['sticky'] ? 'unsticky' : 'sticky' ?>">
            <button type="submit"><?= $topic['sticky'] ? 'Unsticky' : 'Sticky' ?></button>
        </form>
    </div>
    <?php endif; ?>
    <?php foreach ($posts as $post): ?>
        <div class="forum-post">
            <p><strong><?= htmlspecialchars($post['username']) ?></strong> <?= htmlspecialchars($post['created_at']) ?></p>
            <?php if ($post['deleted']): ?>
                <p><em>Post deleted.</em></p>
            <?php else: ?>
                <p><?= nl2br(replaceBBcodes($post['body'])) ?></p>
                <?php if ($can_post): ?>
                    <a href="post.php?id=<?= $topicId ?>&quote=<?= $post['id'] ?>">Quote</a>
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($can_moderate): ?>
                <form method="post" style="display:inline">
                    <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                    <input type="hidden" name="action" value="<?= $post['deleted'] ? 'restore_post' : 'delete_post' ?>">
                    <button type="submit"><?= $post['deleted'] ? 'Restore' : 'Delete' ?></button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <?php if ($error): ?>
        <div class="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!$topic['locked'] && $can_post): ?>
    <form method="post">
        <textarea name="body"><?= htmlspecialchars($prefill) ?></textarea>
        <button type="submit">Post</button>
    </form>
    <?php elseif ($topic['locked']): ?>
        <p>This topic is locked.</p>
    <?php endif; ?>
</div>
<?php require("../footer.php"); ?>
