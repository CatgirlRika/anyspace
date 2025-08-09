<?php
require_once __DIR__ . "/../forum/permissions.php";
if (isset($_SESSION['userId'])) {
    require_once __DIR__ . "/../messages/pm.php";
    $unreadMessages = pm_unread_count($_SESSION['userId']);
} else {
    $unreadMessages = 0;
}
?>
<!-- BEGIN HEADER -->
<header class="main-header">
  <nav class="">
    <div class="top">
      <div class="left">
        <a href="index.php">
            <?= SITE_NAME ?>
          </a> | <a href="index.php">Home</a>
      </div>
      <div class="center">

      <form>


        <label>
          <?= htmlspecialchars(SITE_NAME); ?>
        </label>

        <label>
          <input type="text" name="search">
        </label>

        <input class="submit-btn" type="submit" name="submit-button" value="Search">
      </form>
</div>
  <div class="right">
      <ul class="topnav signup">
        <?php if (isset($_SESSION['user'])): ?>
          <a href="/docs/help.html">Help</a> | <a href="logout.php">LogOut</a>
        <?php else: ?>
          <a href="/docs/help.html">Help</a> |
          <a href="/login.php">LogIn</a> |
          <a href="/register.php">SignUp</a>
        <?php endif; ?>
      </ul>
        </div>
    </div>
    <ul class="links">
      <?php
      $requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
      $currentUrl = parse_url($requestUri, PHP_URL_PATH);
      $currentPage = basename($currentUrl);

      $isHomePage = in_array($currentPage, array('index.php', 'home.php'));

      $navItems = array(
        'Home' => '/index.php',
        'Browse' => '/browse.php',
        'Search' => '/search.php',
        'Mail' => '/messages/inbox.php',
        'Blog' => '/blog/',
        'Bulletins' => '/bulletins/',
        'Forum' => '/forum/forums.php',
        'Groups' => '#',
        'Layouts' => '#',
        'Favs' => '/favorites.php',
        'Source' => 'https://github.com/superswan/anyspace',
        'Help' => '/docs/help.html',
        'About' => '/about.php',
      );

      foreach ($navItems as $name => $page) {
        if ($name == 'Home' && $isHomePage) {
          $activeClass = 'class="active"';
        } else {
          $activeClass = ($currentPage == basename($page)) ? 'class="active"' : '';
        }
        $display = $name;
        if ($name === 'Mail' && $unreadMessages > 0) {
          $display .= ' (' . $unreadMessages . ')';
        }
        echo "<li><a href=\"$page\" $activeClass>&nbsp;$display </a></li>";
      }
      if (in_array(forum_user_role(), ['admin','global_mod'])) {
        $activeClass = ($currentPage == 'dashboard.php') ? 'class="active"' : '';
        echo "<li><a href=\"/forum/mod/dashboard.php\" $activeClass>Mod</a></li>";
      }
      ?>
    </ul>
  </nav>



</header>
<!-- END HEADER -->
