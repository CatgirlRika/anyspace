<?php
require("../core/conn.php"); // Ensure this returns a PDO connection ($conn)
require_once("../core/settings.php");
require_once("../core/site/friend.php");
require("../lib/password.php");
require_once("../core/site/questions.php");

$message = ''; // Variable to hold messages for the user
$antispam_question = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $qid = isset($_SESSION['signup_qid']) ? (int)$_SESSION['signup_qid'] : null;
    $answerOk = $qid === null || check_signup_answer($qid, $_POST['antispam_answer'] ?? '');

    if (!$answerOk) {
        $message = "<small>Incorrect anti-spam answer.</small>";
    } elseif (!empty($_POST['password']) && !empty($_POST['username']) && !empty($_POST['confirm'])) {
        if ($_POST['password'] !== $_POST['confirm'] || strlen($_POST['username']) > 21) {
            $message = "<small>Passwords do not match up or username is too long.</small>";
        } else {
            // Check for existing email only
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute(array($_POST['email']));
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

                $stmt = $conn->prepare("INSERT INTO users (username, email, password, date, interests) VALUES (?, ?, ?, NOW(), ?)");
                $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
                $username = htmlspecialchars($_POST['username']);
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt->execute(array($username, $email, $password, $jsonInterests));

                $newUserId = $conn->lastInsertId();

                autoAddFriend($newUserId);
                $_SESSION['user'] = $username;
                $_SESSION['userId'] = $newUserId;
                header("Location: manage.php");
                exit;
            }
        }
    } else {
        $message = "<small>Please fill in all required fields.</small>";
    }

    $q = get_random_signup_question();
    if ($q) {
        $_SESSION['signup_qid'] = $q['id'];
        $antispam_question = $q['q'];
    }
} else {
    $q = get_random_signup_question();
    if ($q) {
        $_SESSION['signup_qid'] = $q['id'];
        $antispam_question = $q['q'];
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
                    <input required placeholder="Confirm Password" type="password" name="confirm"><br>
                    <?php if ($antispam_question): ?>
                    <label><?= htmlspecialchars($antispam_question); ?></label><br>
                    <input required type="text" name="antispam_answer"><br>
                    <?php endif; ?>
                    <br>
                    <input type="submit" value="Register">
                </form>
            </div>
        </main>
    </div>
</body>

</html>