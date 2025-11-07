<?php
// File: admin_add_doctor.php

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
 * FORM SUBMISSION HANDLING (POST REQUEST)
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        log_system_action('CSRF Token Fail', 'fail', 'admin_add_doctor.php');
        set_flash_message('A security error occurred. Please try again.', 'danger');
        redirect('admin_add_doctor.php');
    }

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    // --- ADDED: Retrieve new form fields ---
    $phone_number = trim($_POST['phone_number'] ?? '');
    $specialist = trim($_POST['specialist'] ?? '');
    $photo_filename = null;

    // --- MODIFIED: Added specialist to the required fields check ---
    if (empty($username) || empty($email) || empty($password) || empty($specialist)) {
        set_flash_message('Full name, email, password, and specialist are required.', 'warning');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash_message('Please enter a valid email address.', 'warning');
    } elseif (strlen($password) < 8) {
        set_flash_message('Password must be at least 8 characters long.', 'warning');
    } else {
        // (This entire photo upload block remains unchanged)
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $photo = $_FILES['photo'];
            if ($photo['size'] > 2 * 1024 * 1024) {
                set_flash_message('Error: Photo exceeds the 2MB size limit.', 'danger');
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime_type = $finfo->file($photo['tmp_name']);
                $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($mime_type, $allowed_mime_types)) {
                    set_flash_message('Error: Invalid file type. Only JPG, PNG, and GIF are allowed.', 'danger');
                } else {
                    $extension = pathinfo($photo['name'], PATHINFO_EXTENSION);
                    $photo_filename = bin2hex(random_bytes(16)) . '.' . strtolower($extension);
                    $destination = UPLOAD_FOLDER . $photo_filename;
                    if (!move_uploaded_file($photo['tmp_name'], $destination)) {
                        set_flash_message('An error occurred while saving the photo.', 'danger');
                        $photo_filename = null;
                    }
                }
            }
        }
    }

    if (!isset($_SESSION['flash_messages'])) {
        $conn = get_db_connection();
        if (!$conn) {
            set_flash_message('Database connection failed.', 'danger');
        } else {
            $password_hash = password_hash($password, PASSWORD_ARGON2ID);

            // --- MODIFIED: Updated SQL INSERT statement ---
            $sql = "INSERT INTO users (username, email, password_hash, photo, role, status, phone_number, specialist) 
                    VALUES (?, ?, ?, ?, 'doctor', 1, ?, ?)";
            $stmt = $conn->prepare($sql);

            // --- MODIFIED: Updated bind_param with 6 strings (ssssss) ---
            $stmt->bind_param("ssssss", $username, $email, $password_hash, $photo_filename, $phone_number, $specialist);
            
            try {
                if ($stmt->execute()) {
                    set_flash_message('Doctor account created successfully!', 'success');
                    log_system_action('Create Doctor Success', 'success', "Email: {$email}");
                    redirect('admin_doctor_details.php');
                }
            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() === 1062) {
                    set_flash_message('Error: An account with this email or username already exists.', 'danger');
                } else { set_flash_message('A database error occurred.', 'danger'); }
                log_system_action('Create Doctor Fail', 'error', "Email: {$email}", null, null, $e->getMessage());
            } finally {
                $stmt->close();
                $conn->close();
            }
        }
    }
    redirect('admin_add_doctor.php');
}

/**
 * PRESENTATION (HTML)
 */
$pageTitle = "Add Doctor Account";
include 'templates/header.php';
include 'templates/sidebar_admin.php';
?>

<!-- Main Content -->
<div class="main-content">
  <h3 class="page-title">Add Doctor Account</h3>

  <?php display_flash_messages(); ?>

  <div class="form-card">
    <form action="admin_add_doctor.php" method="POST" enctype="multipart/form-data">
      <!-- CSRF Token for security -->
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
      
      <div class="mb-4">
        <label for="username" class="form-label">Full Name</label>
        <input type="text" id="username" name="username" class="form-control" placeholder="Enter doctor's name" required>
      </div>

      <div class="mb-4">
        <label for="email" class="form-label">Email</label>
        <input type="email" id="email" name="email" class="form-control" placeholder="Enter email address" required>
      </div>

      <!-- --- ADDED: Specialist Field --- -->
      <div class="mb-4">
        <label for="specialist" class="form-label">Specialist</label>
        <input type="text" id="specialist" name="specialist" class="form-control" placeholder="e.g., Cardiologist, Pediatrician" required>
      </div>

      <!-- --- ADDED: Phone Number Field --- -->
      <div class="mb-4">
        <label for="phone_number" class="form-label">Phone Number</label>
        <input type="text" id="phone_number" name="phone_number" class="form-control" placeholder="Enter phone number (optional)">
      </div>

      <div class="mb-4">
        <label for="password" class="form-label">Password</label>
        <input type="password" id="password" name="password" class="form-control" placeholder="Create a password" required>
      </div>

      <div class="mb-4">
        <label for="photo" class="form-label">Profile Photo</label>
        <input class="form-control" type="file" id="photo" name="photo" accept="image/jpeg, image/png, image/gif">
        <div class="form-text mt-2">Optional: JPG, PNG, GIF; Max 2 MB. System will auto-resize.</div>
      </div>

      <div class="mb-4">
        <label for="role" class="form-label">Role</label>
        <select class="form-select" id="role" disabled>
          <option selected>Doctor</option>
        </select>
      </div>

      <div class="d-flex justify-content-end gap-3 mt-5">
        <a href="admin_dashboard.php" class="btn btn-cancel">Cancel</a>
        <button type="submit" class="btn btn-create">Create Account</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<?php
include 'templates/footer.php';
?>