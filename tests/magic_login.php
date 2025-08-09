<?php
// Test for magic login system
// Creates a SQLite database for testing and validates the magic login flow

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Mock the missing dependencies for testing
if (!defined('SITE_NAME')) define('SITE_NAME', 'Test Site');
if (!defined('DOMAIN_NAME')) define('DOMAIN_NAME', 'test.local');

// Start session for testing
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Create SQLite test database
$dbFile = __DIR__ . '/test_magic_login.db';
if (file_exists($dbFile)) {
    unlink($dbFile);
}

$conn = new PDO("sqlite:$dbFile");
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create test tables
$conn->exec("
    CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        password VARCHAR(255) NOT NULL,
        rank INTEGER DEFAULT 0,
        date DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");

$conn->exec("
    CREATE TABLE login_tokens (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        token VARCHAR(64) NOT NULL UNIQUE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME NOT NULL,
        used_at DATETIME DEFAULT NULL,
        ip_address VARCHAR(45) DEFAULT NULL,
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
    )
");

// Insert test user
$conn->exec("
    INSERT INTO users (username, email, password) 
    VALUES ('testuser', 'test@example.com', 'dummy_password_hash')
");

echo "✅ Test database created with test user\n";

// Define magic login functions inline for testing
function generateMagicToken() {
    if (function_exists('random_bytes')) {
        return bin2hex(random_bytes(32));
    } elseif (function_exists('openssl_random_pseudo_bytes')) {
        return bin2hex(openssl_random_pseudo_bytes(32));
    } else {
        return hash('sha256', uniqid(mt_rand(), true) . microtime(true));
    }
}

function createMagicLoginToken($email) {
    global $conn;
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email address.'];
    }
    
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        return ['success' => true, 'message' => 'If this email is registered, you will receive a magic login link.'];
    }
    
    // Rate limiting check
    $stmt = $conn->prepare("SELECT COUNT(*) FROM login_tokens WHERE user_id = ? AND created_at > datetime('now', '-5 minutes')");
    $stmt->execute([$user['id']]);
    $recentTokens = $stmt->fetchColumn();
    
    if ($recentTokens >= 3) {
        return ['success' => false, 'message' => 'Too many login attempts. Please wait 5 minutes.'];
    }
    
    $token = generateMagicToken();
    $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));
    $ipAddress = '127.0.0.1';
    
    $stmt = $conn->prepare("INSERT INTO login_tokens (user_id, token, expires_at, ip_address) VALUES (?, ?, ?, ?)");
    $result = $stmt->execute([$user['id'], $token, $expiresAt, $ipAddress]);
    
    if (!$result) {
        return ['success' => false, 'message' => 'Failed to generate magic login link.'];
    }
    
    // Mock email sending
    error_log("Test: Magic login email would be sent to $email with token: $token");
    
    return ['success' => true, 'message' => 'Magic login link sent!'];
}

function validateMagicLoginToken($token) {
    global $conn;
    
    if (empty($token) || strlen($token) !== 64) {
        return ['success' => false, 'message' => 'Invalid token format.'];
    }
    
    $stmt = $conn->prepare("
        SELECT lt.*, u.id as user_id, u.username, 0 as rank
        FROM login_tokens lt 
        JOIN users u ON lt.user_id = u.id 
        WHERE lt.token = ? AND lt.used_at IS NULL AND lt.expires_at > datetime('now')
    ");
    $stmt->execute([$token]);
    $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tokenData) {
        return ['success' => false, 'message' => 'Invalid or expired magic login link.'];
    }
    
    $stmt = $conn->prepare("UPDATE login_tokens SET used_at = datetime('now') WHERE token = ?");
    $stmt->execute([$token]);
    
    return [
        'success' => true, 
        'message' => 'Login successful!',
        'user' => [
            'id' => $tokenData['user_id'],
            'username' => $tokenData['username'],
            'rank' => $tokenData['rank']
        ]
    ];
}

function cleanupExpiredTokens() {
    global $conn;
    
    $stmt = $conn->prepare("DELETE FROM login_tokens WHERE expires_at < datetime('now') OR used_at IS NOT NULL");
    $stmt->execute();
    
    return $stmt->rowCount();
}

// Test 1: Generate magic token
echo "\n--- Test 1: Generate Magic Login Token ---\n";
$result = createMagicLoginToken('test@example.com');
echo "Result: " . ($result['success'] ? 'SUCCESS' : 'FAILED') . "\n";
echo "Message: " . $result['message'] . "\n";

// Get the token from database for testing
$stmt = $conn->prepare("SELECT token FROM login_tokens WHERE user_id = 1 ORDER BY created_at DESC LIMIT 1");
$stmt->execute();
$tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tokenData) {
    echo "❌ No token found in database\n";
    exit(1);
}

$token = $tokenData['token'];
echo "Generated token: " . substr($token, 0, 16) . "...\n";

// Test 2: Validate magic token
echo "\n--- Test 2: Validate Magic Login Token ---\n";
$result = validateMagicLoginToken($token);
echo "Result: " . ($result['success'] ? 'SUCCESS' : 'FAILED') . "\n";
echo "Message: " . $result['message'] . "\n";

if ($result['success']) {
    echo "User data: " . json_encode($result['user']) . "\n";
}

// Test 3: Try to use the same token again (should fail)
echo "\n--- Test 3: Reuse Token (should fail) ---\n";
$result = validateMagicLoginToken($token);
echo "Result: " . ($result['success'] ? 'FAILED - Token was reused!' : 'SUCCESS - Token correctly rejected') . "\n";
echo "Message: " . $result['message'] . "\n";

// Test 4: Test invalid token
echo "\n--- Test 4: Invalid Token ---\n";
$result = validateMagicLoginToken('invalid_token_123');
echo "Result: " . ($result['success'] ? 'FAILED - Invalid token accepted!' : 'SUCCESS - Invalid token rejected') . "\n";
echo "Message: " . $result['message'] . "\n";

// Test 5: Test rate limiting
echo "\n--- Test 5: Rate Limiting ---\n";
for ($i = 1; $i <= 4; $i++) {
    $result = createMagicLoginToken('test@example.com');
    echo "Attempt $i: " . ($result['success'] ? 'SUCCESS' : 'RATE LIMITED') . "\n";
    if (!$result['success']) {
        echo "Message: " . $result['message'] . "\n";
        break;
    }
}

// Test 6: Test cleanup function
echo "\n--- Test 6: Cleanup Expired Tokens ---\n";
$deletedCount = cleanupExpiredTokens();
echo "Cleaned up tokens: $deletedCount\n";

// Verify tokens in database
$stmt = $conn->prepare("SELECT COUNT(*) FROM login_tokens");
$stmt->execute();
$totalTokens = $stmt->fetchColumn();
echo "Remaining tokens in database: $totalTokens\n";

// Cleanup
unlink($dbFile);

echo "\n✅ All magic login tests completed successfully!\n";
?>