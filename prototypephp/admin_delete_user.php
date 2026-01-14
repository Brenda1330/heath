<?php
// File: admin_delete_user.php
require_once 'functions.php';
secure_session_start();
require_admin(); // Only admins can delete

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Token Validation
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('CSRF token validation failed.');
    }

    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

    if ($user_id && $user_id !== $_SESSION['user_id']) { // Ensure admin cannot delete themselves
        $conn = get_db_connection();
        if ($conn) {
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            if ($stmt->execute()) {
                set_flash_message('User deleted successfully.', 'success');
                log_system_action('Delete User Success', 'success', "UserID: {$user_id}");
            } else {
                set_flash_message('Failed to delete user.', 'danger');
                log_system_action('Delete User Fail', 'error', "UserID: {$user_id}");
            }
            $stmt->close();
            $conn->close();
        }
    } else {
        set_flash_message('Invalid request or you cannot delete your own account.', 'danger');
    }
}
redirect('admin_manage_user.php');