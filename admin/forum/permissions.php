<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
admin_only();

require("../../core/config.php");

$forumId = isset($_GET['forum_id']) ? (int)$_GET['forum_id'] : (int)($_POST['forum_id'] ?? 0);
if ($forumId <= 0) {
    header('Location: forums.php?msg=' . urlencode('Invalid forum'));
    exit;
}

$stmt = $conn->prepare('SELECT name FROM forums WHERE id = :id');
$stmt->execute([':id' => $forumId]);
$forum = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$forum) {
    header('Location: forums.php?msg=' . urlencode('Forum not found'));
    exit;
}

$roles = ['guest', 'member', 'admin'];
$roleCheck = $conn->query("SHOW TABLES LIKE 'roles'");
if ($roleCheck && $roleCheck->rowCount() > 0) {
    $roles = $conn->query('SELECT name FROM roles')->fetchAll(PDO::FETCH_COLUMN);
}

$permStmt = $conn->prepare('SELECT role, can_view, can_post, can_moderate FROM forum_permissions WHERE forum_id = :fid');
$permStmt->execute([':fid' => $forumId]);
$existing = [];
foreach ($permStmt as $row) {
    $existing[$row['role']] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->prepare('DELETE FROM forum_permissions WHERE forum_id = :fid')->execute([':fid' => $forumId]);
    $insert = $conn->prepare('INSERT INTO forum_permissions (forum_id, role, can_view, can_post, can_moderate) VALUES (:fid, :role, :view, :post, :moderate)');
    foreach ($roles as $role) {
        $view = isset($_POST['perm'][$role]['view']) ? 1 : 0;
        $post = isset($_POST['perm'][$role]['post']) ? 1 : 0;
        $moderate = isset($_POST['perm'][$role]['moderate']) ? 1 : 0;
        $insert->execute([
            ':fid' => $forumId,
            ':role' => $role,
            ':view' => $view,
            ':post' => $post,
            ':moderate' => $moderate,
        ]);
    }
    header('Location: forums.php?msg=' . urlencode('Permissions updated'));
    exit;
}
?>
<?php require("../header.php"); ?>
<div class="simple-container">
    <h1>Permissions for <?= htmlspecialchars($forum['name']) ?></h1>
    <form method="post">
        <input type="hidden" name="forum_id" value="<?= $forumId ?>">
        <table class="bulletin-table">
            <thead>
                <tr>
                    <th>Role</th>
                    <th>View</th>
                    <th>Post</th>
                    <th>Moderate</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($roles as $role): $perm = $existing[$role] ?? ['can_view'=>0,'can_post'=>0,'can_moderate'=>0]; ?>
                <tr>
                    <td><?= htmlspecialchars($role) ?></td>
                    <td><input type="checkbox" name="perm[<?= $role ?>][view]" <?= $perm['can_view'] ? 'checked' : '' ?>></td>
                    <td><input type="checkbox" name="perm[<?= $role ?>][post]" <?= $perm['can_post'] ? 'checked' : '' ?>></td>
                    <td><input type="checkbox" name="perm[<?= $role ?>][moderate]" <?= $perm['can_moderate'] ? 'checked' : '' ?>></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <button type="submit">Save</button>
    </form>
</div>
<?php require("../../public/footer.php"); ?>
