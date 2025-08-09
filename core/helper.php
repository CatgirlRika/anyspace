<?php 
/**
 * Helper functions for AnySpace application
 * Provides authentication, CSRF protection, and input validation
 * Enhanced with comprehensive security features
 */

// Set security headers early
require_once __DIR__ . '/security.php';
setSecurityHeaders();

/**
 * Check if user is logged in and redirect to login if not
 * Also checks if user is banned and logs them out if so
 */
function login_check() {
    if (!isset($_SESSION['user'])) {
        header("Location: /login.php");
        exit;
    }

    require_once __DIR__ . '/users/user.php';
    if (isset($_SESSION['userId']) && isUserBanned($_SESSION['userId'])) {
        session_unset();
        session_destroy();
        header("Location: /login.php?msg=" . urlencode('Account banned'));
        exit;
    }
}

/**
 * Restrict access to admin users only
 */
function admin_only() {
    if (!isset($_SESSION['userId']) || (defined('ADMIN_USER') && $_SESSION['userId'] != ADMIN_USER)) {
        header("Location: /admin/login.php?msg=" . urlencode('Admin access required'));
        exit;
    }
}

/**
 * Centralized error logging and display function
 * @param string $message Error message
 * @param string $level Error level (info, warning, error)
 * @param bool $display Whether to display the error to user
 */
function handle_error($message, $level = 'error', $display = false) {
    error_log("[$level] $message");
    
    if ($display && defined('DEBUG') && DEBUG) {
        echo "<div class='error $level'>" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "</div>";
    }
}

/**
 * Generate CSRF token for form protection
 * Compatible with PHP 5.3+ with fallbacks for older random functions
 * @return string CSRF token
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        if (function_exists('random_bytes')) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
        } else {
            // Fallback for older PHP versions
            $_SESSION['csrf_token'] = bin2hex(uniqid(mt_rand(), true));
        }
    }
    return $_SESSION['csrf_token'];
}

function csrf_token_input() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES) . '">';
}

function csrf_verify() {
    if (!isset($_SERVER['REQUEST_METHOD'])) {
        return; // Skip verification if request method is not available
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
            !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            die('Invalid CSRF token');
        }
        unset($_SESSION['csrf_token']);
    }
}
csrf_verify();

/**
 * Validate and sanitize HTML content for user posts
 * Enhanced with comprehensive XSS protection using DOMDocument
 * @param string $validate HTML content to validate
 * @return string Sanitized HTML content
 */
function validateContentHTML($validate) {
    if (empty($validate)) {
        return '';
    }
    
    // Use enhanced sanitization from security module
    $allowedTags = array('a', 'b', 'big', 'blockquote', 'br', 'center', 'code', 'del', 
                        'div', 'em', 'font', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 
                        'i', 'img', 'li', 'mark', 'ol', 'p', 'pre', 'small', 'span', 
                        'strong', 'sub', 'sup', 'table', 'td', 'th', 'time', 'tr', 'u', 'ul');
    
    $allowedAttrs = array('href', 'src', 'alt', 'title', 'style', 'width', 'height', 'class');
    
    return InputSanitizer::sanitizeHTML($validate, $allowedTags, $allowedAttrs);
}

/**
 * Validate and sanitize HTML for user profile layouts
 * Enhanced with comprehensive security validation
 * @param string $html HTML content to validate
 * @return string Sanitized HTML content
 */
function validateLayoutHTML($html) {
    if (empty($html)) {
        return '';
    }
    
    $allowedTags = array('style', 'img', 'div', 'a', 'h1', 'h2', 'h3', 'p', 'ul',
                        'ol', 'li', 'blockquote', 'code', 'em', 'strong', 'br', 'span');
    $allowedAttrs = array('href', 'src', 'alt', 'title', 'style', 'width', 'height', 'class');

    return InputSanitizer::sanitizeHTML($html, $allowedTags, $allowedAttrs);
}

// thanks dzhaugasharov https://gist.github.com/afsalrahim/bc8caf497a4b54c5d75d
function replaceBBcodes($text) {
    $text = htmlspecialchars($text);
    // BBcode array
    $find = array(
        '~\[b\](.*?)\[/b\]~s',
        '~\[i\](.*?)\[/i\]~s',
        '~\[u\](.*?)\[/u\]~s',
        '~\[quote=([^\]]+?)\](.*?)\[/quote\]~s',
        '~\[quote\](.*?)\[/quote\]~s',
        '~\[size=([^"><]*?)\](.*?)\[/size\]~s',
        '~\[color=([^"><]*?)\](.*?)\[/color\]~s',
        '~\[url\]((?:ftp|https?)://[^"><]*?)\[/url\]~s',
        '~\[img\](https?://[^"><]*?\.(?:jpg|jpeg|gif|png|bmp))\[/img\]~s'
    );
    // HTML tags to replace BBcode
    $replace = array(
        '<b>$1</b>',
        '<i>$1</i>',
        '<span style="text-decoration:underline;">$1</span>',
        '<blockquote><cite>$1 wrote:</cite>$2</blockquote>',
        '<blockquote>$1</blockquote>',
        '<span style="font-size:$1px;">$2</span>',
        '<span style="color:$1;">$2</span>',
        '<a href="$1">$1</a>',
        '<img src="$1" alt="" />'
    );
    // Replacing the BBcodes with corresponding HTML tags
    return preg_replace($find, $replace, $text);
}

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    // Calculate weeks as a separate variable using days
    $weeks = floor($diff->d / 7);
    $diff->d -= $weeks * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );

    if ($weeks) {
        $string = array_merge(array('w' => 'week'), $string); 
    }

    foreach ($string as $k => &$v) {
        $value = ($k === 'w') ? $weeks : $diff->$k; 
        if ($value) {
            $v = $value . ' ' . $v . ($value > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}