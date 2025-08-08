<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
require("../../core/messages/pm.php");

login_check();

$userId = $_SESSION['userId'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = trim($_POST['to'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $body = trim($_POST['body'] ?? '');

    $stmt = $conn->prepare('SELECT id FROM users WHERE username = :name');
    $stmt->execute([':name' => $to]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        pm_send($userId, (int)$row['id'], $subject, $body);
        $message = 'Message sent!';
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
        <p><label>To: <input type="text" name="to" required></label></p>
        <p><label>Subject: <input type="text" name="subject" required></label></p>
        <p><label>Message:<br>
            <textarea name="body" rows="8" cols="40" required></textarea></label></p>
        <p><button type="submit">Send</button></p>
    </form>
</div>

<?php require("../footer.php"); ?>
