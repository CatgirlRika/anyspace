<?php
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline';");
?>
<!DOCTYPE html>
<html>

<head>
    <title>Bulletin Board | <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../static/css/normalize.css">
    <link rel="stylesheet" href="../static/css/header.css">
    <link rel="stylesheet" href="../static/css/base.css">
    <link rel="stylesheet" href="../static/css/my.css">
</head>

<body>
    <div class="master-container">
        <?php require_once("../../core/components/navbar.php"); ?>
        <main>