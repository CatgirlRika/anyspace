<?php
require("../core/conn.php");
require_once("../core/settings.php");
require_once("../core/auth/magic_login.php");

$message = '';
$messageClass = 'error';
$redirectTo = 'login.php';

// Get token from URL
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $message = 'Invalid magic login link. No token provided.';
} else {
    $result = validateMagicLoginToken($token);
    
    if ($result['success']) {
        // Log the user in
        $user = $result['user'];
        $_SESSION['user'] = $user['username'];
        $_SESSION['userId'] = $user['id'];
        $_SESSION['rank'] = $user['rank'];
        
        $message = $result['message'];
        $messageClass = 'success';
        $redirectTo = 'home.php';
        
        // Update last login time
        $stmt = $conn->prepare("UPDATE users SET lastlogon = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        // Auto-redirect after 2 seconds
        echo '<script>setTimeout(function() { window.location.href = "' . $redirectTo . '"; }, 2000);</script>';
    } else {
        $message = $result['message'];
    }
}

// Clean up expired tokens periodically (10% chance)
if (mt_rand(1, 10) === 1) {
    cleanupExpiredTokens();
}
?>
<?php require_once("header.php") ?>
<div class="center-container">
    <div class="box standalone">
        <h4>Magic Login</h4>
        
        <div class="message <?= htmlspecialchars($messageClass) ?>">
            <?= htmlspecialchars($message) ?>
        </div>
        
        <?php if ($messageClass === 'success'): ?>
            <p>You will be redirected to the homepage in 2 seconds...</p>
            <p><a href="home.php">Click here if you're not redirected automatically</a></p>
        <?php else: ?>
            <div class="help-text">
                <h5>What went wrong?</h5>
                <ul>
                    <li>The magic login link may have expired (links are valid for 30 minutes)</li>
                    <li>The link may have already been used (each link works only once)</li>
                    <li>The link may be malformed or incomplete</li>
                </ul>
                
                <p><a href="reset.php">Request a new magic login link</a></p>
                <p><a href="login.php">← Back to Login</a></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.message {
    padding: 15px;
    margin: 15px 0;
    border-radius: 4px;
    font-weight: bold;
    text-align: center;
}

.message.success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.message.error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.help-text {
    margin-top: 20px;
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 4px;
    font-size: 14px;
}

.help-text h5 {
    margin-top: 0;
    color: #495057;
}

.help-text ul {
    margin: 10px 0;
    padding-left: 20px;
}

.help-text li {
    margin: 5px 0;
}
</style>

</main>
</div>
</body>
</html>