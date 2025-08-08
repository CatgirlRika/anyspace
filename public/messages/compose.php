<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
require("../../core/messages/pm.php");

login_check();

$userId = $_SESSION['userId'];
$message = '';
$toPrefill = '';
$subjectPrefill = '';
$parentId = null;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['reply_to'])) {
    $parentId = (int)$_GET['reply_to'];
    $stmt = $conn->prepare('SELECT sender_id, receiver_id, subject FROM messages WHERE id = :id');
    $stmt->execute([':id' => $parentId]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $otherId = ((int)$row['sender_id'] === $userId) ? (int)$row['receiver_id'] : (int)$row['sender_id'];
        $stmt2 = $conn->prepare('SELECT username FROM users WHERE id = :id');
        $stmt2->execute([':id' => $otherId]);
        $toPrefill = (string)$stmt2->fetchColumn();
        $subjectPrefill = 'Re: ' . $row['subject'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = trim($_POST['to'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $body = trim($_POST['body'] ?? '');
    $parentId = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

    $stmt = $conn->prepare('SELECT id FROM users WHERE username = :name');
    $stmt->execute([':name' => $to]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        try {
            pm_send($userId, (int)$row['id'], $subject, $body, $parentId);
            $message = 'Message sent!';
        } catch (InvalidArgumentException $e) {
            $message = $e->getMessage();
        }
    } else {
        $message = 'User not found.';
    }
}
?>
<?php require("../header.php"); ?>

<div class="simple-container">
    <h1>Compose Message</h1>
    <p><a href="inbox.php">Inbox</a> | <a href="outbox.php">Outbox</a></p>
    <?php if ($message): ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
    <form method="post">
    <?= csrf_token_input(); ?>
        <input type="hidden" name="parent_id" value="<?= htmlspecialchars($parentId ?? '') ?>">
        <p><label>To: <input type="text" name="to" value="<?= htmlspecialchars($toPrefill) ?>" required></label></p>
        <p><label>Subject: <input type="text" name="subject" value="<?= htmlspecialchars($subjectPrefill) ?>" required></label></p>
        <p><label>Message:<br>
            <textarea name="body" rows="8" cols="40" required></textarea></label></p>
        <p><button type="submit">Send</button></p>
    </form>
</div>

<?php require("../footer.php"); ?>
