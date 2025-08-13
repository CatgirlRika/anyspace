<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
require_once("../../core/site/user.php");

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

?>
<?php require("../header.php"); ?>

<div class="groups-container">
    <h1>Groups</h1>
    
    <div class="row">
        <div class="col w-70">
            <h2>Browse Groups</h2>
            
            <?php
            // Get all groups with member counts
            $stmt = $conn->prepare("
                SELECT g.*, COUNT(gm.username) as member_count 
                FROM `groups` g 
                LEFT JOIN `group_memberships` gm ON g.id = gm.group_id 
                GROUP BY g.id 
                ORDER BY g.date DESC
            ");
            $stmt->execute();
            $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($groups) > 0) {
                echo "<table class='groups-table'>";
                echo "<tr>";
                echo "<th class='name'>Group</th>";
                echo "<th>Owner</th>";
                echo "<th>Members</th>";
                echo "<th>Created</th>";
                echo "<th>Actions</th>";
                echo "</tr>";
                
                foreach($groups as $row) {
                    echo "<tr>";
                    echo "<td class='name'>";
                    echo "<h4><a href='viewgroup.php?id=" . $row['id'] . "'>" . htmlspecialchars($row['name']) . "</a></h4>";
                    echo "<p>" . htmlspecialchars(substr($row['description'], 0, 100)) . "...</p>";
                    echo "</td>";
                    echo "<td><a href='../profile.php?id=" . getID($row['author'], $conn) . "'>" . htmlspecialchars($row['author']) . "</a></td>";
                    echo "<td>" . $row['member_count'] . "</td>";
                    echo "<td>" . date('M j, Y', strtotime($row['date'])) . "</td>";
                    echo "<td>";
                    echo "<form method='post' action='joingroup.php' style='display:inline;'>";
                    echo csrf_token_input();
                    echo "<input type='hidden' name='group_id' value='" . $row['id'] . "'>";
                    echo "<button type='submit'>Join</button>";
                    echo "</form> | <a href='viewgroup.php?id=" . $row['id'] . "'>View</a></td>";
                    echo "</tr>";
                }
                
                echo "</table>";
            } else {
                echo "<p>No groups found. <a href='newgroup.php'>Create the first group!</a></p>";
            }
            ?>
        </div>
        
        <div class="col w-30">
            <div class="spa">
                <div class="heading">
                    <h4>Group Actions</h4>
                </div>
                <div class="inner">
                    <p><a href="newgroup.php">Create New Group</a></p>
                    <p><a href="../profile.php?id=<?= $_SESSION['userId'] ?>">My Profile</a></p>
                </div>
            </div>
            
            <div class="spa">
                <div class="heading">
                    <h4>Your Groups</h4>
                </div>
                <div class="inner">
                    <?php
                    $stmt = $conn->prepare("
                        SELECT g.id, g.name, gm.joined_at 
                        FROM `groups` g 
                        JOIN `group_memberships` gm ON g.id = gm.group_id 
                        WHERE gm.username = ? 
                        ORDER BY gm.joined_at DESC
                    ");
                    $stmt->execute([$_SESSION['user']]);
                    $user_groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (count($user_groups) > 0) {
                        foreach ($user_groups as $row) {
                            echo "<p><a href='viewgroup.php?id=" . $row['id'] . "'>" . htmlspecialchars($row['name']) . "</a></p>";
                        }
                    } else {
                        echo "<p>Not in any groups yet</p>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require("../footer.php"); ?>