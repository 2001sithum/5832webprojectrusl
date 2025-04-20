<?php
/**
 * Simple Configuration
 */

// Database Configuration (Using SQLite for simplicity)
define('DB_PATH', __DIR__ . '/../data/events.db'); // Path relative to this config file

// Application Settings
define('APP_NAME', 'Event Horizon Uni');
define('ADMIN_EMAIL', 'admin@example.com'); // For contact form etc.

// Session Name
define('SESSION_NAME', 'EVENTUNI_SESS');

// Basic Error Reporting (Adjust for production)
error_reporting(E_ALL);
ini_set('display_errors', '1'); // Show errors during development
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../data/php_errors.log'); // Log errors to data dir

?>
