<?php
// File: doc_patientlist.php

/**
 * CORE INCLUSION & AUTHORIZATION
 */
require_once 'functions.php';
secure_session_start();
add_security_headers();
require_doctor(); // Gatekeeper: Halts if user is not a logged-in doctor.

/**
 * DATA FETCHING LOGIC
 */
$patients = [];
$doctor_id = $_SESSION['user_id']; // Get ID from secure session

$conn = get_db_connection();
if (!$conn) {
    set_flash_message('Database connection failed. Could not load patient list.', 'danger');
} else {
    // Use a Prepared Statement to prevent SQL Injection
    $stmt = $conn->prepare(
        "SELECT patient_id, full_name, age, gender, status, created_at 
         FROM patients WHERE doctor_id = ? ORDER BY created_at DESC"
    );
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $patients_raw = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();

    $patients = $patients_raw;
    foreach ($patients as &$patient) {
        $patient['last_created'] = !empty($patient['created_at']) ? date('d M Y, H:i', strtotime($patient['created_at'])) : 'N/A';
    }
    unset($patient); // Good practice to unset the reference
}
/**
 * PRESENTATION (HTML)
 */
$pageTitle = "Patient List";
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

<!-- Main Content -->
<div class="dashboard-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1>All Patients</h1>
        <div class="d-flex align-items-center gap-2">
            <input type="text" id="searchInput" placeholder="Search by Name or Status..." class="form-control" style="width: 250px;" onkeyup="searchFunction()">
            <div class="dropdown">
                <button class="btn btn-light dropdown-toggle sorting-btn" type="button" id="sortMenu" data-bs-toggle="dropdown" aria-expanded="false">
                    Sort by: Default <span id="sortIndicator" class="sort-indicator"></span>
                </button>
                <ul class="dropdown-menu" aria-labelledby="sortMenu">
                    <li><a class="dropdown-item" href="#" onclick="sortTable(0, 'Patient Name'); return false;">Patient Name</a></li>
                    <!-- --- CHANGED: Sort by Age --- -->
                    <li><a class="dropdown-item" href="#" onclick="sortTable(1, 'Age'); return false;">Age</a></li>
                    <li><a class="dropdown-item" href="#" onclick="sortTable(3, 'Status'); return false;">Status</a></li>
                    <li><a class="dropdown-item" href="#" onclick="sortTable(4, 'Date Added'); return false;">Date Added</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="table-container">
    <?php display_flash_messages(); ?>
    <div class="table-responsive">
        <table class="table table-bordered" id="patientTable">
            <thead>
                <tr>
                    <th>Patient Name</th>
                    <th>Age</th>
                    <th>Gender</th>
                    <th>Status</th>
                    <th>Date Added</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($patients)): ?>
                    <?php foreach ($patients as $patient): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($patient['full_name']); ?></td>
                        <!-- --- CHANGED: Display 'age' instead of 'dob' --- -->
                        <td><?php echo htmlspecialchars($patient['age']); ?></td>
                        <td><?php echo htmlspecialchars($patient['gender']); ?></td>
                        <td>
                            <?php 
                            $status = strtolower($patient['status'] ?? 'unknown');
                            echo "<span class='status-box {$status}'>" . htmlspecialchars(ucfirst($status)) . "</span>";
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($patient['last_created']); ?></td>                
                        <td class="d-flex justify-content-center gap-2">
                            <a href="doc_patientdetail.php?patient_id=<?php echo htmlspecialchars($patient['patient_id']); ?>" class="btn btn-primary btn-sm">View Details</a>
<a href="doc_managepatient.php?id=<?php echo htmlspecialchars($patient['patient_id']); ?>" class="btn btn-outline-primary btn-sm">
        <i class="fas fa-pencil-alt me-1"></i> Manage
    </a>                        </td>                    
                    </tr>
                        <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center p-4">No patient records found. You can add one from the "Add Patient" page.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php 
// Includes the required JS for Bootstrap components, search, and sorting
include 'templates/footer_doctor_scripts.php';
?>