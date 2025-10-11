<?php
// File: login.php
require_once 'functions.php';

secure_session_start();
add_security_headers();

// --- Phase 1: Redirect if already logged in ---
if (!empty($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') redirect('admin_dashboard.php');
    if ($_SESSION['role'] === 'doctor') redirect('doc_dashboard.php');
    redirect('homepage.php');
}

// --- Phase 2: Handle POST login attempt ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $target_info = "Email: {$email}";

    if (empty($email) || empty($password)) {
        set_flash_message('Email and password are required.', 'warning');
        log_system_action('Login Attempt', 'fail', $target_info, null, null, 'Missing fields');
        redirect('login.php');
    }

    $conn = get_db_connection();
    if (!$conn) {
        set_flash_message('Database service is currently unavailable. Please try again later.', 'danger');
        log_system_action('Login DB Connect', 'error', $target_info);
        redirect('login.php');
    }

    $stmt = $conn->prepare("SELECT user_id, username, role, password_hash, status FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();

    // IMPORTANT: Replace with password_verify() in production!
    // if ($user && $user['status'] == 1 && password_verify($password, $user['password_hash'])) {
    if ($user && $user['status'] == 1 && $password === $user['password_hash']) { // Plain text check for demo
        
        // --- Login Success ---
        session_regenerate_id(true); // Prevent session fixation
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        log_system_action('User Login', 'success', $target_info, $user['user_id'], $user['username']);
        set_flash_message('Welcome back, ' . htmlspecialchars($user['username']) . '!', 'success');

        // Redirect based on role
        if ($user['role'] === 'admin') redirect('admin_dashboard.php');
        if ($user['role'] === 'doctor') redirect('doc_dashboard.php');
        redirect('homepage.php');

    } else {
        // --- Login Fail ---
        $details = 'Invalid credentials or inactive account.';
        if (!$user) $details = 'User not found by email.';
        elseif ($user['status'] != 1) $details = "Account inactive (status: {$user['status']}).";
        
        log_system_action('Login Attempt', 'fail', $target_info, null, null, $details);
        set_flash_message('Invalid credentials or account inactive. Please try again.', 'danger');
        redirect('login.php');
    }
}

// --- Phase 3: Display Login Form (GET Request) ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f9f9fc; }
        .login-card { max-width: 450px; margin: 5rem auto; padding: 2rem; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.06); background: #fff;}
    </style>
</head>
<body>
    <div class="container">
        <div class="login-card">
            <h3 class="text-center mb-4">Welcome Back</h3>
            <?php display_flash_messages(); ?>
            <form action="login.php" method="POST" novalidate>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <!-- Note: "Remember Me" functionality is not included in this direct translation for simplicity -->
                <!-- but would involve creating and validating a secure cookie with a long-lived token. -->
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Sign In</button>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>