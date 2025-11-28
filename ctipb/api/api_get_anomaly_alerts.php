<?php
// File: api/api_get_anomaly_alerts.php (ROBUST VERSION)

require_once __DIR__ . '/../functions.php';
secure_session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$conn = get_db_connection();
if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed.']);
    exit();
}

$doctor_id = $_SESSION['user_id'];
$anomaly_alerts = [];

try {
    // Get patients
    $patients_stmt = $conn->prepare("SELECT patient_id, full_name FROM patients WHERE doctor_id = ?");
    $patients_stmt->bind_param("i", $doctor_id);
    $patients_stmt->execute();
    $patients = $patients_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $patients_stmt->close();

    // --- CHECK 1: Activity Decline ---
    // Logic: Look for NON-sedentary entries.
    // "Activity" is anything that DOES NOT match "No activity", "None", "Sedentary"
    $activity_negative_keywords = "'%no activity%', '%none%', '%sedentary%', '%resting%'";
    
    $sql_activity = "SELECT COUNT(*) FROM health_data 
                     WHERE patient_id = ? 
                     AND LOWER(activity_level) NOT LIKE '%no activity%'
                     AND LOWER(activity_level) NOT LIKE '%none%'
                     AND LOWER(activity_level) NOT LIKE '%sedentary%'
                     AND LOWER(activity_level) NOT LIKE '%resting%'
                     AND STR_TO_DATE(timestamp, '%d/%m/%Y %H:%i') >= NOW() - INTERVAL ? DAY";

    $stmt_act = $conn->prepare($sql_activity);

    foreach ($patients as $patient) {
        $pid = $patient['patient_id'];
        
        // Count last 30 days
        $days30 = 30;
        $stmt_act->bind_param("ii", $pid, $days30);
        $stmt_act->execute();
        $count_30 = $stmt_act->get_result()->fetch_row()[0];

        // Count last 7 days
        $days7 = 7;
        $stmt_act->bind_param("ii", $pid, $days7);
        $stmt_act->execute();
        $count_7 = $stmt_act->get_result()->fetch_row()[0];

        // Only run logic if there is enough historical data (at least 5 entries in 30 days)
        if ($count_30 > 5) {
            $avg_weekly_baseline = ($count_30 / 30) * 7;
            
            // Trigger if current week is < 50% of baseline
            if ($count_7 < ($avg_weekly_baseline * 0.5)) {
                $drop = 100;
                if($avg_weekly_baseline > 0) {
                    $drop = 100 - round(($count_7 / $avg_weekly_baseline) * 100);
                }
                
                $anomaly_alerts[] = [
                    "patient_id" => $pid,
                    "full_name" => $patient['full_name'],
                    "anomaly_type" => "Activity Decline",
                    "message" => "Physical activity dropped by {$drop}% compared to their monthly average."
                ];
            }
        }
    }
    $stmt_act->close();

    // --- CHECK 2: Negative Pattern (High Carb + No Activity + High Glucose) ---
    // We use SQL REGEXP or multiple LIKEs to catch food variations
    $sql_pattern = "
        SELECT p.patient_id, p.full_name, hd.timestamp, hd.food_intake, hd.cgm_level
        FROM health_data hd 
        JOIN patients p ON hd.patient_id = p.patient_id
        WHERE p.doctor_id = ? 
          AND STR_TO_DATE(hd.timestamp, '%d/%m/%Y %H:%i') >= NOW() - INTERVAL 7 DAY
          AND hd.cgm_level > 10.0
          AND (
              LOWER(hd.activity_level) LIKE '%no activity%' OR 
              LOWER(hd.activity_level) LIKE '%none%' OR 
              LOWER(hd.activity_level) LIKE '%resting%'
          )
          AND (
              LOWER(hd.food_intake) LIKE '%rice%' OR 
              LOWER(hd.food_intake) LIKE '%noodle%' OR 
              LOWER(hd.food_intake) LIKE '%bread%' OR 
              LOWER(hd.food_intake) LIKE '%pizza%' OR 
              LOWER(hd.food_intake) LIKE '%pasta%' OR
              LOWER(hd.food_intake) LIKE '%fries%' OR
              LOWER(hd.food_intake) LIKE '%sugar%' OR
              LOWER(hd.food_intake) LIKE '%cake%' OR
              LOWER(hd.food_intake) LIKE '%burger%'
          )
        ORDER BY STR_TO_DATE(hd.timestamp, '%d/%m/%Y %H:%i') DESC
        LIMIT 10
    ";
    
    $stmt_pattern = $conn->prepare($sql_pattern);
    $stmt_pattern->bind_param("i", $doctor_id);
    $stmt_pattern->execute();
    $negative_patterns = $stmt_pattern->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_pattern->close();

    foreach ($negative_patterns as $pattern) {
        $date_obj = DateTime::createFromFormat('d/m/Y G:i', $pattern['timestamp']);
        $date_str = $date_obj ? $date_obj->format('M j, H:i') : $pattern['timestamp'];
        
        $anomaly_alerts[] = [
            "patient_id" => $pattern['patient_id'],
            "full_name" => $pattern['full_name'],
            "anomaly_type" => "Negative Pattern Detected",
            "message" => "Spike to {$pattern['cgm_level']} mmol/L after eating '{$pattern['food_intake']}' with no activity ({$date_str})."
        ];
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    $conn->close();
    exit();
}

$conn->close();
echo json_encode(['anomaly_alerts' => $anomaly_alerts]);
?>