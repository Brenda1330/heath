<?php
// File: doc_importdata.php

require_once 'functions.php';
secure_session_start();
add_security_headers();
require_doctor();

// --- Retrieve and clear old form input and errors if they exist ---
$old_input = $_SESSION['old_input'] ?? [];
$field_errors = $_SESSION['field_errors'] ?? [];
unset($_SESSION['old_input']);
unset($_SESSION['field_errors']);

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_health_data'])) {
    // Store POST data in session to repopulate form on failure
    $_SESSION['old_input'] = $_POST;
    $field_errors = [];
    $has_errors = false;
    
    // --- Individual Field Validation Logic ---
    // Patient ID validation
    if (empty(trim($_POST['patient_id']))) {
        $field_errors['patient_id'] = 'Please select a patient from the list';
    }

    // Timestamp validation - FIXED: Convert to UTC for database, validate in Malaysia time
    // Timestamp validation
    if (empty(trim($_POST['timestamp']))) {
        $field_errors['timestamp'] = 'Please enter a valid timestamp';
    } else {
        $timestamp_input = $_POST['timestamp'];
        $malaysia_tz = new DateTimeZone('Asia/Kuala_Lumpur');
        $date_obj = false;

        // 1. Try Strict Format (d/m/Y H:i) - Matches Flatpickr default
        $date_obj = DateTime::createFromFormat('d/m/Y H:i', $timestamp_input, $malaysia_tz);

        // 2. If strict fails, try flexible parsing
        if (!$date_obj) {
            // Replace slashes with dashes to help strtotime if needed, or just try direct
            // Note: strtotime assumes American M/D/Y with slashes, so we must be careful.
            // We force European/Australian d-m-Y if slashes are used by replacing them.
            $clean_ts = str_replace('/', '-', $timestamp_input); 
            if (($timestamp = strtotime($clean_ts)) !== false) {
                $date_obj = new DateTime();
                $date_obj->setTimestamp($timestamp);
                $date_obj->setTimezone($malaysia_tz);
            }
        }

        if (!$date_obj) {
            $field_errors['timestamp'] = 'Invalid format. Please use the calendar picker.';
        } else {
            // Check constraints
            $now = new DateTime('now', $malaysia_tz);
            // Allow a small buffer for "future" times due to server clock differences
            $future_limit = (clone $now)->modify('+10 minutes'); 
            $thirty_days_ago = (new DateTime('now', $malaysia_tz))->modify('-30 days');

            if ($date_obj > $future_limit) {
                $field_errors['timestamp'] = 'Timestamp cannot be in the future.';
            } elseif ($date_obj < $thirty_days_ago) {
                $field_errors['timestamp'] = 'Timestamp cannot be older than 30 days';
            } else {
                // SUCCESS: Reformat to standard DB format
                // This ensures what goes into the DB is always clean 'd/m/Y H:i'
                $_POST['timestamp'] = $date_obj->format('d/m/Y H:i');
            }
        }
    }

    // CGM Level validation
    if (empty(trim($_POST['cgm_level']))) {
        $field_errors['cgm_level'] = 'Please enter CGM Level';
    } elseif (!filter_var($_POST['cgm_level'], FILTER_VALIDATE_FLOAT, ["options" => ["min_range" => 1, "max_range" => 30]])) {
        $field_errors['cgm_level'] = 'Value must be between 1-30 mmol/L';
    }

    // Blood Pressure validation
    if (empty(trim($_POST['blood_pressure']))) {
        $field_errors['blood_pressure'] = 'Please enter Blood Pressure';
    } elseif (!preg_match('/^\d{2,3}\/\d{2,3}$/', $_POST['blood_pressure'])) {
        $field_errors['blood_pressure'] = 'Use format like 120/80';
    }

    // Heart Rate validation
    if (empty(trim($_POST['heart_rate']))) {
        $field_errors['heart_rate'] = 'Please enter Heart Rate';
    } elseif (!filter_var($_POST['heart_rate'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 30, "max_range" => 250]])) {
        $field_errors['heart_rate'] = 'Heart rate should be 30-250 bpm';
    }

    // Cholesterol validation
    if (empty(trim($_POST['cholesterol']))) {
        $field_errors['cholesterol'] = 'Please enter Cholesterol level';
    } elseif (!filter_var($_POST['cholesterol'], FILTER_VALIDATE_FLOAT, ["options" => ["min_range" => 1, "max_range" => 500]])) {
        $field_errors['cholesterol'] = 'Cholesterol range: 1-500';
    }

    // Insulin Intake validation
    if (empty(trim($_POST['insulin_intake']))) {
        $field_errors['insulin_intake'] = 'Please enter Insulin Intake';
    } elseif (!filter_var($_POST['insulin_intake'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 0, "max_range" => 100]])) {
        $field_errors['insulin_intake'] = 'Insulin units: 0-100';
    }

    // Food Intake validation
    if (empty(trim($_POST['food_intake']))) {
        $field_errors['food_intake'] = 'Please describe food intake';
    } elseif (!preg_match('/^[a-zA-Z0-9\s,.-]+$/', $_POST['food_intake'])) {
        $field_errors['food_intake'] = 'Remove special characters (@, #, $, etc.)';
    }

    // Activity Level validation
    if (empty(trim($_POST['activity_level']))) {
        $field_errors['activity_level'] = 'Please describe activity';
    } elseif (!preg_match('/^[a-zA-Z0-9\s,.-]+$/', $_POST['activity_level'])) {
        $field_errors['activity_level'] = 'Remove special characters (@, #, $, etc.)';
    }

    // Weight validation
    if (empty(trim($_POST['weight']))) {
        $field_errors['weight'] = 'Please enter Weight';
    } elseif (!filter_var($_POST['weight'], FILTER_VALIDATE_FLOAT, ["options" => ["min_range" => 1, "max_range" => 500]])) {
        $field_errors['weight'] = 'Weight should be 1-500 kg';
    }

    // HbA1c validation
    if (empty(trim($_POST['hb1ac']))) {
        $field_errors['hb1ac'] = 'Please enter HbA1c value';
    } elseif (!filter_var($_POST['hb1ac'], FILTER_VALIDATE_FLOAT, ["options" => ["min_range" => 1, "max_range" => 25]])) {
        $field_errors['hb1ac'] = 'HbA1c range: 1-25';
    }

    // Store field errors in session and redirect if there are errors
    if (!empty($field_errors)) {
        $_SESSION['field_errors'] = $field_errors;
        redirect('doc_importdata.php');
        exit;
    }

    // If we get here, all validation passed
    if (!$conn) {
        set_flash_message('Database connection was lost. Please try again.', 'danger');
        redirect('doc_importdata.php');
        exit;
    }

    $patient_id = (int)$_POST['patient_id'];
    
    // FIXED: Convert Malaysia time to UTC for database storage
    $timestamp_input = $_POST['timestamp'];
    $malaysia_tz = new DateTimeZone('Asia/Kuala_Lumpur');
    $utc_tz = new DateTimeZone('UTC');
    
    $date_obj = DateTime::createFromFormat('d/m/Y G:i', $timestamp_input, $malaysia_tz);
    $date_obj->setTimezone($utc_tz);
    $timestamp_for_db = $date_obj->format('d/m/Y G:i');
    
    $sql = "INSERT INTO health_data (patient_id, timestamp, cgm_level, blood_pressure, heart_rate, cholesterol, insulin_intake, food_intake, activity_level, weight, hb1ac) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt_insert = $conn->prepare($sql);
    $stmt_insert->bind_param("isdsdssssds", 
        $patient_id, $timestamp_for_db, $_POST['cgm_level'], $_POST['blood_pressure'], $_POST['heart_rate'],
        $_POST['cholesterol'], $_POST['insulin_intake'], $_POST['food_intake'],
        $_POST['activity_level'], $_POST['weight'], $_POST['hb1ac']
    );
    
    if ($stmt_insert->execute()) {
        unset($_SESSION['old_input']); // Clear stored data on success
        set_flash_message('Health data successfully imported! You can now run the analysis.', 'success');
    } else {
        set_flash_message('Failed to import health data. Error: ' . $stmt_insert->error, 'danger');
    }
    $stmt_insert->close();
    
    redirect('doc_importdata.php');
    exit;
}

if ($conn) $conn->close();

// Get current time in Malaysia timezone for the default value
$timezone = new DateTimeZone('Asia/Kuala_Lumpur');
$current_date = new DateTime('now', $timezone);
$current_timestamp = $current_date->format('d/m/Y G:i');

$pageTitle = "Enter Health Data";
include 'templates/header_doctor.php'; 
include 'templates/sidebar_doctor.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<style>
.field-error {
    color: #dc3545;
    font-size: 0.875rem;
    margin-top: 0.25rem;
    display: block;
}

.is-invalid {
    border-color: #dc3545;
    padding-right: calc(1.5em + 0.75rem);
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 3.6.4.4.4-.4'/%3e%3cpath d='M6 7v1'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(0.375em + 0.1875rem) center;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

.is-invalid:focus {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

.timestamp-help {
    font-size: 0.875rem;
    color: #6c757d;
    margin-top: 0.25rem;
}

.input-group .btn {
    border-left: 0;
}

.input-group .form-control:focus {
    z-index: 3;
}

.quick-time-buttons {
    display: flex;
    gap: 8px;
    margin-top: 8px;
    flex-wrap: wrap;
}

.quick-time-btn {
    font-size: 0.75rem;
    padding: 4px 8px;
}

.field-error { color: #dc3545; font-size: 0.875rem; margin-top: 0.25rem; display: block; }
.is-invalid {
    border-color: #dc3545; padding-right: calc(1.5em + 0.75rem);
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 3.6.4.4.4-.4'/%3e%3cpath d='M6 7v1'/%3e%3c/svg%3e");
    background-repeat: no-repeat; background-position: right calc(0.375em + 0.1875rem) center;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}
.is-invalid:focus { border-color: #dc3545; box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25); }
.timestamp-help { font-size: 0.875rem; color: #6c757d; margin-top: 0.25rem; }
.input-group .btn { border-left: 0; }
.input-group .form-control:focus { z-index: 3; }
.quick-time-buttons { display: flex; gap: 8px; margin-top: 8px; flex-wrap: wrap; }
.quick-time-btn { font-size: 0.75rem; padding: 4px 8px; }
/* Fix for Flatpickr calendar positioning if needed */
.flatpickr-calendar { z-index: 9999 !important; } 
</style>

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
<div class="main-content">
    <h1 class="page-title mb-4">Enter Patient Data & Run Algorithms</h1>
    <?php display_flash_messages(); ?>

    <div class="alert alert-info d-flex align-items-center mb-4" role="alert">
        <i class="fas fa-info-circle fa-2x me-3"></i>
        <div>
            <h5 class="alert-heading">Workflow Instructions</h5>
            <ol class="mb-0">
                <li><strong>Enter Data:</strong> Fill in all required health metrics below and click "Enter Data".</li>
                <li><strong>Run Analysis:</strong> After a success message, click "Run Algorithm" to generate insights.</li>
            </ol>
        </div>
    </div>

    <!-- Card 1: Manual Data Enter -->
    <div class="card content-card mb-4">
        <h3 class="card-title">Step 1: Enter Health Data</h3>
        <form action="doc_importdata.php" method="POST" class="mt-4 needs-validation" novalidate>
            <div class="row g-4">
                <div class="col-md-6">
                    <label for="patient_id" class="form-label">Select Patient <span class="text-danger">*</span></label>
                    <select id="patient_id" name="patient_id" class="form-select <?php echo isset($field_errors['patient_id']) ? 'is-invalid' : ''; ?>" required>
                        <option value="" disabled <?php echo empty($old_input['patient_id']) ? 'selected' : ''; ?>>-- Choose a patient --</option>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?php echo htmlspecialchars($p['patient_id']); ?>" <?php echo (isset($old_input['patient_id']) && $old_input['patient_id'] == $p['patient_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($p['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($field_errors['patient_id'])): ?>
                        <div class="field-error"><?php echo htmlspecialchars($field_errors['patient_id']); ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-6">
                    <label for="timestamp" class="form-label">Timestamp <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="text" class="form-control <?php echo isset($field_errors['timestamp']) ? 'is-invalid' : ''; ?>" 
                               id="timestamp" name="timestamp" 
                               placeholder="DD/MM/YYYY HH:MM" 
                               value="<?php echo htmlspecialchars($old_input['timestamp'] ?? $current_timestamp); ?>" 
                               required>
                        <button class="btn btn-outline-secondary" type="button" id="calendarBtn">
                            <i class="fas fa-calendar-alt"></i>
                        </button>
                    </div>
                    <div class="timestamp-help">
                        Format: DD/MM/YYYY HH:MM (e.g., <?php echo $current_timestamp; ?>)
                    </div>
                    <div class="quick-time-buttons">
                        <button type="button" class="btn btn-sm btn-outline-primary quick-time-btn" onclick="setTimestamp(0)">Now</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary quick-time-btn" onclick="setTimestamp(1)">1 hour ago</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary quick-time-btn" onclick="setTimestamp(2)">2 hours ago</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary quick-time-btn" onclick="setTimestamp(24)">Yesterday</button>
                    </div>
                    <?php if (isset($field_errors['timestamp'])): ?>
                        <div class="field-error"><?php echo htmlspecialchars($field_errors['timestamp']); ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-6">
                    <label for="cgm_level" class="form-label">CGM Level (mmol/L) <span class="text-danger">*</span></label>
                    <input type="number" step="0.1" min="1" max="30" class="form-control <?php echo isset($field_errors['cgm_level']) ? 'is-invalid' : ''; ?>" id="cgm_level" name="cgm_level" placeholder="e.g., 7.8" value="<?php echo htmlspecialchars($old_input['cgm_level'] ?? ''); ?>" required>
                    <div class="form-text">Must be between 1 and 30 mmol/L</div>
                    <?php if (isset($field_errors['cgm_level'])): ?>
                        <div class="field-error"><?php echo htmlspecialchars($field_errors['cgm_level']); ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label for="blood_pressure" class="form-label">Blood Pressure (mmHg)<span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?php echo isset($field_errors['blood_pressure']) ? 'is-invalid' : ''; ?>" id="blood_pressure" name="blood_pressure" placeholder="e.g., 120/80" value="<?php echo htmlspecialchars($old_input['blood_pressure'] ?? ''); ?>" required>
                    <div class="form-text">Format: systolic/diastolic (e.g., 120/80)</div>
                    <?php if (isset($field_errors['blood_pressure'])): ?>
                        <div class="field-error"><?php echo htmlspecialchars($field_errors['blood_pressure']); ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label for="heart_rate" class="form-label">Heart Rate (bpm) <span class="text-danger">*</span></label>
                    <input type="number" min="30" max="250" class="form-control <?php echo isset($field_errors['heart_rate']) ? 'is-invalid' : ''; ?>" id="heart_rate" name="heart_rate" placeholder="e.g., 72" value="<?php echo htmlspecialchars($old_input['heart_rate'] ?? ''); ?>" required>
                    <div class="form-text">Must be between 30 and 250 bpm</div>
                    <?php if (isset($field_errors['heart_rate'])): ?>
                        <div class="field-error"><?php echo htmlspecialchars($field_errors['heart_rate']); ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label for="cholesterol" class="form-label">Cholesterol (mg/dL) <span class="text-danger">*</span></label>
                    <input type="number" step="0.1" min="50" max="500" class="form-control <?php echo isset($field_errors['cholesterol']) ? 'is-invalid' : ''; ?>" id="cholesterol" name="cholesterol" placeholder="e.g., 190.5" value="<?php echo htmlspecialchars($old_input['cholesterol'] ?? ''); ?>" required>
                    <div class="form-text">Must be between 1 and 500</div>
                    <?php if (isset($field_errors['cholesterol'])): ?>
                        <div class="field-error"><?php echo htmlspecialchars($field_errors['cholesterol']); ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label for="insulin_intake" class="form-label">Insulin Intake (units) <span class="text-danger">*</span></label>
                    <input type="number" min="0" max="100" class="form-control <?php echo isset($field_errors['insulin_intake']) ? 'is-invalid' : ''; ?>" id="insulin_intake" name="insulin_intake" placeholder="e.g., 2" value="<?php echo htmlspecialchars($old_input['insulin_intake'] ?? ''); ?>" required>
                    <div class="form-text">Must be between 0 and 100 units</div>
                    <?php if (isset($field_errors['insulin_intake'])): ?>
                        <div class="field-error"><?php echo htmlspecialchars($field_errors['insulin_intake']); ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-12">
                    <label for="food_intake" class="form-label">Food Intake <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?php echo isset($field_errors['food_intake']) ? 'is-invalid' : ''; ?>" id="food_intake" name="food_intake" placeholder="e.g., Grilled chicken salad" value="<?php echo htmlspecialchars($old_input['food_intake'] ?? ''); ?>" required>
                    <div class="form-text">Only letters, numbers, spaces, and basic punctuation allowed</div>
                    <?php if (isset($field_errors['food_intake'])): ?>
                        <div class="field-error"><?php echo htmlspecialchars($field_errors['food_intake']); ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-12">
                    <label for="activity_level" class="form-label">Activity Level <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?php echo isset($field_errors['activity_level']) ? 'is-invalid' : ''; ?>" id="activity_level" name="activity_level" placeholder="e.g., Walking 30 minutes" value="<?php echo htmlspecialchars($old_input['activity_level'] ?? ''); ?>" required>
                    <div class="form-text">Only letters, numbers, spaces, and basic punctuation allowed</div>
                    <?php if (isset($field_errors['activity_level'])): ?>
                        <div class="field-error"><?php echo htmlspecialchars($field_errors['activity_level']); ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label for="weight" class="form-label">Weight (kg) <span class="text-danger">*</span></label>
                    <input type="number" step="0.1" min="1" max="500" class="form-control <?php echo isset($field_errors['weight']) ? 'is-invalid' : ''; ?>" id="weight" name="weight" placeholder="e.g., 75.5" value="<?php echo htmlspecialchars($old_input['weight'] ?? ''); ?>" required>
                    <div class="form-text">Must be between 1 and 500 kg</div>
                    <?php if (isset($field_errors['weight'])): ?>
                        <div class="field-error"><?php echo htmlspecialchars($field_errors['weight']); ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label for="hb1ac" class="form-label">HbA1c (%)<span class="text-danger">*</span></label>
                    <input type="number" step="0.1" min="1" max="25" class="form-control <?php echo isset($field_errors['hb1ac']) ? 'is-invalid' : ''; ?>" id="hb1ac" name="hb1ac" placeholder="e.g., 7.0" value="<?php echo htmlspecialchars($old_input['hb1ac'] ?? ''); ?>" required>
                    <div class="form-text">Must be between 1 and 25</div>
                    <?php if (isset($field_errors['hb1ac'])): ?>
                        <div class="field-error"><?php echo htmlspecialchars($field_errors['hb1ac']); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="d-flex justify-content-end mt-4">
                <button type="submit" name="submit_health_data" class="btn btn-primary" data-bs-toggle="tooltip" title="Save this data to the patient's record.">Enter Data</button>
            </div>
        </form>
    </div>

    <!-- Card 2: Trigger Sync & Algorithms -->
    <div class="card content-card" data-aos="fade-up" data-aos-delay="100">
        <h3 class="card-title">Step 2: Run Analysis Algorithm</h3>
        <p class="text-muted">After entering, click here to run analysis algorithms and generate insights.</p>
        <div class="d-flex justify-content-end mt-4">
            <button id="syncButton" class="btn btn-success" data-bs-toggle="tooltip" title="Runs analysis algorithms on the latest data.">
                <span id="syncSpinner" class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display: none;"></span>
                Run Algorithm
            </button>
        </div>
        <div id="syncResult" class="mt-3"></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Sync button functionality
    const syncButton = document.getElementById('syncButton');
    if (syncButton) {
        syncButton.addEventListener('click', function() {
            const button = this;
            const spinner = document.getElementById('syncSpinner');
            const resultDiv = document.getElementById('syncResult');
            button.disabled = true;
            spinner.style.display = 'inline-block';
            resultDiv.innerHTML = '<div class="alert alert-info">Running algorithm... Please wait. This may take up about 1 minute.</div>';
            
            fetch('api/sync.php')
                .then(response => response.json().then(data => ({ ok: response.ok, data })))
                .then(({ ok, data }) => {
                    if (!ok) throw new Error(data.error || 'Unknown server error');
                    resultDiv.innerHTML = `
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>${data.message}
                        </div>
                        <div class="d-flex justify-content-end mt-2 fade-in-up">
                            <a href="doc_recommendation.php" class="btn btn-primary shadow-sm">
                                View Analysis & Recommendations <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                    `;
                })
                .catch(error => {
                    resultDiv.innerHTML = `<div class="alert alert-danger"><strong>Error:</strong> ${error.message}</div>`;
                })
                .finally(() => {
                    button.disabled = false;
                    spinner.style.display = 'none';
                });
        });
    }

    // Calendar functionality with Flatpickr
    const timestampInput = document.getElementById('timestamp');
    const calendarBtn = document.getElementById('calendarBtn');
    
    if (timestampInput && typeof flatpickr !== 'undefined') {
        // Initialize Flatpickr on the input field
        const fp = flatpickr(timestampInput, {
            enableTime: true,
            dateFormat: "d/m/Y H:i", // Matches your PHP format
            time_24hr: true,
            defaultDate: new Date(), // Set to now by default
            allowInput: true, // Allow typing if user prefers
            minuteIncrement: 1,
            maxDate: new Date(), // Restricts selection to current date/time and earlier
            // Set the timezone to match Malaysia time display
            locale: {
                firstDayOfWeek: 1 // Monday
            }
        });

        // Open the calendar when the button is clicked
        if (calendarBtn) {
            calendarBtn.addEventListener('click', () => {
                fp.set('maxDate', new Date()); 
                fp.open();
            });
        }
    }
});

// Quick timestamp buttons for common timeframes
function setTimestamp(hoursAgo = 0) {
    const now = new Date();
    now.setHours(now.getHours() - hoursAgo);
    
    const day = String(now.getDate()).padStart(2, '0');
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const year = now.getFullYear();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    
    const timestampInput = document.getElementById('timestamp');
    const formattedTime = `${day}/${month}/${year} ${hours}:${minutes}`;
    
    if (timestampInput) {
        // Update the input value
        timestampInput.value = formattedTime;
        // Update Flatpickr if it exists attached to the element
        if (timestampInput._flatpickr) {
            timestampInput._flatpickr.setDate(formattedTime);
        }
    }
}
</script>

<style>
    /* Add a small animation for the new button appearing */
    .fade-in-up {
        animation: fadeInUp 0.5s ease-out;
    }
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<?php include 'templates/footer_doctor_scripts.php'; ?>
