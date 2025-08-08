<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
require_once("../../core/forum/topic.php");
require_once("../../core/forum/post.php");
require_once("../../core/forum/permissions.php");
require_once("../../core/helper.php");
require_once("../../core/site/user.php");

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
        } elseif (isset($result['warning'])) {
            $error = $result['warning'] . ': ' . implode(', ', $result['filtered']);
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
            <button type="submit" aria-label="<?= $topic['locked'] ? 'Unlock topic' : 'Lock topic' ?>" role="button"><?= $topic['locked'] ? 'Unlock' : 'Lock' ?></button>
        </form>
        <form method="post" style="display:inline">
            <input type="hidden" name="action" value="<?= $topic['sticky'] ? 'unsticky' : 'sticky' ?>">
            <button type="submit" aria-label="<?= $topic['sticky'] ? 'Unsticky topic' : 'Sticky topic' ?>" role="button"><?= $topic['sticky'] ? 'Unsticky' : 'Sticky' ?></button>
        </form>
    </div>
    <?php endif; ?>
    <table class="post-table">
    <?php foreach ($posts as $post): ?>
        <?php $user = fetchUserInfo($post['user_id']);
              $profileLink = '../profile.php?id=' . (int)$user['id'];
              $avatarPath = '../media/pfp/' . $user['pfp'];
              $badge = '';
              if (!empty($user['lastactive'])) {
                  $lastActive = strtotime($user['lastactive']);
                  if ($lastActive !== false && (time() - $lastActive) <= 300) {
                      $badge = '<img class="online-badge" src="../static/img/green_person.png" alt="Online Now" loading="lazy">';
                  }
              }
        ?>
        <tr class="forum-post">
            <td class="avatar-cell">
                <div class="avatar-wrapper">
                    <a href="<?= $profileLink ?>" aria-label="View <?= htmlspecialchars($user['username']) ?>'s profile" role="link"><img class="avatar" src="<?= htmlspecialchars($avatarPath) ?>" alt="<?= htmlspecialchars($user['username']) ?>'s avatar" loading="lazy"></a>
                    <?= $badge ?>
                </div>
            </td>
            <td class="post-body">
                <p><strong><a class="username" href="<?= $profileLink ?>" aria-label="View <?= htmlspecialchars($user['username']) ?>'s profile" role="link"><?= htmlspecialchars($user['username']) ?></a></strong> <?= htmlspecialchars($post['created_at']) ?></p>
                <?php if ($post['deleted']): ?>
                    <p><em>Post deleted.</em></p>
                <?php else: ?>
                    <p><?= nl2br(replaceBBcodes($post['body'])) ?></p>
<?php $attachments = forum_get_attachments($post['id']); if ($attachments): ?>
<ul class="attachments">
<?php foreach ($attachments as $att): ?>
    <li><a href="../<?= htmlspecialchars($att['path']) ?>" aria-label="Attachment">Attachment</a></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
                    <?php if ($can_post): ?>
                        <a href="post.php?id=<?= $topicId ?>&quote=<?= $post['id'] ?>" aria-label="Quote this post" role="link">Quote</a>
                    <?php endif; ?>
                      <?php if (isset($_SESSION['userId'])): ?>
                      <form method="post" action="report.php" style="display:inline">
                          <input type="hidden" name="type" value="post">
                          <input type="hidden" name="id" value="<?= $post['id'] ?>">
                          <button type="submit" role="button">Report</button>
                      </form>
                      <?php endif; ?>
                <?php endif; ?>
                <?php if ($can_moderate): ?>
                    <form method="post" style="display:inline">
                        <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                        <input type="hidden" name="action" value="<?= $post['deleted'] ? 'restore_post' : 'delete_post' ?>">
                        <button type="submit" aria-label="<?= $post['deleted'] ? 'Restore post' : 'Delete post' ?>" role="button"><?= $post['deleted'] ? 'Restore' : 'Delete' ?></button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </table>

    <?php if ($error): ?>
        <div class="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!$topic['locked'] && $can_post): ?>
    <form method="post">
        <textarea name="body" aria-label="Post message"><?= htmlspecialchars($prefill) ?></textarea>
        <button type="submit" aria-label="Submit reply" role="button">Post</button>
    </form>
    <?php elseif ($topic['locked']): ?>
        <p>This topic is locked.</p>
    <?php endif; ?>
</div>
<?php require("../footer.php"); ?>
