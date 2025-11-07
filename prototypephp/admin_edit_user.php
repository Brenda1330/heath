<?php
// File: admin_edit_user.php

/**
 * CORE INCLUSION & AUTHORIZATION
 */
require_once 'functions.php';
secure_session_start();
add_security_headers();
require_admin();

/**
 * CSRF TOKEN GENERATION
 */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

/**
 * DATA FETCHING & PROCESSING LOGIC
 */
$user = null;
// Input Validation: Ensure user_id is a positive integer
$user_id = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if (!$user_id) {
    set_flash_message('Invalid user ID provided.', 'danger');
    redirect('admin_manage_user.php');
}

$conn = get_db_connection();
if (!$conn) {
    // This will redirect with a flash message
    die('Database connection failed.'); 
}

// --- HANDLE FORM SUBMISSION (POST REQUEST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Token Validation
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('CSRF token validation failed.');
    }

    // Server-side Validation
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = in_array($_POST['role'], ['admin', 'doctor']) ? $_POST['role'] : 'doctor'; // Default to doctor if invalid role
    $status = ($_POST['status'] === '1') ? 1 : 0;
    $post_user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

    if (empty($username) || !filter_var($email, FILTER_VALIDATE_EMAIL) || $post_user_id !== $user_id) {
        set_flash_message('Invalid data submitted. Please check the form and try again.', 'danger');
        redirect("admin_edit_user.php?user_id={$user_id}");
    }

    // Prevent admin from demoting themselves or deactivating their own account
    if ($user_id === $_SESSION['user_id'] && ($role !== 'admin' || $status !== 1)) {
        set_flash_message('Error: You cannot change your own role or deactivate your own account.', 'danger');
        redirect("admin_edit_user.php?user_id={$user_id}");
    }

    // Use a Prepared Statement to prevent SQL Injection on UPDATE
    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, role = ?, status = ? WHERE user_id = ?");
    $stmt->bind_param("sssii", $username, $email, $role, $status, $user_id);
    
    if ($stmt->execute()) {
        set_flash_message('User account updated successfully!', 'success');
        log_system_action('Update User Success', 'success', "UserID: {$user_id}");
    } else {
        set_flash_message('Failed to update user account.', 'danger');
        log_system_action('Update User Fail', 'error', "UserID: {$user_id}", null, null, $stmt->error);
    }
    $stmt->close();
    $conn->close();
    redirect('admin_manage_user.php');
}

// --- FETCH USER DATA FOR THE FORM (GET REQUEST) ---
$stmt = $conn->prepare("SELECT user_id, username, email, role, status FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$user) {
    set_flash_message('User not found.', 'warning');
    redirect('admin_manage_user.php');
}

/**
 * PRESENTATION (HTML)
 */
$pageTitle = "Edit User Account";
// This page uses a simplified layout without the sidebar for focus
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo htmlspecialchars($pageTitle); ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Poppins', sans-serif; background-color: #f0f2f5;
      min-height: 100vh; display: flex; justify-content: center; align-items: center;
    }
    .card {
      background: #ffffff; border-radius: 20px; padding: 40px;
      box-shadow: 0 8px 25px rgba(0,0,0,0.08); width: 100%; max-width: 600px;
    }
    h3 { font-weight: 600; text-align: center; margin-bottom: 30px; color: #333; }
    .btn-save { background-color: #0d6efd; }
    .btn-save:hover { background-color: #0b5ed7; }
  </style>
</head>
<body>
  <div class="card">
    <h3>Edit User Account</h3>
    <?php display_flash_messages(); ?>
    <form method="POST" action="admin_edit_user.php?user_id=<?php echo htmlspecialchars($user['user_id']); ?>">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
      <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['user_id']); ?>">
      
      <div class="mb-3">
        <label for="username" class="form-label">Full Name</label>
        <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
      </div>
      <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
      </div>
      <div class="mb-3">
        <label for="role" class="form-label">Role</label>
        <select id="role" name="role" class="form-select">
          <option value="doctor" <?php echo ($user['role'] === 'doctor') ? 'selected' : ''; ?>>Doctor</option>
          <option value="admin" <?php echo ($user['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
        </select>
      </div>
      <div class="mb-4">
        <label for="status" class="form-label">Status</label>
        <select id="status" name="status" class="form-select">
          <option value="1" <?php echo ($user['status'] == 1) ? 'selected' : ''; ?>>Active</option>
          <option value="0" <?php echo ($user['status'] == 0) ? 'selected' : ''; ?>>Inactive</option>
        </select>
      </div>
      <div class="d-flex justify-content-end gap-2">
        <a href="admin_manage_user.php" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary btn-save">Save Changes</button>
      </div>
    </form>
  </div>
</body>
</html>