<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
require_once("../../core/forum/topic.php");
require_once("../../core/forum/post.php");
require_once("../../core/forum/permissions.php");
require_once("../../core/forum/reactions.php");
require_once("../../core/forum/polls.php");
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

$pollStmt = $conn->prepare('SELECT id, question, options FROM polls WHERE topic_id = :tid');
$pollStmt->execute([':tid' => $topicId]);
$poll = $pollStmt->fetch(PDO::FETCH_ASSOC);
$userVote = false;
if ($poll && isset($_SESSION['userId'])) {
    $voteCheck = $conn->prepare('SELECT option_index FROM poll_votes WHERE poll_id = :pid AND user_id = :uid');
    $voteCheck->execute([':pid' => $poll['id'], ':uid' => $_SESSION['userId']]);
    $userVote = $voteCheck->fetchColumn();
}

$perms = forum_fetch_permissions($forumId);
$can_post = !empty($perms['can_post']);
$can_moderate = !empty($perms['can_moderate']);
$error = '';
$prefill = '';
$availableReactions = ['like','love','laugh'];

if (isset($_GET['quote'])) {
    $quote = post_get_quote((int)$_GET['quote']);
    if ($quote) {
        $prefill = "[quote={$quote['username']}]{$quote['body']}[/quote]\n";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    login_check();
    if ($poll && isset($_POST['poll_vote']) && $userVote === false) {
        votePoll($poll['id'], $_SESSION['userId'], (int)$_POST['poll_vote']);
        header('Location: post.php?id=' . $topicId);
        exit;
    }
    if (isset($_POST['reaction_action'])) {
        $pid = (int)($_POST['post_id'] ?? 0);
        if ($_POST['reaction_action'] === 'add') {
            $reaction = $_POST['reaction'] ?? '';
            if (in_array($reaction, $availableReactions, true)) {
                forum_add_reaction($pid, $_SESSION['userId'], $reaction);
            }
        } elseif ($_POST['reaction_action'] === 'remove') {
            forum_remove_reaction($pid, $_SESSION['userId']);
        }
        header('Location: post.php?id=' . $topicId);
        exit;
    }
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
    <?= csrf_token_input(); ?>
            <input type="hidden" name="action" value="<?= $topic['locked'] ? 'unlock' : 'lock' ?>">
            <button type="submit" aria-label="<?= $topic['locked'] ? 'Unlock topic' : 'Lock topic' ?>" role="button"><?= $topic['locked'] ? 'Unlock' : 'Lock' ?></button>
        </form>
        <form method="post" style="display:inline">
    <?= csrf_token_input(); ?>
            <input type="hidden" name="action" value="<?= $topic['sticky'] ? 'unsticky' : 'sticky' ?>">
            <button type="submit" aria-label="<?= $topic['sticky'] ? 'Unsticky topic' : 'Sticky topic' ?>" role="button"><?= $topic['sticky'] ? 'Unsticky' : 'Sticky' ?></button>
        </form>
    </div>
    <?php endif; ?>
    <?php if ($poll): ?>
    <div class="poll">
        <h2><?= htmlspecialchars($poll['question']) ?></h2>
        <?php if ($userVote === false && isset($_SESSION['userId'])): ?>
        <form method="post">
    <?= csrf_token_input(); ?>
            <?php $opts = json_decode($poll['options'], true) ?: []; foreach ($opts as $i => $opt): ?>
            <div><label><input type="radio" name="poll_vote" value="<?= $i ?>"> <?= htmlspecialchars($opt) ?></label></div>
            <?php endforeach; ?>
            <button type="submit" role="button">Vote</button>
        </form>
        <?php else: ?>
        <?php $results = getPollResults($poll['id']); foreach ($results as $res): ?>
            <div><?= htmlspecialchars($res['option']) ?> - <?= (int)$res['votes'] ?></div>
        <?php endforeach; ?>
        <?php endif; ?>
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
<?php $reactionCounts = forum_get_reaction_counts($post['id']);
      $userReaction = isset($_SESSION['userId']) ? forum_get_user_reaction($post['id'], $_SESSION['userId']) : null; ?>
<div class="reactions">
<?php foreach ($availableReactions as $react): $count = $reactionCounts[$react] ?? 0; ?>
    <form method="post" style="display:inline">
    <?= csrf_token_input(); ?>
        <input type="hidden" name="reaction_action" value="add">
        <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
        <input type="hidden" name="reaction" value="<?= $react ?>">
        <button type="submit" role="button"><?= ucfirst($react) ?> (<?= $count ?>)</button>
    </form>
<?php endforeach; ?>
<?php if ($userReaction): ?>
    <form method="post" style="display:inline">
    <?= csrf_token_input(); ?>
        <input type="hidden" name="reaction_action" value="remove">
        <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
        <button type="submit" role="button">Remove (<?= htmlspecialchars($userReaction) ?>)</button>
    </form>
<?php endif; ?>
</div>
                    <?php if ($can_post): ?>
                        <a href="post.php?id=<?= $topicId ?>&quote=<?= $post['id'] ?>" aria-label="Quote this post" role="link">Quote</a>
                    <?php endif; ?>
                      <?php if (isset($_SESSION['userId'])): ?>
                      <form method="post" action="report.php" style="display:inline">
    <?= csrf_token_input(); ?>
                          <input type="hidden" name="type" value="post">
                          <input type="hidden" name="id" value="<?= $post['id'] ?>">
                          <button type="submit" role="button">Report</button>
                      </form>
                      <?php endif; ?>
                <?php endif; ?>
                <?php if ($can_moderate): ?>
                    <form method="post" style="display:inline">
    <?= csrf_token_input(); ?>
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
    <div class="quick-reply-section">
        <h3>Quick Reply</h3>
        <form method="post" class="quick-reply-form">
        <?= csrf_token_input(); ?>
            <div class="reply-controls">
                <textarea name="body" aria-label="Post message" placeholder="Write your reply..." rows="5"><?= htmlspecialchars($prefill) ?></textarea>
                <div class="reply-buttons">
                    <button type="submit" aria-label="Submit reply" role="button" class="reply-submit">Post Reply</button>
                    <button type="button" onclick="document.querySelector('[name=body]').value=''" class="reply-clear">Clear</button>
                </div>
            </div>
        </form>
    </div>
    <?php elseif ($topic['locked']): ?>
        <p>This topic is locked.</p>
    <?php endif; ?>
</div>
<?php require("../footer.php"); ?>
