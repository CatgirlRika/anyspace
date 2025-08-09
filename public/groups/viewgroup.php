<?php
    require("../../core/conn.php");
    require_once("../../core/settings.php");
    require_once("../../core/site/user.php");
?>
<!DOCTYPE html>
<html>
    <head>
        <link rel="stylesheet" href="../static/css/header.css">
        <link rel="stylesheet" href="../static/css/base.css">
        <?php
            $stmt = $conn->prepare("SELECT * FROM `groups` WHERE id = ?");
            $stmt->execute([(int)$_GET['id']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $name = $result['name'];
                $desc = $result['description'];
                $author = $result['author'];
                $date = $result['date'];
            } else {
                header("Location: groups.php?msg=group_not_found");
                exit;
            }

            if(@$_POST["comment"]) {
                $stmt = $conn->prepare("INSERT INTO `groupcomments` (toid, author, text, date) VALUES (?, ?, ?, datetime('now'))");
                
                $unprocessedText = replaceBBcodes($_POST['comment']);
                $text = str_replace(PHP_EOL, "<br>", $unprocessedText);
                $stmt->execute([(int)$_GET['id'], $_SESSION['user'], $text]);
            }
        ?>
    </head>
    <body>
        <?php
            require("../../core/components/navbar.php");
        ?>
        <div class="container">
            <h1><?php echo $name; ?></h1>
            <?php
                echo "Owner: <a href='../profile.php?id=" . getID($author, $conn) . "'>" . $author . "</a>";
                
                // Show join/leave button
                if (isset($_SESSION['user'])) {
                    $stmt = $conn->prepare("SELECT * FROM `group_memberships` WHERE group_id = ? AND username = ?");
                    $stmt->execute([(int)$_GET['id'], $_SESSION['user']]);
                    $membership_result = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$membership_result) {
                        echo " | <a href='joingroup.php?id=" . $_GET['id'] . "' style='background: #007bff; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px;'>Join Group</a>";
                    } else {
                        echo " | <span style='background: #28a745; color: white; padding: 5px 10px; border-radius: 3px;'>Member</span>";
                    }
                }
                
                // Show messages
                if (isset($_GET['msg'])) {
                    if ($_GET['msg'] == 'joined') {
                        echo "<div class='success' style='margin: 10px 0;'>Successfully joined the group!</div>";
                    } elseif ($_GET['msg'] == 'already_member') {
                        echo "<div class='info' style='margin: 10px 0;'>You are already a member of this group.</div>";
                    }
                }
            ?>
            <pre><?php echo $desc;?></pre>
            
            <!-- Events Section -->
            <div class="info">
                <center>Upcoming Events</center>
            </div>
            <?php
                // Handle event creation
                if(@$_POST["create_event"]) {
                    $stmt = $conn->prepare("INSERT INTO `group_events` (group_id, title, description, event_date, created_by) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([(int)$_GET['id'], $_POST['event_title'], $_POST['event_description'], $_POST['event_date'], $_SESSION['user']]);
                }
                
                // Show event creation form (only for group members or owner)
                echo "<details>";
                echo "<summary>Create New Event</summary>";
                echo "<form method='post' enctype='multipart/form-data'>";
                echo csrf_token_input();
                echo "<input required placeholder='Event Title' size='60' type='text' name='event_title'><br>";
                echo "<textarea required rows='3' cols='60' placeholder='Event Description' name='event_description'></textarea><br>";
                echo "<input required type='datetime-local' name='event_date'><br>";
                echo "<input name='create_event' type='submit' value='Create Event'>";
                echo "</form>";
                echo "</details>";
                echo "<br>";
                
                // Display events
                $stmt = $conn->prepare("SELECT * FROM `group_events` WHERE group_id = ? ORDER BY event_date ASC");
                $stmt->execute([(int)$_GET['id']]);
                $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($events) > 0) {
                    foreach($events as $event) {
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
                $stmt->execute([(int)$_GET['id']]);
                $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($members) > 0) {
                    echo "<div style='margin: 10px 0;'>";
                    echo "<strong>Members (" . count($members) . "):</strong><br>";
                    foreach($members as $member) {
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
            <form method="post" enctype="multipart/form-data">
    <?= csrf_token_input(); ?>
				<textarea required rows="5" cols="80" placeholder="Comment" name="comment"></textarea><br>
				<input name="submit" type="submit" value="Post"> <small>max limit: 500 characters</small>
            </form>
            <br><hr>
            <?php
                $stmt = $conn->prepare("SELECT * FROM `groupcomments` WHERE toid = ? ORDER BY date DESC");
                $stmt->execute([(int)$_GET['id']]);
                $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach($comments as $row) {
                    echo "<div class='commentRight'>";
                    echo "  <small>" . $row['date'] . "</small><br>" . $row['text'];
                    echo "  <a style='float: right;' href='../profile.php?id=" . getID($row['author'], $conn) . "'>" . $row['author'] . "</a> <br>";
                    echo "  <img class='commentPictures' style='float: right;' width='80px;'src='../media/pfp/" . getPFP($row['author'], $conn) . "'><br><br><br><br><br>";
                    echo "</div>";
                }
            ?>
        </div>
    </body>
</html>