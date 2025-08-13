<?php

function forum_get_all_forums(): array {
    global $conn;
    $stmt = $conn->query('SELECT * FROM forums ORDER BY position, id');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function forum_get_forums_by_category(int $category_id): array {
    global $conn;
    $stmt = $conn->prepare('SELECT id, name, description, position, parent_forum_id FROM forums WHERE category_id = :cid ORDER BY position, id');
    $stmt->execute([':cid' => $category_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function forum_get_forum(int $forum_id): ?array {
    global $conn;
    $stmt = $conn->prepare('SELECT id, category_id, name, description FROM forums WHERE id = :id');
    $stmt->execute([':id' => $forum_id]);
    $forum = $stmt->fetch(PDO::FETCH_ASSOC);
    return $forum ?: null;
}

function forum_create_forum(int $category_id, string $name, string $description, int $position = 0, ?int $parent_forum_id = null): int {
    global $conn;
    $stmt = $conn->prepare('INSERT INTO forums (category_id, parent_forum_id, name, description, position) VALUES (:cid, :pid, :name, :descr, :pos)');
    $stmt->execute([':cid' => $category_id, ':pid' => $parent_forum_id, ':name' => $name, ':descr' => $description, ':pos' => $position]);
    return (int)$conn->lastInsertId();
}

function forum_update_forum(int $id, int $category_id, string $name, string $description, int $position, ?int $parent_forum_id = null): void {
    global $conn;
    $stmt = $conn->prepare('UPDATE forums SET category_id = :cid, parent_forum_id = :pid, name = :name, description = :descr, position = :pos WHERE id = :id');
    $stmt->execute([':cid' => $category_id, ':pid' => $parent_forum_id, ':name' => $name, ':descr' => $description, ':pos' => $position, ':id' => $id]);
}

function forum_delete_forum(int $id): void {
    global $conn;

    // delete child forums first
    $childStmt = $conn->prepare('SELECT id FROM forums WHERE parent_forum_id = :id');
    $childStmt->execute([':id' => $id]);
    $children = $childStmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($children as $childId) {
        forum_delete_forum((int)$childId);
    }

    // remove posts and topics belonging to this forum
    $postDel = $conn->prepare('DELETE FROM forum_posts WHERE topic_id IN (SELECT id FROM forum_topics WHERE forum_id = :id)');
    $postDel->execute([':id' => $id]);
    $topicDel = $conn->prepare('DELETE FROM forum_topics WHERE forum_id = :id');
    $topicDel->execute([':id' => $id]);

    // remove moderator assignments and permissions
    $modDel = $conn->prepare('DELETE FROM forum_moderators WHERE forum_id = :id');
    $modDel->execute([':id' => $id]);
    $permDel = $conn->prepare('DELETE FROM forum_permissions WHERE forum_id = :id');
    $permDel->execute([':id' => $id]);

    // finally delete the forum itself
    $stmt = $conn->prepare('DELETE FROM forums WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

function searchTopics(string $query, ?string $role = null): array {
    global $conn;

    if ($role === null) {
        require_once __DIR__ . '/permissions.php';
        $role = forum_user_role();
    }

    if (in_array($role, ['admin', 'global_mod'], true)) {
        $stmt = $conn->prepare('SELECT DISTINCT t.id, t.title FROM forum_topics t LEFT JOIN forum_posts p ON t.id = p.topic_id WHERE t.title LIKE :search OR p.body LIKE :search ORDER BY t.id DESC');
        $stmt->execute([':search' => "%" . $query . "%"]);
    } else {
        $stmt = $conn->prepare('SELECT DISTINCT t.id, t.title FROM forum_topics t LEFT JOIN forum_posts p ON t.id = p.topic_id JOIN forum_permissions fp ON t.forum_id = fp.forum_id AND fp.role = :role AND fp.can_view = 1 WHERE t.title LIKE :search OR p.body LIKE :search ORDER BY t.id DESC');
        $stmt->execute([':search' => "%" . $query . "%", ':role' => $role]);
    }

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get cached forum statistics or compute them if cache is expired
 * @param int $forum_id Forum ID (0 for global stats)
 * @return array Statistics array
 */
function forum_get_cached_stats(int $forum_id = 0): array {
    $cache_key = "forum_stats_" . $forum_id;
    $cache_file = __DIR__ . "/../../cache/" . $cache_key . ".json";
    $cache_ttl = 300; // 5 minutes cache time
    
    // Create cache directory if it doesn't exist
    $cache_dir = dirname($cache_file);
    if (!is_dir($cache_dir)) {
        @mkdir($cache_dir, 0755, true);
    }
    
    // Check if cache exists and is still valid
    if (file_exists($cache_file)) {
        $cache_time = filemtime($cache_file);
        if ((time() - $cache_time) < $cache_ttl) {
            $cached_data = json_decode(file_get_contents($cache_file), true);
            if ($cached_data) {
                return $cached_data;
            }
        }
    }
    
    // Compute fresh statistics
    $stats = forum_compute_stats($forum_id);
    
    // Save to cache
    file_put_contents($cache_file, json_encode($stats));
    
    return $stats;
}

/**
 * Compute forum statistics
 * @param int $forum_id Forum ID (0 for global stats)
 * @return array Statistics array
 */
function forum_compute_stats(int $forum_id = 0): array {
    global $conn;
    
    if ($forum_id > 0) {
        // Stats for specific forum
        $stmt = $conn->prepare('SELECT COUNT(*) FROM forum_topics WHERE forum_id = :fid');
        $stmt->execute([':fid' => $forum_id]);
        $topics = (int)$stmt->fetchColumn();
        
        $stmt = $conn->prepare('SELECT COUNT(*) FROM forum_posts p JOIN forum_topics t ON p.topic_id = t.id WHERE t.forum_id = :fid');
        $stmt->execute([':fid' => $forum_id]);
        $posts = (int)$stmt->fetchColumn();
        
        $stmt = $conn->prepare('SELECT MAX(p.created_at) FROM forum_posts p JOIN forum_topics t ON p.topic_id = t.id WHERE t.forum_id = :fid');
        $stmt->execute([':fid' => $forum_id]);
        $last_post = $stmt->fetchColumn();
        
        return [
            'topics' => $topics,
            'posts' => $posts,
            'last_post' => $last_post ?: 'No posts yet'
        ];
    } else {
        // Global stats
        $stmt = $conn->query('SELECT COUNT(*) FROM forum_topics');
        $topics = (int)$stmt->fetchColumn();
        
        $stmt = $conn->query('SELECT COUNT(*) FROM forum_posts');
        $posts = (int)$stmt->fetchColumn();
        
        $stmt = $conn->query('SELECT COUNT(DISTINCT user_id) FROM forum_posts');
        $active_users = (int)$stmt->fetchColumn();
        
        $stmt = $conn->query('SELECT MAX(created_at) FROM forum_posts');
        $last_post = $stmt->fetchColumn();
        
        return [
            'topics' => $topics,
            'posts' => $posts,
            'active_users' => $active_users,
            'last_post' => $last_post ?: 'No posts yet'
        ];
    }
}

/**
 * Clear forum statistics cache
 * @param int $forum_id Forum ID (0 for all caches)
 */
function forum_clear_stats_cache(int $forum_id = 0): void {
    $cache_dir = __DIR__ . "/../../cache/";
    
    if ($forum_id > 0) {
        // Clear specific forum cache
        $cache_file = $cache_dir . "forum_stats_" . $forum_id . ".json";
        @unlink($cache_file);
    } else {
        // Clear all forum stats caches
        $pattern = $cache_dir . "forum_stats_*.json";
        foreach (glob($pattern) as $file) {
            @unlink($file);
        }
    }
}

?>
