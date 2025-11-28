<?php
// File: api/api_get_spike_explanation.php (REWRITTEN FOR LOGICAL INFERENCE)

require_once __DIR__ . '/../functions.php';

secure_session_start();
header('Content-Type: application/json');

// --- Security & Authorization (Unchanged) ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    http_response_code(403); 
    echo json_encode(['error' => 'Unauthorized']); 
    exit();
}

$data_id = filter_input(INPUT_GET, 'data_id', FILTER_VALIDATE_INT);
$doctor_id = $_SESSION['user_id'];

if (!$data_id) {
    http_response_code(400); 
    echo json_encode(['error' => 'Invalid data ID provided.']);
    exit();
}

$conn = get_db_connection();
if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed.']);
    exit();
}

try {
    // --- Step 1: Get the single data record for the spike and verify ownership ---
    $stmt_spike = $conn->prepare(
        "SELECT hd.patient_id, hd.timestamp, hd.cgm_level, hd.food_intake, hd.activity_level 
         FROM health_data hd
         JOIN patients p ON hd.patient_id = p.patient_id
         WHERE hd.data_id = ? AND p.doctor_id = ?"
    );
    $stmt_spike->bind_param("ii", $data_id, $doctor_id);
    $stmt_spike->execute();
    $spike_data = $stmt_spike->get_result()->fetch_assoc();
    $stmt_spike->close();

    if (!$spike_data) {
        throw new Exception("Spike event not found or you do not have permission to view it.");
    }

    $spike_timestamp_obj = DateTime::createFromFormat('d/m/Y G:i', $spike_data['timestamp']);
    if (!$spike_timestamp_obj) {
        throw new Exception("Failed to parse the spike timestamp: " . $spike_data['timestamp']);
    }

    // --- Step 2: Extract data for logical analysis ---
    $food = trim($spike_data['food_intake'] ?? '');
    $activity = trim($spike_data['activity_level'] ?? '');
    $cgm = $spike_data['cgm_level'];
    $time = $spike_timestamp_obj->format('h:i A');

    $has_food = !empty($food) && strtolower($food) !== 'no food';
    $has_activity = !empty($activity) && strtolower($activity) !== 'no activity';
    $insight = '';

    // --- Step 3: Generate the insight based on the combination of data in the single row ---
    if ($has_food && !$has_activity) {
        $insight = "The spike to <strong>{$cgm} mmol/L</strong> at <strong>{$time}</strong> may be linked to the meal <em>'{$food}'</em> being consumed without any subsequent physical activity recorded at the same time.";
    } elseif ($has_food && $has_activity) {
        $insight = "The spike to <strong>{$cgm} mmol/L</strong> at <strong>{$time}</strong> occurred despite the recorded activity of <em>'{$activity}'</em>. This suggests the glycemic impact of the meal <em>'{$food}'</em> was significant.";
    } elseif (!$has_food && $has_activity) {
        $insight = "A spike to <strong>{$cgm} mmol/L</strong> was recorded at <strong>{$time}</strong> during the activity <em>'{$activity}'</em>. This could indicate exercise-induced hyperglycemia or a delayed meal reaction. Further monitoring is advised.";
    } elseif (!$has_food && !$has_activity) {
        $insight = "This spike to <strong>{$cgm} mmol/L</strong> at <strong>{$time}</strong> appears to be a fasting glucose event, as no meal or specific activity was logged in this record.";
    }
    
    // --- Step 4: Assemble the final response ---
    $explanation = [
        'spike_value' => $cgm . ' mmol/L',
        'time' => $time,
        'food' => $has_food ? $food : 'None Recorded',
        'activity' => $has_activity ? $activity : 'None Recorded',
        'insight' => $insight
    ];
    
    echo json_encode(['success' => true, 'explanation' => $explanation]);

} catch (Throwable $e) {
    http_response_code(500);
    error_log("Error in api_get_spike_explanation: " . $e->getMessage());
    echo json_encode(['error' => 'A server error occurred: ' . $e->getMessage()]);
} finally {
    if ($conn) {
        $conn->close();
    }
}
?>