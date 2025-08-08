<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
require_once("../../core/forum/forum.php");
require_once("../../core/forum/topic.php");
require_once("../../core/forum/permissions.php");
require_once("../../core/forum/subscriptions.php");
require_once("../../core/helper.php");

$forumId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
forum_require_permission($forumId, 'can_view');

$perms = forum_fetch_permissions($forumId);
$can_post = !empty($perms['can_post']);
$can_moderate = !empty($perms['can_moderate']);
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['subscribe_topic'])) {
        login_check();
        $tid = (int)$_POST['subscribe_topic'];
        subscribeTopic($_SESSION['userId'], $tid);
        header('Location: topic.php?id=' . $forumId);
        exit;
    }
    login_check();
    if ($can_moderate && isset($_POST['action'], $_POST['topic_id'])) {
        forum_require_permission($forumId, 'can_moderate');
        $tid = (int)$_POST['topic_id'];
        switch ($_POST['action']) {
            case 'lock':
                topic_lock($tid, $_SESSION['userId']);
                break;
            case 'unlock':
                topic_unlock($tid, $_SESSION['userId']);
                break;
            case 'sticky':
                topic_sticky($tid, $_SESSION['userId']);
                break;
            case 'unsticky':
                topic_unsticky($tid, $_SESSION['userId']);
                break;
        }
        header('Location: topic.php?id=' . $forumId);
        exit;
    }

    forum_require_permission($forumId, 'can_post');
    $title = $_POST['title'] ?? '';
    $body = $_POST['body'] ?? '';
    if ($title !== '' && $body !== '') {
        $result = forum_create_topic($forumId, $_SESSION['userId'], $title, $body);
        if (is_array($result)) {
            if (isset($result['warning'])) {
                $error = $result['warning'] . ': ' . implode(', ', $result['filtered']);
            } else {
                $error = $result['error'] ?? 'Unable to create topic.';
            }
        } else {
            header('Location: post.php?id=' . $result);
            exit;
        }
    } else {
        $error = 'Title and body are required.';
    }
}

$topics = forum_get_topics($forumId);
$subscribed = [];
if (isset($_SESSION['userId'])) {
    $subs = getUserSubscriptions($_SESSION['userId']);
    $subscribed = array_column($subs, 'id');
}

$pageCSS = "../static/css/forum.css";
?>
<?php require("../header.php"); ?>
<div class="simple-container">
    <h1>Topics</h1>
    <table class="forum-table">
        <tr class="forum-header">
            <th></th>
            <th>Topic</th>
            <th>Posts</th>
            <th>Last Post</th>
            <?php if ($can_moderate): ?><th>Actions</th><?php endif; ?>
        </tr>
        <?php foreach ($topics as $t): ?>
        <?php $linkId = $t['moved_to'] ? $t['moved_to'] : $t['id']; ?>
        <tr>
            <td class="icon-cell"><img src="../static/icons/comment.png" alt="Topic icon" loading="lazy"></td>
            <td><a href="post.php?id=<?= $linkId ?>" aria-label="View topic <?= htmlspecialchars($t['title']) ?>" role="link"><?= htmlspecialchars($t['title']) ?></a>
                <?php if (isset($_SESSION['userId'])): ?>
                <form method="post" action="report.php" style="display:inline">
                    <input type="hidden" name="type" value="topic">
                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                    <button type="submit" role="button">Report</button>
                </form>
                <form method="post" style="display:inline">
                    <input type="hidden" name="subscribe_topic" value="<?= $t['id'] ?>">
                    <button type="submit" role="button"><?= in_array($t['id'], $subscribed) ? 'Unsubscribe' : 'Subscribe' ?></button>
                </form>
                <?php endif; ?>
            </td>
            <td><?= (int)$t['posts'] ?></td>
            <td><?= htmlspecialchars($t['last_post']) ?></td>
            <?php if ($can_moderate): ?>
            <td>
                <form method="post" style="display:inline">
                    <input type="hidden" name="topic_id" value="<?= $t['id'] ?>">
                    <input type="hidden" name="action" value="<?= $t['locked'] ? 'unlock' : 'lock' ?>">
                    <button type="submit" aria-label="<?= $t['locked'] ? 'Unlock topic' : 'Lock topic' ?>" role="button"><?= $t['locked'] ? 'Unlock' : 'Lock' ?></button>
                </form>
                <form method="post" style="display:inline">
                    <input type="hidden" name="topic_id" value="<?= $t['id'] ?>">
                    <input type="hidden" name="action" value="<?= $t['sticky'] ? 'unsticky' : 'sticky' ?>">
                    <button type="submit" aria-label="<?= $t['sticky'] ? 'Unsticky topic' : 'Sticky topic' ?>" role="button"><?= $t['sticky'] ? 'Unsticky' : 'Sticky' ?></button>
                </form>
            </td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
    </table>

    <?php if ($error): ?>
        <div class="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($can_post): ?>
    <h2>New Topic</h2>
    <form method="post">
        <input type="text" name="title" placeholder="Title" aria-label="Topic title">
        <textarea name="body" aria-label="Topic message"></textarea>
        <button type="submit" aria-label="Post new topic" role="button">Post</button>
    </form>
    <?php endif; ?>
</div>
<?php require("../footer.php"); ?>
