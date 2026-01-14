<?php
// File: doc_importdata.php

require_once 'functions.php';
secure_session_start();
add_security_headers();
require_doctor();

// --- Get the list of patients for the dropdown ---
$patients = [];
$doctor_id = $_SESSION['user_id'];
$conn = get_db_connection();
if ($conn) {
    $stmt = $conn->prepare("SELECT patient_id, full_name FROM patients WHERE doctor_id = ? ORDER BY full_name ASC");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $patients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// --- Handle Form Submission to INSERT data into health_data ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_health_data'])) {
    // Basic validation
    $patient_id = filter_input(INPUT_POST, 'patient_id', FILTER_VALIDATE_INT);
    $cgm_level = filter_input(INPUT_POST, 'cgm_level', FILTER_VALIDATE_FLOAT);
    
    if (!$patient_id || $cgm_level === false) {
        set_flash_message('Patient and a valid CGM Level are required.', 'danger');
    } elseif (!$conn) { // Check if the connection from above is still valid
        set_flash_message('Database connection was lost. Please try again.', 'danger');
    } else {
        // All other fields are optional strings
        $timestamp = date('d/m/Y G:i'); // Use time in the required format
        $blood_pressure = $_POST['blood_pressure'] ?? null;
        $heart_rate = $_POST['heart_rate'] ?? null;
        $cholesterol = $_POST['cholesterol'] ?? null;
        $insulin_intake = $_POST['insulin_intake'] ?? null;
        $food_intake = $_POST['food_intake'] ?? null;
        $activity_level = $_POST['activity_level'] ?? null;
        $weight = $_POST['weight'] ?? null;
        $hb1ac = $_POST['hb1ac'] ?? null;

        $sql = "INSERT INTO health_data (patient_id, timestamp, cgm_level, blood_pressure, heart_rate, cholesterol, insulin_intake, food_intake, activity_level, weight, hb1ac) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt_insert = $conn->prepare($sql);
        $stmt_insert->bind_param("isdsdssssds", $patient_id, $timestamp, $cgm_level, $blood_pressure, $heart_rate, $cholesterol, $insulin_intake, $food_intake, $activity_level, $weight, $hb1ac);
        
        if ($stmt_insert->execute()) {
            set_flash_message('Health data successfully imported!', 'success');
        } else {
            set_flash_message('Failed to import health data. Error: ' . $stmt_insert->error, 'danger');
        }
        $stmt_insert->close();
    }
    // Redirect to the same page to show the flash message
    redirect('doc_importdata.php');
}

if ($conn) {
    $conn->close();
}

$pageTitle = "Import Health Data";
include 'templates/header_doctor.php'; 
include 'templates/sidebar_doctor.php';
?>

<!-- Main Content -->
<div class="main-content">
    <h1 class="page-title mb-4">Import & Sync Patient Data</h1>
    <?php display_flash_messages(); ?>

    <!-- Card 1: Manual Data Import -->
<div class="card content-card mb-4" data-aos="fade-up">
    <h3 class="card-title">Step 1: Manually Import Health Data</h3>
    <p class="text-muted">Fill in the patient's latest health metrics. Only Patient and CGM Level are required.</p>
    
    <form action="doc_importdata.php" method="POST" class="mt-4">
        <div class="row g-4">

            <!-- == Required Fields == -->
            <div class="col-md-6">
                <label for="patient_id" class="form-label">Select Patient <span class="text-danger">*</span></label>
                <select id="patient_id" name="patient_id" class="form-select" required>
                    <option value="" selected disabled>-- Choose a patient --</option>
                    <?php foreach ($patients as $p): ?>
                        <option value="<?php echo htmlspecialchars($p['patient_id']); ?>">
                            <?php echo htmlspecialchars($p['full_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="cgm_level" class="form-label">CGM Level (mmol/L) <span class="text-danger">*</span></label>
                <input type="number" step="0.1" class="form-control" id="cgm_level" name="cgm_level" placeholder="e.g., 7.8" required>
            </div>

            <!-- == Optional Fields (All new fields added here) == -->
            <div class="col-md-6">
                <label for="blood_pressure" class="form-label">Blood Pressure</label>
                <input type="text" class="form-control" id="blood_pressure" name="blood_pressure" placeholder="e.g., 120/80">
            </div>
            <div class="col-md-6">
                <label for="heart_rate" class="form-label">Heart Rate (bpm)</label>
                <input type="number" class="form-control" id="heart_rate" name="heart_rate" placeholder="e.g., 72">
            </div>
            <div class="col-md-6">
                <label for="cholesterol" class="form-label">Cholesterol</label>
                <input type="number" step="0.1" class="form-control" id="cholesterol" name="cholesterol" placeholder="e.g., 190.5">
            </div>
            <div class="col-md-6">
                <label for="insulin_intake" class="form-label">Insulin Intake (units)</label>
                <input type="number" class="form-control" id="insulin_intake" name="insulin_intake" placeholder="e.g., 2">
            </div>
            <div class="col-12">
                <label for="food_intake" class="form-label">Food Intake</label>
                <input type="text" class="form-control" id="food_intake" name="food_intake" placeholder="e.g., Grilled chicken salad">
            </div>
            <div class="col-12">
                <label for="activity_level" class="form-label">Activity Level</label>
                <input type="text" class="form-control" id="activity_level" name="activity_level" placeholder="e.g., Walking 30 minutes">
            </div>
            <div class="col-md-6">
                <label for="weight" class="form-label">Weight (kg)</label>
                <input type="number" step="0.1" class="form-control" id="weight" name="weight" placeholder="e.g., 75.5">
            </div>
            <div class="col-md-6">
                <label for="hb1ac" class="form-label">HbA1c</label>
                <input type="number" step="0.1" class="form-control" id="hb1ac" name="hb1ac" placeholder="e.g., 48.0">
            </div>

        </div>
        <div class="d-flex justify-content-end mt-4">
            <button type="submit" name="submit_health_data" class="btn btn-primary">Import Data</button>
        </div>
    </form>
</div>

    <!-- Card 2: Trigger Sync & Algorithms -->
    <div class="card content-card" data-aos="fade-up" data-aos-delay="100">
        <h3 class="card-title">Step 2: Sync to Graph & Generate Insights</h3>
        <p class="text-muted">After importing new data, click the button below to sync the latest record to the Neo4j graph database and run the analysis algorithms (PPR, Node2Vec, GAT). This may take a minute to complete.</p>
        <div class="d-flex justify-content-end mt-4">
            <button id="syncButton" class="btn btn-success">
                <span id="syncSpinner" class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display: none;"></span>
                Sync Latest Record & Run Algorithms
            </button>
        </div>
        <div id="syncResult" class="mt-3"></div>
    </div>
</div>

<script>
document.getElementById('syncButton').addEventListener('click', function() {
    const button = this;
    const spinner = document.getElementById('syncSpinner');
    const resultDiv = document.getElementById('syncResult');

    // UI feedback
    button.disabled = true;
    spinner.style.display = 'inline-block';
    resultDiv.innerHTML = '<div class="alert alert-info">Syncing in progress... Please wait. This may take up to a minute.</div>';

    // --- THIS IS THE FIX ---
    // The URL now correctly points to your PHP sync script.
    fetch('api/sync.php')
        .then(response => {
            if (!response.ok) {
                // Try to get a more detailed error message from the server
                return response.text().then(text => { 
                    try {
                        // If the error is JSON, parse it
                        const errorData = JSON.parse(text);
                        throw new Error(errorData.error || 'Unknown server error');
                    } catch (e) {
                        // If not JSON, it might be an HTML error page. Show a snippet.
                        throw new Error('Server returned a non-JSON error page. Check PHP logs.');
                    }
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            resultDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
        })
        .catch(error => {
            resultDiv.innerHTML = `<div class="alert alert-danger"><strong>Error:</strong> ${error.message}</div>`;
        })
        .finally(() => {
            // Restore button
            button.disabled = false;
            spinner.style.display = 'none';
        });
});
</script>

<?php include 'templates/footer_doctor_scripts.php'; ?>