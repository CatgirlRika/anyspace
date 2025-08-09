#!/usr/bin/env php
<?php
/**
 * Configuration validation script
 * Run this to check your AnySpace configuration
 */

require_once __DIR__ . '/../core/config_validator.php';

echo "AnySpace Configuration Validator\n";
echo "=================================\n\n";

$results = validate_configuration();

if (!empty($results['errors'])) {
    echo "❌ Configuration Errors Found:\n";
    foreach ($results['errors'] as $error) {
        echo "  • $error\n";
    }
    echo "\n";
}

if (!empty($results['warnings'])) {
    echo "⚠️  Configuration Warnings:\n";
    foreach ($results['warnings'] as $warning) {
        echo "  • $warning\n";
    }
    echo "\n";
}

if (empty($results['errors']) && empty($results['warnings'])) {
    echo "✅ Configuration looks good!\n";
} elseif (empty($results['errors'])) {
    echo "✅ Configuration is valid (with warnings above)\n";
} else {
    echo "❌ Configuration has errors that need to be fixed\n";
    exit(1);
}