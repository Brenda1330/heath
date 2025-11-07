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
        <h1 class="page-title mb-0">AI Analysis & Recommendation</h1>
        <div class="profile-dropdown">
            <!-- Profile Dropdown remains the same -->
        </div>
    </div>

    <?php display_flash_messages(); ?>

    <!-- Main Card for the entire workflow -->
    <div class="card recommendation-card" data-aos="fade-up">
        
        <!-- Step 1: Patient Selection -->
        <div class="analysis-step">
            <h5 class="step-title">Step 1: Patient & Timestamp Selection</h5>
            <p class="step-description">Begin by selecting a patient from your list, then choose a specific timestamp to analyze or select "View All" for a holistic summary.</p>
            <div class="row g-4 align-items-center">
                <div class="col-lg-6">
                    <select id="patientSelect" class="form-select form-select-lg" aria-label="Select Patient">
                        <option value="" selected disabled>-- Choose a patient --</option>
                        <?php foreach ($patients as $patient): ?>
                            <option value="<?php echo htmlspecialchars($patient['patient_id']); ?>">
                                <?php echo htmlspecialchars($patient['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-6">
                    <select id="timestampSelect" class="form-select form-select-lg" aria-label="Select Timestamp" disabled>
                        <option value="" selected disabled>-- Select a patient first --</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Patient Profile appears here after selection -->
        <div id="patientProfile" class="mt-4" style="display: none;" data-aos="fade-in">
            <div class="patient-profile-banner">
                <div class="profile-banner-avatar"><img id="profileImage" src="" alt="Patient Profile Image" /></div>
                <div class="profile-banner-info">
                    <h4 id="profileFullName">Patient Name</h4>
                    
                    <!-- The status is now a larger, more prominent status-box on its own line -->
                    <div class="mt-2">
                        <span id="profileStatusBox" class="status-box">Status</span>
                    </div>

                    <!-- The other details remain below -->
                    <div class="profile-meta-details mt-3">
                        <div class="meta-item"><i class="fas fa-venus-mars"></i><span id="profileGender"></span></div>
                        <div class="meta-item"><i class="fas fa-birthday-cake"></i><span id="profileAge"></span>years old</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Output Section (This wrapper is still controlled by JS) -->
        <div id="outputSection" style="margin-top: 3.5rem; display: none;" data-aos="fade-up">            
            <!-- Step 2: Graph Visualization -->
            <div id="graphSection" class="analysis-step">
                <h5 class="step-title">Step 2: Knowledge Graph Visualization</h5>
                <p class="step-description">The knowledge graph below illustrates the relationships between the patient's health metrics at the selected moment.</p>
                <div id="graphLoadingSpinner" class="text-center my-4">
                    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status"></div>
                    <p class="mt-2 text-muted">Fetching and rendering graph...</p>
                </div>
                <div id="graphDisplayWrapper" class="graph-display-wrapper">
                    <div id="graphContainer" class="graph-container" style="display: none;"></div>
                    <div id="graphNodeDetailPanel" class="node-detail-panel" style="display: none;">
                        <div class="panel-header"><h6 id="nodeDetailTitle"></h6><button id="closeDetailPanel" class="close-btn">&times;</button></div>
                        <div id="nodeDetailContent" class="panel-content"></div>
                    </div>
                </div>
            </div>
            
            <div id="algorithmSelectionWrapper" class="analysis-step" style="display: none;" data-aos="fade-up">
        
        <!-- The title and description now apply to this entire combined step -->
        <h5 class="step-title">Step 3: Generate AI Recommendation</h5>
        <p class="step-description">Select an algorithm to generate insights based on the graph data. The recommendation will appear below.</p>
        
        <!-- The dropdown menu is now inside the new parent -->
        <select id="algorithmSelect" class="form-select form-select-lg">
            <option value="" selected disabled>-- Select an algorithm --</option>
            <option value="PPR">PPR (Similarity Analysis)</option>
            <option value="Node2Vec">Node2Vec (Behavioral Clustering)</option>
            <option value="GAT">GAT (Risk Prediction)</option>
        </select>
        
        <div id="recommendationSection" style="display: none; margin-top: 2rem;" data-aos="fade-up" data-aos-delay="200">
            <div id="recommendationLoadingSpinner" class="text-center my-4">
                <div class="spinner-border text-success" style="width: 3rem; height: 3rem;" role="status"></div>
                <p class="mt-2 text-muted">Generating recommendation...</p>
            </div>
            <div id="recommendationText" class="alert"></div>
        </div>
        
    </div>

        <!-- Summary View Hint (remains the same) -->
        <div id="summaryHint" class="alert alert-info mt-4" style="display: none;">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Summary View:</strong> The graph and recommendation are based on an average of all available patient data.
        </div>
    </div>
</div>

<?php 
// This file must now contain the correct, multi-step JavaScript logic
include 'templates/footer_recommendation_scripts.php';
?>