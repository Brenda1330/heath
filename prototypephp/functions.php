<?php
// File: functions.php
require_once 'config.php';

/**
 * SECURE SESSION MANAGEMENT
 */
function secure_session_start() {
    $session_name = 'app_secure_session';
    $secure = isset($_SERVER['HTTPS']); // True if using HTTPS
    $httponly = true; // Prevent JavaScript access to the session cookie

    ini_set('session.use_only_cookies', 1);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => $secure,
        'httponly' => $httponly,
        'samesite' => 'Lax'
    ]);
    session_name($session_name);
    session_start();

    // Regenerate session ID to prevent session fixation attacks
    if (empty($_SESSION['initiated'])) {
        session_regenerate_id(true);
        $_SESSION['initiated'] = true;
    }
}

/**
 * HTTP SECURITY HEADERS
 */
function add_security_headers() {
    // This array holds all the rules for your Content Security Policy.
    $csp_policy = [
        "default-src 'self'",
        "object-src 'none'",
        "frame-ancestors 'self'",
        "base-uri 'self'",
        "script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://d3js.org 'unsafe-inline'",
        "style-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com 'unsafe-inline'",
        "img-src 'self' data: https://img.freepik.com https://cdn-icons-png.flaticon.com https://via.placeholder.com",
        "font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com",
        "connect-src 'self' https://cdn.jsdelivr.net",
        "form-action 'self'",
    ];

    // --- THIS IS THE SINGLE, CORRECT LINE TO SET THE CSP HEADER ---
    // It correctly implodes your `$csp_policy` array into a string.
    header("Content-Security-Policy: " . implode('; ', $csp_policy));

    // Set other important security headers
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: SAMEORIGIN"); // SAMEORIGIN is more common than DENY
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");

    // Add HSTS header only if the site is served over HTTPS
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
    }
}

/**
 * DATABASE CONNECTION
 */
function get_db_connection() {
    try {
        $conn = new mysqli(DB_HOST, CURRENT_DB_USER, CURRENT_DB_PASSWORD, CURRENT_DB_NAME);
        if ($conn->connect_error) {
            error_log("DB Connection Error: " . $conn->connect_error);
            return null;
        }
        return $conn;
    } catch (Exception $e) {
        error_log("DB Connection Exception: " . $e->getMessage());
        return null;
    }
}

/**
 * SYSTEM EVENT LOGGING
 */
function log_system_action($action, $status, $target = null, $user_id = null, $username = null, $details = null) {
    $conn = get_db_connection();
    if (!$conn) return;

    $uid = $user_id ?? $_SESSION['user_id'] ?? null;
    $uname = $username ?? $_SESSION['username'] ?? 'System';
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $time = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("INSERT INTO system_logs (user_id, username, action, target, status, ip_address, details, time) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssss", $uid, $uname, $action, $target, $status, $ip, $details, $time);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}

/**
 * FLASH MESSAGES
 */
function set_flash_message($message, $type = 'info') {
    $_SESSION['flash_messages'][] = ['message' => $message, 'type' => $type];
}

function display_flash_messages() {
    if (empty($_SESSION['flash_messages'])) return;

    foreach ($_SESSION['flash_messages'] as $msg) {
        $message = htmlspecialchars($msg['message'], ENT_QUOTES, 'UTF-8');
        $type = htmlspecialchars($msg['type'], ENT_QUOTES, 'UTF-8');
        echo "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
                {$message}
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
              </div>";
    }
    unset($_SESSION['flash_messages']);
}

/**
 * ROUTING & AUTHORIZATION
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

function require_login() {
    if (empty($_SESSION['user_id'])) {
        set_flash_message('You must be logged in to view that page.', 'warning');
        redirect('login.php');
    }
}

function require_admin() {
    require_login();
    if ($_SESSION['role'] !== 'admin') {
        log_system_action('Authorization Fail', 'fail', 'Admin page access denied');
        set_flash_message('You do not have permission to access that page.', 'danger');
        redirect('doc_dashboard.php'); // Or a dedicated 'unauthorized' page
    }
}

function require_doctor() {
    require_login();
    if ($_SESSION['role'] !== 'doctor') {
        log_system_action('Authorization Fail', 'fail', 'Doctor page access denied');
        set_flash_message('You do not have permission to access that page.', 'danger');
        redirect('admin_dashboard.php'); // Or a dedicated 'unauthorized' page
    }
}

function parse_flexible_timestamp(string $timestamp_str): DateTime|false {
    $formats = ['Y-m-d H:i:s', 'd/m/Y H:i', 'j/n/Y G:i']; // Add all possible formats
    $utc_tz = new DateTimeZone('UTC');
    foreach ($formats as $format) {
        $date = DateTime::createFromFormat($format, $timestamp_str, $utc_tz);
        if ($date !== false) return $date;
    }
    return false;
}