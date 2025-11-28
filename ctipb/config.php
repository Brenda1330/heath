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
define('DB_HOST', 'localhost'); // Or your database host, e.g., 'localhost'

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
define('NEO4J_URI', 'neo4j+s://340bc0f6.databases.neo4j.io');
define('NEO4J_USER', 'neo4j');
define('NEO4J_PASSWORD', 'BT-cX-O2JPYbbcFJc_y7gEof-EpMyK9_EXfsw9Cf27E');
// --- END OF ADDITION ---

/**
 * -----------------------------------------------------------------------------
 * APPLICATION SETTINGS
 * -----------------------------------------------------------------------------
 */
// A strong, random key used for signing tokens and other security purposes.
// Generate a new one for your application.
define('APP_SECRET_KEY', 'passwordstrongkey');

// Define the absolute path to the directory for user-uploaded files.
define('UPLOAD_FOLDER', __DIR__ . '/static/uploads/');
define('GEMINI_API_KEY', 'AIzaSyCBVJG3HanAvSYzMub7RULsm0IufooiugA');
define('GEMINI_API_URL', "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . GEMINI_API_KEY);

// --- TIMEZONE ---
date_default_timezone_set('UTC');
?>