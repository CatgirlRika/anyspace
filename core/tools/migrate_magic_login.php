<?php
/**
 * Database migration script for Magic Login System
 * Run this script to add the login_tokens table to existing AnySpace installations
 */

require_once __DIR__ . '/../core/conn.php';

echo "AnySpace Magic Login Migration\n";
echo "==============================\n\n";

try {
    // Check if login_tokens table already exists
    $checkStmt = $conn->prepare("SHOW TABLES LIKE 'login_tokens'");
    $checkStmt->execute();
    $tableExists = $checkStmt->fetch();
    
    if ($tableExists) {
        echo "✅ login_tokens table already exists. No migration needed.\n";
        exit(0);
    }
    
    echo "Creating login_tokens table...\n";
    
    // Create the login_tokens table
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS `login_tokens` (
          `id` int(11) NOT NULL auto_increment,
          `user_id` int(11) NOT NULL,
          `token` varchar(64) NOT NULL,
          `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `expires_at` datetime NOT NULL,
          `used_at` datetime DEFAULT NULL,
          `ip_address` varchar(45) DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `token` (`token`),
          KEY `user_id` (`user_id`),
          KEY `expires_at` (`expires_at`),
          FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8
    ";
    
    $conn->exec($createTableSQL);
    
    echo "✅ Successfully created login_tokens table!\n";
    echo "\nMagic Login system is now ready to use.\n";
    echo "Users can access it at: /reset.php\n\n";
    
    echo "Features added:\n";
    echo "- Passwordless login via email links\n";
    echo "- 30-minute token expiration\n";
    echo "- Single-use tokens\n";
    echo "- Rate limiting (3 attempts per 5 minutes)\n";
    echo "- Automatic cleanup of expired tokens\n\n";
    
} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "\nPlease check your database connection and try again.\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Unexpected error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Migration completed successfully! 🎉\n";
?>