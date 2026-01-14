<?php
// File: api/sync.php (FINAL SIMPLIFIED VERSION)

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../functions.php';
header('Content-Type: application/json');
set_time_limit(300); // 5 minutes

try {
    // Define absolute paths
    $python_path = 'C:/xamp/htdocs/ctipb/venv312/Scripts/python.exe';
    $script_path = __DIR__ . '/../sync_from_mysql.py';

    // Verify files exist
    if (!file_exists($python_path)) {
        throw new Exception("FATAL: Python executable not found at: " . $python_path);
    }
    if (!file_exists($script_path)) {
        throw new Exception("FATAL: Sync script not found at path: " . $script_path);
    }
    
    // --- THIS IS THE SINGLE, CORRECT EXECUTION BLOCK ---
    $command = '"' . $python_path . '" "' . $script_path . '"';
    
    $descriptorspec = [
       0 => ["pipe", "r"],  // stdin
       1 => ["pipe", "w"],  // stdout (for the final JSON)
       2 => ["pipe", "w"]   // stderr (for progress and errors)
    ];

    $process = proc_open($command, $descriptorspec, $pipes);

    $stdout = '';
    $stderr = '';

    if (is_resource($process)) {
        // Read both output streams
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $return_code = proc_close($process);

        // A failure is a non-zero return code OR any output on stderr
        if ($return_code !== 0) {
            throw new Exception(
                "The sync process failed with exit code {$return_code}. " .
                "Error Details (stderr): " . ($stderr ?: "No error output, but script failed.")
            );
        }
    } else {
        throw new Exception("Failed to open the Python process. Check server configuration (e.g., Apache permissions).");
    }

    // If we get here, the script ran without a fatal error.
    // Now, decode the stdout to get the final JSON message.
    $json_response = json_decode($stdout, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Sync script ran, but produced invalid JSON. Raw output (stdout): " . $stdout);
    }

    // If the Python script's own try/except block caught an error, it will be in the JSON
    if (isset($json_response['error'])) {
        throw new Exception($json_response['error']);
    }

    // Success! Relay the JSON from stdout.
    echo json_encode($json_response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}