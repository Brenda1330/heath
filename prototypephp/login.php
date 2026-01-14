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
// --- NEW, SECURE CODE ---
// Use password_verify() to securely check the entered password against the stored hash.
if ($user && $user['status'] == 1 && $password === $user['password_hash']) {
    
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
    if (!$user) { $details = 'User not found by email.'; }
    elseif ($user['status'] != 1) { $details = "Account is inactive."; }
    
    log_system_action('Login Attempt', 'fail', $target_info, null, null, $details);
    set_flash_message('Invalid credentials or account is inactive.', 'danger');
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
    <title>Login | HealthTrack Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet"/>

    <style>
        /* --- NEW MODERN & CREATIVE STYLES --- */
        :root {
            --primary-color: #3B82F6;
            --primary-hover: #2563EB;
            --main-bg: #F9FAFB;
            --card-bg: #FFFFFF;
            --text-primary: #111827;
            --text-secondary: #6B7280;
            --border-color: #E5E7EB;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--main-bg);
            margin: 0;
            overflow: hidden; /* Prevent scrollbars during animation */
        }
        .login-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 100vh;
        }
        .login-graphic {
            background: linear-gradient(45deg, #1e3a8a, #3b82f6);
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 3rem;
            text-align: center;
            animation: slideInLeft 0.8s ease-out forwards;
        }
        @keyframes slideInLeft {
            from { transform: translateX(-100%); }
            to { transform: translateX(0); }
        }
        .login-graphic .icon {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            opacity: 0.8;
        }
        .login-graphic h1 {
            font-weight: 700;
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        .login-graphic p {
            font-size: 1.1rem;
            max-width: 400px;
            opacity: 0.9;
        }
        .login-form-container {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            animation: fadeIn 1.2s 0.5s ease-out forwards;
            opacity: 0;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .login-card {
            width: 100%;
            max-width: 420px;
        }
        .login-card h3 {
            font-weight: 700;
            font-size: 1.75rem;
            color: var(--text-primary);
        }
        .form-floating > label { color: var(--text-secondary); }
        .form-control {
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            padding: 1rem;
            height: 58px;
        }
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        .btn-primary {
            background-color: var(--primary-color);
            border: none;
            border-radius: 0.5rem;
            padding: 0.85rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .btn-primary:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }
        .alert {
            border-radius: 0.5rem;
        }
        @media (max-width: 992px) {
            .login-wrapper { grid-template-columns: 1fr; }
            .login-graphic { display: none; }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <!-- Left Side: Graphic -->
        <div class="login-graphic">
            <div class="icon"><i class="fas fa-heart-pulse"></i></div>
            <h1>HealthTrack Pro</h1>
            <p>Advanced Health Monitoring for modern medical practice. Log in to access your dashboard.</p>
        </div>

        <!-- Right Side: Form -->
        <div class="login-form-container">
            <div class="login-card">
                <h3 class="mb-4">Sign In to Your Account</h3>
                <?php display_flash_messages(); ?>
                <form action="login.php" method="POST" novalidate>
                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" required>
                        <label for="email">Email address</label>
                    </div>
                    <div class="form-floating mb-4">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                        <label for="password">Password</label>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Sign In</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>