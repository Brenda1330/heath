<?php
// File: admin_dashboard.php

/**
 * CORE INCLUSION & AUTHORIZATION
 */
require_once 'functions.php';
secure_session_start();
add_security_headers();
require_admin();

/**
 * DATA FETCHING LOGIC
 */
$conn = get_db_connection();
$error_message_for_template = null;

// Initialize variables with default values
$doctor_count = 0;
$patient_count = 0;
$audit_count = 0;
$recent_logs = [];
$labels = [];
$chart_data_points = [];
$current_view = $_GET['view'] ?? 'last_7_calendar_days';
$chart_title = "Data Unavailable";

if (!$conn) {
    $error_message_for_template = "Database service is unavailable. Dashboard data could not be loaded.";
    log_system_action('DB Connection Fail', 'error', 'admin_dashboard.php');
} else {
    // --- Dashboard Counter Cards ---
    $doctor_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'doctor'")->fetch_assoc()['count'] ?? 0;
    $patient_count = $conn->query("SELECT COUNT(*) as count FROM patients")->fetch_assoc()['count'] ?? 0;
    $audit_count = (isset($_SESSION['audit_results']['issues_critical'])) ? 1 : 0;

    // --- Fetch Recent System Logs ---
    $stmt = $conn->prepare("SELECT username, action, target, status, time FROM system_logs ORDER BY time DESC LIMIT 5");
    $stmt->execute();
    $recent_logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // --- Chart Data Logic (handles both views) ---
    if ($current_view === 'latest_7_entries') {
        $chart_title = "CGM Level Trends (Latest 7 Data Points)";
        
        // --- FIX: Use STR_TO_DATE to correctly parse the timestamp string ---
        $sql = "SELECT trend_date, avg_cgm FROM (
                    SELECT 
                        DATE(STR_TO_DATE(timestamp, '%d/%m/%Y %H:%i')) AS trend_date, 
                        ROUND(AVG(cgm_level), 1) AS avg_cgm
                    FROM health_data 
                    WHERE 
                        cgm_level IS NOT NULL 
                        AND STR_TO_DATE(timestamp, '%d/%m/%Y %H:%i') IS NOT NULL
                    GROUP BY trend_date 
                    ORDER BY trend_date DESC 
                    LIMIT 7
                ) AS latest_data ORDER BY trend_date ASC";
        
        if ($result = $conn->query($sql)) {
            while ($row = $result->fetch_assoc()) {
                if (!empty($row['trend_date'])) {
                    $labels[] = date('M d', strtotime($row['trend_date']));
                    $chart_data_points[] = (float)$row['avg_cgm'];
                }
            }
        }
    } else { // Default to 'last_7_calendar_days'
        $chart_title = "CGM Level Trends (Last 7 Calendar Days)";
        $date_data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date_key = date('Y-m-d', strtotime("-$i days"));
            $labels[] = date('M d', strtotime("-$i days"));
            $date_data[$date_key] = null;
        }

        // --- FIX: Use STR_TO_DATE in the WHERE clause and GROUP BY ---
        $sql = "SELECT 
                    DATE(STR_TO_DATE(timestamp, '%d/%m/%Y %H:%i')) as trend_date, 
                    ROUND(AVG(cgm_level), 1) as avg_cgm 
                FROM health_data 
                WHERE 
                    STR_TO_DATE(timestamp, '%d/%m/%Y %H:%i') >= CURDATE() - INTERVAL 6 DAY 
                    AND cgm_level IS NOT NULL
                GROUP BY trend_date 
                ORDER BY trend_date ASC";
        
        if ($result = $conn->query($sql)) {
            while ($row = $result->fetch_assoc()) {
                if (isset($date_data[$row['trend_date']])) {
                    $date_data[$row['trend_date']] = (float)$row['avg_cgm'];
                }
            }
        }
        $chart_data_points = array_values($date_data);
    }
    
    $conn->close();
    // Pass the view type as the third argument ($target)
    log_system_action('Admin Dashboard Viewed', 'success', "Chart View: {$current_view}");
}


/**
 * PRESENTATION (HTML)
 */
$pageTitle = "Admin Dashboard";
include 'templates/header.php';
include 'templates/sidebar_admin.php';
?>


<!-- Main Content -->
<div class="main-content">
  <div class="dashboard-header">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></div>

  <?php display_flash_messages(); ?>
  <?php if ($error_message_for_template): ?>
      <div class="alert alert-warning mt-3" role="alert"><?php echo htmlspecialchars($error_message_for_template); ?></div>
  <?php endif; ?>

<!-- Counter Cards -->
<div class="row g-4 mb-5 justify-content-center">

    <div class="col-md-4">
        <div class="card text-center p-4 shadow-sm" style="border-radius: 16px;">
            <h6 class="text-muted mb-2">Doctors</h6>
            <h2 class="count" data-target="<?php echo htmlspecialchars($doctor_count); ?>">0</h2>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card text-center p-4 shadow-sm" style="border-radius: 16px;">
            <h6 class="text-muted mb-2">Patients</h6>
            <h2 class="count" data-target="<?php echo htmlspecialchars($patient_count); ?>">0</h2>
        </div>
    </div>
    
    <!--
    <div class="col-md-4">
        <div class="card text-center p-4 shadow-sm" style="border-radius: 16px;">
            <h6 class="text-muted mb-2">Completed Audits</h6>
            <h2 class="count" data-target="<?php echo htmlspecialchars($audit_count); ?>">0</h2>
        </div>
    </div>
    -->
</div>

  <!-- Chart Section -->
  <div class="card p-4 shadow-sm mb-5" style="border-radius: 16px;">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
        <h5 id="chartTitleElement"><?php echo htmlspecialchars($chart_title); ?></h5>
        <div>
            <a href="admin_dashboard.php?view=last_7_calendar_days" class="btn btn-sm <?php echo ($current_view == 'last_7_calendar_days') ? 'btn-primary' : 'btn-outline-primary'; ?>">Last 7 Calendar Days</a>
            <a href="admin_dashboard.php?view=latest_7_entries" class="btn btn-sm <?php echo ($current_view == 'latest_7_entries') ? 'btn-primary' : 'btn-outline-primary'; ?> ms-2">Latest 7 Data Points</a>
        </div>
    </div>
    <div class="chart-container" style="position: relative; height:40vh; max-width: 100%;"> 
        <canvas id="cgmChart"></canvas>
    </div>
  </div>

  <!-- Recent Activities -->
  <div class="card p-4 shadow-sm mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0">Recent System Activities</h5>
      <a href="admin_system_logs.php" class="btn btn-sm btn-outline-secondary">View All Logs</a>
    </div>
    <ul class="list-group list-group-flush recent-activities">
      <?php if (!empty($recent_logs)): ?>
        <?php foreach ($recent_logs as $log): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center py-2">
            <div>
              <strong class="d-block"><?php echo htmlspecialchars($log['username'] ?? "System"); ?></strong> 
              <small class="text-muted">
                <?php echo htmlspecialchars($log['action']); ?>
                <?php if (!empty($log['target'])): ?>
                  - Target: <?php echo htmlspecialchars(mb_strimwidth($log['target'], 0, 35, "...")); ?>
                <?php endif; ?>
              </small>
            </div>
            <div class="text-end">
                <?php 
                  $status_lower = strtolower($log['status'] ?? '');
                  if ($status_lower === 'success') { echo '<span class="badge bg-success">Success</span>'; } 
                  elseif ($status_lower === 'fail' || $status_lower === 'error') { echo '<span class="badge bg-danger">Fail/Error</span>'; } 
                  elseif (!empty($status_lower)) { echo '<span class="badge bg-info">' . htmlspecialchars(ucfirst($status_lower)) . '</span>'; } 
                  else { echo '<span class="badge bg-secondary">Unknown</span>'; }
                ?>
                <small class="text-muted d-block mt-1">
                    <?php
                        if (!empty($log['time'])) {
                            echo htmlspecialchars(date('d M H:i', strtotime($log['time'])));
                        } else {
                            echo 'N/A';
                        }
                    ?>
                </small>
            </div>
          </li>
        <?php endforeach; ?>
      <?php else: ?>
        <li class="list-group-item text-muted text-center py-3">No recent activity found.</li>
      <?php endif; ?>
    </ul>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<?php
include 'templates/footer.php';
?>