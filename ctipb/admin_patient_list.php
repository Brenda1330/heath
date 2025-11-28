<?php
// File: admin_patient_list.php

/**
 * CORE INCLUSION & AUTHORIZATION
 */
require_once 'functions.php';
secure_session_start();
add_security_headers();
require_admin(); // Gatekeeper: Ensures only admins can access this page.

/**
 * DATA FETCHING LOGIC
 */
$doctor = null;
$patients = [];

// 1. Input Validation: Ensure doctor_id from the URL is a positive integer.
$doctor_id = filter_input(INPUT_GET, 'doctor_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if (!$doctor_id) {
    set_flash_message('Invalid or missing doctor ID.', 'danger');
    redirect('admin_doctor_details.php');
}

$conn = get_db_connection();
if (!$conn) {
    set_flash_message('Database connection failed.', 'danger');
} else {
    // 2. Fetch the Doctor's details for the page title
    $stmt_doc = $conn->prepare("SELECT username FROM users WHERE user_id = ? AND role = 'doctor'");
    $stmt_doc->bind_param("i", $doctor_id);
    $stmt_doc->execute();
    $doctor = $stmt_doc->get_result()->fetch_assoc();
    $stmt_doc->close();

    if (!$doctor) {
        set_flash_message('No doctor found for the specified ID.', 'warning');
        redirect('admin_doctor_details.php');
    }

    // 3. Fetch all patients assigned to this specific doctor
    $stmt_patients = $conn->prepare(
        "SELECT patient_id, full_name, age, gender, status, created_at 
         FROM patients WHERE doctor_id = ? ORDER BY full_name ASC"
    );
    $stmt_patients->bind_param("i", $doctor_id);
    $stmt_patients->execute();
    $patients = $stmt_patients->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_patients->close();
    
    $conn->close();
}

/**
 * PRESENTATION (HTML)
 */
$pageTitle = "Doctor's Patient List";
include 'templates/header.php'; 
include 'templates/sidebar_admin.php';
?>

<!-- Main Content -->
<div class="main-content">
    <div class="page-header">
        <a href="admin_doctor_profile.php?doctor_id=<?php echo htmlspecialchars($doctor_id, ENT_QUOTES, 'UTF-8'); ?>" class="back-link">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-left"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            <span>Back to Dr. <?php echo htmlspecialchars($doctor['username'], ENT_QUOTES, 'UTF-8'); ?>'s Profile</span>
        </a>
        <h1 class="page-title">Patient Roster</h1>
        <?php if ($doctor): ?>
            <p>A complete list of patients assigned to <strong><?php echo htmlspecialchars($doctor['username'], ENT_QUOTES, 'UTF-8'); ?></strong>.</p>
        <?php endif; ?>
    </div>

    <?php display_flash_messages(); ?>

    <div class="card patient-list-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Patient Name</th>
                            <th>Age</th>
                            <th>Gender</th>
                            <th>Status</th>
                            <th>Date Added</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($patients)): ?>
                            <?php foreach ($patients as $patient): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($patient['full_name'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                <td><?php echo htmlspecialchars($patient['age'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($patient['gender'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <?php 
                                    $status = strtolower($patient['status'] ?? 'unknown');
                                    echo "<span class='status-badge status-{$status}'>" . htmlspecialchars(ucfirst($status), ENT_QUOTES, 'UTF-8') . "</span>";
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars(date('M d, Y', strtotime($patient['created_at'])), ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center p-5">
                                    <p class="mb-0">This doctor currently has no patients assigned.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<?php
include 'templates/footer.php'; 
?>