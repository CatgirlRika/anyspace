<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
require_once("../../core/site/user.php");

// Fetch group information
$stmt = $conn->prepare("SELECT * FROM `groups` WHERE id = ?");
$stmt->execute([(int)($_GET['id'] ?? 0)]);
$group = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$group) {
    header("Location: groups.php?msg=group_not_found");
    exit;
}
$name   = $group['name'];
$desc   = $group['description'];
$author = $group['author'];
$date   = $group['date'];

// Determine membership
$membership = null;
if (isset($_SESSION['user'])) {
    $stmt = $conn->prepare("SELECT * FROM `group_memberships` WHERE group_id = ? AND username = ?");
    $stmt->execute([(int)$group['id'], $_SESSION['user']]);
    $membership = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle comment submission
if (isset($_SESSION['user']) && isset($_POST['comment'])) {
    $stmt = $conn->prepare("INSERT INTO `groupcomments` (toid, author, text, date) VALUES (?, ?, ?, CURRENT_TIMESTAMP)");
    $unprocessedText = replaceBBcodes($_POST['comment']);
    $text = validateContentHTML($unprocessedText);
    $stmt->execute([(int)$group['id'], $_SESSION['user'], $text]);
}

// Handle event creation for members
if (isset($_SESSION['user']) && $membership && isset($_POST['create_event'])) {
    $title = trim($_POST['event_title'] ?? '');
    $description = trim($_POST['event_description'] ?? '');
    $eventDate = $_POST['event_date'] ?? '';
    if ($title !== '' && $description !== '' && $eventDate !== '') {
        $title = strip_tags($title);
        $description = validateContentHTML($description);
        $stmt = $conn->prepare("INSERT INTO `group_events` (group_id, title, description, event_date, created_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([(int)$group['id'], $title, $description, $eventDate, $_SESSION['user']]);
    }
}
?>
<!DOCTYPE html>
<html>
    <head>
        <link rel="stylesheet" href="../static/css/header.css">
        <link rel="stylesheet" href="../static/css/base.css">
    </head>
    <body>
        <?php require("../../core/components/navbar.php"); ?>
        <div class="container">
            <h1><?= htmlspecialchars($name); ?></h1>
            <?php
                echo "Owner: <a href='../profile.php?id=" . getID($author, $conn) . "'>" . htmlspecialchars($author) . "</a>";
                if (isset($_SESSION['user'])) {
                    if (!$membership) { ?>
                        | <form method="post" action="joingroup.php" style="display:inline;">
                            <?= csrf_token_input(); ?>
                            <input type="hidden" name="group_id" value="<?= (int)$group['id']; ?>">
                            <button type="submit" style="background: #007bff; color: white; padding: 5px 10px; border: none; border-radius: 3px;">Join Group</button>
                        </form>
                    <?php } else { ?>
                        | <span style="background: #28a745; color: white; padding: 5px 10px; border-radius: 3px;">Member</span>
                    <?php }
                }
                if (isset($_GET['msg'])) {
                    if ($_GET['msg'] === 'joined') {
                        echo "<div class='success' style='margin: 10px 0;'>Successfully joined the group!</div>";
                    } elseif ($_GET['msg'] === 'already_member') {
                        echo "<div class='info' style='margin: 10px 0;'>You are already a member of this group.</div>";
                    }
                }
            ?>
            <pre><?= $desc; ?></pre>

            <!-- Events Section -->
            <div class="info">
                <center>Upcoming Events</center>
            </div>
            <?php if (isset($_SESSION['user']) && $membership) { ?>
                <details>
                    <summary>Create New Event</summary>
                    <form method="post">
                        <?= csrf_token_input(); ?>
                        <input required placeholder="Event Title" size="60" type="text" name="event_title" maxlength="255"><br>
                        <textarea required rows="3" cols="60" placeholder="Event Description" name="event_description"></textarea><br>
                        <input required type="datetime-local" name="event_date"><br>
                        <input name="create_event" type="submit" value="Create Event">
                    </form>
                </details>
                <br>
            <?php }
                $stmt = $conn->prepare("SELECT * FROM `group_events` WHERE group_id = ? ORDER BY event_date ASC");
                $stmt->execute([(int)$group['id']]);
                $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if ($events) {
                    foreach ($events as $event) {
                        $event_date = new DateTime($event['event_date']);
                        $now = new DateTime();
                        $is_past = $event_date < $now;

                        echo "<div class='event-item' style='border: 1px solid #ccc; margin: 10px 0; padding: 10px; " . ($is_past ? "opacity: 0.6;" : "") . "'>";
                        echo "<h4>" . htmlspecialchars($event['title']) . " " . ($is_past ? "(Past)" : "") . "</h4>";
                        echo "<p><strong>Date:</strong> " . $event_date->format('M j, Y g:i A') . "</p>";
                        echo "<p><strong>Description:</strong> " . htmlspecialchars($event['description']) . "</p>";
                        echo "<p><small>Created by: <a href='../profile.php?id=" . getID($event['created_by'], $conn) . "'>" . htmlspecialchars($event['created_by']) . "</a></small></p>";
                        echo "</div>";
                    }
                } else {
                    echo "<p>No events scheduled. Create one above!</p>";
                }
            ?>

            <!-- Members Section -->
            <div class="info" style="margin-top: 20px;">
                <center>Group Members</center>
            </div>
            <?php
                $stmt = $conn->prepare("SELECT username, joined_at, role FROM `group_memberships` WHERE group_id = ? ORDER BY joined_at ASC");
                $stmt->execute([(int)$group['id']]);
                $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if ($members) {
                    echo "<div style='margin: 10px 0;'>";
                    echo "<strong>Members (" . count($members) . "):</strong><br>";
                    foreach ($members as $member) {
                        $user_id = getID($member['username'], $conn);
                        $pfp = getPFP($member['username'], $conn);
                        echo "<div style='display: inline-block; margin: 10px; text-align: center; width: 80px;'>";
                        echo "<a href='../profile.php?id=" . $user_id . "'>";
                        echo "<img src='../media/pfp/" . htmlspecialchars($pfp) . "' alt='Profile Picture' style='width: 60px; height: 60px; border-radius: 50%; object-fit: cover;'><br>";
                        echo "<small>" . htmlspecialchars($member['username']) . "</small>";
                        echo "</a>";
                        echo "</div>";
                    }
                    echo "</div>";
                } else {
                    echo "<p>No members yet. Be the first to join!</p>";
                }
            ?>

            <div class="info" style="margin-top: 20px;">
                <center>Comments</center>
            </div>
            <?php if (isset($_SESSION['user'])) { ?>
            <form method="post">
                <?= csrf_token_input(); ?>
                <textarea required rows="5" cols="80" placeholder="Comment" name="comment" maxlength="500"></textarea><br>
                <input name="submit" type="submit" value="Post"> <small>max limit: 500 characters</small>
            </form>
            <?php } else { echo "<p>Login to comment.</p>"; } ?>
            <br><hr>
            <?php
                $stmt = $conn->prepare("SELECT * FROM `groupcomments` WHERE toid = ? ORDER BY date DESC");
                $stmt->execute([(int)$group['id']]);
                $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($comments as $row) {
                    echo "<div class='commentRight'>";
                    echo "  <small>" . htmlspecialchars($row['date']) . "</small><br>" . $row['text'];
                    echo "  <a style='float: right;' href='../profile.php?id=" . getID($row['author'], $conn) . "'>" . htmlspecialchars($row['author']) . "</a> <br>";
                    echo "  <img class='commentPictures' style='float: right;' width='80px;' src='../media/pfp/" . htmlspecialchars(getPFP($row['author'], $conn)) . "'><br><br><br><br><br>";
                    echo "</div>";
                }
            ?>
        </div>
    </body>
</html>
