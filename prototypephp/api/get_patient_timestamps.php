<?php
// File: api_get_patient_timestamps.php
// File: api/api_get_patient_timestamps.php
require_once __DIR__ . '/../functions.php';
secure_session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$patient_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$patient_id) {
    echo json_encode(['error' => 'Invalid patient ID.']);
    exit();
}

$conn = get_db_connection();
if (!$conn) {
    echo json_encode(['error' => 'Database connection failed.']);
    exit();
}

$stmt = $conn->prepare(
    "SELECT hd.data_id, hd.timestamp FROM health_data hd 
     JOIN patients p ON hd.patient_id = p.patient_id 
     WHERE hd.patient_id = ? AND p.doctor_id = ? 
     ORDER BY hd.timestamp DESC"
);

$stmt->bind_param("ii", $patient_id, $_SESSION['user_id']);
$stmt->execute();
$timestamps = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

echo json_encode(['timestamps' => $timestamps]);