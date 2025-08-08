<?php
require("../../../core/conn.php");
require_once("../../../core/settings.php");
require_once("../../../core/forum/mod_check.php");
require_once("../../../core/forum/word_filter.php");

forum_mod_check();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $word = trim($_POST['word'] ?? '');
    if ($action === 'add' && $word !== '') {
        add_bad_word($word);
        $message = 'Word added';
    } elseif ($action === 'delete' && $word !== '') {
        remove_bad_word($word);
        $message = 'Word removed';
    }
}

$words = bad_words_all();
$pageCSS = "../../static/css/forum.css";
?>
<?php require("../../header.php"); ?>
<div class="simple-container">
    <h1>Word Filter</h1>
    <form method="post">
        <input type="hidden" name="action" value="add">
        <input type="text" name="word" required>
        <button type="submit">Add</button>
    </form>
    <?php if ($message): ?>
    <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
    <ul>
    <?php foreach ($words as $w): ?>
        <li><?= htmlspecialchars($w) ?>
            <form method="post" style="display:inline">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="word" value="<?= htmlspecialchars($w) ?>">
                <button type="submit">Remove</button>
            </form>
        </li>
    <?php endforeach; ?>
    </ul>
</div>
<?php require("../../footer.php"); ?>
