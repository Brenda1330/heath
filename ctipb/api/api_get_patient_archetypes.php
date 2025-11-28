<?php
// File: api/api_get_patient_archetypes.php (CORRECTED)

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
$at_risk_patients = [];

try {
    $high_carb_foods_list = "'Rice', 'Noodles', 'Bread', 'Pizza', 'Fried Rice', 'Pasta', 'Chicken Rice'";

    // ==================================================================
    // == THE FIX: Modified the SUM() logic for 'activity_count' to be case-insensitive
    // ==================================================================
    $sql = "
        SELECT
            p.patient_id,
            p.full_name,
            COUNT(hd.data_id) as total_records,
            ROUND(AVG(hd.cgm_level), 1) as avg_cgm,
            -- This now correctly ignores any case variation of 'no activity'.
            SUM(CASE 
                WHEN hd.activity_level IS NOT NULL AND LOWER(hd.activity_level) != 'no activity' AND hd.activity_level != '' 
                THEN 1 
                ELSE 0 
            END) as activity_count,
            SUM(CASE WHEN hd.food_intake IN ($high_carb_foods_list) THEN 1 ELSE 0 END) as high_carb_count
        FROM patients p
        LEFT JOIN health_data hd ON p.patient_id = hd.patient_id
        WHERE p.doctor_id = ?
        GROUP BY p.patient_id, p.full_name
    ";
    // ==================================================================

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $patient_stats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // The rest of the PHP logic for assigning archetypes is correct and remains unchanged.
    foreach ($patient_stats as $stats) {
        if ($stats['total_records'] < 3) continue;

        $activity_rate = ($stats['total_records'] > 0) ? ($stats['activity_count'] / $stats['total_records']) : 0;
        $high_carb_rate = ($stats['total_records'] > 0) ? ($stats['high_carb_count'] / $stats['total_records']) : 0;
        $archetype = null;
        $insight = '';

        if ($stats['avg_cgm'] > 10.0 && $activity_rate < 0.3) {
            $archetype = "Sedentary & High-Spike";
            $insight = "Patient has a high average CGM ({$stats['avg_cgm']}) and a low physical activity rate (activity in only " . round($activity_rate * 100) . "% of records). Risk is likely driven by lack of exercise.";
        } elseif ($stats['avg_cgm'] > 9.0 && $high_carb_rate > 0.6) {
            $archetype = "Diet-Driven Spikes";
            $insight = "Patient's average CGM is high ({$stats['avg_cgm']}) and high-carb meals make up over 60% of their diet. Risk is primarily diet-related.";
        }

        if ($archetype) {
            $at_risk_patients[] = [
                'patient_id' => $stats['patient_id'],
                'full_name' => $stats['full_name'],
                'archetype' => $archetype,
                'insight' => $insight
            ];
        }
    }

} catch (Throwable $e) {
    http_response_code(500);
    error_log("Error in api_get_patient_archetypes: " . $e->getMessage());
    echo json_encode(['error' => 'A server error occurred: ' . $e->getMessage()]);
    exit();
}

$conn->close();
echo json_encode(['at_risk_patients' => $at_risk_patients]);
?>