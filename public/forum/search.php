<?php
require __DIR__ . "/../../core/conn.php";
require_once __DIR__ . "/../../core/settings.php";
require_once __DIR__ . "/../../core/forum/forum.php";

$pageCSS = "../static/css/forum.css";

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$results = [];
if ($query !== '') {
    $results = searchTopics($query);
}
?>
<?php require __DIR__ . "/../header.php"; ?>
<div class="simple-container">
    <h1>Forum Search</h1>
    <form class="forum-search" action="search.php" method="get">
        <input type="text" name="q" value="<?= htmlspecialchars($query) ?>">
        <button type="submit">Search</button>
    </form>
    <?php if ($query !== ''): ?>
        <?php if (!empty($results)): ?>
            <ul>
                <?php foreach ($results as $topic): ?>
                    <li><a href="topic.php?id=<?= $topic['id'] ?>"><?= htmlspecialchars($topic['title']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No results found.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php require __DIR__ . "/../footer.php"; ?>
