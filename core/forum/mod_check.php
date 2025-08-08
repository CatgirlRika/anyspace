<?php
require_once(__DIR__ . '/../helper.php');
require_once(__DIR__ . '/permissions.php');

function forum_mod_check(): void
{
    if (!isset($_SESSION['userId'])) {
        login_check();
    }
    $role = forum_user_role();
    if ($role !== 'admin' && $role !== 'global_mod') {
        header('HTTP/1.1 403 Forbidden');
        echo 'Forbidden';
        exit;
    }
}

?>
