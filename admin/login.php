<?php
require("../core/conn.php");
require_once("../core/settings.php");
require("../lib/password.php"); // compatibility library for PHP 5.3

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'login') {
        // Sanitize input
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];

        if ($_POST['action'] == 'login') {
            // Prepare SQL statement for login
            $stmt = $conn->prepare("SELECT id, username, password, rank, banned_until FROM users WHERE email = ?");
            $stmt->execute(array($email));
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            $isAdmin = ($user['id'] == $adminUser);

            $notBanned = (empty($user['banned_until']) || strtotime($user['banned_until']) <= time());
            if (($user && password_verify($password, $user['password']) && $notBanned) && $isAdmin) {
                $_SESSION['user'] = $user['username'];
                $_SESSION['userId'] = $user['id'];
                $_SESSION['rank'] = $user['rank'];

                header("Location: index.php");
                exit;
            } elseif ($user && !$notBanned) {
                echo '<p>Account banned until ' . htmlspecialchars($user['banned_until']) . '</p><hr>';
            } else {
                echo '<p>Login information doesn\'t exist, incorrect password, or user does not have proper permission.</p><hr>';
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
                                                Up!</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </form>
                    <a class="forgot" href="/reset">Forgot your password?</a>
                </div>
            </div>
        </main>
    </div>
</body>

</html>