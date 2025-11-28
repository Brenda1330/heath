<?php
// File: api/api_get_recommendation.php (FIXED)

// Enable error reporting temporarily
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../functions.php'; 
require_once __DIR__ . '/../gemini_functions.php';

secure_session_start();
header('Content-Type: application/json');

// Close session early to prevent blocking
session_write_close();

// --- Security & Input Validation ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    http_response_code(403); 
    echo json_encode(['error' => 'Unauthorized']); 
    exit();
}

$patient_id = filter_input(INPUT_GET, 'patient_id', FILTER_VALIDATE_INT);
$data_id_str = $_GET['data_id'] ?? '';
$algorithm = $_GET['algorithm'] ?? '';
$allowed_algorithms = ['PPR', 'Node2Vec', 'GAT'];

$is_data_id_valid = ($data_id_str === 'all' || filter_var($data_id_str, FILTER_VALIDATE_INT));

if (!$patient_id || !$is_data_id_valid || !in_array($algorithm, $allowed_algorithms)) {
    http_response_code(400); 
    echo json_encode(['error' => 'Invalid parameters provided. Patient, Timestamp, and Algorithm are all required.']); 
    exit();
}

try {
    $recommendation = generate_recommendations_for_doctor($patient_id, $data_id_str, $algorithm);
    
    if (str_starts_with($recommendation, 'ERROR:')) {
        http_response_code(500);
        echo json_encode(['error' => $recommendation]);
    } else {
        echo json_encode(['recommendations' => [$recommendation]]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>