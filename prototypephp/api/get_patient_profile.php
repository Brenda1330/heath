<?php
// File: api/api_get_patient_profile.php

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

// --- CORRECTED SECTION START ---
// Updated the SELECT statement to fetch 'age' instead of 'dob'.
$stmt = $conn->prepare("SELECT full_name, gender, status, age FROM patients WHERE patient_id = ? AND doctor_id = ?");
// --- CORRECTED SECTION END ---

$stmt->bind_param("ii", $patient_id, $_SESSION['user_id']);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$patient) {
    echo json_encode(['error' => 'Patient not found or not assigned to you.']);
    exit();
}

// Sanitize output for security
foreach ($patient as $key => $value) {
    $patient[$key] = htmlspecialchars($value ?? '');
}

echo json_encode($patient);