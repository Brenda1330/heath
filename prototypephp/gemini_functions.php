<?php
// File: gemini_functions.php (FINAL CLEANED VERSION)

require_once __DIR__ . '/functions.php';

ini_set('display_errors', 0);
error_reporting(E_ALL);

function generate_recommendations_for_doctor(int $patient_id, string $data_id_str, string $algorithm): string 
{
    $conn = get_db_connection();
    if (!$conn) { return "ERROR: Database connection failed."; }

    $recommendation_text = '';

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
        $stmt_check->bind_param("is", $patient_id, $algorithm);
        $stmt_check->execute();
        if ($existing_summary = $stmt_check->get_result()->fetch_assoc()) {
            $stmt_check->close(); $conn->close();
            return $existing_summary['recommendation_text']; // Return the cached text
        }
        $stmt_check->close();

        // 2. Get the patient's overall average data (this is needed for all three summary types)
        $stmt_avg = $conn->prepare("SELECT AVG(cgm_level) as average_cgm, GROUP_CONCAT(DISTINCT food_intake SEPARATOR ', ') as food_summary, GROUP_CONCAT(DISTINCT activity_level SEPARATOR ', ') as activity_summary FROM health_data WHERE patient_id = ? AND cgm_level IS NOT NULL");
        $stmt_avg->bind_param("i", $patient_id);
        $stmt_avg->execute();
        $summary = $stmt_avg->get_result()->fetch_assoc();
        $stmt_avg->close();

        if (!$summary || $summary['average_cgm'] === null) {
            $conn->close();
            return "ERROR: No CGM data found for this patient to generate a summary.";
        }

        $average_cgm = round($summary['average_cgm'], 1);
        $food_summary = $summary['food_summary'] ?? 'N/A';
        $activity_summary = $summary['activity_summary'] ?? 'N/A';
        
        // 3. NEW: Use a switch to generate a unique prompt for each algorithm's summary
        switch ($algorithm) {
            case "PPR":
                // Find how many other patients have a similar AVERAGE glucose (+/- 0.5 mmol/L)
                $avg_low = (float)$average_cgm - 0.5;
                $avg_high = (float)$average_cgm + 0.5;
                $stmt_similar = $conn->prepare(
                    "SELECT COUNT(*) as similar_count FROM (
                        SELECT patient_id FROM health_data WHERE patient_id != ? GROUP BY patient_id 
                        HAVING AVG(cgm_level) BETWEEN ? AND ?
                    ) as similar_patients"
                );
                $stmt_similar->bind_param("idd", $patient_id, $avg_low, $avg_high);
                $stmt_similar->execute();
                $result = $stmt_similar->get_result()->fetch_assoc();
                $similar_patient_count = $result['similar_count'] ?? 0;
                $stmt_similar->close();

                $prompt = "You are a medical AI using a 'Personalized PageRank' (PPR) insight for a long-term summary. Your task is to compare the patient's OVERALL AVERAGE health to a cohort of similar patients.\n\n"
                        . "**RULE:** Your output MUST start by stating the number of similar patients found, framing the advice as a common strategy for this group.\n\n"
                        . "**Final Output Example:** Based on your overall average glucose, you are in a cohort with {$similar_patient_count} other patients. A successful long-term strategy for this group is focusing on consistent meal timing to prevent large glucose fluctuations, rather than strict dieting. Your regular walking is already a great foundation for this approach.";
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

        // 4. Call API and Save to DB (this logic is now universal)
        try {
            $recommendation_text = call_api_with_curl(json_encode(['contents' => [['parts' => [['text' => $full_prompt]]]]]));
            if (str_starts_with($recommendation_text, 'ERROR:')) throw new Exception($recommendation_text);
        } catch (Throwable $e) {
            error_log("API Call Failed (All View, Algo: {$algorithm}): " . $e->getMessage());
            $conn->close();
            return "ERROR: The Gemini service is currently timed out. Please try again.";
        }

        try {
            $stmt_insert = $conn->prepare(
                "INSERT INTO patient_summaries (patient_id, avg_cgm_level, food_summary, activity_summary, recommendation_text, model_used, algorithm_used) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $model_used_info = 'Gemini (Summary)';
            // This now correctly saves the algorithm (PPR, Node2Vec, or GAT) that was used to generate the summary
            $stmt_insert->bind_param("idsssss", $patient_id, $average_cgm, $food_summary, $activity_summary, $recommendation_text, $model_used_info, $algorithm);
            $stmt_insert->execute();
            $stmt_insert->close();
        } catch (mysqli_sql_exception $e) {
            error_log("Failed to insert patient summary: " . $e->getMessage());
        }
     } // --- LOGIC PATH 2: SINGLE TIMESTAMP ---
    else {
        $data_id = (int)$data_id_str;

        // 1. Get Health and Insight Data (Unchanged)
        $stmt_health = $conn->prepare("SELECT * FROM health_data WHERE data_id = ? AND patient_id = ?");
        $stmt_health->bind_param("ii", $data_id, $patient_id);
        $stmt_health->execute();
        $health = $stmt_health->get_result()->fetch_assoc();
        $stmt_health->close();
        if (!$health) { $conn->close(); return "ERROR: Health data not found."; }

        $stmt_insight = $conn->prepare("SELECT * FROM graph_insights WHERE data_id = ? AND algorithm = ?");
        $stmt_insight->bind_param("is", $data_id, $algorithm);
        $stmt_insight->execute();
        $insight = $stmt_insight->get_result()->fetch_assoc();
        $stmt_insight->close();
        if (!$insight) { $conn->close(); return "ERROR: No graph insight found for this algorithm."; }
        
        // 2. Check for Existing Recommendation (Unchanged)
        $insight_id = $insight['insight_id'];
        $stmt_rec = $conn->prepare("SELECT recommendation FROM recommendations WHERE insight_id = ?");
        $stmt_rec->bind_param("i", $insight_id);
        $stmt_rec->execute();
        if ($existing_rec = $stmt_rec->get_result()->fetch_assoc()) {
            $stmt_rec->close(); $conn->close(); return $existing_rec['recommendation'];
        }
        $stmt_rec->close();

        // 3. Prepare data for the prompt
        $cgm_value = $health['cgm_level'] ?? 'N/A';
        $food_intake = $health['food_intake'] ?? 'N/A';
        $activity_level = $health['activity_level'] ?? 'N/A';
        
        // ===================================================================
        // START: PHP ALGORITHM-SPECIFIC PROMPT SWITCHER
        // ===================================================================
        switch ($algorithm) {
        case "PPR":
            // 1. Define what "similar" means. Let's say a CGM level +/- 1.0 mmol/L.
            $cgm_low = (float)$cgm_value - 1.0;
            $cgm_high = (float)$cgm_value + 1.0;

            // 2. Run a query to count how many OTHER patients fall into this category.
            $stmt_similar = $conn->prepare(
                "SELECT COUNT(DISTINCT patient_id) as similar_count 
                FROM health_data 
                WHERE cgm_level BETWEEN ? AND ? 
                AND patient_id != ?"
            );
            $stmt_similar->bind_param("ddi", $cgm_low, $cgm_high, $patient_id);
            $stmt_similar->execute();
            $result = $stmt_similar->get_result()->fetch_assoc();
            $similar_patient_count = $result['similar_count'] ?? 0;
            $stmt_similar->close();

            // 3. Now, build the prompt WITH the number we just found.
            $prompt = "You are a medical AI using a 'Personalized PageRank' (PPR) insight. Your task is to compare a patient's data to a specific number of similar patients.\n\n"
                . "**Your Task:** Generate a detailed recommendation (60-80 words) that frames the advice as a comparison to these similar patients. You must imply that this advice has been effective for others.\n\n"
                . "**RULE:** Your output MUST start by stating the number of similar patients found, exactly like in the example.\n\n"
                . "--- EXAMPLE ---\n"
                . "**Data Provided:** CGM: 11.3, Similar Patients Found: 5\n"
                . "**Final Output:** Your recent patterns are similar to 5 other patients who successfully managed glucose spikes by switching to brown rice and incorporating a 15-minute post-meal walk.\n"
                . "--- END EXAMPLE ---\n\n"
                . "--- YOUR TURN ---\n"
                . "**Patient Data to Analyze:**\n"
                . "- CGM Level: {$cgm_value} mmol/L\n"
                . "- Food Intake: {$food_intake}\n"
                . "- Activity Level: {$activity_level}\n"
                // --- INJECT THE NUMBER HERE ---
                . "- **Similar Patients Found:** {$similar_patient_count}\n\n"
                . "Now, provide only the **Final Output**, following all rules and the updated example format exactly.";
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
            // This fallback remains the same
            $prompt = "Analyze this patient data: CGM {$cgm_value}, Food {$food_intake}, Activity {$activity_level} and provide a generic health recommendation between 60 and 80 words.";
            break;
        }
        try {
        $recommendation_text = call_api_with_curl(json_encode(['contents' => [['parts' => [['text' => $prompt]]]]]));
        
        if (str_starts_with($recommendation_text, 'ERROR:')) {
            throw new Exception($recommendation_text);
        }
    } catch (Throwable $e) {
        error_log("API Call Failed (Single View): " . $e->getMessage());
        $conn->close();
        return "ERROR: The Gemini service is currently timed out. Please try again.";
    }
    
    // 5. Save the NEW recommendation to the database
    try {
        $stmt_insert = $conn->prepare("INSERT INTO recommendations (patient_id, insight_id, model_used, algorithm_used, prompt_used, recommendation, created_at) VALUES (?, ?, 'Gemini', ?, ?, ?, NOW())");
        
        $stmt_insert->bind_param("iisss", $patient_id, $insight_id, $algorithm, $prompt, $recommendation_text);
        
        $stmt_insert->execute();
        $stmt_insert->close();

    } catch (mysqli_sql_exception $e) {
        error_log("Failed to insert recommendation: " . $e->getMessage());
        // Do not return an error to the user. They still get their recommendation.
    }
    
    // ===================================================================
    // END: CORRECTED LOGIC ORDER
    // ===================================================================
}

    $conn->close();
    return $recommendation_text;
}


/**
 * A generic helper function to make a POST request with cURL for the Gemini API.
 */
function call_api_with_curl(string $json_data): string
{
    // The function now uses the constant directly from config.php
    $ch = curl_init(GEMINI_API_URL); 
    // Set a timeout for the connection to be established (e.g., 20 seconds)
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20); 
    // Set a total timeout for the entire API call (e.g., 45 seconds)
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
        return $error;
    }
    curl_close($ch);
    
    $result = json_decode($response, true);
    if (!$result) {
        return "ERROR: Gemini API did not return valid JSON. Raw response: " . substr($response, 0, 500);
    }

    if ($http_code !== 200 || isset($result['error'])) {
        $error_details = $result['error']['message'] ?? 'Unknown API error.';
        return "ERROR: Gemini API returned an error ({$http_code}). Details: {$error_details}";
    }
    
    return trim($result['candidates'][0]['content']['parts'][0]['text'] ?? '');
}
?>