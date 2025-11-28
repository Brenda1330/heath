<?php
// File: gemini_functions.php (FINAL CLEANED VERSION - FIXED)

// TEMPORARY: Enable error reporting to see what's wrong
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/functions.php';

function generate_recommendations_for_doctor(int $patient_id, string $data_id_str, string $algorithm): string 
{
    $conn = get_db_connection();
    if (!$conn) { 
        error_log("Database connection failed");
        return "ERROR: Database connection failed."; 
    }

    $recommendation_text = '';

    try {
        if ($data_id_str === 'all') {
            // --- "VIEW ALL" LOGIC WITH ALGORITHM SWITCH ---

            // 1. MODIFIED CACHE CHECK: Check for a summary for this patient AND this algorithm
            $stmt_check = $conn->prepare(
                "SELECT recommendation_text 
                 FROM patient_summaries
                 WHERE patient_id = ? AND algorithm_used = ? AND generated_at >= NOW() - INTERVAL 24 HOUR
                 ORDER BY generated_at DESC
                 LIMIT 1"
            );
            if (!$stmt_check) {
                throw new Exception("Failed to prepare cache check query: " . $conn->error);
            }
            
            $stmt_check->bind_param("is", $patient_id, $algorithm);
            $stmt_check->execute();
            if ($existing_summary = $stmt_check->get_result()->fetch_assoc()) {
                $stmt_check->close(); 
                return $existing_summary['recommendation_text'];
            }
            $stmt_check->close();

            // 2. Get the patient's overall average data
            $stmt_avg = $conn->prepare("SELECT AVG(cgm_level) as average_cgm, GROUP_CONCAT(DISTINCT food_intake SEPARATOR ', ') as food_summary, GROUP_CONCAT(DISTINCT activity_level SEPARATOR ', ') as activity_summary FROM health_data WHERE patient_id = ? AND cgm_level IS NOT NULL");
            if (!$stmt_avg) {
                throw new Exception("Failed to prepare average data query: " . $conn->error);
            }
            
            $stmt_avg->bind_param("i", $patient_id);
            $stmt_avg->execute();
            $summary = $stmt_avg->get_result()->fetch_assoc();
            $stmt_avg->close();

            if (!$summary || $summary['average_cgm'] === null) {
                return "ERROR: No CGM data found for this patient to generate a summary.";
            }

            $average_cgm = round($summary['average_cgm'], 1);
            $food_summary = $summary['food_summary'] ?? 'N/A';
            $activity_summary = $summary['activity_summary'] ?? 'N/A';
            
            // 3. Algorithm-specific prompts for summary
            switch ($algorithm) {
                case "PPR":
                // Find how many other patients have a similar AVERAGE glucose (+/- 0.5 mmol/L)
                $avg_low = (float)$average_cgm - 0.5;
                $avg_high = (float)$average_cgm + 0.5;
                
                // CORRECTED: Count distinct patients with similar average glucose
               $stmt_similar = $conn->prepare(
                    "SELECT COUNT(DISTINCT sub.patient_id) as similar_count
                    FROM (
                        SELECT patient_id, AVG(cgm_level) as avg_glucose
                        FROM health_data 
                        GROUP BY patient_id
                        HAVING AVG(cgm_level) BETWEEN ? AND ? AND patient_id != ?
                    ) as sub"
                );
                if (!$stmt_similar) {
                    throw new Exception("Failed to prepare similar patients query: " . $conn->error);
                }
                
                $stmt_similar->bind_param("ddi", $avg_low, $avg_high, $patient_id);
                $stmt_similar->execute();
                $result = $stmt_similar->get_result()->fetch_assoc();
                $similar_patient_count = $result['similar_count'] ?? 0;
                $stmt_similar->close();

                // Get successful long-term patterns from similar patients
                $stmt_patterns = $conn->prepare(
                    "SELECT 
                        GROUP_CONCAT(DISTINCT hd.food_intake) AS common_foods,
                        GROUP_CONCAT(DISTINCT hd.activity_level) AS common_activities
                    FROM health_data hd
                    JOIN (
                        SELECT patient_id, AVG(cgm_level) AS avg_glucose
                        FROM health_data
                        GROUP BY patient_id
                        HAVING patient_id != ? AND AVG(cgm_level) BETWEEN ? AND ?
                    ) AS similar_patients ON hd.patient_id = similar_patients.patient_id
                    GROUP BY hd.patient_id
                    ORDER BY COUNT(*) DESC
                    LIMIT 10"
                );
                if (!$stmt_patterns) {
                    throw new Exception("Failed to prepare patterns query: " . $conn->error);
                }
                
                $stmt_patterns->bind_param("ddi", $avg_low, $avg_high, $patient_id);
                $stmt_patterns->execute();
                $similar_cohort = $stmt_patterns->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt_patterns->close();

                // If no similar patients found, use broader patterns
                if ($similar_patient_count === 0) {
                    // Fallback: Get patterns from all patients (not just similar ones)
                    $stmt_fallback = $conn->prepare(
                        "SELECT 
                            GROUP_CONCAT(DISTINCT food_intake) AS common_foods,
                            GROUP_CONCAT(DISTINCT activity_level) AS common_activities
                        FROM health_data 
                        WHERE patient_id != ?
                        GROUP BY patient_id
                        ORDER BY COUNT(*) DESC
                        LIMIT 10"
                    );
                    if ($stmt_fallback) {
                        $stmt_fallback->bind_param("i", $patient_id);
                        $stmt_fallback->execute();
                        $similar_cohort = $stmt_fallback->get_result()->fetch_all(MYSQLI_ASSOC);
                        $stmt_fallback->close();
                        
                        // Update the count to reflect we're using broader patterns
                        $similar_patient_count = count($similar_cohort);
                    }
                }

                // Extract successful long-term patterns
                $long_term_foods = [];
                $long_term_activities = [];

                foreach ($similar_cohort as $cohort) {
                    if (!empty($cohort['common_foods'])) {
                        $foods = explode(',', $cohort['common_foods']);
                        // Clean and filter foods
                        $foods = array_map('trim', $foods);
                        $foods = array_filter($foods, function($food) {
                            return !empty($food) && $food !== 'N/A';
                        });
                        $long_term_foods = array_merge($long_term_foods, $foods);
                    }
                    if (!empty($cohort['common_activities'])) {
                        $activities = explode(',', $cohort['common_activities']);
                        // Clean and filter activities
                        $activities = array_map('trim', $activities);
                        $activities = array_filter($activities, function($activity) {
                            return !empty($activity) && $activity !== 'N/A';
                        });
                        $long_term_activities = array_merge($long_term_activities, $activities);
                    }
                }

                // Get most common successful patterns
                $food_frequency = array_count_values($long_term_foods);
                $activity_frequency = array_count_values($long_term_activities);

                // Remove empty values
                unset($food_frequency['']);
                unset($activity_frequency['']);

                arsort($food_frequency);
                arsort($activity_frequency);

                $top_foods = array_slice(array_keys($food_frequency), 0, 3);
                $top_activities = array_slice(array_keys($activity_frequency), 0, 3);

                $successful_diets = !empty($top_foods) ? implode(', ', $top_foods) : 'balanced nutrition with consistent meal patterns';
                $successful_activities = !empty($top_activities) ? implode(', ', $top_activities) : 'regular physical activity';

                $prompt = "You are a medical AI using a 'Personalized PageRank' (PPR) insight for a long-term summary. Your task is to compare the patient's OVERALL AVERAGE health to successful long-term strategies from similar patients.\n\n"
                        . "**RULE:** Your output MUST:\n"
                        . "1. Start by stating the number of similar patients found\n"
                        . "2. Suggest long-term diet strategies based on successful patterns\n"
                        . "3. Recommend sustainable activities that worked for the cohort\n"
                        . "4. Frame it as proven long-term approaches\n\n"
                        . "--- SUCCESSFUL LONG-TERM PATTERNS FROM COHORT ---\n"
                        . "- Most Effective Diets: {$successful_diets}\n"
                        . "- Most Effective Activities: {$successful_activities}\n"
                        . "- Cohort Size: {$similar_patient_count} similar patients\n\n"
                        . "--- PATIENT DATA FOR ANALYSIS ---\n"
                        . "- Average CGM Level: {$average_cgm} mmol/L\n"
                        . "- Common Food Intakes: {$food_summary}\n"
                        . "- Common Activities: {$activity_summary}\n\n"
                        . "Now, generate the final output comparing their current lifestyle to successful long-term patterns from the cohort.";
                break;

                case "Node2Vec":
                    $prompt = "You are a medical AI using a 'Node2Vec' insight for a long-term summary. Your task is to define a patient 'archetype' based on their overall lifestyle patterns (food and activity).\n\n"
                            . "**RULE:** The output MUST start with 'Your overall lifestyle fits the...' and clearly state a descriptive archetype name.\n\n"
                            . "**Final Output Example:** Your overall lifestyle fits the 'High-Energy Intake, Low-Impact Activity' archetype. This suggests that while your diet provides ample energy, your activity level may not be sufficient to utilize it effectively. To improve long-term balance, consider incorporating moderate-intensity resistance training twice a week to build muscle and improve your baseline metabolic rate.";
                    break;

                case "GAT":
                    $prompt = "You are a medical AI using a 'Graph Attention Network' (GAT) insight for a long-term summary. Your task is to provide a long-term risk assessment based on the patient's average glucose level.\n\n"
                            . "**RULE:** The output MUST start with 'Long-term risk assessment:' and classify the risk based on their average CGM.\n\n"
                            . "**Final Output Example:** Long-term risk assessment: Moderate. An average glucose of {$average_cgm} mmol/L, while not in the diabetic range, indicates a sustained metabolic strain. If this trend continues, the primary risk over the next 5-10 years is the development of insulin resistance. Proactively increasing daily fiber intake and ensuring consistent sleep are key preventative measures.";
                    break;
                
                default:
                    $prompt = "Provide a generic long-term health summary based on this data: Average CGM {$average_cgm}, Common Foods {$food_summary}, Common Activities {$activity_summary}.";
                    break;
            }
            
            // Add the patient data to the chosen prompt
            $full_prompt = $prompt . "\n\n--- PATIENT DATA FOR ANALYSIS ---\n"
                        . "- Average CGM Level: {$average_cgm} mmol/L\n"
                        . "- Common Food Intakes: {$food_summary}\n"
                        . "- Common Activities: {$activity_summary}\n\n"
                        . "Now, generate the final output based on the rules and example provided in the prompt above.";

            // 4. Call API and Save to DB
            $recommendation_text = call_api_with_curl(json_encode(['contents' => [['parts' => [['text' => $full_prompt]]]]]));
            if (str_starts_with($recommendation_text, 'ERROR:')) {
                throw new Exception($recommendation_text);
            }

            // Save to database
            $stmt_insert = $conn->prepare(
                "INSERT INTO patient_summaries (patient_id, avg_cgm_level, food_summary, activity_summary, recommendation_text, model_used, algorithm_used) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            if ($stmt_insert) {
                $model_used_info = 'Gemini (Summary)';
                $stmt_insert->bind_param("idsssss", $patient_id, $average_cgm, $food_summary, $activity_summary, $recommendation_text, $model_used_info, $algorithm);
                $stmt_insert->execute();
                $stmt_insert->close();
            }

        } else {
            // --- LOGIC PATH 2: SINGLE TIMESTAMP ---
            $data_id = (int)$data_id_str;

            // 1. Get Health and Insight Data
            $stmt_health = $conn->prepare("SELECT * FROM health_data WHERE data_id = ? AND patient_id = ?");
            if (!$stmt_health) {
                throw new Exception("Failed to prepare health data query: " . $conn->error);
            }
            
            $stmt_health->bind_param("ii", $data_id, $patient_id);
            $stmt_health->execute();
            $health = $stmt_health->get_result()->fetch_assoc();
            $stmt_health->close();
            
            if (!$health) { 
                throw new Exception("Health data not found for data_id: {$data_id} and patient_id: {$patient_id}");
            }

            $stmt_insight = $conn->prepare("SELECT * FROM graph_insights WHERE data_id = ? AND algorithm = ?");
            if (!$stmt_insight) {
                throw new Exception("Failed to prepare insights query: " . $conn->error);
            }
            
            $stmt_insight->bind_param("is", $data_id, $algorithm);
            $stmt_insight->execute();
            $insight = $stmt_insight->get_result()->fetch_assoc();
            $stmt_insight->close();
            
            if (!$insight) { 
                throw new Exception("No graph insight found for data_id: {$data_id} and algorithm: {$algorithm}");
            }
            
            // 2. Check for Existing Recommendation
            $insight_id = $insight['insight_id'];
            $stmt_rec = $conn->prepare("SELECT recommendation FROM recommendations WHERE insight_id = ?");
            if ($stmt_rec) {
                $stmt_rec->bind_param("i", $insight_id);
                $stmt_rec->execute();
                if ($existing_rec = $stmt_rec->get_result()->fetch_assoc()) {
                    $stmt_rec->close(); 
                    return $existing_rec['recommendation'];
                }
                $stmt_rec->close();
            }

            // 3. Prepare data for the prompt
            $cgm_value = $health['cgm_level'] ?? 'N/A';
            $food_intake = $health['food_intake'] ?? 'N/A';
            $activity_level = $health['activity_level'] ?? 'N/A';
            
            // Algorithm-specific prompts for single timestamp
            switch ($algorithm) {
                case "PPR":
                // 1. Find patients with similar health patterns (CGM, food, activity)
                $cgm_low = (float)$cgm_value - 1.0;
                $cgm_high = (float)$cgm_value + 1.0;

                // 2. CORRECTED: Count DISTINCT PATIENTS (not patterns)
                $stmt_similar = $conn->prepare(
                    "SELECT COUNT(DISTINCT hd.patient_id) as similar_count
                    FROM health_data hd
                    WHERE hd.cgm_level BETWEEN ? AND ? 
                    AND hd.patient_id != ?
                    AND hd.food_intake IS NOT NULL
                    AND hd.activity_level IS NOT NULL
                    AND hd.food_intake != 'N/A'
                    AND hd.activity_level != 'N/A'"
                );
                
                if (!$stmt_similar) {
                    throw new Exception("Failed to prepare similar patients query: " . $conn->error);
                }
                
                $stmt_similar->bind_param("ddi", $cgm_low, $cgm_high, $patient_id);
                
                if (!$stmt_similar->execute()) {
                    throw new Exception("Failed to execute similar patients query: " . $stmt_similar->error);
                }
                
                $result = $stmt_similar->get_result()->fetch_assoc();
                $similar_patient_count = $result['similar_count'] ?? 0;
                $stmt_similar->close();

                // 3. Get successful diet/activity patterns (separate query)
                $stmt_patterns = $conn->prepare(
                    "SELECT 
                        hd.food_intake,
                        hd.activity_level,
                        COUNT(*) as pattern_count
                    FROM health_data hd
                    WHERE hd.cgm_level BETWEEN ? AND ? 
                    AND hd.patient_id != ?
                    AND hd.food_intake IS NOT NULL
                    AND hd.activity_level IS NOT NULL
                    AND hd.food_intake != 'N/A'
                    AND hd.activity_level != 'N/A'
                    GROUP BY hd.food_intake, hd.activity_level
                    HAVING COUNT(*) >= 1
                    ORDER BY pattern_count DESC
                    LIMIT 5"
                );
                
                if (!$stmt_patterns) {
                    throw new Exception("Failed to prepare patterns query: " . $conn->error);
                }
                
                $stmt_patterns->bind_param("ddi", $cgm_low, $cgm_high, $patient_id);
                $stmt_patterns->execute();
                $similar_patterns = $stmt_patterns->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt_patterns->close();

                // 4. Extract successful diet and activity patterns
                $preferred_diets = [];
                $preferred_activities = [];
                
                foreach ($similar_patterns as $pattern) {
                    if (!empty($pattern['food_intake']) && $pattern['food_intake'] !== 'N/A') {
                        $preferred_diets[] = $pattern['food_intake'];
                    }
                    if (!empty($pattern['activity_level']) && $pattern['activity_level'] !== 'N/A') {
                        $preferred_activities[] = $pattern['activity_level'];
                    }
                }
                
                // Remove duplicates and limit to top patterns
                $preferred_diets = array_slice(array_unique($preferred_diets), 0, 3);
                $preferred_activities = array_slice(array_unique($preferred_activities), 0, 3);
                
                $diet_suggestions = !empty($preferred_diets) ? implode(', ', $preferred_diets) : 'balanced meals with complex carbs';
                $activity_suggestions = !empty($preferred_activities) ? implode(', ', $preferred_activities) : 'regular walking';

                // 5. Build the prompt with specific diet/activity comparisons
                $prompt = "You are a medical AI using a 'Personalized PageRank' (PPR) insight. Your task is to compare a patient's data to successful patterns from similar patients.\n\n"
                        . "**Your Task:** Generate a detailed recommendation (60-80 words) that suggests specific diet and activity changes based on what worked for similar patients.\n\n"
                        . "**RULE:** Your output MUST:\n"
                        . "1. Start by stating the number of similar patient patterns found\n"
                        . "2. Suggest specific diet changes based on successful patterns\n"
                        . "3. Recommend specific activities that worked for others\n"
                        . "4. Frame it as proven strategies from similar cases\n\n"
                        . "--- SUCCESSFUL PATTERNS FROM SIMILAR PATIENTS ---\n"
                        . "- Preferred Diets: {$diet_suggestions}\n"
                        . "- Preferred Activities: {$activity_suggestions}\n"
                        . "- Total Similar Patients Found: {$similar_patient_count}\n\n"
                        . "--- CURRENT PATIENT DATA ---\n"
                        . "- CGM Level: {$cgm_value} mmol/L\n"
                        . "- Current Food: {$food_intake}\n"
                        . "- Current Activity: {$activity_level}\n\n"
                        . "Now, provide only the **Final Output** that compares their current habits to successful patterns and suggests specific improvements.";
                break;

                case "Node2Vec":
                    $prompt = "You are a medical AI providing a recommendation based on a 'Node2Vec' insight. This insight has placed the patient into a behavioral group or cluster based on latent similarities in their lifestyle.\n\n"
                            . "**Your Task:** Generate a detailed recommendation (60-80 words) that explicitly names the group the patient belongs to and offers a specific, actionable tip to address the group's common challenge.\n\n"
                            . "**RULE:** The output MUST start with 'You belong to the...' and clearly state the group's name (e.g., 'high-carb morning', 'sedentary afternoon').\n\n"
                            . "--- EXAMPLE ---\n"
                            . "**Patient Data:** CGM: 9.8 mmol/L (at 10:00 AM), Food: Toast and Juice\n"
                            . "**Final Output:** You belong to the 'high-carb morning' group. This pattern of high-sugar breakfasts often leads to midday energy slumps. To counteract this, try incorporating a source of protein like eggs or Greek yogurt into your morning meal to help stabilize your glucose levels throughout the day.\n"
                            . "--- END EXAMPLE ---\n\n"
                            . "--- YOUR TURN ---\n"
                            . "**Patient Data to Analyze:**\n"
                            . "- CGM Level: {$cgm_value} mmol/L\n"
                            . "- Food Intake: {$food_intake}\n"
                            . "- Activity Level: {$activity_level}\n\n"
                            . "Now, provide only the **Final Output**, following all rules and the example format exactly.";
                    break;

                case "GAT":
                    $prompt = "You are a medical AI providing a recommendation based on a 'Graph Attention Network' (GAT) insight. This is a predictive model that has calculated a future risk level.\n\n"
                            . "**Your Task:** Generate a detailed (60-80 words) and clear preventive alert. You must state the risk level and provide a direct action to mitigate it.\n\n"
                            . "**RULE:** 1. The output MUST start with 'Spike risk level:' followed by 'Low', 'Moderate', or 'High'. It should be framed as a forward-looking warning.\n\n"
                            . "2. Use the following ranges to determine the Spike Risk based on the CGM level (in mmol/L):\n"
                            . "   - < 4.0: High (due to rebound risk)\n"
                            . "   - 4.0 - 6.0: Low\n"
                            . "   - 6.1 - 7.8: Moderate\n"
                            . "   - 7.9 - 10.0: Moderate-High\n"
                            . "   - 10.1 - 13.9: High\n"
                            . "   - >= 14.0: Very High\n"
                            . "3. Your recommendation should align with the determined risk level.\n\n"

                            . "--- EXAMPLE ---\n"
                            . "**Patient Data:** CGM: 13.5 mmol/L (Risk: High)\n"
                            . "**Final Output:** Spike risk level: High. Your current glucose of 13.5 mmol/L and reduced activity indicate a high probability of continued high readings. It is crucial to reintroduce light activity, such as a 20-minute daily walk, to improve insulin sensitivity. Check with your clinician if readings remain above 15 mmol/L.\n"
                            . "--- END EXAMPLE ---\n\n"
                            . "--- YOUR TURN ---\n"
                            . "**Patient Data to Analyze:**\n"
                            . "- CGM Level: {$cgm_value} mmol/L\n"
                            . "- Food Intake: {$food_intake}\n"
                            . "- Activity Level: {$activity_level}\n\n"
                            . "Now, provide only the **Final Output**, following all rules and the example format exactly.";
                    break;

                default:
                    $prompt = "Analyze this patient data: CGM {$cgm_value}, Food {$food_intake}, Activity {$activity_level} and provide a generic health recommendation between 60 and 80 words.";
                    break;
            }

            // Call API
            $recommendation_text = call_api_with_curl(json_encode(['contents' => [['parts' => [['text' => $prompt]]]]]));
            
            if (str_starts_with($recommendation_text, 'ERROR:')) {
                throw new Exception($recommendation_text);
            }
            
            // Save the NEW recommendation to the database
            $stmt_insert = $conn->prepare("INSERT INTO recommendations (patient_id, insight_id, model_used, algorithm_used, prompt_used, recommendation, created_at) VALUES (?, ?, 'Gemini', ?, ?, ?, NOW())");
            
            if ($stmt_insert) {
                $stmt_insert->bind_param("iisss", $patient_id, $insight_id, $algorithm, $prompt, $recommendation_text);
                $stmt_insert->execute();
                $stmt_insert->close();
            }
        }

     } catch (Throwable $e) {
    error_log("Recommendation generation failed: " . $e->getMessage());
    
    // Provide fallback recommendations based on algorithm
    $recommendation_text = generate_fallback_recommendation($algorithm, $patient_id, $conn);
    
} finally {
    // Always close connection
    if ($conn) {
        $conn->close();
    }
}

return $recommendation_text;
}

/**
 * Generate fallback recommendations when API is unavailable
 */
function generate_fallback_recommendation(string $algorithm, int $patient_id, $conn): string 
{
    switch ($algorithm) {
        case "PPR":
            return "Based on analysis of similar patient patterns, we recommend maintaining consistent meal timing and incorporating regular physical activity. When the AI service is available, we can provide more personalized dietary and activity suggestions tailored to your specific glucose patterns.";
            
        case "Node2Vec":
            return "Your health patterns suggest you would benefit from lifestyle adjustments. Common successful strategies include balanced nutrition and regular exercise. Please try again later for a more detailed archetype analysis when our AI service recovers.";
            
        case "GAT":
            // Only try to get CGM data if connection is available
            if ($conn) {
                $stmt = $conn->prepare("SELECT cgm_level FROM health_data WHERE patient_id = ? ORDER BY data_id DESC LIMIT 1");
                if ($stmt) {
                    $stmt->bind_param("i", $patient_id);
                    $stmt->execute();
                    $result = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    
                    $cgm = $result['cgm_level'] ?? null;
                    if ($cgm) {
                        if ($cgm < 4.0) return "Alert: Low glucose pattern detected. Please monitor your levels closely and consult your healthcare provider. Please try again later for a more detailed archetype analysis when our AI service recovers.";
                        if ($cgm <= 7.8) return "Your glucose levels appear stable. Continue with your current management plan. Please try again later for a more detailed archetype analysis when our AI service recovers.";
                        if ($cgm <= 13.9) return "Moderate elevation detected. Consider reviewing your diet and activity patterns. Please try again later for a more detailed archetype analysis when our AI service recovers.";
                        return "Elevated glucose levels observed. Please consult your healthcare provider for immediate guidance. Please try again later for a more detailed archetype analysis when our AI service recovers.";
                    }
                }
            }
            return "Our predictive analysis service is temporarily unavailable. Based on general guidelines, we recommend monitoring your glucose levels and maintaining a balanced lifestyle.";
            
        default:
            return "Our AI recommendation service is currently experiencing high demand. Please try again in a few moments for personalized health advice.";
    }
}

/**
 * A generic helper function to make a POST request with cURL for the Gemini API.
 */
/**
 * A generic helper function to make a POST request with cURL for the Gemini API.
 */
function call_api_with_curl(string $json_data): string
{
    // Check if API URL is defined
    if (!defined('GEMINI_API_URL')) {
        return "ERROR: Gemini API URL is not defined. Check your configuration.";
    }

    $max_retries = 3;
    $retry_delay = 1000000; // 1 second in microseconds
    
    for ($attempt = 1; $attempt <= $max_retries; $attempt++) {
        $ch = curl_init(GEMINI_API_URL); 
        
        // Set timeouts
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20); 
        curl_setopt($ch, CURLOPT_TIMEOUT, 45); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = 'ERROR: cURL request failed: ' . curl_error($ch);
            curl_close($ch);
            
            if ($attempt < $max_retries) {
                usleep($retry_delay * $attempt); // Exponential backoff
                continue;
            }
            return $error;
        }
        curl_close($ch);
        
        // If rate limited or overloaded, wait and retry
        if (($http_code === 429 || $http_code === 503) && $attempt < $max_retries) {
            error_log("Gemini API overloaded (503). Retrying in " . ($retry_delay * $attempt / 1000000) . " seconds...");
            usleep($retry_delay * $attempt);
            continue;
        }
        
        // If successful, break out of retry loop
        if ($http_code === 200) {
            break;
        }
    }
    
    if (!$response) {
        return "ERROR: No response received from Gemini API after {$max_retries} attempts.";
    }
    
    $result = json_decode($response, true);
    if (!$result) {
        return "ERROR: Gemini API did not return valid JSON. Raw response: " . substr($response, 0, 500);
    }

    if ($http_code !== 200 || isset($result['error'])) {
        $error_details = $result['error']['message'] ?? 'Unknown API error.';
        return "ERROR: Gemini API returned an error ({$http_code}). Details: {$error_details}";
    }
    
    if (!isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        return "ERROR: Unexpected response format from Gemini API.";
    }
    
    return trim($result['candidates'][0]['content']['parts'][0]['text']);
}

// Add the missing closing brace for the file
?>