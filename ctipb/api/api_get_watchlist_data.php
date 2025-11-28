<?php
// File: api/api_get_watchlist_data.php (ROBUST VERSION)

require_once __DIR__ . '/../functions.php';
secure_session_start();
header('Content-Type: application/json');

// --- Security ---
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
$response_data = [
    'high_risk_patients' => [],
    'outlier_events' => []
];

try {
    // --- QUERY 1: High-Risk Patients ---
    // Logic: Look for recent high glucose OR high-carb + low activity patterns
    // We use LOWER() for case-insensitive matching
    $sql_high_risk = "
        SELECT 
            p.patient_id, 
            p.full_name, 
            p.status,
            (SELECT food_intake FROM health_data WHERE patient_id = p.patient_id ORDER BY STR_TO_DATE(timestamp, '%d/%m/%Y %H:%i') DESC LIMIT 1) as last_food,
            (SELECT activity_level FROM health_data WHERE patient_id = p.patient_id ORDER BY STR_TO_DATE(timestamp, '%d/%m/%Y %H:%i') DESC LIMIT 1) as last_activity,
            (SELECT ROUND(AVG(cgm_level), 1) FROM health_data WHERE patient_id = p.patient_id AND STR_TO_DATE(timestamp, '%d/%m/%Y %H:%i') >= NOW() - INTERVAL 7 DAY) as avg_glucose_7d,
            
            -- Count entries with significant activity
            (SELECT COUNT(*) FROM health_data 
             WHERE patient_id = p.patient_id 
               AND LOWER(activity_level) NOT LIKE '%no activity%' 
               AND LOWER(activity_level) NOT LIKE '%sedentary%'
               AND LOWER(activity_level) NOT LIKE '%resting%'
               AND LOWER(activity_level) NOT LIKE '%none%'
               AND STR_TO_DATE(timestamp, '%d/%m/%Y %H:%i') >= NOW() - INTERVAL 7 DAY) as activity_count_7d,
               
            (SELECT COUNT(*) FROM health_data WHERE patient_id = p.patient_id AND STR_TO_DATE(timestamp, '%d/%m/%Y %H:%i') >= NOW() - INTERVAL 7 DAY) as total_records_7d,
            
            -- Count high carb meals (Case Insensitive Pattern Matching)
            (SELECT COUNT(*) FROM health_data 
             WHERE patient_id = p.patient_id 
               AND (
                   LOWER(food_intake) LIKE '%rice%' OR 
                   LOWER(food_intake) LIKE '%noodle%' OR 
                   LOWER(food_intake) LIKE '%bread%' OR 
                   LOWER(food_intake) LIKE '%pizza%' OR 
                   LOWER(food_intake) LIKE '%pasta%' OR
                   LOWER(food_intake) LIKE '%fries%' OR
                   LOWER(food_intake) LIKE '%burger%' OR
                   LOWER(food_intake) LIKE '%sugar%' OR
                   LOWER(food_intake) LIKE '%cake%'
               )
               AND STR_TO_DATE(timestamp, '%d/%m/%Y %H:%i') >= NOW() - INTERVAL 7 DAY) as high_carb_count_7d
        FROM patients p
        WHERE p.doctor_id = ? 
          AND p.patient_id IN (
              SELECT DISTINCT hd.patient_id 
              FROM health_data hd 
              WHERE hd.patient_id = p.patient_id 
                AND STR_TO_DATE(hd.timestamp, '%d/%m/%Y %H:%i') >= NOW() - INTERVAL 7 DAY
                AND (
                    hd.cgm_level > 10.0 -- Lowered threshold to catch moderate risk too
                    OR 
                    (
                        (LOWER(hd.activity_level) LIKE '%no activity%' OR LOWER(hd.activity_level) LIKE '%none%')
                        AND 
                        (LOWER(hd.food_intake) LIKE '%rice%' OR LOWER(hd.food_intake) LIKE '%noodle%' OR LOWER(hd.food_intake) LIKE '%bread%')
                    )
                )
          )
        ORDER BY avg_glucose_7d DESC
        LIMIT 5
    ";
    
    $stmt_high_risk = $conn->prepare($sql_high_risk);
    $stmt_high_risk->bind_param("i", $doctor_id);
    $stmt_high_risk->execute();
    $high_risk_results = $stmt_high_risk->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_high_risk->close();

    foreach ($high_risk_results as $patient) {
        $reasons = [];
        
        // Explicit float comparison
        if (floatval($patient['avg_glucose_7d']) > 10.0) {
            $reasons[] = "high avg glucose ({$patient['avg_glucose_7d']} mmol/L)";
        }
        
        if ($patient['total_records_7d'] > 0) {
            $activity_rate = $patient['activity_count_7d'] / $patient['total_records_7d'];
            if ($activity_rate < 0.3) {
                $reasons[] = "sedentary lifestyle (" . round($activity_rate * 100) . "% active)";
            }
        }
        
        if ($patient['high_carb_count_7d'] > 1) {
            $reasons[] = "frequent high-carb meals";
        }
        
        $explanation = empty($reasons) 
            ? "Concerning patterns detected in analysis." 
            : "Risk factors: " . implode(', ', $reasons) . ".";
        
        $response_data['high_risk_patients'][] = [
            'patient_id' => $patient['patient_id'],
            'full_name' => $patient['full_name'],
            'last_food' => $patient['last_food'],
            'last_activity' => $patient['last_activity'],
            'explanation' => $explanation,
            'avg_glucose_7d' => $patient['avg_glucose_7d']
        ];
    }

    // --- QUERY 2: Clinically Significant Outliers (DIRECT CHECK) ---
    // Removed dependency on graph_insights table to ensure immediate alerts
    $sql_outliers = "
        SELECT 
            p.patient_id, p.full_name, 
            hd.timestamp, hd.cgm_level,
            (SELECT ROUND(AVG(hd_avg.cgm_level), 1) FROM health_data hd_avg WHERE hd_avg.patient_id = p.patient_id) as historical_avg_cgm
        FROM health_data hd
        JOIN patients p ON hd.patient_id = p.patient_id
        WHERE p.doctor_id = ? 
          AND (hd.cgm_level > 14.0 OR hd.cgm_level < 4.0)
        ORDER BY STR_TO_DATE(hd.timestamp, '%d/%m/%Y %H:%i') DESC
        LIMIT 5
    ";
    
    $stmt_outliers = $conn->prepare($sql_outliers);
    $stmt_outliers->bind_param("i", $doctor_id);
    $stmt_outliers->execute();
    $response_data['outlier_events'] = $stmt_outliers->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_outliers->close();

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    $conn->close();
    exit();
}

$conn->close();
echo json_encode($response_data);
?>