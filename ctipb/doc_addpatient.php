<?php
// File: doc_addpatient.php

/**
 * CORE INCLUSION & AUTHORIZATION
 */
require_once 'functions.php';
secure_session_start();
add_security_headers();
require_doctor();

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
        log_system_action('CSRF Token Fail', 'fail', 'doc_addpatient.php');
        set_flash_message('A security error occurred. Please try again.', 'danger');
        redirect('doc_addpatient.php');
    }

    $full_name = trim($_POST['full_name'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $status = $_POST['status'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $doctor_id = $_SESSION['user_id'];
    $allowed_genders = ['Male', 'Female'];
    $allowed_statuses = ['Stable', 'Critical', 'Warning', 'Recovered'];

    // Validation for patient name - only letters and spaces allowed
    if (empty($full_name) || empty($gender) || empty($status) || empty($dob)) {
        set_flash_message('All fields are required.', 'warning');
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $full_name)) {
        set_flash_message('Patient name can only contain letters and spaces. No numbers, symbols, or special characters allowed.', 'danger');
    } elseif (!in_array($gender, $allowed_genders)) {
        set_flash_message('Invalid gender selected.', 'danger');
    } elseif (!in_array($status, $allowed_statuses)) {
        set_flash_message('Invalid status selected.', 'danger');
    } else {
        $dob_date = DateTime::createFromFormat('Y-m-d', $dob);
        if (!$dob_date || $dob_date > new DateTime()) {
            set_flash_message('Invalid or future date of birth provided.', 'danger');
            redirect('doc_addpatient.php');
        }
        $age = (new DateTime())->diff($dob_date)->y;

        $conn = get_db_connection();
        if (!$conn) {
            set_flash_message('Database connection failed.', 'danger');
            log_system_action('DB Connection Fail', 'error', 'doc_addpatient.php');
        } else {
            $stmt = $conn->prepare("INSERT INTO patients (full_name, age, gender, status, doctor_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sissi", $full_name, $age, $gender, $status, $doctor_id);

            if ($stmt->execute()) {
                set_flash_message('Patient added successfully!', 'success');
                log_system_action('Add Patient Success', 'success', "Patient: {$full_name}");
                redirect('doc_patientlist.php');
            } else {
                set_flash_message('Failed to add patient to the database.', 'danger');
                log_system_action('Add Patient Fail', 'error', "Patient: {$full_name}", null, null, $stmt->error);
            }
            $stmt->close();
            $conn->close();
        }
    }
    redirect('doc_addpatient.php');
}

$pageTitle = "Add Patient";
include 'templates/header_doctor.php';
include 'templates/sidebar_doctor.php';
?>

<div class="profile-dropdown">
    <div class="dropdown">
        <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-user-circle"></i></button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="doc_userprofile.php">View Profile</a></li>
            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
        </ul>
    </div>
</div>

<div class="pro-form-container">
    <div class="pro-form-header">
        <h1>Add New Patient</h1>
        <p class="text-muted">Fill in the patient details below</p>
    </div>
    <div class="pro-form-card">
        <?php display_flash_messages(); ?>
        <form method="POST" action="doc_addpatient.php" id="addPatientForm" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            
            <div class="pro-form-group">
                <label for="full_name" class="pro-form-label" data-icon="user">Full Name</label>
                <input type="text" id="full_name" name="full_name" class="pro-form-control" 
                       placeholder="Enter patient's full name" 
                       pattern="[a-zA-Z\s]+" 
                       title="Only letters and spaces are allowed. No numbers, symbols, or special characters."
                       required>
                <div class="pro-form-text">Only letters and spaces are allowed</div>
                <div class="pro-invalid-feedback">Please enter a valid name using only letters and spaces.</div>
            </div>
            
            <div class="pro-form-group">
                <label for="gender" class="pro-form-label" data-icon="gender">Gender</label>
                <select id="gender" name="gender" class="pro-form-select" required>
                    <option value="" disabled selected>Select Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
                <div class="pro-invalid-feedback">Please select a gender.</div>
            </div>
            
            <div class="pro-form-group">
                <label for="status" class="pro-form-label" data-icon="status">Status</label>
                <select id="status" name="status" class="pro-form-select" required>
                    <option value="" disabled selected>Select Initial Status</option>
                    <option value="Stable">Stable</option>
                    <option value="Critical">Critical</option>
                    <option value="Warning">Warning</option>
                    <option value="Recovered">Recovered</option>
                </select>
                <div class="pro-invalid-feedback">Please select a status.</div>
            </div>
            
            <div class="pro-form-group">
                <label for="dob" class="pro-form-label" data-icon="calendar">Date of Birth</label>
                <input type="date" id="dob" name="dob" class="pro-form-control" max="<?php echo date('Y-m-d'); ?>" required>
                <div class="pro-invalid-feedback">Please provide a valid date of birth.</div>
            </div>
            
            <div class="pro-form-group">
                <button type="submit" class="pro-form-button">
                    <i class="fas fa-user-plus me-2"></i>Add Patient
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
<script>
(function () {
  'use strict'
  var form = document.getElementById('addPatientForm');
  
  // Real-time validation for patient name
  var nameInput = document.getElementById('full_name');
  nameInput.addEventListener('input', function() {
    var value = this.value;
    // Remove any non-letter characters as user types (keep only letters and spaces)
    var cleanValue = value.replace(/[^a-zA-Z\s]/g, '');
    if (value !== cleanValue) {
      this.value = cleanValue;
    }
  });
  
  form.addEventListener('submit', function (event) {
    if (!form.checkValidity()) {
      event.preventDefault();
      event.stopPropagation();
    }
    form.classList.add('was-validated');
  }, false);
})();
</script>