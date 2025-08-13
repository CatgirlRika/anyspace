<?php
session_start();
require_once("../core/helper.php");

// Redirect if configuration already exists
if (file_exists("../core/config.php")) {
    header("Location: index.php");
    exit;
}

$requirementsErrors = [];

// Required PHP extensions
$requiredExtensions = [
    'pdo' => 'PDO',
    'pdo_mysql' => 'PDO MySQL',
    'fileinfo' => 'fileinfo'
];
foreach ($requiredExtensions as $ext => $name) {
    if (!extension_loaded($ext)) {
        $requirementsErrors[] = "PHP extension {$name} is required.";
    }
}

// Directories that must be writable
$requiredDirs = [
    __DIR__ . '/../core' => 'core/',
    __DIR__ . '/media/pfp' => 'public/media/pfp/',
    __DIR__ . '/media/music' => 'public/media/music/'
];
foreach ($requiredDirs as $path => $label) {
    if (!is_dir($path)) {
        $requirementsErrors[] = "Directory {$label} is missing.";
    } elseif (!is_writable($path)) {
        $requirementsErrors[] = "Directory {$label} is not writable.";
    }
}

$requirementsMet = empty($requirementsErrors);

$error = '';

if ($requirementsMet && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = trim($_POST['host']);
    $dbName = trim($_POST['dbname']);
    $dbUser = trim($_POST['username']);
    $dbPass = $_POST['password'];
    $siteName = trim($_POST['siteName']);
    $domainName = trim($_POST['domainName']);
    $adminUsername = trim($_POST['adminUsername']);
    $adminEmail = trim($_POST['adminEmail']);

    // Test database connection before writing config
    try {
        $dsn = "mysql:host={$dbHost};dbname={$dbName}";
        $conn = new PDO($dsn, $dbUser, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        $error = 'Database connection failed: ' . $e->getMessage();
    }

    if (!$error) {
        $config_file = "../core/config.php";
        $config_content = "<?php\n";
        $config_content .= "// Database configuration\n";
        $config_content .= "\$host = '" . addslashes($dbHost) . "';\n";
        $config_content .= "\$dbname = '" . addslashes($dbName) . "';\n";
        $config_content .= "\$username = '" . addslashes($dbUser) . "';\n";
        $config_content .= "\$password = '" . addslashes($dbPass) . "';\n\n";
        $config_content .= "// Site localization\n";
        $config_content .= "\$siteName = \"" . addslashes($siteName) . "\";\n";
        $config_content .= "\$domainName = \"" . addslashes($domainName) . "\";\n";
        $config_content .= "\$adminUser = 1;\n";
        $config_content .= "\n?>";

        if (file_put_contents($config_file, $config_content) === false) {
            $error = 'Failed to write config file. Please check permissions.';
        } else {
            chmod($config_file, 0644);
            require_once("../lib/password.php");

            try {
                $conn->beginTransaction();

                $schemaPath = realpath(__DIR__ . '/../schema.sql');
                if (!$schemaPath || !file_exists($schemaPath)) {
                    throw new Exception('schema.sql not found.');
                }

                $schemaSql = file_get_contents($schemaPath);
                // Remove comments
                $schemaSql = preg_replace('/^--.*$/m', '', $schemaSql);
                $schemaSql = preg_replace('/^#.*$/m', '', $schemaSql);
                $statements = array_filter(array_map('trim', explode(';', $schemaSql)));

                foreach ($statements as $statement) {
                    if ($statement !== '') {
                        $conn->exec($statement);
                    }
                }

                function generateRandomPassword($length = 12) {
                    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                    $randomPassword = '';
                    for ($i = 0, $len = strlen($characters); $i < $length; $i++) {
                        $randomPassword .= $characters[rand(0, $len - 1)];
                    }
                    return $randomPassword;
                }

                $password = generateRandomPassword();
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $sqlInsertUser = "INSERT INTO users (id, rank, username, email, password, date, bio, interests, css, music, pfp, currentgroup, status, private, views) VALUES (1, 1, ?, ?, ?, NOW(), '', '', '', 'default.mp3', 'default.jpg', 'None', '', 0, 0);";
                $stmt = $conn->prepare($sqlInsertUser);
                $stmt->execute(array($adminUsername, $adminEmail, $hashedPassword));

                $conn->commit();

                ?>
                <!DOCTYPE html>
                <html>
                <head>
                    <title>AnySpace Installation Complete</title>
                    <style>
                        body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 0 20px; }
                        .success { background: #e8f5e9; padding: 20px; border-radius: 4px; margin: 20px 0; }
                        .success a { color: #2196F3; text-decoration: none; }
                        .success a:hover { text-decoration: underline; }
                        .credentials { background: #fff3e0; padding: 20px; border-radius: 4px; margin: 20px 0; }
                    </style>
                </head>
                <body>
                    <h1>Installation Complete!</h1>

                    <div class="credentials">
                        <h3>Admin Account Credentials</h3>
                        <p>Username: <?php echo htmlspecialchars($adminUsername); ?></p>
                        <p>Email: <?php echo htmlspecialchars($adminEmail); ?></p>
                        <p>Password: <?php echo htmlspecialchars($password); ?></p>
                        <p><strong>Please save these credentials immediately!</strong></p>
                    </div>

                    <div class="success">
                        <h3>Installation Successful!</h3>
                        <p>Your AnySpace installation is complete. Next steps:</p>
                        <ul>
                            <li><strong>Delete the install.php file.</strong></li>
                            <li><a href="//<?php echo htmlspecialchars($domainName); ?>">Visit homepage</a></li>
                            <li><a href="//<?php echo htmlspecialchars($domainName); ?>/admin/">Access the admin panel</a></li>
                        </ul>
                    </div>
                </body>
                </html>
                <?php
                exit;
            } catch (Exception $e) {
                $conn->rollBack();
                $error = 'Installation failed: ' . $e->getMessage();
            }
        }
    }
}

$hostValue = htmlspecialchars($_POST['host'] ?? 'localhost');
$dbnameValue = htmlspecialchars($_POST['dbname'] ?? 'anyspace');
$dbuserValue = htmlspecialchars($_POST['username'] ?? 'anyspace');
$siteNameValue = htmlspecialchars($_POST['siteName'] ?? 'AnySpace');
$domainValue = htmlspecialchars($_POST['domainName'] ?? ($_SERVER['HTTP_HOST'] ?? ''));
$adminUserValue = htmlspecialchars($_POST['adminUsername'] ?? '');
$adminEmailValue = htmlspecialchars($_POST['adminEmail'] ?? '');

?>
<!DOCTYPE html>
<html>
<head>
    <title>AnySpace Installation</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 0 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="text"], input[type="password"], input[type="email"] { width: 100%; padding: 8px; }
        .section { margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
        .error { background: #ffebee; color: #c62828; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
    </style>
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
    <h1>AnySpace Installation</h1>
    <?php if (!$requirementsMet): ?>
        <div class="error">
            <p>Please fix the following issues before continuing:</p>
            <ul>
                <?php foreach ($requirementsErrors as $msg): ?>
                    <li><?= htmlspecialchars($msg); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($requirementsMet): ?>
    <form method="POST">
        <?= csrf_token_input(); ?>
        <div class="section">
            <h2>Database Configuration</h2>
            <div class="form-group">
                <label>Database Host:</label>
                <input type="text" name="host" value="<?= $hostValue ?>" required>
            </div>
            <div class="form-group">
                <label>Database Name:</label>
                <input type="text" name="dbname" value="<?= $dbnameValue ?>" required>
            </div>
            <div class="form-group">
                <label>Database Username:</label>
                <input type="text" name="username" value="<?= $dbuserValue ?>" required>
            </div>
            <div class="form-group">
                <label>Database Password:</label>
                <input type="password" name="password">
            </div>
        </div>

        <div class="section">
            <h2>Site Configuration</h2>
            <div class="form-group">
                <label>Site Name:</label>
                <input type="text" name="siteName" value="<?= $siteNameValue ?>" required>
            </div>
            <div class="form-group">
                <label>Domain Name:</label>
                <input type="text" name="domainName" value="<?= $domainValue ?>" required>
            </div>
        </div>

        <div class="section">
            <h2>Admin Account</h2>
            <div class="form-group">
                <label>Admin Username:</label>
                <input type="text" name="adminUsername" value="<?= $adminUserValue ?>" required>
            </div>
            <div class="form-group">
                <label>Admin Email:</label>
                <input type="email" name="adminEmail" value="<?= $adminEmailValue ?>" required>
            </div>
        </div>

        <input type="submit" value="Install AnySpace">
    </form>
    <?php endif; ?>
</body>
</html>

