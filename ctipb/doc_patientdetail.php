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
$patient = null; $health_data = []; $recommendations = [];
$health_data = [];
$recommendations = [];
$doctor_id = $_SESSION['user_id'];
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
    // Fetch Patient Details (Unchanged and correct)
    $stmt = $conn->prepare("SELECT patient_id, full_name, age, gender, status FROM patients WHERE patient_id = ? AND doctor_id = ?");
    $stmt->bind_param("ii", $patient_id, $doctor_id);
    $stmt->execute();
    $patient = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$patient) {
        set_flash_message('Patient not found or not assigned to you.', 'warning');
        redirect('doc_patientlist.php');
    }

    // Fetch Health Data (Unchanged and correct)
$stmt_health = $conn->prepare("SELECT data_id, timestamp, cgm_level, blood_pressure, heart_rate, cholesterol, insulin_intake, food_intake, activity_level, weight, hb1ac FROM health_data WHERE patient_id = ? ORDER BY STR_TO_DATE(timestamp, '%d/%m/%Y %H:%i') DESC");    $stmt_health->bind_param("i", $patient_id);
    $stmt_health->execute();
    $health_data = $stmt_health->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_health->close();

    // ==================================================================
    // === THE FIX: The SQL query is updated to select the necessary IDs and types
    // ==================================================================
     $sql_union = "
        SELECT 
            recommendation_id as rec_id, -- Use the correct column name and alias it
            recommendation, 
            created_at, 
            algorithm_used,
            'single' as rec_type
        FROM recommendations
        WHERE patient_id = ?

        UNION ALL

        SELECT 
            summary_id as rec_id, -- This part was already correct
            recommendation_text AS recommendation, 
            generated_at AS created_at, 
            algorithm_used,
            'summary' as rec_type
        FROM patient_summaries
        WHERE patient_id = ?

        ORDER BY created_at DESC
    ";
    // ==================================================================
    // === END OF FIX
    // ==================================================================

    $stmt_recs = $conn->prepare($sql_union);
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

<div class="content-card mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Patient Health Trends</h3>
        <div class="d-flex align-items-center">
            <label for="metricSelect" class="form-label me-2 mb-0">Metric:</label>
            <select id="metricSelect" class="form-select" style="width: auto;">
                <option value="cgm_level" selected>CGM</option>
                <option value="hb1ac">HbA1c</option>
                <option value="heart_rate">Heart Rate</option>
                <option value="weight">Weight</option>
            </select>
        </div>
    </div>
    <div style="height: 350px;">
        <canvas id="patientTrendChart"></canvas>
    </div>
</div>

<!-- Health Data History Card -->
<div class="content-card">
    <h3>Health Data History</h3>
    <div class="table-responsive">
        <table class="table modern-table">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>CGM (mmol/L)</th>
                    <th>BP (mmHg)</th>
                    <th>HR (bpm)</th>
                    <th>Cholesterol (mg/dL)</th>
                    <th>Insulin (units)</th>
                    <th>Food Intake</th>
                    <th>Activity</th>
                    <th>Weight (kg)</th>
                    <th>HbA1c (%)</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($health_data)): ?>
                    <?php foreach ($health_data as $data): ?>
                    <tr>
                        <td>
                        <?php 
                        // 1. Create a DateTime object treating the DB value as UTC
                        $utc_date = DateTime::createFromFormat('d/m/Y H:i', $data['timestamp'], new DateTimeZone('UTC'));
                        
                        if ($utc_date) {
                            // 2. Convert the timezone to Malaysia (Asia/Kuala_Lumpur)
                            $utc_date->setTimezone(new DateTimeZone('Asia/Kuala_Lumpur'));
                            // 3. Display the formatted date
                            echo htmlspecialchars($utc_date->format('d/m/Y H:i')); 
                        } else {
                            // Fallback if date format is invalid
                            echo htmlspecialchars($data['timestamp']); 
                        }
                        ?>
                    </td>
                        <td>
                            <?php 
                                $cgm = floatval($data['cgm_level']);
                                echo htmlspecialchars($cgm); 
                                if ($cgm > 10.0) {
                                    echo ' <i class="fas fa-exclamation-triangle text-danger" title="High Glucose Reading"></i>';
                                }
                            ?>
                        </td>
                        <!-- THE FIX: The two duplicated columns below have been removed. -->
                        <td><?php echo htmlspecialchars($data['blood_pressure']); ?></td>
                        <td><?php echo htmlspecialchars($data['heart_rate']); ?></td>
                        <td><?php echo htmlspecialchars($data['cholesterol']); ?></td>
                        <td><?php echo htmlspecialchars($data['insulin_intake']); ?></td>
                        <td><?php echo htmlspecialchars($data['food_intake']); ?></td>
                        <td><?php echo htmlspecialchars($data['activity_level']); ?></td>
                        <td><?php echo htmlspecialchars($data['weight']); ?></td>
                        <td><?php echo htmlspecialchars($data['hb1ac']); ?></td>
                        <td>
    <?php
    $cgm = floatval($data['cgm_level']);
    
    if ($cgm > 13.9) {
        echo '<div class="d-flex align-items-center gap-2 justify-content-center">';
        echo '<span class="badge bg-danger">Critical</span>';
        echo '<button class="btn btn-sm btn-outline-danger" title="Explain critical glucose spike" onclick="getSpikeExplanation(' . htmlspecialchars($data['data_id']) . ')">';
        echo '<i class="fas fa-exclamation-triangle"></i>';
        echo '</button>';
        echo '</div>';
    } elseif ($cgm > 10.0) {
        echo '<div class="d-flex align-items-center gap-2 justify-content-center">';
        echo '<span class="badge bg-warning text-dark">High</span>';
        echo '<button class="btn btn-sm btn-outline-warning" title="Explain high glucose reading" onclick="getSpikeExplanation(' . htmlspecialchars($data['data_id']) . ')">';
        echo '<i class="fas fa-lightbulb"></i>';
        echo '</button>';
        echo '</div>';
    } elseif ($cgm < 4.0) {
        echo '<div class="d-flex align-items-center gap-2 justify-content-center">';
        echo '<span class="badge bg-info">Low</span>';
        echo '<button class="btn btn-sm btn-outline-info" title="Explain low glucose reading" onclick="getSpikeExplanation(' . htmlspecialchars($data['data_id']) . ')">';
        echo '<i class="fas fa-arrow-down"></i>';
        echo '</button>';
        echo '</div>';
    } else {
        echo '<span class="badge bg-success">Normal</span>';
    }
    ?>
</td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- THE FIX: Corrected the colspan to match the 11 columns. -->
                    <tr><td colspan="11" class="text-center p-4">No health data has been recorded for this patient.</td></tr>
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
                <!-- FIX: Add the proper ID that matches what JavaScript expects -->
                <div id="rec-item-<?php echo htmlspecialchars($rec['rec_type']); ?>-<?php echo htmlspecialchars($rec['rec_id']); ?>" class="recommendation-item">
                    <div class="rec-icon">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="rec-content">
                        <p><?php echo htmlspecialchars($rec['recommendation']); ?></p>
                        <div class="rec-footer">
                            <small class="text-muted">
                                Generated on: <?php echo htmlspecialchars(date('d M Y, h:i A', strtotime($rec['created_at']))); ?>
                            </small>
                            <div class="rec-meta-actions">
                                <?php if (!empty($rec['algorithm_used'])): ?>
                                    <span class="badge bg-primary algorithm-badge">
                                        <?php echo htmlspecialchars($rec['algorithm_used']); ?>
                                    </span>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-outline-danger" 
                                        title="Delete Recommendation" 
                                        onclick="confirmRecDelete('<?php echo htmlspecialchars($rec['rec_type']); ?>', <?php echo htmlspecialchars($rec['rec_id']); ?>)">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-muted mt-3">No recommendations have been generated for this patient yet.</p>
    <?php endif; ?>
</div>

<div class="modal fade" id="spikeExplanationModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-lightbulb me-2"></i> Spike Causal Path</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <!-- The content will be loaded here by JavaScript -->
        <div id="explanationSpinner" class="text-center">
            <div class="spinner-border text-info" role="status"></div>
            <p class="mt-2 text-muted">Analyzing data pathway...</p>
        </div>
        <div id="explanationContent" style="display: none;">
            <!-- JS will populate this -->
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- NEW: Danger Zone - Delete Recommendation Confirmation Modal -->
<div class="modal fade" id="deleteRecModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Confirm Deletion</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to permanently delete this AI recommendation? This action cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteRecBtn">Yes, Delete</button>
      </div>
    </div>
  </div>
</div>

<?php else: ?>
    <div class="alert alert-danger mt-4">Could not load patient details.</div>
<?php endif; ?>
</div>

<?php 
// Includes the required JS for Bootstrap components, search, and sorting
include 'templates/footer_doctor_scripts.php';
?>