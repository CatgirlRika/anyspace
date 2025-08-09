<?php
/**
 * Security Tables Migration for AnySpace
 * Creates necessary tables for security audit logging, rate limiting, and session management
 */

require_once __DIR__ . '/conn.php';

try {
    echo "Creating security tables...\n";
    
    // Security logs table for audit trail
    $conn->exec("
        CREATE TABLE IF NOT EXISTS security_logs (
            id INTEGER PRIMARY KEY " . ($conn->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite' ? 'AUTOINCREMENT' : 'AUTO_INCREMENT') . ",
            event_type VARCHAR(50) NOT NULL,
            risk_level VARCHAR(20) NOT NULL,
            user_id INTEGER NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT NOT NULL,
            request_path VARCHAR(255) NOT NULL,
            request_method VARCHAR(10) NOT NULL,
            details TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_event_type (event_type),
            INDEX idx_user_id (user_id),
            INDEX idx_created_at (created_at),
            INDEX idx_risk_level (risk_level)
        )
    ");
    
    // Rate limiting table for tracking attempts
    $conn->exec("
        CREATE TABLE IF NOT EXISTS rate_limits (
            id INTEGER PRIMARY KEY " . ($conn->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite' ? 'AUTOINCREMENT' : 'AUTO_INCREMENT') . ",
            identifier VARCHAR(255) NOT NULL,
            action VARCHAR(50) NOT NULL DEFAULT 'login',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_identifier (identifier),
            INDEX idx_action (action),
            INDEX idx_created_at (created_at)
        )
    ");
    
    // Enhanced sessions table for better session management
    $conn->exec("
        CREATE TABLE IF NOT EXISTS user_sessions (
            id VARCHAR(128) PRIMARY KEY,
            user_id INTEGER NULL,
            session_data TEXT NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_expires_at (expires_at)
        )
    ");
    
    // Blacklisted tokens table for token revocation
    $conn->exec("
        CREATE TABLE IF NOT EXISTS blacklisted_tokens (
            id INTEGER PRIMARY KEY " . ($conn->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite' ? 'AUTOINCREMENT' : 'AUTO_INCREMENT') . ",
            token_hash VARCHAR(64) NOT NULL UNIQUE,
            expires_at TIMESTAMP NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_token_hash (token_hash),
            INDEX idx_expires_at (expires_at)
        )
    ");
    
    // Update users table to add security-related fields if they don't exist
    try {
        $conn->exec("ALTER TABLE users ADD COLUMN password_changed_at TIMESTAMP NULL");
    } catch (PDOException $e) {
        // Column might already exist
    }
    
    try {
        $conn->exec("ALTER TABLE users ADD COLUMN last_login_at TIMESTAMP NULL");
    } catch (PDOException $e) {
        // Column might already exist
    }
    
    try {
        $conn->exec("ALTER TABLE users ADD COLUMN failed_login_attempts INTEGER DEFAULT 0");
    } catch (PDOException $e) {
        // Column might already exist
    }
    
    try {
        $conn->exec("ALTER TABLE users ADD COLUMN locked_until TIMESTAMP NULL");
    } catch (PDOException $e) {
        // Column might already exist
    }
    
    echo "Security tables created successfully!\n";
    
    // Insert initial security log entry
    $stmt = $conn->prepare("
        INSERT INTO security_logs (event_type, risk_level, ip_address, user_agent, request_path, request_method, details)
        VALUES ('ADMIN_ACTION', 'LOW', '127.0.0.1', 'Migration Script', '/core/security_migration.php', 'CLI', ?)
    ");
    $stmt->execute([json_encode(['action' => 'security_tables_created'])]);
    
    echo "Initial security log entry created.\n";
    
} catch (PDOException $e) {
    echo "Error creating security tables: " . $e->getMessage() . "\n";
    exit(1);
}

?>