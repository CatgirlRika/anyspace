<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
admin_only();

require("../../core/config.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $forum_id = isset($_POST['forum_id']) ? (int)$_POST['forum_id'] : 0;
        $identifier = trim($_POST['user'] ?? '');
        if ($forum_id <= 0 || $identifier === '') {
            header('Location: moderators.php?msg=' . urlencode('Invalid data'));
            exit;
        }

        if (ctype_digit($identifier)) {
            $stmt = $conn->prepare('SELECT id FROM users WHERE id = :id');
            $stmt->execute([':id' => (int)$identifier]);
        } else {
            $stmt = $conn->prepare('SELECT id FROM users WHERE username = :username');
            $stmt->execute([':username' => $identifier]);
        }
        $userId = $stmt->fetchColumn();
        if (!$userId) {
            header('Location: moderators.php?msg=' . urlencode('User not found'));
            exit;
        }

        $check = $conn->prepare('SELECT COUNT(*) FROM forum_moderators WHERE forum_id = :fid AND user_id = :uid');
        $check->execute([':fid' => $forum_id, ':uid' => $userId]);
        if ($check->fetchColumn() > 0) {
            header('Location: moderators.php?msg=' . urlencode('Already a moderator'));
            exit;
        }

        $insert = $conn->prepare('INSERT INTO forum_moderators (forum_id, user_id) VALUES (:fid, :uid)');
        $insert->execute([':fid' => $forum_id, ':uid' => $userId]);
        header('Location: moderators.php?msg=' . urlencode('Moderator added'));
        exit;
    }

    if (isset($_POST['remove'])) {
        $forum_id = isset($_POST['forum_id']) ? (int)$_POST['forum_id'] : 0;
        $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        if ($forum_id <= 0 || $user_id <= 0) {
            header('Location: moderators.php?msg=' . urlencode('Invalid data'));
            exit;
        }
        $del = $conn->prepare('DELETE FROM forum_moderators WHERE forum_id = :fid AND user_id = :uid');
        $del->execute([':fid' => $forum_id, ':uid' => $user_id]);
        header('Location: moderators.php?msg=' . urlencode('Moderator removed'));
        exit;
    }
}

$forums = $conn->query('SELECT id, name FROM forums ORDER BY position ASC')->fetchAll(PDO::FETCH_ASSOC);
?>
<?php require("../header.php"); ?>
<div class="simple-container">
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert"><?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>
    <h1>Forum Moderators</h1>
    <table class="bulletin-table">
        <thead>
            <tr>
                <th>Forum</th>
                <th>Moderators</th>
                <th>Add Moderator</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($forums as $forum): ?>
            <tr>
                <td><?= htmlspecialchars($forum['name']) ?></td>
                <td>
                    <?php
                    $modsStmt = $conn->prepare('SELECT fm.user_id, u.username FROM forum_moderators fm JOIN users u ON fm.user_id = u.id WHERE fm.forum_id = :fid');
                    $modsStmt->execute([':fid' => $forum['id']]);
                    $mods = $modsStmt->fetchAll(PDO::FETCH_ASSOC);
                    if (!$mods):
                    ?>
                        None
                    <?php else: ?>
                        <?php foreach ($mods as $mod): ?>
                            <?= htmlspecialchars($mod['username']) ?> (ID: <?= $mod['user_id'] ?>)
                            <form method="post" style="display:inline">
                                <input type="hidden" name="forum_id" value="<?= $forum['id'] ?>">
                                <input type="hidden" name="user_id" value="<?= $mod['user_id'] ?>">
                                <button type="submit" name="remove">Remove</button>
                            </form><br>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </td>
                <td>
                    <form method="post">
                        <input type="hidden" name="forum_id" value="<?= $forum['id'] ?>">
                        <input type="text" name="user" placeholder="Username or ID">
                        <button type="submit" name="add">Add</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require("../../public/footer.php"); ?>
