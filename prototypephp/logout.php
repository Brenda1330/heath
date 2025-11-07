<?php
// File: logout.php
require_once 'functions.php';
secure_session_start();

// Log the action *before* destroying the session
log_system_action('User Logout', 'success');

// Unset all session variables
$_SESSION = [];

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session.
session_destroy();

set_flash_message('You have been logged out successfully.', 'info');
redirect('login.php');