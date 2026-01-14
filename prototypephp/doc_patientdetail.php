<?php
// File: doc_patientdetail.php

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
$patient = null;
$health_data = [];
$recommendations = [];
$doctor_id = $_SESSION['user_id'];

// 1. Input Validation: Ensure patient_id is a positive integer.
$patient_id = filter_input(INPUT_GET, 'patient_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if (!$patient_id) {
    set_flash_message('Invalid or missing patient ID.', 'danger');
    redirect('doc_patientlist.php');
}

$conn = get_db_connection();
if (!$conn) {
    set_flash_message('Database connection failed. Cannot load patient details.', 'danger');
    redirect('doc_patientlist.php');
} else {
    // 2. Fetch Patient Details using a Prepared Statement
    // CRITICAL: Also check that this patient belongs to the logged-in doctor
    // --- CHANGED: Fetched 'age' instead of 'dob' ---
    $stmt = $conn->prepare(
        "SELECT patient_id, full_name, age, gender, status 
         FROM patients WHERE patient_id = ? AND doctor_id = ?"
    );
    $stmt->bind_param("ii", $patient_id, $doctor_id);
    $stmt->execute();
    $patient = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$patient) {
        set_flash_message('Patient not found or not assigned to you.', 'warning');
        redirect('doc_patientlist.php');
    }

    // 3. Fetch Health Data for this patient
    $stmt_health = $conn->prepare(
        "SELECT * FROM health_data 
         WHERE patient_id = ? ORDER BY STR_TO_DATE(timestamp, '%d/%m/%Y %H:%i') DESC"
    );
    $stmt_health->bind_param("i", $patient_id);
    $stmt_health->execute();
    $health_data = $stmt_health->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_health->close();

    $sql_union = "
        -- First, get the single-event recommendations
        SELECT 
            recommendation, 
            created_at, 
            algorithm_used 
        FROM recommendations
        WHERE patient_id = ?

        UNION ALL

        -- Second, get the summary recommendations
        SELECT 
            recommendation_text AS recommendation, 
            generated_at AS created_at, 
            'Summary' AS algorithm_used 
        FROM patient_summaries
        WHERE patient_id = ?

        -- Finally, order the combined results by date, newest first
        ORDER BY created_at DESC
    ";

    $stmt_recs = $conn->prepare($sql_union);
    // We need to bind the patient_id for both parts of the union
    $stmt_recs->bind_param("ii", $patient_id, $patient_id);
    $stmt_recs->execute();
    $recommendations = $stmt_recs->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_recs->close();

    $conn->close();
}


/**
 * PRESENTATION (HTML)
 */
$pageTitle = "Patient Detail";
// Use the dedicated header for the doctor panel
include 'templates/header_doctor.php'; 
include 'templates/sidebar_doctor.php';

?>
<!-- Profile Dropdown (Top Right) -->
<div class="profile-dropdown">
    <div class="dropdown">
        <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="fas fa-user-circle"></i></button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="doc_userprofile.php">View Profile</a></li>
            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
        </ul>
    </div>
</div>
<!-- Main Content -->
<div class="main-content">
<?php if ($patient): ?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <a href="doc_patientlist.php" class="back-link"><i class="fas fa-arrow-left me-2"></i> Back to Patient List</a>
        <h1 class="patient-name mt-2"><?php echo htmlspecialchars($patient['full_name']); ?></h1>
    </div>
</div>

<!-- Patient Profile Card -->
<div class="patient-profile-card">
    <div class="patient-avatar">
        <?php
            // --- START: NEW LOGIC FOR DYNAMIC AVATAR ---
            // 1. Get the gender and convert it to lowercase for a reliable comparison.
            $gender = strtolower($patient['gender'] ?? 'unknown');
            
            // 2. Set the default image path.
            $avatar_path = 'static/uploads/patient.jpg'; // A default/neutral avatar

            // 3. Check the gender and change the path if it's male or female.
            if ($gender === 'male') {
                $avatar_path = 'static/uploads/male.png';
            } elseif ($gender === 'female') {
                $avatar_path = 'static/uploads/female.png';
            }
            // --- END: NEW LOGIC ---
        ?>
        <!-- The img tag now uses the dynamic $avatar_path variable -->
        <img src="<?php echo htmlspecialchars($avatar_path); ?>" alt="Patient Avatar">
    </div>
    <div class="patient-details">
        <div class="info-block">
            <span class="label">Gender</span>
            <span class="value"><?php echo htmlspecialchars($patient['gender']); ?></span>
        </div>
        <div class="info-block">
            <span class="label">Age</span>
            <span class="value"><?php echo htmlspecialchars($patient['age']); ?></span>
        </div>
        <div class="info-block">
            <span class="label">Status</span>
            <span class="value">
                <?php 
                    $status = strtolower($patient['status'] ?? 'unknown');
                    echo "<span class='status-box {$status}'>" . htmlspecialchars(ucfirst($status)) . "</span>";
                ?>
            </span>
        </div>
    </div>
</div>

<!-- Health Data History Card -->
<div class="content-card">
    <h3>Health Data History</h3>
    <div class="table-responsive">
        <table class="table modern-table">
            <thead>
                <tr>
                    <th>Timestamp</th><th>CGM (mmol/L)</th><th>BP</th><th>HR</th><th>Cholesterol</th><th>Insulin</th>
                    <th>Food Intake</th><th>Activity</th><th>Weight</th><th>HbA1c</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($health_data)): ?>
                    <?php foreach ($health_data as $data): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($data['timestamp']); ?></td>
                        <td><?php echo htmlspecialchars($data['cgm_level']); ?></td>
                        <td><?php echo htmlspecialchars($data['blood_pressure']); ?></td>
                        <td><?php echo htmlspecialchars($data['heart_rate']); ?></td>
                        <td><?php echo htmlspecialchars($data['cholesterol']); ?></td>
                        <td><?php echo htmlspecialchars($data['insulin_intake']); ?></td>
                        <td><?php echo htmlspecialchars($data['food_intake']); ?></td>
                        <td><?php echo htmlspecialchars($data['activity_level']); ?></td>
                        <td><?php echo htmlspecialchars($data['weight']); ?></td>
                        <td><?php echo htmlspecialchars($data['hb1ac']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="10" class="text-center p-4">No health data has been recorded for this patient.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="content-card">
    <h3>AI Recommendation History</h3>
    
    <?php if (!empty($recommendations)): ?>
        <div class="recommendation-list mt-3">
            <?php foreach ($recommendations as $rec): ?>
                <div class="recommendation-item">
                    <div class="rec-icon">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="rec-content">
                        <p><?php echo htmlspecialchars($rec['recommendation']); ?></p>
                        <small class="text-muted">
                            Generated on: <?php echo htmlspecialchars(date('d M Y, h:i A', strtotime($rec['created_at']))); ?>
                        </small>
                        <?php if (!empty($rec['algorithm_used'])): ?>
                            <span class="badge bg-primary algorithm-badge">
                                    <?php echo htmlspecialchars($rec['algorithm_used']); ?>
                                </span>
                            <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-muted mt-3">No recommendations have been generated for this patient yet.</p>
    <?php endif; ?>
</div>

<?php else: ?>
    <div class="alert alert-danger mt-4">Could not load patient details.</div>
<?php endif; ?>
</div>

<?php 
// Includes the required JS for Bootstrap components, search, and sorting
include 'templates/footer_doctor_scripts.php';
?>