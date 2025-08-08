<?php
require_once __DIR__ . '/../core/users/ban.php';
require_once __DIR__ . '/../core/helper.php';

session_start();

$dbFile = __DIR__ . '/forum_bans.db';
@unlink($dbFile);
$dsn = 'sqlite:' . $dbFile;
putenv('DB_DSN=' . $dsn);

$conn = new PDO($dsn);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$conn->sqliteCreateFunction('NOW', function() { return date('Y-m-d H:i:s'); });
$conn->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT, email TEXT, password TEXT, rank INTEGER, banned_until TEXT)');
$conn->exec('CREATE TABLE mod_log (id INTEGER PRIMARY KEY AUTOINCREMENT, moderator_id INTEGER, action TEXT, target_type TEXT, target_id INTEGER, timestamp TEXT DEFAULT CURRENT_TIMESTAMP)');

global $conn;

// seed user
$hash = password_hash('secret', PASSWORD_DEFAULT);
$conn->prepare('INSERT INTO users (username, email, password, rank, banned_until) VALUES (?,?,?,?,NULL)')
     ->execute(['alice', 'a@example.com', $hash, 0]);

// moderator session
$_SESSION = ['userId' => 99, 'user' => 'mod', 'rank' => 1];

echo "Ban user...\n";
$until = date('Y-m-d H:i:s', time() + 3600);
banUser(1, $until);
$banned = $conn->query('SELECT banned_until FROM users WHERE id = 1')->fetchColumn();
$log = $conn->query("SELECT COUNT(*) FROM mod_log WHERE action = 'ban' AND target_id = 1")->fetchColumn();
if ($banned === $until && (int)$log === 1) {
    echo "User banned\n";
} else {
    echo "Ban failed\n";
    unlink($dbFile);
    exit(1);
}

echo "Login blocked...\n";
$stmt = $conn->prepare('SELECT id, username, password, rank, banned_until FROM users WHERE email = ?');
$stmt->execute(['a@example.com']);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if ($user && password_verify('secret', $user['password']) && (empty($user['banned_until']) || strtotime($user['banned_until']) <= time())) {
    echo "Login allowed when should be banned\n";
    unlink($dbFile);
    exit(1);
} else {
    echo "Login denied\n";
}

echo "Session enforcement...\n";
$script = <<<'PHP'
<?php
session_start();
$dsn = getenv('DB_DSN');
$conn = new PDO($dsn);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
global $conn;
require __DIR__ . '/../core/helper.php';
$_SESSION = ['user' => 'alice', 'userId' => 1];
login_check();
echo "ALLOWED";
PHP;
file_put_contents(__DIR__ . '/check_ban.php', $script);
$output = shell_exec('php ' . escapeshellarg(__DIR__ . '/check_ban.php'));
$output = $output === null ? '' : trim($output);
unlink(__DIR__ . '/check_ban.php');
if ($output === '') {
    echo "Access denied\n";
} else {
    echo "Session check failed\n";
    unlink($dbFile);
    exit(1);
}

echo "Unban user...\n";
unbanUser(1);
$banned = $conn->query('SELECT banned_until FROM users WHERE id = 1')->fetchColumn();
$log = $conn->query("SELECT COUNT(*) FROM mod_log WHERE action = 'unban' AND target_id = 1")->fetchColumn();
if (($banned === null || $banned === false) && (int)$log === 1) {
    echo "User unbanned\n";
} else {
    echo "Unban failed\n";
    unlink($dbFile);
    exit(1);
}

echo "Login allowed after unban...\n";
$stmt->execute(['a@example.com']);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if ($user && password_verify('secret', $user['password']) && (empty($user['banned_until']) || strtotime($user['banned_until']) <= time())) {
    echo "Login allowed\n";
} else {
    echo "Login still blocked\n";
    unlink($dbFile);
    exit(1);
}

echo "Session allowed after unban...\n";
file_put_contents(__DIR__ . '/check_ban.php', $script);
$output = shell_exec('php ' . escapeshellarg(__DIR__ . '/check_ban.php'));
$output = $output === null ? '' : trim($output);
unlink(__DIR__ . '/check_ban.php');
if ($output === 'ALLOWED') {
    echo "Session ok\n";
} else {
    echo "Session still denied\n";
    unlink($dbFile);
    exit(1);
}

unlink($dbFile);
?>
