<?php
require __DIR__ . "/../../core/conn.php";
require_once __DIR__ . "/../../core/config.php"; // Load config before settings
require_once __DIR__ . "/../../core/settings.php";
require_once __DIR__ . "/../../core/forum/category.php";
require_once __DIR__ . "/../../core/forum/forum.php";
require_once __DIR__ . "/../../core/forum/permissions.php";

$pageCSS = "../static/css/forum.css";
$categories = forum_get_categories();
$recent_topics = forum_get_recent_topics(5);
$online_users = forum_get_online_users(15);
$forum_stats = forum_get_total_stats();
?>
<?php require __DIR__ . "/../header.php"; ?>
<div class="simple-container">
    <h1>Forums</h1>
    
    <!-- Search and Navigation -->
    <div class="forum-nav">
        <form class="forum-search" action="search.php" method="get">
            <input type="text" name="q" placeholder="Search forums...">
            <button type="submit" aria-label="Search forums" role="button">Search</button>
        </form>
        <nav class="forum-breadcrumb">
            <a href="/">Home</a> &raquo; <span>Forums</span>
        </nav>
    </div>

    <!-- Forum Stats Overview -->
    <div class="forum-stats-overview">
        <div class="stats-box">
            <strong><?= number_format($forum_stats['total_topics']) ?></strong> Topics
        </div>
        <div class="stats-box">
            <strong><?= number_format($forum_stats['total_posts']) ?></strong> Posts
        </div>
        <div class="stats-box">
            <strong><?= number_format($forum_stats['total_members']) ?></strong> Members
        </div>
        <div class="stats-box">
            Newest: <a href="/profile.php?user=<?= urlencode($forum_stats['newest_member']) ?>"><?= htmlspecialchars($forum_stats['newest_member']) ?></a>
        </div>
    </div>

    <!-- Forum Categories and Forums -->
    <?php foreach ($categories as $cat): ?>
        <div class="forum-category" id="category-<?= $cat['id'] ?>">
            <h2 class="category-title"><?= htmlspecialchars($cat['name']) ?></h2>
            
            <table class="forum-table">
                <thead>
                    <tr>
                        <th class="icon-cell"></th>
                        <th class="forum-name">Forum</th>
                        <th class="stats">Topics</th>
                        <th class="stats">Posts</th>
                        <th class="last-post">Last Post</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $forums = forum_get_forums_with_stats_by_category($cat['id']);
                    if (empty($forums)): 
                    ?>
                        <tr>
                            <td colspan="5" class="no-forums">No forums in this category yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($forums as $forum): ?>
                            <tr>
                                <td class="icon-cell">
                                    <span class="forum-icon">📋</span>
                                </td>
                                <td class="forum-info">
                                    <div class="forum-name">
                                        <a href="forums.php?id=<?= $forum['id'] ?>"><?= htmlspecialchars($forum['name']) ?></a>
                                    </div>
                                    <div class="forum-description"><?= htmlspecialchars($forum['description']) ?></div>
                                </td>
                                <td class="stats"><?= $forum['topic_count'] ?></td>
                                <td class="stats"><?= $forum['post_count'] ?></td>
                                <td class="last-post">
                                    <?php if ($forum['last_post']): ?>
                                        <div class="last-post-info">
                                            <a href="topic.php?id=<?= $forum['last_post']['topic_id'] ?>" title="<?= htmlspecialchars($forum['last_post']['topic_title']) ?>">
                                                <?= htmlspecialchars(strlen($forum['last_post']['topic_title']) > 25 ? substr($forum['last_post']['topic_title'], 0, 25) . '...' : $forum['last_post']['topic_title']) ?>
                                            </a>
                                            <br>
                                            <small>by <a href="/profile.php?user=<?= urlencode($forum['last_post']['username']) ?>"><?= htmlspecialchars($forum['last_post']['username']) ?></a></small>
                                            <br>
                                            <small><?= date('M j, Y g:i A', strtotime($forum['last_post']['created_at'])) ?></small>
                                        </div>
                                    <?php else: ?>
                                        <span class="no-posts">No posts yet</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>

    <!-- Sidebar Content -->
    <div class="forum-sidebar">
        <!-- Recent Activity -->
        <?php if (!empty($recent_topics)): ?>
            <div class="recent-activity">
                <h3>Recent Forum Activity</h3>
                <ul class="recent-list">
                    <?php foreach ($recent_topics as $topic): ?>
                        <li>
                            <a href="topic.php?id=<?= $topic['id'] ?>"><?= htmlspecialchars($topic['title']) ?></a>
                            in <a href="forums.php?id=<?= $topic['forum_id'] ?>"><?= htmlspecialchars($topic['forum_name']) ?></a>
                            <br>
                            <small>by <a href="/profile.php?user=<?= urlencode($topic['username']) ?>"><?= htmlspecialchars($topic['username']) ?></a>
                            on <?= date('M j, Y g:i A', strtotime($topic['created_at'])) ?></small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Who's Online -->
        <?php if (!empty($online_users)): ?>
            <div class="online-users">
                <h3>Who's Online</h3>
                <div class="online-count">
                    <strong><?= count($online_users) ?></strong> users active in the last 15 minutes
                </div>
                <div class="online-list">
                    <?php foreach ($online_users as $index => $user): ?>
                        <a href="/profile.php?user=<?= urlencode($user['username']) ?>"><?= htmlspecialchars($user['username']) ?></a><?= $index < count($online_users) - 1 ? ', ' : '' ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . "/../footer.php"; ?>
