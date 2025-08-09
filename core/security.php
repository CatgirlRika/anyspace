<?php
/**
 * Security Module for AnySpace
 * Comprehensive security enhancements including password validation, 
 * rate limiting, audit logging, and input sanitization
 */

/**
 * Password complexity validation
 * Ensures passwords meet security requirements
 * @param string $password Password to validate
 * @return array Result with isValid boolean and errors array
 */
function validatePasswordComplexity($password) {
    $errors = array();
    
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter';
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }
    
    if (!preg_match('/[@$!%*?&]/', $password)) {
        $errors[] = 'Password must contain at least one special character (@$!%*?&)';
    }
    
    // Check for common weak patterns
    $weakPatterns = array(
        '/^(.)\1+$/',  // All same characters
        '/^(012|123|234|345|456|567|678|789|890|abc|bcd|cde|def|efg|fgh|ghi|hij|ijk|jkl|klm|lmn|mno|nop|opq|pqr|qrs|rst|stu|tuv|uvw|vwx|wxy|xyz)/i',
        '/^(.)(.*)\1$/'  // Starts and ends with same character
    );
    
    foreach ($weakPatterns as $pattern) {
        if (preg_match($pattern, $password)) {
            $errors[] = 'Password contains predictable patterns';
            break;
        }
    }
    
    return array(
        'isValid' => empty($errors),
        'errors' => $errors
    );
}

/**
 * Enhanced rate limiting system
 * Tracks login attempts per IP and implements account lockouts
 */
class RateLimiter {
    private $conn;
    private $maxAttempts;
    private $lockoutDuration;
    private $windowDuration;
    
    public function __construct($conn, $maxAttempts = 5, $lockoutDuration = 900, $windowDuration = 900) {
        $this->conn = $conn;
        $this->maxAttempts = $maxAttempts;
        $this->lockoutDuration = $lockoutDuration; // 15 minutes
        $this->windowDuration = $windowDuration;   // 15 minutes
    }
    
    /**
     * Check if IP/user is rate limited
     * @param string $identifier IP address or user identifier
     * @param string $action Type of action (login, register, etc.)
     * @return array Result with allowed boolean and remaining attempts
     */
    public function checkRateLimit($identifier, $action = 'login') {
        $this->cleanup(); // Clean old entries
        
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as attempts, MAX(created_at) as last_attempt 
            FROM rate_limits 
            WHERE identifier = ? AND action = ? AND created_at > ?
        ");
        $cutoff = date('Y-m-d H:i:s', time() - $this->windowDuration);
        $stmt->execute(array($identifier, $action, $cutoff));
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $attempts = intval($result['attempts']);
        $lastAttempt = $result['last_attempt'];
        
        // Check if locked out
        if ($attempts >= $this->maxAttempts && $lastAttempt) {
            $lockoutEnd = strtotime($lastAttempt) + $this->lockoutDuration;
            if (time() < $lockoutEnd) {
                return array(
                    'allowed' => false,
                    'remaining' => 0,
                    'lockoutEnd' => $lockoutEnd
                );
            }
        }
        
        return array(
            'allowed' => $attempts < $this->maxAttempts,
            'remaining' => max(0, $this->maxAttempts - $attempts - 1)
        );
    }
    
    /**
     * Record a rate limit attempt
     * @param string $identifier IP address or user identifier
     * @param string $action Type of action
     * @param bool $success Whether the attempt was successful
     */
    public function recordAttempt($identifier, $action = 'login', $success = false) {
        // Only record failed attempts for rate limiting
        if (!$success) {
            $stmt = $this->conn->prepare("
                INSERT INTO rate_limits (identifier, action, created_at) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute(array($identifier, $action, date('Y-m-d H:i:s')));
        } else {
            // Clear rate limit on successful login
            $this->clearRateLimit($identifier, $action);
        }
    }
    
    /**
     * Clear rate limit for identifier
     */
    public function clearRateLimit($identifier, $action = 'login') {
        $stmt = $this->conn->prepare("DELETE FROM rate_limits WHERE identifier = ? AND action = ?");
        $stmt->execute(array($identifier, $action));
    }
    
    /**
     * Clean up old rate limit entries
     */
    private function cleanup() {
        $cutoff = date('Y-m-d H:i:s', time() - $this->lockoutDuration);
        $stmt = $this->conn->prepare("DELETE FROM rate_limits WHERE created_at < ?");
        $stmt->execute(array($cutoff));
    }
}

/**
 * Security audit logger
 * Logs security events for monitoring and analysis
 */
class SecurityAuditLogger {
    private $conn;
    
    // Security event types
    const AUTH_SUCCESS = 'AUTH_SUCCESS';
    const AUTH_FAILURE = 'AUTH_FAILURE';
    const AUTH_LOCKOUT = 'AUTH_LOCKOUT';
    const PERMISSION_DENIED = 'PERMISSION_DENIED';
    const SUSPICIOUS_ACTIVITY = 'SUSPICIOUS_ACTIVITY';
    const RATE_LIMIT_EXCEEDED = 'RATE_LIMIT_EXCEEDED';
    const PASSWORD_CHANGE = 'PASSWORD_CHANGE';
    const ADMIN_ACTION = 'ADMIN_ACTION';
    
    // Risk levels
    const RISK_LOW = 'LOW';
    const RISK_MEDIUM = 'MEDIUM';
    const RISK_HIGH = 'HIGH';
    const RISK_CRITICAL = 'CRITICAL';
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Log a security event
     * @param string $eventType Type of security event
     * @param string $riskLevel Risk level of the event
     * @param int $userId User ID (if applicable)
     * @param array $details Additional event details
     */
    public function logEvent($eventType, $riskLevel, $userId = null, $details = array()) {
        $ipAddress = $this->maskIP($this->getClientIP());
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown';
        $requestPath = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'unknown';
        $requestMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'unknown';
        
        // Don't log sensitive data - mask or exclude sensitive information
        $safeDetails = $this->sanitizeLogDetails($details);
        
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO security_logs (event_type, risk_level, user_id, ip_address, user_agent, 
                                         request_path, request_method, details, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute(array(
                $eventType,
                $riskLevel,
                $userId,
                $ipAddress,
                $userAgent,
                $requestPath,
                $requestMethod,
                json_encode($safeDetails),
                date('Y-m-d H:i:s')
            ));
            
            // Log to error log for immediate visibility
            $logMessage = "[SECURITY] $eventType - $riskLevel";
            if ($userId) {
                $logMessage .= " (User: $userId)";
            }
            $logMessage .= " (IP: $ipAddress)";
            error_log($logMessage);
            
            // Alert on high-risk events
            if ($riskLevel === self::RISK_HIGH || $riskLevel === self::RISK_CRITICAL) {
                $this->alertHighRiskEvent($eventType, $riskLevel, $userId, $ipAddress, $safeDetails);
            }
            
        } catch (Exception $e) {
            error_log("Failed to log security event: " . $e->getMessage());
        }
    }
    
    /**
     * Get client IP address
     */
    private function getClientIP() {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_X_REAL_IP'])) {
            return $_SERVER['HTTP_X_REAL_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }
        return 'unknown';
    }
    
    /**
     * Mask IP address for privacy
     */
    private function maskIP($ip) {
        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            return $parts[0] . '.' . $parts[1] . '.xxx.xxx';
        }
        return 'xxx.xxx.xxx.xxx';
    }
    
    /**
     * Sanitize log details to remove sensitive information
     */
    private function sanitizeLogDetails($details) {
        $sensitiveKeys = array('password', 'token', 'secret', 'key');
        $safe = array();
        
        foreach ($details as $key => $value) {
            $lowerKey = strtolower($key);
            $isSensitive = false;
            
            foreach ($sensitiveKeys as $sensitiveKey) {
                if (strpos($lowerKey, $sensitiveKey) !== false) {
                    $isSensitive = true;
                    break;
                }
            }
            
            if ($isSensitive) {
                $safe[$key] = '[REDACTED]';
            } else {
                $safe[$key] = is_string($value) ? substr($value, 0, 255) : $value;
            }
        }
        
        return $safe;
    }
    
    /**
     * Alert on high-risk security events
     */
    private function alertHighRiskEvent($eventType, $riskLevel, $userId, $ipAddress, $details) {
        $message = "🚨 HIGH RISK SECURITY EVENT: $eventType";
        $message .= "\nRisk Level: $riskLevel";
        $message .= "\nUser ID: " . ($userId ?: 'N/A');
        $message .= "\nIP Address: $ipAddress";
        $message .= "\nTimestamp: " . date('Y-m-d H:i:s');
        $message .= "\nDetails: " . json_encode($details);
        
        error_log($message);
        
        // In production, this could send emails or integrate with monitoring systems
    }
}

/**
 * Enhanced input sanitization
 * Improves upon existing validation with additional security measures
 */
class InputSanitizer {
    
    /**
     * Sanitize HTML content with comprehensive XSS protection
     * Uses DOMDocument for more robust parsing than regex
     * @param string $html HTML content to sanitize
     * @param array $allowedTags Allowed HTML tags
     * @param array $allowedAttrs Allowed attributes
     * @return string Sanitized HTML
     */
    public static function sanitizeHTML($html, $allowedTags = null, $allowedAttrs = null) {
        if (empty($html)) {
            return '';
        }
        
        if ($allowedTags === null) {
            $allowedTags = array('p', 'br', 'strong', 'em', 'u', 'code', 'pre', 'blockquote', 'a', 'img');
        }
        
        if ($allowedAttrs === null) {
            $allowedAttrs = array('href', 'src', 'alt', 'title');
        }
        
        // First, remove obviously dangerous content
        $html = self::removeScriptTags($html);
        $html = self::removeDangerousURLs($html);
        
        // Use DOMDocument for more robust parsing
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $loadResult = @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        
        if (!$loadResult) {
            // Fallback to simple sanitization
            return self::fallbackSanitize($html, $allowedTags);
        }
        
        $xpath = new DOMXPath($dom);
        
        // Remove disallowed tags and dangerous attributes
        $elementsToRemove = array();
        foreach ($xpath->query('//*') as $node) {
            if (!in_array(strtolower($node->nodeName), $allowedTags)) {
                $elementsToRemove[] = $node;
                continue;
            }
            
            if ($node->hasAttributes()) {
                $attrsToRemove = array();
                foreach ($node->attributes as $attr) {
                    $name = strtolower($attr->nodeName);
                    $value = $attr->nodeValue;
                    
                    // Remove event handlers
                    if (strpos($name, 'on') === 0) {
                        $attrsToRemove[] = $attr->nodeName;
                        continue;
                    }
                    
                    // Remove non-whitelisted attributes
                    if (!in_array($name, $allowedAttrs)) {
                        $attrsToRemove[] = $attr->nodeName;
                        continue;
                    }
                    
                    // Sanitize URLs
                    if (in_array($name, array('href', 'src'))) {
                        if (preg_match('/^\s*(javascript|data|vbscript):/i', $value)) {
                            $attrsToRemove[] = $attr->nodeName;
                            continue;
                        }
                    }
                }
                
                foreach ($attrsToRemove as $attrName) {
                    $node->removeAttribute($attrName);
                }
            }
        }
        
        // Remove flagged elements
        foreach ($elementsToRemove as $element) {
            if ($element->parentNode) {
                $element->parentNode->removeChild($element);
            }
        }
        
        $result = $dom->saveHTML();
        libxml_clear_errors();
        
        return $result;
    }
    
    /**
     * Remove script tags more comprehensively
     */
    private static function removeScriptTags($html) {
        // Remove script tags (case insensitive, handles malformed tags)
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
        $html = preg_replace('/<script\b[^>]*\/>/is', '', $html);
        
        // Remove other potentially dangerous tags
        $dangerousTags = array('script', 'iframe', 'object', 'embed', 'form', 'meta', 'link');
        foreach ($dangerousTags as $tag) {
            $html = preg_replace("/<{$tag}\b[^>]*>.*?<\/{$tag}>/is", '', $html);
            $html = preg_replace("/<{$tag}\b[^>]*\/>/is", '', $html);
        }
        
        return $html;
    }
    
    /**
     * Remove dangerous URLs
     */
    private static function removeDangerousURLs($html) {
        $html = preg_replace('/javascript\s*:/i', '', $html);
        $html = preg_replace('/vbscript\s*:/i', '', $html);
        $html = preg_replace('/data\s*:/i', '', $html);
        return $html;
    }
    
    /**
     * Fallback sanitization using strip_tags
     */
    private static function fallbackSanitize($html, $allowedTags) {
        $allowedTagsString = '<' . implode('><', $allowedTags) . '>';
        return strip_tags($html, $allowedTagsString);
    }
}

/**
 * Set security headers
 * Implements comprehensive security headers for XSS, clickjacking, etc.
 */
function setSecurityHeaders() {
    // Prevent clickjacking
    header('X-Frame-Options: DENY');
    
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Enable XSS filtering
    header('X-XSS-Protection: 1; mode=block');
    
    // Strict Transport Security (HTTPS only)
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }
    
    // Content Security Policy
    $csp = "default-src 'self'; " .
           "script-src 'self' 'unsafe-inline'; " .
           "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
           "font-src 'self' https://fonts.gstatic.com; " .
           "img-src 'self' data: https:; " .
           "connect-src 'self'; " .
           "frame-src 'none'; " .
           "object-src 'none'; " .
           "base-uri 'self'; " .
           "form-action 'self'";
    
    header("Content-Security-Policy: $csp");
    
    // Referrer Policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Permissions Policy
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}

// Initialize global security components if connection exists
if (isset($conn)) {
    $securityLogger = new SecurityAuditLogger($conn);
    $rateLimiter = new RateLimiter($conn);
}

?>