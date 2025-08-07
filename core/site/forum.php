<?php
require_once __DIR__ . '/user.php';

/**
 * Render a forum post with user avatar and online badge.
 *
 * Expected structure of $post:
 * - 'author' => user id of the post author
 * - 'text'   => post body
 * - 'date'   => timestamp or formatted string
 */
function renderForumPost(array $post)
{
    $user = fetchUserInfo($post['author']);
    if (!$user) {
        return;
    }

    $username = htmlspecialchars($user['username']);
    $profileLink = '/profile.php?id=' . intval($user['id']);
    $avatarPath = 'media/pfp/' . $user['pfp'];
    $avatarPath = htmlspecialchars($avatarPath);

    // Determine if the user is currently online (active within last 5 minutes)
    $badge = '';
    if (!empty($user['lastactive'])) {
        $lastActive = strtotime($user['lastactive']);
        if ($lastActive !== false && (time() - $lastActive) <= 300) {
            $badge = '<img class="online-badge" src="static/img/online_now.gif" alt="Online Now" loading="lazy">';
        }
    }

    $body = nl2br(htmlspecialchars($post['text']));

    echo "<div class='forum-post'>";
    echo "  <div class='avatar-wrapper'>";
    echo "    <a href='{$profileLink}'><img class='avatar' src='{$avatarPath}' alt='{$username}\'s avatar' loading='lazy'></a>";
    if ($badge) {
        echo $badge;
    }
    echo "  </div>";
    echo "  <div class='post-body'>";
    echo "    <a class='username' href='{$profileLink}'>{$username}</a>";
    echo "    <p>{$body}</p>";
    echo "  </div>";
    echo "</div>";
}
?>
