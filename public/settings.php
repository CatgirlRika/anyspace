<?php
require("../core/conn.php");
require_once("../core/settings.php");
require_once("../lib/password.php");
require("../core/site/user.php");
require("../core/site/edit.php");


login_check();

$userId = $_SESSION['userId'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['password-old']) && isset($_POST['password-new']) && isset($_POST['password-confirm'])) {
        $oldPassword = $_POST['password-old'];
        $newPassword = $_POST['password-new'];
        $confirmPassword = $_POST['password-confirm'];

        $currentUserPassword = fetchUserPassword($userId);
        $currentUserPassword = $currentUserPassword['password'];

        if (password_verify($oldPassword, $currentUserPassword)) {
            if ($newPassword === $confirmPassword) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

                changePassword($userId, $hashedPassword);

                echo "Password updated successfully.";
            } else {
                echo "New passwords do not match.";
            }
        } else {
            echo "Old password is incorrect.";
        }
    }

    $colorScheme = isset($_POST['color_scheme']) ? $_POST['color_scheme'] : 'light';
    $fontSize = isset($_POST['font_size']) ? $_POST['font_size'] : 'normal';
    $stmt = $conn->prepare("UPDATE users SET color_scheme = ?, font_size = ? WHERE id = ?");
    $stmt->execute(array($colorScheme, $fontSize, $userId));
    $_SESSION['color_scheme'] = $colorScheme;
    $_SESSION['font_size'] = $fontSize;

    $accentColor = isset($_POST['accent_color']) ? $_POST['accent_color'] : '#003399';
    $backgroundColor = isset($_POST['background_color']) ? $_POST['background_color'] : '#e5e5e5';
    $textColor = isset($_POST['text_color']) ? $_POST['text_color'] : '#000000';

    // basic validation for hex colors
    foreach (['accentColor' => &$accentColor, 'backgroundColor' => &$backgroundColor, 'textColor' => &$textColor] as $var => &$color) {
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $color = $var === 'accentColor' ? '#003399' : ($var === 'backgroundColor' ? '#e5e5e5' : '#000000');
        }
    }
    saveUserColors($userId, $accentColor, $backgroundColor, $textColor);
    $_SESSION['accent_color'] = $accentColor;
    $_SESSION['background_color'] = $backgroundColor;
    $_SESSION['text_color'] = $textColor;
}

$currentScheme = fetchColorScheme($userId);
$currentFont = fetchFontSize($userId);
$currentColors = fetchUserColors($userId);

?>
<?php require("header.php"); ?>

<div class="simple-container">
  <h1>Account Settings</h1>
    <form method="post" class="ctrl-enter-submit">
    <?= csrf_token_input(); ?>
    <div class="setting-section">
      <div class="heading">
        <h4>Basic Details</h4>
      </div>
      <div class="inner">
        <label for="id">Account ID:</label>
        <input type="text" id="id" value="<?= $userId ?>" readonly disabled>
        <br>
        <br>
        <label for="name">Email Address:</label>
        <input type="email" id="email" name="email" autocomplete="email" value="<?= fetchEmail($userId) ?>" required>
        <br>
        <br>
        <label for="name">Your Name:</label>
        <input type="text" id="name" value="<?= fetchName($userId) ?>" readonly disabled>
        <small>Change on the <a href="manage.php">Edit Profile</a> page</small>
        <!-- Currently Not Implemented
        <br>
        <br>
        <label for="username">Username: (optional)</label>
        <span class="username-box">
          https://<?= DOMAIN_NAME ?>/
          <input type="text" id="username" name="username" autocomplete="username" value="">
        </span>
        <p class="info">
          If you set a Username, you will get a custom URL for your Profile. Example: <b>https://<?= DOMAIN_NAME ?>/username</b><br><br>
          <b>Attention:</b> If you change your Username, your previous Profile URL won't work anymore and your Username will be available for other people again!
        </p>
          -->
      </div>
    </div>
        <div class="setting-section">
      <div class="heading">
        <h4>Change Password</h4>
      </div>
      <div class="inner">
      <label for="id">Old Password:</label>
        <input type="password" id="id" value="" name="password-old" noautocomplete>
        <br>
        <br>
        <label for="name">New Password:</label>
        <input type="password" id="id" value="" name="password-new" noautocomplete>
        <br>
        <br>
        <label for="name">Confirm New Password:</label>
        <input type="password" id="id" value="" name="password-confirm" noautocomplete>
      </div>
    </div>
    <div class="setting-section">
      <div class="heading">
        <h4>Privacy</h4>
      </div>
      <div class="inner">
        <!-- real check doesn't exist yet
        <label for="show_online">Online Status:</label>
        <input type="checkbox" id="show_online" name="show_online" checked> Show Online Status on your Profile
                <br>
        <br>
    
        <label for="im_privacy">Who can start an IM conversation with you:</label>
        <select name="im_privacy" id="im_privacy" required>
          <option value="friends" selected>Your Friends</option>
          <option value="everyone" >Everyone</option>
          <option value="noone" >No one</option>
        </select>
        <br>
        <br>
          -->
        <label for="profile_visibility">Who can view your Profile:</label>
        <select name="profile_visibility" id="profile_visibility" required>
          <option value="public" selected>Everyone (Public)</option>
          <option value="private" >Only Friends (Private)</option>
        </select>
        <p class="info">If your Profile is set to <b>private</b>, only Friends can view the content of your Profile. All other content posted by you will stay public.</p>

      </div>
    </div>

    <div class="setting-section">
      <div class="heading">
        <h4>Display Options</h4>
      </div>
      <div class="inner">
        <label for="color_scheme">Color Scheme:</label>
        <select id="color_scheme" name="color_scheme">
          <option value="light" <?= $currentScheme === 'light' ? 'selected' : '' ?>>Light</option>
          <option value="dark" <?= $currentScheme === 'dark' ? 'selected' : '' ?>>Dark</option>
        </select>
        <br>
        <label for="font_size">Font Size:</label>
        <select id="font_size" name="font_size">
          <option value="normal" <?= $currentFont === 'normal' ? 'selected' : '' ?>>Normal</option>
          <option value="large" <?= $currentFont === 'large' ? 'selected' : '' ?>>Large</option>
        </select>
        <br>
        <label for="accent_color">Accent Color:</label>
        <input type="color" id="accent_color" name="accent_color" value="<?= htmlspecialchars($currentColors['accent_color'], ENT_QUOTES) ?>">
        <br>
        <label for="background_color">Background Color:</label>
        <input type="color" id="background_color" name="background_color" value="<?= htmlspecialchars($currentColors['background_color'], ENT_QUOTES) ?>">
        <br>
        <label for="text_color">Text Color:</label>
        <input type="color" id="text_color" name="text_color" value="<?= htmlspecialchars($currentColors['text_color'], ENT_QUOTES) ?>">
      </div>
    </div>

    <!-- 
    <div class="setting-section">
      <div class="heading">
        <h4>Security & Account Access</h4>
      </div>
      <div class="inner">
        <label for="2fa">2-Factor-Authentication:</label>
        <span id="2fa">
          <i>not enabled</i> [<a href="/enable2fa">enable</a>]        </span>
        <h4 style="margin: 15px 0 0 0;">Active Sessions:</h4>
        <table class="settings-sessions-table" border="1" cellspacing="0" cellpadding="3">
          <tr>
            <th>Client</th>
            <th>Device</th>
            <th>Last used</th>
            <th style="text-align:center;">Action</th>
          </tr>
                      <tr>
                       </td>
            </tr>
                  </table>
      </div>
    </div>
    <br>
          -->
    <button type="submit" name="submit">Save All</button>
  </form>
  <br>
  <h4 style="margin-bottom: 5px;">More Options</h4>
  <!--
  <ul>
    <li>Export your Account Data: <a href="/export" target="_blank">Download</a></li>
    <li>If you want to permanently delete your Account and all your data, please <a href="deleteaccount.php">click here</a></li>
  </ul>
          -->
</div>


<?php require("footer.php"); ?>