<?php
/**
 * Configuration File Template
 *
 * Copy this file to config.php and update with your actual credentials.
 * NEVER commit config.php to version control!
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'your_database_username');
define('DB_PASS', 'your_database_password');
define('DB_NAME', 'your_database_name');
define('DB_PORT', '3306');

// Application Settings
define('APP_NAME', 'ShopEasy');
define('APP_URL', 'http://localhost/spm');
define('APP_ENV', 'development'); // development, staging, production

// Security Settings
define('SESSION_LIFETIME', 3600); // 1 hour in seconds
define('PASSWORD_HASH_ALGO', PASSWORD_BCRYPT);
define('CSRF_TOKEN_EXPIRY', 3600);

// eSewa Payment Configuration
define('ESEWA_MERCHANT_ID', 'your_merchant_id');
define('ESEWA_SUCCESS_URL', APP_URL . '/esewa_success.php');
define('ESEWA_FAILURE_URL', APP_URL . '/esewa_failure.php');
define('ESEWA_SERVICE_URL', 'https://esewa.com.np/epay/main'); // Use sandbox for testing
// Sandbox URL: https://uat.esewa.com.np/epay/main

// Email Configuration (if implementing email notifications)
define('MAIL_HOST', 'smtp.example.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'your_email@example.com');
define('MAIL_PASSWORD', 'your_email_password');
define('MAIL_FROM_ADDRESS', 'noreply@shopeasy.com');
define('MAIL_FROM_NAME', 'ShopEasy');

// File Upload Settings
define('UPLOAD_MAX_SIZE', 5242880); // 5MB in bytes
define('UPLOAD_ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('UPLOAD_PATH', __DIR__ . '/uploads/');

// Admin Settings
define('ADMIN_EMAIL', 'admin@shopeasy.com');
define('ITEMS_PER_PAGE', 12);

// Error Reporting
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/logs/php_errors.log');
}

// Timezone
date_default_timezone_set('Asia/Kathmandu');

// Database Connection
function getDatabaseConnection() {
    static $conn = null;

    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

        if ($conn->connect_error) {
            if (APP_ENV === 'development') {
                die("Connection failed: " . $conn->connect_error);
            } else {
                die("Database connection error. Please try again later.");
            }
        }

        $conn->set_charset("utf8mb4");
    }

    return $conn;
}
?>
