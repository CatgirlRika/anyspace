<?php
/**
 * Magic Login System
 * Provides passwordless login via email links
 */

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../email_config.php';

/**
 * Generate a secure random token for magic login
 * @return string 64-character hex token
 */
function generateMagicToken() {
    if (function_exists('random_bytes')) {
        return bin2hex(random_bytes(32));
    } elseif (function_exists('openssl_random_pseudo_bytes')) {
        return bin2hex(openssl_random_pseudo_bytes(32));
    } else {
        // Fallback for older PHP versions
        return hash('sha256', uniqid(mt_rand(), true) . microtime(true));
    }
}

/**
 * Create a magic login token for a user
 * @param string $email User's email address
 * @return array Result with success status and message
 */
function createMagicLoginToken($email) {
    global $conn;
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email address.'];
    }
    
    // Check if user exists
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // Don't reveal if email exists or not for security
        return ['success' => true, 'message' => 'If this email is registered, you will receive a magic login link.'];
    }
    
    // Rate limiting: Check for recent tokens (prevent spam)
    $stmt = $conn->prepare("SELECT COUNT(*) FROM login_tokens WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
    $stmt->execute([$user['id']]);
    $recentTokens = $stmt->fetchColumn();
    
    if ($recentTokens >= 3) {
        return ['success' => false, 'message' => 'Too many login attempts. Please wait 5 minutes before requesting another magic login link.'];
    }
    
    // Generate token
    $token = generateMagicToken();
    $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    
    // Store token in database
    $stmt = $conn->prepare("INSERT INTO login_tokens (user_id, token, expires_at, ip_address) VALUES (?, ?, ?, ?)");
    $result = $stmt->execute([$user['id'], $token, $expiresAt, $ipAddress]);
    
    if (!$result) {
        return ['success' => false, 'message' => 'Failed to generate magic login link. Please try again.'];
    }
    
    // Send magic login email
    $emailSent = sendMagicLoginEmail($email, $user['username'], $token);
    
    if (!$emailSent) {
        // Clean up the token if email failed
        $stmt = $conn->prepare("DELETE FROM login_tokens WHERE token = ?");
        $stmt->execute([$token]);
        return ['success' => false, 'message' => 'Failed to send magic login email. Please try again.'];
    }
    
    return ['success' => true, 'message' => 'If this email is registered, you will receive a magic login link.'];
}

/**
 * Validate and use a magic login token
 * @param string $token The magic login token
 * @return array Result with success status, message, and user data
 */
function validateMagicLoginToken($token) {
    global $conn;
    
    if (empty($token) || strlen($token) !== 64) {
        return ['success' => false, 'message' => 'Invalid token format.'];
    }
    
    // Find the token and associated user
    $stmt = $conn->prepare("
        SELECT lt.*, u.id as user_id, u.username, u.rank 
        FROM login_tokens lt 
        JOIN users u ON lt.user_id = u.id 
        WHERE lt.token = ? AND lt.used_at IS NULL AND lt.expires_at > NOW()
    ");
    $stmt->execute([$token]);
    $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tokenData) {
        return ['success' => false, 'message' => 'Invalid or expired magic login link.'];
    }
    
    // Mark token as used
    $stmt = $conn->prepare("UPDATE login_tokens SET used_at = NOW() WHERE token = ?");
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

/**
 * Send magic login email to user
 * @param string $email User's email address
 * @param string $username User's username
 * @param string $token Magic login token
 * @return bool Success status
 */
function sendMagicLoginEmail($email, $username, $token) {
    $emailConfig = include __DIR__ . '/../email_config.php';
    
    // Skip email sending if not configured (for development)
    if (empty($emailConfig['smtp_host']) || $emailConfig['smtp_host'] === 'smtp.example.com') {
        error_log("Magic login email would be sent to $email with token: $token");
        return true; // Return true for development
    }
    
    $siteName = defined('SITE_NAME') ? SITE_NAME : 'AnySpace';
    $domainName = defined('DOMAIN_NAME') ? DOMAIN_NAME : $_SERVER['HTTP_HOST'];
    
    $magicLink = "https://{$domainName}/magic-login.php?token=" . urlencode($token);
    
    $subject = "Magic Login Link for {$siteName}";
    $message = "Hello {$username},\n\n";
    $message .= "Click the link below to log in to {$siteName}:\n\n";
    $message .= "{$magicLink}\n\n";
    $message .= "This link will expire in 30 minutes and can only be used once.\n\n";
    $message .= "If you didn't request this login link, you can safely ignore this email.\n\n";
    $message .= "Best regards,\n{$siteName} Team";
    
    $headers = [
        'From' => $emailConfig['from_email'],
        'Reply-To' => $emailConfig['from_email'],
        'Content-Type' => 'text/plain; charset=UTF-8',
        'X-Mailer' => 'PHP/' . phpversion()
    ];
    
    $headerString = '';
    foreach ($headers as $key => $value) {
        $headerString .= "$key: $value\r\n";
    }
    
    return mail($email, $subject, $message, $headerString);
}

/**
 * Clean up expired magic login tokens (should be called periodically)
 */
function cleanupExpiredTokens() {
    global $conn;
    
    $stmt = $conn->prepare("DELETE FROM login_tokens WHERE expires_at < NOW() OR used_at IS NOT NULL");
    $stmt->execute();
    
    return $stmt->rowCount();
}

?>