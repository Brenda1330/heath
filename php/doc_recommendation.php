<?php
// File: doc_recommendation.php

/**
 * CORE INCLUSION & AUTHORIZATION
 */
require_once 'functions.php';
secure_session_start();
add_security_headers();
require_doctor();

/**
 * DATA FETCHING LOGIC (for initial patient list)
 */
$patients = [];
$doctor_id = $_SESSION['user_id'];
$conn = get_db_connection();
if ($conn) {
    $stmt = $conn->prepare("SELECT patient_id, full_name FROM patients WHERE doctor_id = ? ORDER BY full_name ASC");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $patients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
} else {
    set_flash_message('Database connection failed.', 'danger');
}

/**
 * PRESENTATION (HTML)
 */
$pageTitle = "Patient Recommendation";
include 'templates/header_doctor.php'; 
include 'templates/sidebar_doctor.php';
?>

<!-- Main Content -->
<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title mb-0">AI Recommendation</h1>
        <div class="profile-dropdown">
            <div class="dropdown">
                <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="fas fa-user-circle"></i></button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="doc_userprofile.php">View Profile</a></li>
                    <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </div>

    <?php display_flash_messages(); ?>

    <div class="card recommendation-card" data-aos="fade-up">
        <h5 class="card-title">Generate Patient Recommendation</h5>
        
        <div class="row g-4">
            <!-- Step 1: Select Patient -->
            <div class="col-12">
                <label for="patientSelect" class="form-label"><span class="step-badge">1</span> Select Patient</label>
                <select id="patientSelect" class="form-select form-select-lg">
                    <option value="" selected disabled>-- Choose a patient from your list --</option>
                    <?php foreach ($patients as $patient): ?>
                        <option value="<?php echo htmlspecialchars($patient['patient_id']); ?>">
                            <?php echo htmlspecialchars($patient['full_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Patient Profile (Initially Hidden) -->
            <div id="patientProfile" class="col-12" style="display: none;" data-aos="fade-in">
                <div class="patient-profile-layout">
                    <div class="profile-image-container"><img id="profileImage" src="" alt="Profile Image" /></div>
                    <div class="profile-info">
                        <h6 id="profileFullName"></h6>
                        <span><strong>Gender:</strong> <span id="profileGender"></span></span>
                        <span><strong>DOB:</strong> <span id="profileDOB"></span></span>
                        <span><strong>Status:</strong> <span id="profileStatus"></span></span>
                    </div>
                </div>
            </div>

            <!-- Step 2 & 3: Select Timestamp and Algorithm -->
            <div class="col-md-6">
                <label for="timestampSelect" class="form-label"><span class="step-badge">2</span> Select Timestamp</label>
                <select id="timestampSelect" class="form-select form-select-lg" disabled>
                    <option value="" selected disabled>-- Select a patient first --</option>
                </select>
            </div>
            <div class="col-md-6">
                <label for="algorithmSelect" class="form-label"><span class="step-badge">3</span> Select Algorithm</label>
                <select id="algorithmSelect" class="form-select form-select-lg" disabled>
                    <option value="" selected disabled>-- Select a timestamp first --</option>
                    <option value="PPR">PPR</option>
                    <option value="Node2Vec">Node2Vec</option>
                    <option value="GAT">GAT</option>
                </select>
            </div>
        </div>

        <!-- Recommendation Output (Initially Hidden) -->
        <div id="recommendationSection" style="margin-top: 30px; display: none;" data-aos="fade-up" data-aos-delay="200">
            <h5 class="card-title mt-4">Generated Recommendation</h5>
            <div id="loadingSpinner" style="display: none;" class="text-center my-4">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Generating AI recommendation...</p>
            </div>
            <div id="recommendationText" class="alert"></div>
        </div>
    </div>
</div>

<?php 
include 'templates/footer_doctor_scripts.php';
?>