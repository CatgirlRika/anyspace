<?php
/**
 * Enhanced logging functionality for forum activities
 * Provides detailed audit trail for security and moderation purposes
 */

/**
 * Log forum activity with context and IP address
 * @param string $action Action performed (e.g., 'post_created', 'topic_locked')
 * @param int $user_id User who performed the action
 * @param string $details Additional context (e.g., topic_id, post_id)
 * @param string $level Log level (info, warning, error)
 */
function forum_log_activity($action, $user_id, $details = '', $level = 'info') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $timestamp = date('Y-m-d H:i:s');
    
    // Sanitize inputs
    $action = htmlspecialchars($action, ENT_QUOTES, 'UTF-8');
    $details = htmlspecialchars($details, ENT_QUOTES, 'UTF-8');
    $level = htmlspecialchars($level, ENT_QUOTES, 'UTF-8');
    
    $logEntry = "[$timestamp] [$level] User $user_id from $ip: $action";
    if (!empty($details)) {
        $logEntry .= " - $details";
    }
    $logEntry .= " (UA: " . substr($userAgent, 0, 100) . ")" . PHP_EOL;
    
    // Write to forum-specific log file
    $logFile = __DIR__ . '/../../admin_logs.txt';
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    
    // Also log to system error log for production monitoring
    error_log("FORUM: $logEntry");
}

/**
 * Log security-related events with higher priority
 * @param string $event Security event type
 * @param int $user_id User involved
 * @param string $details Event details
 */
function forum_log_security_event($event, $user_id, $details = '') {
    forum_log_activity("SECURITY: $event", $user_id, $details, 'warning');
}

/**
 * Log moderation actions with full context
 * @param string $action Moderation action
 * @param int $moderator_id Moderator who performed action
 * @param string $target_type Type of target (post, topic, user)
 * @param int $target_id ID of target
 * @param string $reason Reason for action
 */
function forum_log_moderation($action, $moderator_id, $target_type, $target_id, $reason = '') {
    $details = "Target: {$target_type}#{$target_id}";
    if (!empty($reason)) {
        $details .= ", Reason: $reason";
    }
    forum_log_activity("MODERATION: $action", $moderator_id, $details, 'info');
}

/**
 * Get recent forum activity logs for admin dashboard
 * @param int $limit Number of entries to return
 * @return array Recent log entries
 */
function forum_get_recent_activity($limit = 50) {
    $logFile = __DIR__ . '/../../admin_logs.txt';
    if (!file_exists($logFile)) {
        return [];
    }
    
    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $lines = array_reverse($lines); // Most recent first
    return array_slice($lines, 0, $limit);
}
?>