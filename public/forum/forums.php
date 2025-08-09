<?php
require __DIR__ . "/../../core/conn.php";
require_once __DIR__ . "/../../core/settings.php";
require_once __DIR__ . "/../../core/forum/category.php";
require_once __DIR__ . "/../../core/forum/forum.php";
require_once __DIR__ . "/../../core/forum/permissions.php";

$pageCSS = "../static/css/forum.css";
$categories = forum_get_categories();
?>
<?php require __DIR__ . "/../header.php"; ?>
<div class="simple-container">
    <h1>Forums</h1>
    <?php foreach ($categories as $cat): ?>
        <h2><?= htmlspecialchars($cat['name']) ?></h2>
        <div class="forum-category">
            <?php 
            $forums = forum_get_forums_by_category($cat['id']);
            if (empty($forums)):
            ?>
                <p>No forums in this category yet.</p>
            <?php else: ?>
                <table class="forum-table">
                    <thead>
                        <tr>
                            <th>Forum</th>
                            <th>Topics</th>
                            <th>Posts</th>
                            <th>Last Post</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($forums as $forum): ?>
                            <tr>
                                <td>
                                    <a href="topic.php?id=<?= $forum['id'] ?>">
                                        <strong><?= htmlspecialchars($forum['name']) ?></strong>
                                    </a>
                                    <?php if (!empty($forum['description'])): ?>
                                        <br><small><?= htmlspecialchars($forum['description']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    // Get topic count for this forum
                                    $stmt = $conn->prepare('SELECT COUNT(*) FROM forum_topics WHERE forum_id = :fid');
                                    $stmt->execute([':fid' => $forum['id']]);
                                    echo (int)$stmt->fetchColumn();
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    // Get post count for this forum
                                    $stmt = $conn->prepare('SELECT COUNT(*) FROM forum_posts p JOIN forum_topics t ON p.topic_id = t.id WHERE t.forum_id = :fid AND p.deleted = 0');
                                    $stmt->execute([':fid' => $forum['id']]);
                                    echo (int)$stmt->fetchColumn();
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    // Get last post info
                                    $stmt = $conn->prepare('SELECT p.created_at, u.username FROM forum_posts p JOIN forum_topics t ON p.topic_id = t.id JOIN users u ON p.user_id = u.id WHERE t.forum_id = :fid AND p.deleted = 0 ORDER BY p.created_at DESC LIMIT 1');
                                    $stmt->execute([':fid' => $forum['id']]);
                                    $lastPost = $stmt->fetch(PDO::FETCH_ASSOC);
                                    if ($lastPost):
                                        echo 'by ' . htmlspecialchars($lastPost['username']) . '<br>';
                                        echo '<small>' . htmlspecialchars($lastPost['created_at']) . '</small>';
                                    else:
                                        echo 'No posts yet';
                                    endif;
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
<?php require __DIR__ . "/../footer.php"; ?>
