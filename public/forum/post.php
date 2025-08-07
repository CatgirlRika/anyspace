<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
require_once("../../core/forum/topic.php");
require_once("../../core/forum/post.php");
require_once("../../core/forum/permissions.php");
require_once("../../core/helper.php");

$topicId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $conn->prepare('SELECT id, forum_id, title, locked FROM forum_topics WHERE id = :id');
$stmt->execute([':id' => $topicId]);
$topic = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$topic) {
    echo 'Topic not found';
    exit;
}

$perms = forum_fetch_permissions($topic['forum_id']);
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
