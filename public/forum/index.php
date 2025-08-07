<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
require_once("../../core/forum.php");

$forumId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
forum_require_permission($forumId, 'can_view');

$userSettings = ['background_image_url' => '', 'background_color' => '', 'text_color' => ''];
if (isset($_SESSION['userId'])) {
    $userSettings = forum_get_user_settings($_SESSION['userId']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    forum_require_permission($forumId, 'can_post');
    // process post submission here
}

if (isset($_GET['moderate'])) {
    forum_require_permission($forumId, 'can_moderate');
    // process moderation actions here
}

$stmt = $conn->prepare('SELECT t.id, t.title, t.locked, t.sticky, t.moved_to, (SELECT COUNT(*) FROM forum_posts p WHERE p.topic_id = t.id) AS posts, (SELECT MAX(created_at) FROM forum_posts p WHERE p.topic_id = t.id) AS last_post FROM forum_topics t WHERE t.forum_id = :fid ORDER BY t.sticky DESC, t.id DESC');
$stmt->execute([':fid' => $forumId]);
$threads = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageCSS = "../static/css/forum.css";
?>
<?php require("../header.php"); ?>

<?php if (!empty($userSettings['background_image_url']) || !empty($userSettings['background_color']) || !empty($userSettings['text_color'])): ?>
<style>
body {
<?php if (!empty($userSettings['background_image_url'])): ?>
    background-image: url('<?= htmlspecialchars($userSettings['background_image_url'], ENT_QUOTES) ?>');
<?php endif; ?>
<?php if (!empty($userSettings['background_color'])): ?>
    background-color: <?= htmlspecialchars($userSettings['background_color'], ENT_QUOTES) ?>;
<?php endif; ?>
<?php if (!empty($userSettings['text_color'])): ?>
    color: <?= htmlspecialchars($userSettings['text_color'], ENT_QUOTES) ?>;
<?php endif; ?>
}
</style>
<?php endif; ?>

<div class="simple-container">
    <h1>Forums</h1>
    <?php if (isset($_SESSION['userId'])): ?>
    <p><a href="settings.php">Customize Forum</a></p>
    <?php endif; ?>
    <table class="forum-table">
        <tr>
            <th></th>
            <th>Thread</th>
            <th>Posts</th>
            <th>Last Post</th>
        </tr>
        <?php foreach ($threads as $i => $t): ?>
        <?php
            $title = $t['moved_to'] ? 'Moved: ' . $t['title'] : $t['title'];
            $linkId = $t['moved_to'] ? $t['moved_to'] : $t['id'];
        ?>
        <tr<?php if ($i % 2 === 1) echo ' class="forum-row-alt"'; ?>>
            <td>
                <?php if ($t['locked']): ?>
                    <img src="../static/icons/locked.gif" alt="Locked">
                <?php else: ?>
                    <img src="../static/icons/new-post.gif" alt="New Post">
                <?php endif; ?>
            </td>
            <td><a href="topic.php?id=<?= $linkId ?>"><?= htmlspecialchars($title) ?></a></td>
            <td><?= (int)$t['posts'] ?></td>
            <td><?= htmlspecialchars($t['last_post']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php require("../footer.php"); ?>
