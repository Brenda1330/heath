<?php
// File: api/api_get_neo4j_graph.php (FINAL CORRECTED VERSION)


define('ROOT_PATH', dirname(__DIR__)); 
require_once ROOT_PATH . '/functions.php';

secure_session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    http_response_code(403); echo json_encode(['error' => 'Unauthorized']); exit();
}

$patient_id = filter_input(INPUT_GET, 'patient_id', FILTER_VALIDATE_INT);
$timestamp_text = $_GET['timestamp_text'] ?? null;

if (!$patient_id) {
    http_response_code(400); echo json_encode(['error' => 'Invalid parameter: patient_id is required.']); exit();
}

$python_executable = 'C:/xamp/htdocs/ctipb/venv/Scripts/python.exe'; 
$script_path = ROOT_PATH . '/neo4j_service.py';

$patient_id_safe = escapeshellarg($patient_id);
$command = "{$python_executable} {$script_path} {$patient_id_safe}";

if ($timestamp_text && strtolower($timestamp_text) !== 'all') {
    
    // ============================================================================================
    // === THE FIX: PARSE THE FULL TIMESTAMP STRING, NOT JUST THE DATE PART ===
    //
    // Old code that was removed:
    // $date_part = explode(' ', $timestamp_text)[0];
    // $date = DateTime::createFromFormat('j/n/Y|', $date_part, new DateTimeZone('UTC'));
    //
    // The new code below handles formats like "5/4/2023 8:30" correctly.
    // The format string 'j/n/Y G:i' matches day/month/year hour(no leading zero):minute.
    $date = DateTime::createFromFormat('j/n/Y G:i', $timestamp_text, new DateTimeZone('UTC'));
    // ============================================================================================

    // Now, check if the parsing was successful.
    if ($date) {
        // Format the date into the ISO 8601 string that the Python script expects.
        // This will now include the correct time, e.g., 2023-04-05T08:30:00Z
        $iso_timestamp = $date->format('Y-m-d\TH:i:s\Z');
        $timestamp_safe = escapeshellarg($iso_timestamp);
        $command .= " {$timestamp_safe}";
    } else {
        // Fallback if the timestamp format is unexpected
        error_log("API Error: Failed to parse timestamp_text: " . $timestamp_text);
        http_response_code(400); 
        echo json_encode(['error' => 'Invalid timestamp format provided.']); 
        exit();
    }
}

error_log("DEBUG NEO4J COMMAND: " . $command);


$command .= " 2>&1";
$output = shell_exec($command);

// --- Process the Output (no changes needed below) ---
if ($output === null) {
    http_response_code(500);
    echo json_encode(['error' => 'The Neo4j service script failed to execute. Check server permissions.']);
    exit();
}

$json_response = json_decode($output, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode([
        'error' => 'The Neo4j Python script produced a fatal error.',
        'python_error_output' => $output 
    ]);
    exit();
}

echo json_encode($json_response);