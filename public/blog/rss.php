<?php
require __DIR__ . '/../../core/conn.php';
require __DIR__ . '/../../core/settings.php';
require __DIR__ . '/../../core/site/blog.php';

header('Content-Type: application/rss+xml; charset=UTF-8');

$entries = fetchAllBlogEntries(20);

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<rss version="2.0">
  <channel>
    <title><?= SITE_NAME ?> Blog</title>
    <link>https://<?= DOMAIN_NAME ?>/blog/</link>
    <description>Latest blog entries from <?= SITE_NAME ?></description>
<?php foreach ($entries as $entry): ?>
    <item>
      <title><?= htmlspecialchars($entry['title']) ?></title>
      <link>https://<?= DOMAIN_NAME ?>/blog/entry.php?id=<?= $entry['id'] ?></link>
      <pubDate><?= date(DATE_RSS, strtotime($entry['date'])) ?></pubDate>
      <guid>https://<?= DOMAIN_NAME ?>/blog/entry.php?id=<?= $entry['id'] ?></guid>
    </item>
<?php endforeach; ?>
  </channel>
</rss>

