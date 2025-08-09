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
    <form class="forum-search" action="search.php" method="get">
        <input type="text" name="q" placeholder="Search forums..." required>
        <button type="submit" aria-label="Search forums" role="button">Search</button>
    </form>
    
    <?php if (empty($categories)): ?>
        <p>No forum categories have been created yet.</p>
    <?php else: ?>
        <?php foreach ($categories as $cat): ?>
            <div class="forum-category-section">
                <h2><?= htmlspecialchars($cat['name']) ?></h2>
                <?php 
                $forums = forum_get_forums_by_category($cat['id']);
                if (empty($forums)):
                ?>
                    <p>No forums in this category yet.</p>
                <?php else: ?>
                    <div class="forum-list">
                        <?php foreach ($forums as $forum): ?>
                            <div class="forum-item">
                                <h3>
                                    <a href="topic.php?id=<?= $forum['id'] ?>">
                                        <?= htmlspecialchars($forum['name']) ?>
                                    </a>
                                </h3>
                                <?php if (!empty($forum['description'])): ?>
                                    <p class="forum-description"><?= htmlspecialchars($forum['description']) ?></p>
                                <?php endif; ?>
                                <div class="forum-stats">
                                    <?php
                                    // Get topic and post counts
                                    $stmt = $conn->prepare('SELECT COUNT(*) FROM forum_topics WHERE forum_id = :fid');
                                    $stmt->execute([':fid' => $forum['id']]);
                                    $topicCount = (int)$stmt->fetchColumn();
                                    
                                    $stmt = $conn->prepare('SELECT COUNT(*) FROM forum_posts p JOIN forum_topics t ON p.topic_id = t.id WHERE t.forum_id = :fid AND p.deleted = 0');
                                    $stmt->execute([':fid' => $forum['id']]);
                                    $postCount = (int)$stmt->fetchColumn();
                                    ?>
                                    <span><?= $topicCount ?> topics, <?= $postCount ?> posts</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php require __DIR__ . "/../footer.php"; ?>
