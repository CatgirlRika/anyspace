<?php
require("../core/conn.php");
require_once("../core/settings.php");

require("../core/site/admin/questions.php");

login_check();

$questions = get_signup_questions();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $q = trim($_POST['question']);
        $a = trim($_POST['answer']);
        if ($q !== '' && $a !== '') {
            if (admin_add_signup_question($q, $a)) {
                header("Location: questions.php?status=added");
                exit;
            } else {
                $error = "Failed to add question.";
            }
        } else {
            $error = "Both fields required.";
        }
    } elseif (isset($_POST['delete']) && isset($_POST['id'])) {
        if (admin_delete_signup_question((int)$_POST['id'])) {
            header("Location: questions.php?status=deleted");
            exit;
        } else {
            $error = "Failed to delete question.";
        }
    }
    $questions = get_signup_questions();
}

require("header.php");
?>

<div class="simple-container">
    <div class="row edit-profile">
        <div class="col w-20 left">
            <!-- SIDEBAR CONTENT -->
        </div>
        <div class="col right">
            <h1>Signup Questions</h1>
            <p>Manage anti-spam questions displayed during user registration.</p>
            <h3>Existing Questions</h3>
            <ul>
                <?php foreach ($questions as $id => $qa): ?>
                <li>
                    <?= htmlspecialchars($qa['q']); ?>
                    <form method="post" style="display:inline">
    <?= csrf_token_input(); ?>
                        <input type="hidden" name="id" value="<?= $id; ?>">
                        <button type="submit" name="delete">Delete</button>
                    </form>
                </li>
                <?php endforeach; ?>
            </ul>
            <h3>Add Question</h3>
            <form method="post">
    <?= csrf_token_input(); ?>
                <input type="text" name="question" placeholder="Question" required><br>
                <input type="text" name="answer" placeholder="Answer" required><br>
                <button type="submit" name="add">Add</button>
            </form>
            <?php
            if (isset($_GET['status'])) {
                if ($_GET['status'] === 'added') {
                    echo "<p style='color: green;'>Question added.</p>";
                } elseif ($_GET['status'] === 'deleted') {
                    echo "<p style='color: green;'>Question deleted.</p>";
                }
            } elseif (isset($error)) {
                echo "<p style='color: red;'>$error</p>";
            }
            ?>
        </div>
    </div>
</div>

<?php require("../public/footer.php"); ?>
