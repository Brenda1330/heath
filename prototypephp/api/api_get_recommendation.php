<?php
// File: api/api_get_recommendation.php (CORRECTED)

require_once __DIR__ . '/../functions.php'; 
require_once __DIR__ . '/../gemini_functions.php';

secure_session_start();
header('Content-Type: application/json');

// --- Security & Input Validation ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    http_response_code(403); 
    echo json_encode(['error' => 'Unauthorized']); 
    exit();
}

$patient_id = filter_input(INPUT_GET, 'patient_id', FILTER_VALIDATE_INT);
// --- MODIFICATION: Accept 'all' as a valid string for data_id ---
$data_id_str = $_GET['data_id'] ?? '';
$algorithm = $_GET['algorithm'] ?? '';
$allowed_algorithms = ['PPR', 'Node2Vec', 'GAT'];

// --- MODIFICATION: Updated validation logic ---
$is_data_id_valid = ($data_id_str === 'all' || filter_var($data_id_str, FILTER_VALIDATE_INT));

if (!$patient_id || !$is_data_id_valid || !in_array($algorithm, $allowed_algorithms)) {
    http_response_code(400); 
    echo json_encode(['error' => 'Invalid parameters provided. Patient, Timestamp, and Algorithm are all required.']); 
    exit();
}

// --- Pass the string 'all' or the integer ID to the function ---
$recommendation = generate_recommendations_for_doctor($patient_id, $data_id_str, $algorithm);

// --- Process the Output (Unchanged) ---
if (str_starts_with($recommendation, 'ERROR:')) {
    http_response_code(500);
    echo json_encode(['error' => $recommendation]);
} else {
    echo json_encode(['recommendations' => [$recommendation]]);
}