<?php
// File: api/api_get_similar_patients.php (CORRECTED)

require_once __DIR__ . '/../functions.php';

secure_session_start();
header('Content-Type: application/json');

// --- Security & Input Validation ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    http_response_code(403); 
    echo json_encode(['error' => 'Unauthorized']); 
    exit();
}

$patient_id = filter_input(INPUT_GET, 'patient_id', FILTER_VALIDATE_INT);
$data_id_str = $_GET['data_id'] ?? '';
$is_data_id_valid = ($data_id_str === 'all' || filter_var($data_id_str, FILTER_VALIDATE_INT));

if (!$patient_id || !$is_data_id_valid) {
    http_response_code(400); 
    echo json_encode(['error' => 'Invalid patient or data ID provided.']); 
    exit();
}

$conn = get_db_connection();
if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed.']);
    exit();
}

$similar_patients_data = [];

try {
    if ($data_id_str === 'all') {
        // --- Logic for "View All" (based on AVERAGE CGM) ---
        $stmt_avg = $conn->prepare("SELECT AVG(cgm_level) as average_cgm FROM health_data WHERE patient_id = ?");
        $stmt_avg->bind_param("i", $patient_id);
        $stmt_avg->execute();
        $summary = $stmt_avg->get_result()->fetch_assoc();
        $stmt_avg->close();
        
        if (!$summary || $summary['average_cgm'] === null) {
            throw new Exception("No average CGM data found to find similarities.");
        }

        $average_cgm = $summary['average_cgm'];
        $avg_low = (float)$average_cgm - 0.5;
        $avg_high = (float)$average_cgm + 0.5;

        // SQL FIX: Added "hd.patient_id != ?" to the WHERE clause
        $stmt_similar = $conn->prepare(
            "SELECT p.full_name, p.age, ROUND(AVG(hd.cgm_level), 1) as relevant_cgm, 
                   ANY_VALUE(SUBSTRING_INDEX(GROUP_CONCAT(hd.food_intake ORDER BY hd.data_id DESC), ',', 1)) as food_intake,
                   ANY_VALUE(SUBSTRING_INDEX(GROUP_CONCAT(hd.activity_level ORDER BY hd.data_id DESC), ',', 1)) as activity_level
             FROM health_data hd JOIN patients p ON hd.patient_id = p.patient_id
             WHERE hd.patient_id != ?
             GROUP BY p.patient_id, p.full_name, p.age
             HAVING AVG(hd.cgm_level) BETWEEN ? AND ?"
        );
        $stmt_similar->bind_param("idd", $patient_id, $avg_low, $avg_high);
        $stmt_similar->execute();
        $similar_patients_data = $stmt_similar->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_similar->close();

    } else {
        // --- Logic for a SINGLE TIMESTAMP ---
        $data_id = (int)$data_id_str;
        $stmt_health = $conn->prepare("SELECT cgm_level FROM health_data WHERE data_id = ? AND patient_id = ?");
        $stmt_health->bind_param("ii", $data_id, $patient_id);
        $stmt_health->execute();
        $health = $stmt_health->get_result()->fetch_assoc();
        $stmt_health->close();

        if (!$health) {
            throw new Exception("Health data for the selected timestamp not found.");
        }

        $cgm_value = $health['cgm_level'];
        $cgm_low = (float)$cgm_value - 1.0;
        $cgm_high = (float)$cgm_value + 1.0;

        // SQL FIX: Added "hd.patient_id != ?" to the WHERE clause
        $stmt_similar = $conn->prepare(
            "SELECT p.full_name, p.age, ANY_VALUE(hd.cgm_level) as relevant_cgm, ANY_VALUE(hd.food_intake) as food_intake, ANY_VALUE(hd.activity_level) as activity_level
                FROM health_data hd JOIN patients p ON hd.patient_id = p.patient_id
                WHERE hd.cgm_level BETWEEN ? AND ? AND hd.patient_id != ?
                GROUP BY p.patient_id"
        );
        $stmt_similar->bind_param("ddi", $cgm_low, $cgm_high, $patient_id);
        $stmt_similar->execute();
        $similar_patients_data = $stmt_similar->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_similar->close();
    }
} catch (Throwable $e) {
    http_response_code(500);
    error_log("Error in api_get_similar_patients: " . $e->getMessage());
    echo json_encode(['error' => 'A server error occurred while finding similar patients.']);
    $conn->close();
    exit();
}

$conn->close();
echo json_encode(['similar_patients' => $similar_patients_data]);

?>