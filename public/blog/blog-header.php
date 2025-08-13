<?php
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline';");
?>
<!DOCTYPE html>
<html>

<head>
    <title><?= SITE_NAME ?> | Blog</title>
    <link rel="stylesheet" href="/static/css/normalize.min.css">
    <link rel="stylesheet" href="/static/css/style.min.css">
    <?= $rssLinkTag ?? '<link rel="alternate" type="application/rss+xml" title="Blog RSS" href="/blog/rss.php">' ?>
</head>

<body>
    <div class="master-container">
        <?php require_once("../../core/components/navbar.php"); ?>
        <main>