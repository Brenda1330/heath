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

$pageTitle = "Login - HealthTrack Pro";
include 'templates/header_login.php';
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
 
</head>
<body>
    <div class="login-wrapper">
        <div class="login-graphic">
            <div class="icon"><i class="fas fa-heart-pulse"></i></div>
            <h1>HealthTrack Pro</h1>
            <p>Advanced Health Monitoring for modern medical practice. Log in to access your dashboard.</p>
        </div>
        <div class="login-form-container">
            <div class="login-card">
                <h3>Sign In to Your Account</h3>
                <p class="login-subtitle">Enter your credentials to continue</p>
                
                <?php display_flash_messages(); ?>
                
                <form action="login.php" method="POST" novalidate id="loginForm">
                    <!-- Custom Email Field - No Bootstrap classes -->
                    <div class="form-group">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="custom-input" id="email" name="email" 
                               placeholder="Enter your email" required autocomplete="email">
                        <div class="error-message" id="emailError">Please enter a valid email address</div>
                    </div>
                    
                    <!-- Custom Password Field - ONE eye icon only -->
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="password-container">
                            <input type="password" class="password-input" id="password" name="password" 
                                   placeholder="Enter your password" required autocomplete="current-password">
                            <button type="button" class="eye-toggle-btn" id="eyeToggle" 
                                    aria-label="Show password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="error-message" id="passwordError">Please enter your password</div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                            <span class="btn-text">Sign In</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const eyeToggle = document.getElementById('eyeToggle');
            const eyeIcon = eyeToggle.querySelector('i');
            const loginForm = document.getElementById('loginForm');
            const submitBtn = document.getElementById('submitBtn');
            const emailInput = document.getElementById('email');
            const emailError = document.getElementById('emailError');
            const passwordError = document.getElementById('passwordError');

            // SINGLE Eye Icon Toggle - No duplicates
            eyeToggle.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                // Toggle the ONE icon
                if (type === 'text') {
                    eyeIcon.classList.remove('fa-eye');
                    eyeIcon.classList.add('fa-eye-slash');
                    this.setAttribute('aria-label', 'Hide password');
                } else {
                    eyeIcon.classList.remove('fa-eye-slash');
                    eyeIcon.classList.add('fa-eye');
                    this.setAttribute('aria-label', 'Show password');
                }
            });

            // Real-time validation
            function validateEmail(email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email);
            }

            function validateForm() {
                let isValid = true;
                const email = emailInput.value.trim();
                const password = passwordInput.value.trim();

                // Validate email
                if (!email) {
                    emailInput.classList.add('error');
                    emailError.style.display = 'block';
                    emailError.textContent = 'Email address is required';
                    isValid = false;
                } else if (!validateEmail(email)) {
                    emailInput.classList.add('error');
                    emailError.style.display = 'block';
                    emailError.textContent = 'Please enter a valid email address';
                    isValid = false;
                } else {
                    emailInput.classList.remove('error');
                    emailError.style.display = 'none';
                }

                // Validate password
                if (!password) {
                    passwordInput.classList.add('error');
                    passwordError.style.display = 'block';
                    passwordError.textContent = 'Password is required';
                    isValid = false;
                } else {
                    passwordInput.classList.remove('error');
                    passwordError.style.display = 'none';
                }

                return isValid;
            }

            // Input event listeners for real-time validation
            emailInput.addEventListener('input', function() {
                const email = this.value.trim();
                if (email && validateEmail(email)) {
                    this.classList.remove('error');
                    emailError.style.display = 'none';
                }
            });

            passwordInput.addEventListener('input', function() {
                if (this.value.trim()) {
                    this.classList.remove('error');
                    passwordError.style.display = 'none';
                }
            });

            // Form submission with validation
            loginForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                if (!validateForm()) {
                    return;
                }

                // Show loading state
                submitBtn.classList.add('btn-loading');
                submitBtn.disabled = true;

                // Submit the form
                this.submit();
            });

            // Blur validation
            emailInput.addEventListener('blur', function() {
                const email = this.value.trim();
                if (email && !validateEmail(email)) {
                    this.classList.add('error');
                    emailError.style.display = 'block';
                    emailError.textContent = 'Please enter a valid email address';
                }
            });

            passwordInput.addEventListener('blur', function() {
                if (!this.value.trim()) {
                    this.classList.add('error');
                    passwordError.style.display = 'block';
                    passwordError.textContent = 'Password is required';
                }
            });
        });
    </script>
</body>
</html>