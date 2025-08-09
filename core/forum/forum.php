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

function searchTopics(string $query): array {
    global $conn;
    $stmt = $conn->prepare('SELECT DISTINCT t.id, t.title FROM forum_topics t LEFT JOIN forum_posts p ON t.id = p.topic_id WHERE t.title LIKE :search OR p.body LIKE :search ORDER BY t.id DESC');
    $stmt->execute([':search' => "%" . $query . "%"]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function forum_get_forum_stats(int $forum_id): array {
    global $conn;
    
    // Get topic count
    $topicStmt = $conn->prepare('SELECT COUNT(*) as topic_count FROM forum_topics WHERE forum_id = :fid');
    $topicStmt->execute([':fid' => $forum_id]);
    $topic_count = $topicStmt->fetchColumn();
    
    // Get post count
    $postStmt = $conn->prepare('SELECT COUNT(*) as post_count FROM forum_posts p 
                               JOIN forum_topics t ON p.topic_id = t.id 
                               WHERE t.forum_id = :fid AND p.deleted = 0');
    $postStmt->execute([':fid' => $forum_id]);
    $post_count = $postStmt->fetchColumn();
    
    // Get last post info
    $lastPostStmt = $conn->prepare('SELECT p.created_at, p.user_id, u.username, t.title as topic_title, t.id as topic_id
                                   FROM forum_posts p 
                                   JOIN forum_topics t ON p.topic_id = t.id 
                                   JOIN users u ON p.user_id = u.id
                                   WHERE t.forum_id = :fid AND p.deleted = 0
                                   ORDER BY p.created_at DESC LIMIT 1');
    $lastPostStmt->execute([':fid' => $forum_id]);
    $last_post = $lastPostStmt->fetch(PDO::FETCH_ASSOC);
    
    return [
        'topic_count' => $topic_count,
        'post_count' => $post_count,
        'last_post' => $last_post
    ];
}

function forum_get_forums_with_stats_by_category(int $category_id): array {
    global $conn;
    $forums = forum_get_forums_by_category($category_id);
    
    foreach ($forums as &$forum) {
        $stats = forum_get_forum_stats($forum['id']);
        $forum['topic_count'] = $stats['topic_count'];
        $forum['post_count'] = $stats['post_count'];
        $forum['last_post'] = $stats['last_post'];
    }
    
    return $forums;
}

function forum_get_recent_topics(int $limit = 10): array {
    global $conn;
    $stmt = $conn->prepare('SELECT t.id, t.title, t.forum_id, f.name as forum_name, 
                           p.created_at, p.user_id, u.username 
                           FROM forum_topics t 
                           JOIN forums f ON t.forum_id = f.id
                           JOIN forum_posts p ON t.id = p.topic_id
                           JOIN users u ON p.user_id = u.id
                           WHERE p.deleted = 0
                           ORDER BY p.created_at DESC 
                           LIMIT :limit');
    $stmt->execute([':limit' => $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function forum_get_online_users(int $minutes = 15): array {
    global $conn;
    $cutoff = date('Y-m-d H:i:s', time() - ($minutes * 60));
    $stmt = $conn->prepare('SELECT DISTINCT u.id, u.username FROM users u 
                           WHERE u.lastactive >= :cutoff 
                           ORDER BY u.lastactive DESC 
                           LIMIT 20');
    $stmt->execute([':cutoff' => $cutoff]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function forum_get_total_stats(): array {
    global $conn;
    
    // Get total topics
    $topicStmt = $conn->query('SELECT COUNT(*) FROM forum_topics');
    $total_topics = $topicStmt->fetchColumn();
    
    // Get total posts (excluding deleted)
    $postStmt = $conn->query('SELECT COUNT(*) FROM forum_posts WHERE deleted = 0');
    $total_posts = $postStmt->fetchColumn();
    
    // Get total members
    $memberStmt = $conn->query('SELECT COUNT(*) FROM users');
    $total_members = $memberStmt->fetchColumn();
    
    // Get newest member
    $newestStmt = $conn->query('SELECT username FROM users ORDER BY date DESC LIMIT 1');
    $newest_member = $newestStmt->fetchColumn();
    
    return [
        'total_topics' => $total_topics,
        'total_posts' => $total_posts,
        'total_members' => $total_members,
        'newest_member' => $newest_member
    ];
}

?>
