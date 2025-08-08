<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
admin_only();

require("../../core/config.php");

function logAction(string $message): void {
    $logFile = __DIR__ . '/../../admin_logs.txt';
    $entry = date('c') . ' ' . $message . PHP_EOL;
    file_put_contents($logFile, $entry, FILE_APPEND);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $identifier = trim($_POST['user'] ?? '');
        if ($identifier === '') {
            header('Location: global_mods.php?msg=' . urlencode('User required'));
            exit;
        }
        if (ctype_digit($identifier)) {
            $stmt = $conn->prepare('SELECT id, rank FROM users WHERE id = :id');
            $stmt->execute([':id' => (int)$identifier]);
        } else {
            $stmt = $conn->prepare('SELECT id, rank FROM users WHERE username = :username');
            $stmt->execute([':username' => $identifier]);
        }
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            header('Location: global_mods.php?msg=' . urlencode('User not found'));
            exit;
        }
        if ((int)$user['rank'] >= 1) {
            header('Location: global_mods.php?msg=' . urlencode('Already moderator'));
            exit;
        }
        $upd = $conn->prepare('UPDATE users SET rank = 1 WHERE id = :id');
        $upd->execute([':id' => $user['id']]);
        logAction("Promoted user {$user['id']} to global moderator by {$_SESSION['userId']}");
        header('Location: global_mods.php?msg=' . urlencode('User promoted'));
        exit;
    }

    if (isset($_POST['demote'])) {
        $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        if ($user_id <= 0) {
            header('Location: global_mods.php?msg=' . urlencode('Invalid user'));
            exit;
        }
        $upd = $conn->prepare('UPDATE users SET rank = 0 WHERE id = :id');
        $upd->execute([':id' => $user_id]);
        logAction("Demoted user {$user_id} from global moderator by {$_SESSION['userId']}");
        header('Location: global_mods.php?msg=' . urlencode('User demoted'));
        exit;
    }
}

$modsStmt = $conn->query('SELECT id, username, rank FROM users WHERE rank >= 1 ORDER BY id ASC');
$mods = $modsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php require("../header.php"); ?>
<div class="simple-container">
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert"><?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>
    <h1>Global Moderators</h1>
    <table class="bulletin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Rank</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$mods): ?>
                <tr><td colspan="4">None</td></tr>
            <?php else: foreach ($mods as $mod): ?>
                <tr>
                    <td><?= $mod['id'] ?></td>
                    <td><?= htmlspecialchars($mod['username']) ?></td>
                    <td><?= $mod['rank'] == 2 ? 'Admin' : 'Global Mod' ?></td>
                    <td>
                        <?php if ($mod['rank'] == 1): ?>
                        <form method="post" style="display:inline" onsubmit="return confirm('Demote this user?');">
    <?= csrf_token_input(); ?>
                            <input type="hidden" name="user_id" value="<?= $mod['id'] ?>">
                            <button type="submit" name="demote">Demote</button>
                        </form>
                        <?php else: ?>
                        N/A
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <h2>Add Global Moderator</h2>
    <form method="post">
    <?= csrf_token_input(); ?>
        <input type="text" name="user" placeholder="Username or ID">
        <button type="submit" name="add">Promote</button>
    </form>
</div>
<?php require("../../public/footer.php"); ?>
