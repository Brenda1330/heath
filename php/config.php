<?php
// File: config.php

/**
 * -----------------------------------------------------------------------------
 * ERROR REPORTING
 * -----------------------------------------------------------------------------
 * For development, show all errors.
 * For production, hide errors from users and log them to a file.
 */
ini_set('display_errors', 1); // Set to 0 in production
error_reporting(E_ALL);

/**
 * -----------------------------------------------------------------------------
 * DATABASE CREDENTIALS
 * -----------------------------------------------------------------------------
 */
define('DB_HOST', '127.0.0.1'); // Or your database host, e.g., 'localhost'

// Main Application Database
define('DB_USER_MAIN_APP', 'root');
define('DB_PASSWORD_MAIN_APP', 'toor');
define('DB_NAME_MAIN_APP', 'health');

// ZAP Test Database (Optional)
define('DB_USER_ZAP_TEST', 'test_app_user');
define('DB_PASSWORD_ZAP_TEST', 'test_user_password');
define('DB_NAME_ZAP_TEST', 'test_app_db');

/**
 * -----------------------------------------------------------------------------
 * DATABASE MODE SELECTION
 * -----------------------------------------------------------------------------
 * Determines which database credentials to use. In a real server environment,
 * this would be set as an actual environment variable.
 */
if (getenv('ZAP_TEST_MODE') === 'true') {
    print("!!! RUNNING IN ZAP TEST MODE - USING TEST DATABASE !!!");
    define('CURRENT_DB_USER', DB_USER_ZAP_TEST);
    define('CURRENT_DB_PASSWORD', DB_PASSWORD_ZAP_TEST);
    define('CURRENT_DB_NAME', DB_NAME_ZAP_TEST);
} else {
    define('CURRENT_DB_USER', DB_USER_MAIN_APP);
    define('CURRENT_DB_PASSWORD', DB_PASSWORD_MAIN_APP);
    define('CURRENT_DB_NAME', DB_NAME_MAIN_APP);
}

/**
 * -----------------------------------------------------------------------------
 * APPLICATION SETTINGS
 * -----------------------------------------------------------------------------
 */
// A strong, random key used for signing tokens and other security purposes.
// Generate a new one for your application.
define('APP_SECRET_KEY', 'CHANGE_THIS_TO_A_VERY_LONG_AND_RANDOM_SECRET_STRING');

// Define the absolute path to the directory for user-uploaded files.
define('UPLOAD_FOLDER', __DIR__ . '/uploads/');

// --- TIMEZONE ---
date_default_timezone_set('UTC');