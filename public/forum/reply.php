<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
require_once("../../core/forum.php");
require_once("../../core/forum/topic.php");
require_once("../../core/forum/post.php");

$topicId = isset($_GET['topic_id']) ? (int)$_GET['topic_id'] : 0;
$quotePostId = isset($_GET['quote_post_id']) ? (int)$_GET['quote_post_id'] : 0;

$stmt = $conn->prepare('SELECT id, forum_id, title, locked FROM forum_topics WHERE id = :id');
$stmt->execute([':id' => $topicId]);
$topic = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$topic) {
    echo 'Topic not found';
    exit;
}

forum_require_permission($topic['forum_id'], 'can_post');

$error = '';
$prefill = '';

if ($quotePostId) {
    $quote = post_get_quote($quotePostId);
    if ($quote) {
        $prefill = "[quote={$quote['username']}]{$quote['body']}[/quote]\n";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = $_POST['body'] ?? '';
    $result = forum_add_post($topicId, $_SESSION['userId'], $body);
    if (isset($result['error'])) {
        $error = $result['error'];
    } else {
        header('Location: topic.php?id=' . $topicId);
        exit;
    }
}

$pageCSS = "../static/css/forum.css";
?>
<?php require("../header.php"); ?>
<div class="simple-container">
    <h1>Reply to <?= htmlspecialchars($topic['title']) ?></h1>
    <?php if ($error): ?>
        <div class="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post">
        <textarea name="body"><?= htmlspecialchars($prefill) ?></textarea>
        <button type="submit">Post</button>
    </form>
</div>
<?php require("../footer.php"); ?>

