<?php
// File: doc_addpatient.php

/**
 * CORE INCLUSION & AUTHORIZATION
 */
require_once 'functions.php';
secure_session_start();
add_security_headers();
require_doctor(); // Gatekeeper: Halts if user is not a logged-in doctor.

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
    // 1. CSRF Token Validation
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        log_system_action('CSRF Token Fail', 'fail', 'doc_addpatient.php');
        set_flash_message('A security error occurred. Please try again.', 'danger');
        redirect('doc_addpatient.php');
    }

    // 2. Server-Side Input Validation
    $full_name = trim($_POST['full_name'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $status = $_POST['status'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $doctor_id = $_SESSION['user_id'];

    // Define allowed values for select inputs to prevent tampering
    $allowed_genders = ['Male', 'Female'];
    $allowed_statuses = ['Stable', 'Critical', 'Warning', 'Recovered'];

    if (empty($full_name) || empty($gender) || empty($status) || empty($dob)) {
        set_flash_message('All fields are required.', 'warning');
    } elseif (!in_array($gender, $allowed_genders)) {
        set_flash_message('Invalid gender selected.', 'danger');
    } elseif (!in_array($status, $allowed_statuses)) {
        set_flash_message('Invalid status selected.', 'danger');
    } else {
        // ✅ Calculate age from date of birth
        $dob_date = DateTime::createFromFormat('Y-m-d', $dob);
        if (!$dob_date) {
            set_flash_message('Invalid date of birth format.', 'danger');
            redirect('doc_addpatient.php');
        }
        $today = new DateTime();
        $age = $today->diff($dob_date)->y; // returns age in years

        // 3. Database Insertion (only if validation passes)
        $conn = get_db_connection();
        if (!$conn) {
            set_flash_message('Database connection failed.', 'danger');
            log_system_action('DB Connection Fail', 'error', 'doc_addpatient.php');
        } else {
            // Use Prepared Statement to prevent SQL Injection
            $stmt = $conn->prepare(
                "INSERT INTO patients (full_name, age, gender, status, doctor_id) VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("sissi", $full_name, $age, $gender, $status, $doctor_id);

            if ($stmt->execute()) {
                set_flash_message('Patient added successfully!', 'success');
                log_system_action('Add Patient Success', 'success', "Patient: {$full_name}, Age: {$age}");
                redirect('doc_patientlist.php'); // Success!
            } else {
                set_flash_message('Failed to add patient to the database.', 'danger');
                log_system_action('Add Patient Fail', 'error', "Patient: {$full_name}", null, null, $stmt->error);
            }
            $stmt->close();
            $conn->close();
        }
    }
    // If we reach here, a validation error occurred. Redirect back to the form.
    redirect('doc_addpatient.php');
}

/**
 * PRESENTATION (HTML)
 */
$pageTitle = "Add Patient";
// Use the dedicated header for the doctor panel
include 'templates/header_doctor.php';
include 'templates/sidebar_doctor.php';
?>

<!-- Profile Dropdown (Top Right) -->
<div class="profile-dropdown">
    <div class="dropdown">
        <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-user-circle"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="doc_userprofile.php">View Profile</a></li>
            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
        </ul>
    </div>
</div>

<!-- Main Content (Centered Form) -->
<div class="main-content" style="text-align: initial;">
    <div class="form-header">
        <h1>Add New Patient</h1>
    </div>

    <div class="form-container">
        <?php display_flash_messages(); ?>
        <form method="POST" action="doc_addpatient.php">
            <!-- CSRF Token for security -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <div class="form-group mb-3">
                <label for="full_name" class="form-label">Full Name</label>
                <input type="text" id="full_name" name="full_name" class="form-control" placeholder="Enter patient's full name" required>
            </div>

            <div class="form-group mb-3">
                <label for="gender" class="form-label">Gender</label>
                <div class="dropdown-wrapper">
                    <select id="gender" name="gender" class="form-control" required>
                        <option value="" disabled selected>Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>

            <div class="form-group mb-3">
                <label for="status" class="form-label">Status</label>
                <div class="dropdown-wrapper">
                    <select id="status" name="status" class="form-control" required>
                        <option value="" disabled selected>Select Initial Status</option>
                        <option value="Stable">Stable</option>
                        <option value="Critical">Critical</option>
                        <option value="Warning">Warning</option>
                        <option value="Recovered">Recovered</option>
                    </select>
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>

            <div class="form-group mb-4">
                <label for="dob" class="form-label">Date of Birth</label>
                <input type="date" id="dob" name="dob" class="form-control" required>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary w-100">Add Patient</button>
            </div>
        </form>
    </div>
</div>

<?php 
// Include the standard doctor footer
include 'templates/footer.php';
?>
