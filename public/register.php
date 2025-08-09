<?php
require("../core/conn.php"); // Ensure this returns a PDO connection ($conn)
require_once("../core/settings.php");
require_once("../core/security.php");
require_once("../core/site/friend.php");
require("../lib/password.php");

// Initialize security components
$securityLogger = new SecurityAuditLogger($conn);
$rateLimiter = new RateLimiter($conn);

$message = ''; // Variable to hold messages for the user


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!empty($_POST['password']) && !empty($_POST['username']) && !empty($_POST['confirm'])) {
        // Get client IP for rate limiting
        $clientIP = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
        
        // Check rate limiting for registration
        $rateCheck = $rateLimiter->checkRateLimit($clientIP, 'register');
        if (!$rateCheck['allowed']) {
            $message = "<small>Too many registration attempts. Please try again later.</small>";
        } else {
            // Validate password complexity
            $passwordValidation = validatePasswordComplexity($_POST['password']);
            if (!$passwordValidation['isValid']) {
                $message = "<small>Password requirements not met:<br>" . implode("<br>", $passwordValidation['errors']) . "</small>";
            } elseif ($_POST['password'] !== $_POST['confirm'] || strlen($_POST['username']) > 21) {
                $message = "<small>Passwords do not match up or username is too long.</small>";
            } else {
                // Sanitize input
                $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
                $username = InputSanitizer::sanitizeHTML($_POST['username'], array(), array());
                
                // Validate email format
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $message = "<small>Invalid email format.</small>";
                } else {
                    // Check for existing email only
                    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
                    $stmt->execute(array($email));
                    if ($stmt->fetch()) {
                        $message .= "<small>There's already a user with that same email!</small><br>";
                        $emailcheck = false;
                    } else {
                        $emailcheck = true;
                    }

                    if ($emailcheck) {
                        $interests = array(
                            "General" => "",
                            "Music" => "",
                            "Movies" => "",
                            "Television" => "",
                            "Books" => "",
                            "Heroes" => ""
                        );
                        $jsonInterests = json_encode($interests);

                        $stmt = $conn->prepare("INSERT INTO users (username, email, password, date, interests, password_changed_at) VALUES (?, ?, ?, NOW(), ?, NOW())");
                        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                        $stmt->execute(array($username, $email, $password, $jsonInterests));

                        $newUserId = $conn->lastInsertId();

                        // Log successful registration
                        $securityLogger->logEvent(
                            SecurityAuditLogger::AUTH_SUCCESS,
                            SecurityAuditLogger::RISK_LOW,
                            $newUserId,
                            array('action' => 'register', 'email' => $email, 'username' => $username)
                        );

                        autoAddFriend($newUserId);
                        $_SESSION['user'] = $username;
                        $_SESSION['userId'] = $newUserId;
                        header("Location: manage.php");
                        exit;
                    } else {
                        // Record failed registration attempt
                        $rateLimiter->recordAttempt($clientIP, 'register', false);
                        
                        $securityLogger->logEvent(
                            SecurityAuditLogger::AUTH_FAILURE,
                            SecurityAuditLogger::RISK_LOW,
                            null,
                            array('action' => 'register', 'email' => $email, 'reason' => 'Email already exists')
                        );
                    }
                }
            }
        }
    } else {
        $message = "<small>Please fill in all required fields.</small>";
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Register</title>
    <link rel="stylesheet" href="/static/css/normalize.min.css">
    <link rel="stylesheet" href="/static/css/style.min.css">
</head>

<body>
    <div class="master-container">
        <?php require("../core/components/navbar.php"); ?>
        <main>
                <h1>Sign Up</h1>

                <br>
            <div class="center-container">
                <div class="contactInfo">
                    <div class="contactInfoTop">
                        <!-- This is long deprecated - remove asap -->
                        <center>Benefits</center>
                    </div>
                    - Make new friends!<br>
                    - Talk to people!<br>
                    - Algorithm Free!<br>
                    - Free and Open Source
                </div>
                <small style="color: red;">- email verification is currently disabled. you can enter any valid email address</small><br>
                <small style="color: red;">- this is a test server. data may be wiped at any time</small>
                <br>
                <br>
                <?php if ($message)
                    echo $message; ?>
                <form action="" method="post">
    <?= csrf_token_input(); ?>
                    <input required placeholder="Username" type="text" name="username"><br>
                    <input required placeholder="E-Mail" type="email" name="email"><br>
                    <input required placeholder="Password" type="password" name="password"><br>
                    <input required placeholder="Confirm Password" type="password" name="confirm"><br><br>
                    <input type="submit" value="Register">
                </form>
            </div>
        </main>
    </div>
</body>

</html>