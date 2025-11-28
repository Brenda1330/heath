<?php
// File: admin_delete_user.php
require_once 'functions.php';
secure_session_start();
require_admin();

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$response = ['success' => false, 'message' => 'Unknown error occurred.'];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method. Only POST allowed.');
    }

    // CSRF Token Validation
    if (!isset($_POST['csrf_token']) || empty($_POST['csrf_token'])) {
        throw new Exception('CSRF token is missing.');
    }

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        throw new Exception('CSRF token validation failed.');
    }

    if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
        throw new Exception('User ID is missing.');
    }

    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    
    if (!$user_id) {
        throw new Exception('Invalid user ID.');
    }

    // Ensure admin cannot delete themselves
    if ($user_id == $_SESSION['user_id']) {
        throw new Exception('You cannot delete your own account.');
    }

    $conn = get_db_connection();
    if (!$conn) {
        throw new Exception('Database connection failed.');
    }

    // First, check if user exists
    $check_stmt = $conn->prepare("SELECT username FROM users WHERE user_id = ?");
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        throw new Exception('User not found.');
    }
    
    $user_data = $check_result->fetch_assoc();
    $username = $user_data['username'];
    $check_stmt->close();

    // Now delete the user
    $delete_stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $delete_stmt->bind_param("i", $user_id);
    
    if ($delete_stmt->execute()) {
        $response['success'] = true;
        $response['message'] = "User '{$username}' deleted successfully.";
        log_system_action('Delete User Success', 'success', "UserID: {$user_id}, Username: {$username}");
    } else {
        throw new Exception('Database error: Could not delete user.');
    }
    
    $delete_stmt->close();
    $conn->close();

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    log_system_action('Delete User Fail', 'error', "Error: " . $e->getMessage());
}

echo json_encode($response);
exit;