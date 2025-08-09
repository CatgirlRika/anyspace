<?php
/**
 * Configuration validation helper
 * Validates required configuration settings and provides warnings
 */

/**
 * Validate core configuration settings
 * @return array Array of validation results
 */
function validate_configuration() {
    $errors = [];
    $warnings = [];
    
    // Check if config.php exists
    $configFile = __DIR__ . '/config.php';
    if (!file_exists($configFile)) {
        $errors[] = 'config.php not found. Please copy config.php.example to config.php and configure it.';
        return ['errors' => $errors, 'warnings' => $warnings];
    }
    
    // Include config and check required variables
    require_once($configFile);
    
    // Required database settings
    if (!isset($host) || empty($host)) {
        $errors[] = 'Database host not configured in config.php';
    }
    
    if (!isset($dbname) || empty($dbname)) {
        $errors[] = 'Database name not configured in config.php';
    }
    
    if (!isset($username)) {
        $warnings[] = 'Database username not set - using default';
    }
    
    if (!isset($password)) {
        $warnings[] = 'Database password not set - using default';
    }
    
    // Site settings
    if (!isset($siteName) || empty($siteName)) {
        $warnings[] = 'Site name not configured - using default';
    }
    
    if (!isset($domainName) || empty($domainName)) {
        $warnings[] = 'Domain name not configured - this may affect some features';
    }
    
    if (!isset($adminUser) || !is_numeric($adminUser)) {
        $warnings[] = 'Admin user ID not properly configured - defaulting to user ID 1';
    }
    
    // Directory permissions
    $writableDirs = [
        __DIR__,
        __DIR__ . '/../public/media/pfp',
        __DIR__ . '/../public/media/music'
    ];
    
    foreach ($writableDirs as $dir) {
        if (!is_writable($dir)) {
            $warnings[] = "Directory $dir is not writable - some features may not work";
        }
    }
    
    return ['errors' => $errors, 'warnings' => $warnings];
}

/**
 * Display configuration validation results
 */
function display_config_validation() {
    $results = validate_configuration();
    
    if (!empty($results['errors'])) {
        echo "<div class='error'><h3>Configuration Errors:</h3><ul>";
        foreach ($results['errors'] as $error) {
            echo "<li>" . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . "</li>";
        }
        echo "</ul></div>";
    }
    
    if (!empty($results['warnings']) && defined('DEBUG') && DEBUG) {
        echo "<div class='warning'><h3>Configuration Warnings:</h3><ul>";
        foreach ($results['warnings'] as $warning) {
            echo "<li>" . htmlspecialchars($warning, ENT_QUOTES, 'UTF-8') . "</li>";
        }
        echo "</ul></div>";
    }
    
    return empty($results['errors']);
}
?>