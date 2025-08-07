<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
require_once("../../core/forum/forum.php");
require_once("../../core/forum/topic.php");
require_once("../../core/forum/permissions.php");
require_once("../../core/helper.php");

$forumId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
forum_require_permission($forumId, 'can_view');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    login_check();
    forum_require_permission($forumId, 'can_post');
    $title = $_POST['title'] ?? '';
    $body = $_POST['body'] ?? '';
    if ($title !== '' && $body !== '') {
        $topicId = forum_create_topic($forumId, $_SESSION['userId'], $title, $body);
        header('Location: post.php?id=' . $topicId);
        exit;
    } else {
        $error = 'Title and body are required.';
    }
}

$topics = forum_get_topics($forumId);

$pageCSS = "../static/css/forum.css";
?>
<?php require("../header.php"); ?>
<div class="simple-container">
    <h1>Topics</h1>
    <table class="forum-table">
        <tr>
            <th>Topic</th>
            <th>Posts</th>
            <th>Last Post</th>
        </tr>
        <?php foreach ($topics as $t): ?>
        <?php $linkId = $t['moved_to'] ? $t['moved_to'] : $t['id']; ?>
        <tr>
            <td><a href="post.php?id=<?= $linkId ?>"><?= htmlspecialchars($t['title']) ?></a></td>
            <td><?= (int)$t['posts'] ?></td>
            <td><?= htmlspecialchars($t['last_post']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <?php if ($error): ?>
        <div class="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php $perms = forum_fetch_permissions($forumId); if ($perms['can_post']): ?>
    <h2>New Topic</h2>
    <form method="post">
        <input type="text" name="title" placeholder="Title">
        <textarea name="body"></textarea>
        <button type="submit">Post</button>
    </form>
    <?php endif; ?>
</div>
<?php require("../footer.php"); ?>
