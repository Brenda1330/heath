<?php
// File: doc_managepatient.php (NEW FILE)

/**
 * CORE INCLUSION & AUTHORIZATION
 */
require_once 'functions.php';
secure_session_start();
add_security_headers();
require_doctor();

$conn = get_db_connection();
$doctor_id = $_SESSION['user_id'];
$patient = null;

// --- 1. GET & VALIDATE PATIENT ID ---
$patient_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$patient_id) {
    set_flash_message('Invalid patient ID provided.', 'danger');
    redirect('doc_patientlist.php');
}

// --- 2. HANDLE FORM SUBMISSIONS (POST REQUESTS) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$conn) {
        set_flash_message('Database connection lost. Please try again.', 'danger');
        redirect('doc_managepatient.php?id=' . $patient_id);
    }

    // --- LOGIC FOR UPDATING STATUS ---
    if (isset($_POST['update_status'])) {
        $new_status = $_POST['status'] ?? '';
        $allowed_statuses = ['critical', 'warning', 'stable', 'recovered'];

        if (in_array($new_status, $allowed_statuses)) {
            $stmt = $conn->prepare("UPDATE patients SET status = ? WHERE patient_id = ? AND doctor_id = ?");
            $stmt->bind_param("sii", $new_status, $patient_id, $doctor_id);
            if ($stmt->execute()) {
                set_flash_message('Patient status updated successfully!', 'success');
            } else {
                set_flash_message('Failed to update patient status.', 'danger');
            }
            $stmt->close();
        } else {
            set_flash_message('Invalid status selected.', 'danger');
        }
        redirect('doc_managepatient.php?id=' . $patient_id);
    }

    // --- LOGIC FOR DELETING PATIENT ---
    if (isset($_POST['delete_patient'])) {
        // For data integrity, we should delete related records first in a transaction.
        $conn->begin_transaction();
        try {
            // Delete from all child tables first
            $conn->execute_query("DELETE FROM patient_summaries WHERE patient_id = ?", [$patient_id]);
            $conn->execute_query("DELETE FROM recommendations WHERE patient_id = ?", [$patient_id]);
            $conn->execute_query("DELETE FROM graph_insights WHERE patient_id = ?", [$patient_id]);
            $conn->execute_query("DELETE FROM health_data WHERE patient_id = ?", [$patient_id]);

            // Finally, delete the patient. The doctor_id check is a crucial security measure.
            $stmt = $conn->prepare("DELETE FROM patients WHERE patient_id = ? AND doctor_id = ?");
            $stmt->bind_param("ii", $patient_id, $doctor_id);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                $conn->commit();
                set_flash_message('Patient and all related data have been successfully deleted.', 'success');
                redirect('doc_patientlist.php');
            } else {
                throw new Exception('Patient not found or you do not have permission to delete.');
            }
        } catch (Exception $e) {
            $conn->rollback();
            set_flash_message('An error occurred during deletion: ' . $e->getMessage(), 'danger');
            redirect('doc_managepatient.php?id=' . $patient_id);
        }
    }
}

// --- 3. FETCH PATIENT DATA FOR DISPLAY (GET REQUEST) ---
if ($conn) {
    $stmt = $conn->prepare("SELECT * FROM patients WHERE patient_id = ? AND doctor_id = ?");
    $stmt->bind_param("ii", $patient_id, $doctor_id);
    $stmt->execute();
    $patient = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
}

// If no patient was found for this doctor, redirect away.
if (!$patient) {
    set_flash_message('Patient not found or you do not have access.', 'danger');
    redirect('doc_patientlist.php');
}

/**
 * PRESENTATION (HTML)
 */
$pageTitle = "Manage Patient";
include 'templates/header_doctor.php'; 
include 'templates/sidebar_doctor.php';
?>

<div class="main-content">
    <?php display_flash_messages(); ?>

    <!-- Page Header -->
<div class="page-header">
    <div>
        <a href="doc_patientlist.php" class="back-link"><i class="fas fa-arrow-left me-2"></i> Back to Patient List</a>
        <h1 class="page-title mb-4">Manage Patient: <strong><?php echo htmlspecialchars($patient['full_name']); ?></strong></h1>
    </div>
</div>

    <!-- Card 1: Update Status -->
    <div class="card content-card mb-4" data-aos="fade-up">
        <h3 class="card-title">Update Patient Status</h3>
        <p class="text-muted">Change the patient's current health status. The current status is <strong><?php echo htmlspecialchars(ucfirst($patient['status'])); ?></strong>.</p>
        <form action="doc_managepatient.php?id=<?php echo $patient_id; ?>" method="POST" class="mt-4">
            <div class="row align-items-end">
                <div class="col-md-8">
                    <label for="status" class="form-label">New Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="stable" <?php echo ($patient['status'] === 'stable') ? 'selected' : ''; ?>>Stable</option>
                        <option value="warning" <?php echo ($patient['status'] === 'warning') ? 'selected' : ''; ?>>Warning</option>
                        <option value="critical" <?php echo ($patient['status'] === 'critical') ? 'selected' : ''; ?>>Critical</option>
                        <option value="recovered" <?php echo ($patient['status'] === 'recovered') ? 'selected' : ''; ?>>Recovered</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" name="update_status" class="btn btn-primary w-100">Update Status</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Card 2: Delete Patient (Danger Zone) -->
    <div class="card content-card border-danger" data-aos="fade-up" data-aos-delay="100">
        <h3 class="card-title text-danger">Danger Zone</h3>
        <p class="text-muted">Permanently delete this patient and all of their associated health data. This action is irreversible.</p>
        <form action="doc_managepatient.php?id=<?php echo $patient_id; ?>" method="POST" onsubmit="return confirm('Are you absolutely sure you want to delete this patient? All associated health data, insights, and recommendations will be permanently lost.');">
            <div class="d-flex justify-content-end mt-4">
                <input type="hidden" name="delete_patient" value="1">
                <button type="submit" class="btn btn-danger">Delete This Patient</button>
            </div>
        </form>
    </div>
</div>

<?php include 'templates/footer_doctor_scripts.php'; ?>