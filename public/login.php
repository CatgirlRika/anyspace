<?php
require("../core/conn.php");
require_once("../core/settings.php");
require_once("../core/security.php");
require("../lib/password.php"); // compatibility library for PHP 5.3

// Initialize security components
$securityLogger = new SecurityAuditLogger($conn);
$rateLimiter = new RateLimiter($conn);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'login') {
        // Get client IP for rate limiting
        $clientIP = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
        
        // Check rate limiting
        $rateCheck = $rateLimiter->checkRateLimit($clientIP, 'login');
        if (!$rateCheck['allowed']) {
            $lockoutEnd = isset($rateCheck['lockoutEnd']) ? date('H:i:s', $rateCheck['lockoutEnd']) : 'unknown';
            
            $securityLogger->logEvent(
                SecurityAuditLogger::RATE_LIMIT_EXCEEDED,
                SecurityAuditLogger::RISK_HIGH,
                null,
                array('action' => 'login', 'ip' => $clientIP)
            );
            
            echo '<p>Too many failed login attempts. Please try again after ' . $lockoutEnd . '.</p><hr>';
        } else {
            // Sanitize input
            $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'];

            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo '<p>Invalid email format.</p><hr>';
            } else {
                // Prepare SQL statement for login
                $stmt = $conn->prepare("SELECT id, username, password, rank FROM users WHERE email = ?");
                $stmt->execute(array($email));
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user['password'])) {
                    // Successful login
                    $_SESSION['user'] = $user['username'];
                    $_SESSION['userId'] = $user['id'];
                    $_SESSION['rank'] = $user['rank'];

                    // Record successful login
                    $rateLimiter->recordAttempt($clientIP, 'login', true);
                    
                    $securityLogger->logEvent(
                        SecurityAuditLogger::AUTH_SUCCESS,
                        SecurityAuditLogger::RISK_LOW,
                        $user['id'],
                        array('action' => 'login', 'email' => $email)
                    );

                    // Update last login time
                    $stmt = $conn->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?");
                    $stmt->execute(array($user['id']));

                    header("Location: home.php");
                    exit;
                } else {
                    // Failed login
                    $rateLimiter->recordAttempt($clientIP, 'login', false);
                    
                    $securityLogger->logEvent(
                        SecurityAuditLogger::AUTH_FAILURE,
                        SecurityAuditLogger::RISK_MEDIUM,
                        $user ? $user['id'] : null,
                        array('action' => 'login', 'email' => $email, 'reason' => 'Invalid credentials')
                    );
                    
                    echo '<p>Login information doesn\'t exist or incorrect password.</p><hr>';
                }
            }
        }
    }
}
?>
<?php require_once("header.php") ?>
            <div class="center-container">
                <div class="box standalone">
                    <!-- Login/Signup Form -->
                    <h4>Please login or signup to continue.</h4>
                    <form action="" method="post" name="theForm" id="theForm">
    <?= csrf_token_input(); ?>
                        <input name="client_id" type="hidden" value="web">
                        <table>
                            <tbody>
                                <tr class="email">
                                    <td class="label"><label for="email">E-Mail:</label></td>
                                    <td class="input"><input type="email" name="email" id="email" autocomplete="email"
                                            value="" required></td>
                                </tr>
                                <tr class="password">
                                    <td class="label"><label for="password">Password:</label></td>
                                    <td class="input"><input name="password" type="password" id="password"
                                            autocomplete="current-password" required></td>
                                </tr>
                                <tr class="remember">
                                    <td></td>
                                    <td>
                                        <input type="checkbox" name="remember" value="yes" id="checkbox">
                                        <label for="checkbox">Remember my E-mail</label>
                                    </td>
                                </tr>
                                <tr class="buttons">
                                    <td></td>
                                    <td>
                                        <button type="submit" class="login_btn" name="action"
                                            value="login">Login</button>
                                            <button type="button" class="signup_btn" onclick="location.href='register.php'" name="action" value="signup">Sign
                                                Up</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </form>
                    <div class="auth-options">
                        <a class="forgot" href="/reset">✨ Magic Login (no password needed)</a>
                        <br>
                        <small>Or use the form above if you remember your password</small>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>