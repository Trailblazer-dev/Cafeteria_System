<?php
/**
 * Application Configuration
 */

// Define constant to prevent direct access to included files
define('ACCESS_ALLOWED', true);

// Environment setting: 'development', 'testing', or 'production'
define('ENVIRONMENT', 'production');

// Set up error reporting based on environment
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    define('SHOW_DEBUG', true);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    define('SHOW_DEBUG', false);
}

// Site settings
define('SITE_NAME', 'Cafeteria Management System');
define('SITE_VERSION', '1.0.0');

// Feature flags - enable/disable features globally
$FEATURES = [
    'reports' => false,  // Reports module
    'receipts' => false, // Receipt generation
    'inventory' => false // Inventory management
];
