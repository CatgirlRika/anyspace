<?php
require("../core/conn.php");
require_once("../core/settings.php");
require_once("../core/auth/magic_login.php");

$message = '';
$messageClass = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'request_magic_login') {
    // CSRF protection is handled by helper.php
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    
    if (empty($email)) {
        $message = 'Please enter your email address.';
        $messageClass = 'error';
    } else {
        $result = createMagicLoginToken($email);
        $message = $result['message'];
        $messageClass = $result['success'] ? 'success' : 'error';
    }
}
?>
<?php require_once("header.php") ?>
<div class="center-container">
    <div class="box standalone">
        <h4>Magic Login</h4>
        <p>Enter your email address and we'll send you a magic login link. No password required!</p>
        
        <?php if ($message): ?>
            <div class="message <?= htmlspecialchars($messageClass) ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <form action="" method="post" id="magicLoginForm">
            <?= csrf_token_input(); ?>
            <table>
                <tbody>
                    <tr class="email">
                        <td class="label"><label for="email">E-Mail:</label></td>
                        <td class="input">
                            <input type="email" name="email" id="email" 
                                   autocomplete="email" value="" required>
                        </td>
                    </tr>
                    <tr class="buttons">
                        <td></td>
                        <td>
                            <button type="submit" name="action" value="request_magic_login">
                                Send Magic Login Link
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>
        
        <div class="help-text">
            <h5>How it works:</h5>
            <ul>
                <li>Enter your email address above</li>
                <li>We'll send you a secure login link</li>
                <li>Click the link to log in instantly</li>
                <li>The link expires in 30 minutes and works only once</li>
            </ul>
            
            <p><a href="login.php">← Back to Login</a></p>
        </div>
    </div>
</div>

<style>
.message {
    padding: 10px;
    margin: 10px 0;
    border-radius: 4px;
    font-weight: bold;
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