<?php
/**
 * Rate limiting functionality for forum posts to prevent spam
 */

/**
 * Check if user has exceeded posting rate limit
 * @param int $user_id User ID to check
 * @param int $limit_minutes Time window in minutes (default: 1 minute)
 * @param int $max_posts Maximum posts allowed in time window (default: 3)
 * @return bool True if rate limit exceeded, false otherwise
 */
function forum_rate_limit_exceeded($user_id, $limit_minutes = 1, $max_posts = 3) {
    global $conn;
    
    $since = date('Y-m-d H:i:s', time() - ($limit_minutes * 60));
    
    $stmt = $conn->prepare('SELECT COUNT(*) FROM forum_posts WHERE user_id = :uid AND created_at > :since');
    $stmt->execute([':uid' => $user_id, ':since' => $since]);
    $count = (int)$stmt->fetchColumn();
    
    return $count >= $max_posts;
}

/**
 * Get remaining time until user can post again
 * @param int $user_id User ID to check
 * @param int $limit_minutes Time window in minutes
 * @return int Seconds until next post allowed, 0 if can post now
 */
function forum_rate_limit_time_remaining($user_id, $limit_minutes = 1) {
    global $conn;
    
    $stmt = $conn->prepare('SELECT created_at FROM forum_posts WHERE user_id = :uid ORDER BY created_at DESC LIMIT 1');
    $stmt->execute([':uid' => $user_id]);
    $lastPost = $stmt->fetchColumn();
    
    if (!$lastPost) {
        return 0;
    }
    
    $lastPostTime = strtotime($lastPost);
    $nextAllowedTime = $lastPostTime + ($limit_minutes * 60);
    $remaining = $nextAllowedTime - time();
    
    return max(0, $remaining);
}
?>