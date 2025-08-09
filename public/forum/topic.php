<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
require_once("../../core/forum/forum.php");
require_once("../../core/forum/topic.php");
require_once("../../core/forum/permissions.php");
require_once("../../core/forum/subscriptions.php");
require_once("../../core/forum/polls.php");
require_once("../../core/helper.php");

$forumId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle AJAX preview request
if (isset($_GET['preview']) && isset($_GET['topic_id'])) {
    $topicId = (int)$_GET['topic_id'];
    $preview = forum_get_topic_preview($topicId);
    header('Content-Type: application/json');
    echo json_encode($preview);
    exit;
}

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
            $pollQuestion = trim($_POST['poll_question'] ?? '');
            $pollOptions = trim($_POST['poll_options'] ?? '');
            if ($pollQuestion !== '' && $pollOptions !== '') {
                $opts = array_filter(array_map('trim', explode("\n", $pollOptions)));
                if (count($opts) >= 2) {
                    createPoll($result, $pollQuestion, $opts);
                }
            }
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
            <td><a href="post.php?id=<?= $linkId ?>" 
                   aria-label="View topic <?= htmlspecialchars($t['title']) ?>" 
                   role="link" 
                   class="topic-link" 
                   data-topic-id="<?= $t['id'] ?>">
                   <?= htmlspecialchars($t['title']) ?>
                </a>
                <?php if (isset($_SESSION['userId'])): ?>
                <form method="post" action="report.php" style="display:inline">
    <?= csrf_token_input(); ?>
                    <input type="hidden" name="type" value="topic">
                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                    <button type="submit" role="button">Report</button>
                </form>
                <form method="post" style="display:inline">
    <?= csrf_token_input(); ?>
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
    <?= csrf_token_input(); ?>
                    <input type="hidden" name="topic_id" value="<?= $t['id'] ?>">
                    <input type="hidden" name="action" value="<?= $t['locked'] ? 'unlock' : 'lock' ?>">
                    <button type="submit" aria-label="<?= $t['locked'] ? 'Unlock topic' : 'Lock topic' ?>" role="button"><?= $t['locked'] ? 'Unlock' : 'Lock' ?></button>
                </form>
                <form method="post" style="display:inline">
    <?= csrf_token_input(); ?>
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
    <?= csrf_token_input(); ?>
        <input type="text" name="title" placeholder="Title" aria-label="Topic title">
        <textarea name="body" aria-label="Topic message"></textarea>
        <h3>Poll (optional)</h3>
        <input type="text" name="poll_question" placeholder="Poll question" aria-label="Poll question">
        <textarea name="poll_options" placeholder="One option per line" aria-label="Poll options"></textarea>
        <button type="submit" aria-label="Post new topic" role="button">Post</button>
    </form>
    <?php endif; ?>
</div>

<!-- Topic Preview JavaScript -->
<div id="topic-preview" class="topic-preview-popup" style="display: none;"></div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const preview = document.getElementById('topic-preview');
    const topicLinks = document.querySelectorAll('.topic-link');
    let previewTimeout;
    
    topicLinks.forEach(link => {
        link.addEventListener('mouseenter', function() {
            const topicId = this.dataset.topicId;
            clearTimeout(previewTimeout);
            
            previewTimeout = setTimeout(() => {
                fetch(`topic.php?preview=1&topic_id=${topicId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data) {
                            preview.innerHTML = `
                                <div class="preview-header">
                                    <strong>By: ${data.username}</strong>
                                </div>
                                <div class="preview-body">
                                    ${data.body}
                                </div>
                            `;
                            
                            const rect = link.getBoundingClientRect();
                            preview.style.left = (rect.right + 10) + 'px';
                            preview.style.top = rect.top + 'px';
                            preview.style.display = 'block';
                        }
                    })
                    .catch(error => console.error('Preview error:', error));
            }, 500); // 500ms delay
        });
        
        link.addEventListener('mouseleave', function() {
            clearTimeout(previewTimeout);
            preview.style.display = 'none';
        });
    });
    
    // Hide preview when moving mouse over it
    preview.addEventListener('mouseenter', function() {
        this.style.display = 'none';
    });
});
</script>

<?php require("../footer.php"); ?>
