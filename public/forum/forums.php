<?php
require __DIR__ . "/../../core/conn.php";
require_once __DIR__ . "/../../core/config.php";
require_once __DIR__ . "/../../core/settings.php";
require_once __DIR__ . "/../../core/forum/category.php";
require_once __DIR__ . "/../../core/forum/forum.php";
require_once __DIR__ . "/../../core/forum/topic.php";
require_once __DIR__ . "/../../core/forum/permissions.php";

$pageCSS = "../static/css/forum.css";

// Get forum ID from URL
$forum_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$forum_id) {
    header('Location: index.php');
    exit;
}

// Get forum info
$forum = forum_get_forum($forum_id);
if (!$forum) {
    header('Location: index.php');
    exit;
}

// Get category info for breadcrumb
$category = forum_get_category($forum['category_id']);

// Get topics for this forum
function forum_get_topics_by_forum(int $forum_id, int $limit = 50): array {
    global $conn;
    $stmt = $conn->prepare('SELECT t.id, t.title, t.locked, t.sticky, 
                           COUNT(p.id) as post_count,
                           MAX(p.created_at) as last_post_time,
                           u1.username as author,
                           u2.username as last_poster
                           FROM forum_topics t
                           LEFT JOIN forum_posts p ON t.id = p.topic_id AND p.deleted = 0
                           LEFT JOIN forum_posts first_post ON t.id = first_post.topic_id 
                               AND first_post.id = (SELECT MIN(id) FROM forum_posts WHERE topic_id = t.id AND deleted = 0)
                           LEFT JOIN users u1 ON first_post.user_id = u1.id
                           LEFT JOIN forum_posts last_post ON t.id = last_post.topic_id 
                               AND last_post.created_at = (SELECT MAX(created_at) FROM forum_posts WHERE topic_id = t.id AND deleted = 0)
                           LEFT JOIN users u2 ON last_post.user_id = u2.id
                           WHERE t.forum_id = :fid
                           GROUP BY t.id, t.title, t.locked, t.sticky
                           ORDER BY t.sticky DESC, last_post_time DESC
                           LIMIT :limit');
    $stmt->execute([':fid' => $forum_id, ':limit' => $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$topics = forum_get_topics_by_forum($forum_id);
?>
<?php require __DIR__ . "/../header.php"; ?>
<div class="simple-container">
    <!-- Breadcrumb Navigation -->
    <nav class="forum-breadcrumb">
        <a href="/">Home</a> &raquo; 
        <a href="index.php">Forums</a> &raquo; 
        <a href="index.php#category-<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></a> &raquo; 
        <span><?= htmlspecialchars($forum['name']) ?></span>
    </nav>

    <h1><?= htmlspecialchars($forum['name']) ?></h1>
    
    <?php if ($forum['description']): ?>
        <div class="forum-description-block">
            <?= htmlspecialchars($forum['description']) ?>
        </div>
    <?php endif; ?>

    <!-- Forum Actions -->
    <div class="forum-actions">
        <a href="new_post.php?forum_id=<?= $forum_id ?>" class="btn btn-primary">New Topic</a>
        <form class="forum-search" action="search.php" method="get" style="display: inline;">
            <input type="hidden" name="forum_id" value="<?= $forum_id ?>">
            <input type="text" name="q" placeholder="Search this forum..." size="20">
            <button type="submit">Search</button>
        </form>
    </div>

    <!-- Topics Table -->
    <table class="forum-table">
        <thead>
            <tr>
                <th class="icon-cell"></th>
                <th class="topic-title">Topic</th>
                <th class="stats">Replies</th>
                <th class="topic-author">Author</th>
                <th class="last-post">Last Post</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($topics)): ?>
                <tr>
                    <td colspan="5" class="no-topics">No topics in this forum yet. <a href="new_post.php?forum_id=<?= $forum_id ?>">Start the first discussion!</a></td>
                </tr>
            <?php else: ?>
                <?php foreach ($topics as $topic): ?>
                    <tr class="<?= $topic['sticky'] ? 'sticky-topic' : '' ?>">
                        <td class="icon-cell">
                            <?php if ($topic['sticky']): ?>
                                📌
                            <?php elseif ($topic['locked']): ?>
                                🔒
                            <?php else: ?>
                                💬
                            <?php endif; ?>
                        </td>
                        <td class="topic-info">
                            <div class="topic-title">
                                <a href="topic.php?id=<?= $topic['id'] ?>"><?= htmlspecialchars($topic['title']) ?></a>
                                <?php if ($topic['sticky']): ?>
                                    <span class="topic-label sticky">Sticky</span>
                                <?php endif; ?>
                                <?php if ($topic['locked']): ?>
                                    <span class="topic-label locked">Locked</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="stats"><?= max(0, $topic['post_count'] - 1) ?></td>
                        <td class="topic-author">
                            <?php if ($topic['author']): ?>
                                <a href="/profile.php?user=<?= urlencode($topic['author']) ?>"><?= htmlspecialchars($topic['author']) ?></a>
                            <?php else: ?>
                                <span class="deleted-user">Deleted User</span>
                            <?php endif; ?>
                        </td>
                        <td class="last-post">
                            <?php if ($topic['last_post_time'] && $topic['last_poster']): ?>
                                <div class="last-post-info">
                                    <small><?= date('M j, Y g:i A', strtotime($topic['last_post_time'])) ?></small>
                                    <br>
                                    <small>by <a href="/profile.php?user=<?= urlencode($topic['last_poster']) ?>"><?= htmlspecialchars($topic['last_poster']) ?></a></small>
                                </div>
                            <?php else: ?>
                                <span class="no-posts">No posts</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Back to Category -->
    <div class="forum-navigation">
        <a href="index.php" class="btn btn-secondary">&larr; Back to Forum Index</a>
    </div>
</div>
<?php require __DIR__ . "/../footer.php"; ?>
