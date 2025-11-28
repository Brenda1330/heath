<?php
// File: api/api_delete_recommendation.php (CORRECTED & MORE ROBUST)

require_once __DIR__ . '/../functions.php';

secure_session_start();
header('Content-Type: application/json');

// --- 1. Security & Authorization (Unchanged) ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor' || !isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    http_response_code(403); 
    echo json_encode(['error' => 'Unauthorized or invalid request.']); 
    exit();
}

// --- 2. Input Validation (Unchanged) ---
$rec_type = $_POST['rec_type'] ?? '';
$rec_id = filter_input(INPUT_POST, 'rec_id', FILTER_VALIDATE_INT);
$doctor_id = $_SESSION['user_id'];

if (!$rec_id || !in_array($rec_type, ['single', 'summary'])) {
    http_response_code(400); 
    echo json_encode(['error' => 'Invalid recommendation ID or type provided.']);
    exit();
}

// --- 3. Database Operation (COMPLETELY REWRITTEN FOR ROBUSTNESS) ---
$conn = get_db_connection();
if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed.']);
    exit();
}

try {
    $conn->begin_transaction(); // Start a transaction

    // Determine table and column names
    if ($rec_type === 'single') {
        $table_name = 'recommendations';
        $id_column = 'recommendation_id';
    } else { // 'summary'
        $table_name = 'patient_summaries';
        $id_column = 'summary_id';
    }

    // STEP 1: Get the patient_id from the recommendation we want to delete.
    $stmt_get_patient = $conn->prepare("SELECT patient_id FROM {$table_name} WHERE {$id_column} = ?");
    if (!$stmt_get_patient) throw new Exception("Failed to prepare statement to find patient.");
    $stmt_get_patient->bind_param("i", $rec_id);
    $stmt_get_patient->execute();
    $result = $stmt_get_patient->get_result();
    $rec_data = $result->fetch_assoc();
    $stmt_get_patient->close();

    if (!$rec_data) {
        http_response_code(404);
        throw new Exception("Recommendation not found.");
    }
    $patient_id_from_rec = $rec_data['patient_id'];

    // STEP 2: Verify that this patient belongs to the logged-in doctor. (CRUCIAL SECURITY CHECK)
    $stmt_verify = $conn->prepare("SELECT patient_id FROM patients WHERE patient_id = ? AND doctor_id = ?");
    if (!$stmt_verify) throw new Exception("Failed to prepare statement for ownership verification.");
    $stmt_verify->bind_param("ii", $patient_id_from_rec, $doctor_id);
    $stmt_verify->execute();
    $stmt_verify->store_result();
    $is_owner = $stmt_verify->num_rows > 0;
    $stmt_verify->close();

    if (!$is_owner) {
        http_response_code(403); // Forbidden
        throw new Exception("You do not have permission to delete this recommendation.");
    }

    // STEP 3: If verification passes, perform the simple, safe delete.
    $stmt_delete = $conn->prepare("DELETE FROM {$table_name} WHERE {$id_column} = ?");
    if (!$stmt_delete) throw new Exception("Failed to prepare delete statement.");
    $stmt_delete->bind_param("i", $rec_id);
    $stmt_delete->execute();
    
    $affected_rows = $stmt_delete->affected_rows;
    $stmt_delete->close();
    
    $conn->commit(); // Commit the transaction

    if ($affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Recommendation deleted successfully.']);
    } else {
        // This case should ideally not be reached if the previous checks passed, but it's a good safeguard.
        http_response_code(404);
        echo json_encode(['error' => 'Recommendation could not be deleted, it may have already been removed.']);
    }
    
} catch (Throwable $e) {
    $conn->rollback(); // Roll back any changes if an error occurred
    http_response_code(500);
    error_log("Error in api_delete_recommendation: " . $e->getMessage());
    // Send back the specific error message from the exception for easier debugging
    echo json_encode(['error' => 'A server error occurred during deletion: ' . $e->getMessage()]);
} finally {
    if ($conn) {
        $conn->close();
    }
}
?>