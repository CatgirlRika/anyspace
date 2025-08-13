<?php
require("../core/conn.php");
require_once("../core/settings.php");

// Page functions
require("../core/site/admin/pages.php");

login_check();

$pages = get_pages_config();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $home = isset($_POST['pages']['home_welcome']) ? validateContentHTML($_POST['pages']['home_welcome']) : '';
    $about = isset($_POST['pages']['about']) ? validateContentHTML($_POST['pages']['about']) : '';

    if (update_page_config(array('home_welcome' => $home, 'about' => $about))) {
        header("Location: pages.php?status=success");
        exit;
    } else {
        $error = "Failed to update pages.";
    }
    $pages = get_pages_config();
}

require("header.php");
?>

<div class="simple-container">
    <div class="row edit-profile">
        <div class="col w-20 left">
            <!-- SIDEBAR CONTENT -->
        </div>
        <div class="col right">
            <h1>Page Content</h1>
            <p>Edit homepage and about page content</p>
            <form method="post" class="ctrl-enter-submit">
    <?= csrf_token_input(); ?>
                <button type="submit" name="submit">Save All</button>
                <br>
                <label for="pages_home_welcome">
                    <h3>Homepage Message:</h3>
                </label>
                <textarea id="pages_home_welcome" class="status_input" name="pages[home_welcome]" rows="3"><?= htmlspecialchars($pages['home_welcome']) ?></textarea>
                <label for="pages_about">
                    <h3>About Page Content:</h3>
                </label>
                <textarea id="pages_about" class="status_input" name="pages[about]" rows="6"><?= htmlspecialchars($pages['about']) ?></textarea>
                <p></p>
                <button type="submit" name="submit">Save All</button>
            </form>
            <?php
            if (isset($_GET['status']) && $_GET['status'] === 'success') {
                echo "<p style='color: green;'>Pages updated successfully.</p>";
            } elseif (isset($error)) {
                echo "<p style='color: red;'>$error</p>";
            }
            ?>
        </div>
    </div>
</div>

<?php require("../public/footer.php"); ?>
