<?php
// File: doc_dashboard.php

/**
 * CORE INCLUSION & AUTHORIZATION
 */
require_once 'functions.php';
secure_session_start();
add_security_headers();
require_doctor(); // Gatekeeper: Ensures only logged-in doctors can access.

/**
 * DATA FETCHING LOGIC
 */
$conn = get_db_connection();
$error_message = null;
$doctor = ['username' => 'Doctor'];
$total_patients = 0;
$new_patients_7_days = 0;
$new_patients_30_days = 0;
$avg_cgm_level = 'N/A';
$chart_data = [0, 0, 0, 0];
$patients = [];
$doctor_id = $_SESSION['user_id'];

if (!$conn) {
    $error_message = "Database service is unavailable. Dashboard data could not be loaded.";
} else {
    // Fetch Doctor's Name
    $stmt = $conn->prepare("SELECT username FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    if ($doc_result = $stmt->get_result()->fetch_assoc()) { $doctor = $doc_result; }
    $stmt->close();

    // Fetch Counter & Pie Chart Data
    $stmt_all = $conn->prepare("SELECT COUNT(*) as total, 
        SUM(CASE WHEN created_at >= CURDATE() - INTERVAL 7 DAY THEN 1 ELSE 0 END) as week,
        SUM(CASE WHEN created_at >= CURDATE() - INTERVAL 30 DAY THEN 1 ELSE 0 END) as month,
        SUM(CASE WHEN LOWER(status) = 'stable' THEN 1 ELSE 0 END) as stable,
        SUM(CASE WHEN LOWER(status) = 'critical' THEN 1 ELSE 0 END) as critical,
        SUM(CASE WHEN LOWER(status) = 'recovered' THEN 1 ELSE 0 END) as recovered,
        SUM(CASE WHEN LOWER(status) = 'warning' THEN 1 ELSE 0 END) as warning
        FROM patients WHERE doctor_id = ?");
    $stmt_all->bind_param("i", $doctor_id);
    $stmt_all->execute();
    if ($counts = $stmt_all->get_result()->fetch_assoc()) {
        $total_patients = $counts['total'] ?? 0;
        $new_patients_7_days = $counts['week'] ?? 0;
        $new_patients_30_days = $counts['month'] ?? 0;
        $chart_data = [(int)($counts['stable']??0), (int)($counts['critical']??0), (int)($counts['recovered']??0), (int)($counts['warning']??0)];
    }
    $stmt_all->close();

    // Fetch Average CGM
    $stmt_cgm = $conn->prepare("SELECT ROUND(AVG(hd.cgm_level), 1) FROM health_data hd JOIN patients p ON hd.patient_id = p.patient_id WHERE p.doctor_id = ?");
    $stmt_cgm->bind_param("i", $doctor_id);
    $stmt_cgm->execute();
    $avg_cgm_level = $stmt_cgm->get_result()->fetch_row()[0] ?? 'N/A';
    $stmt_cgm->close();
    
    // Fetch Patient Table Data
    $stmt_patients = $conn->prepare("SELECT p.patient_id, p.full_name, p.status, h.cgm_level, h.timestamp AS last_updated FROM patients p LEFT JOIN (SELECT patient_id, cgm_level, timestamp, ROW_NUMBER() OVER(PARTITION BY patient_id ORDER BY STR_TO_DATE(timestamp, '%d/%m/%Y %H:%i') DESC) as rn FROM health_data) h ON p.patient_id = h.patient_id AND h.rn = 1 WHERE p.doctor_id = ? ORDER BY p.full_name ASC");
    $stmt_patients->bind_param("i", $doctor_id);
    $stmt_patients->execute();
    $patients_raw = $stmt_patients->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($patients_raw as $key => $p) {
        if (!empty($p['last_updated'])) {
            $date = date_create_from_format('d/m/Y H:i', $p['last_updated']) ?: date_create($p['last_updated']);
            $patients_raw[$key]['last_updated'] = $date ? $date->format('d M Y, H:i') : 'Invalid Date';
        }
    }
    $patients = $patients_raw;
    $stmt_patients->close();
    $conn->close();
}

/**
 * PRESENTATION (HTML)
 */
$pageTitle = "Doctor Dashboard";
include 'templates/header_doctor.php'; 
include 'templates/sidebar_doctor.php'; 

?>
    
<!-- Profile Dropdown (Top Right) -->
<div class="profile-dropdown">
    <div class="dropdown">
        <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-user-circle"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="doc_userprofile.php">View Profile</a></li>
            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
        </ul>
    </div>
</div>

<!-- Main Content Wrapper -->
<div class="dashboard-header">
    <!-- Top Row with Widgets -->
    <div class="row g-4 mb-4">
        <!-- WIDGET 1: Total Patients -->
        <!-- FIX: Each card is wrapped in its own column div -->
        <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="100">
            <div class="card widget-card h-100">
                <div class="card-body">
                    <div class="widget-icon"><i class="fas fa-users"></i></div>
                    <div class="widget-text">
                        <h6 class="text-muted">Total Patients</h6>
                        <h2 class="fw-bold"><?php echo htmlspecialchars($total_patients); ?></h2>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- WIDGET 2: New Patients -->
        <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="200">
            <div class="card widget-card h-100">
                <div class="card-body">
                     <div class="widget-icon" style="color: #10B981; background-color: rgba(16, 185, 129, 0.1);">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="widget-text">
                        <h6 class="text-muted">New Patients(7 Days)</h6>
                        <h2 class="fw-bold"><?php echo htmlspecialchars($new_patients_7_days); ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- WIDGET 3: Average CGM -->
        <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="300">
             <div class="card widget-card h-100">
                <div class="card-body">
                    <div class="widget-icon" style="color: #F59E0B; background-color: rgba(245, 158, 11, 0.1);">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="widget-text">
                        <h6 class="text-muted">Avg. CGM</h6>
                        <h2 class="fw-bold"><?php echo htmlspecialchars($avg_cgm_level); ?></h2>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- WIDGET 4: Calendar -->
        <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="400">
            <div class="card calendar-card h-100">
                <div class="calendar-header" id="currentMonthYear"></div>
                <div class="calendar-grid" id="calendarDays"></div>
                <div class="calendar-footer" id="malaysiaTime"></div>
            </div>
        </div>
    </div>
        
        <!-- MODIFIED: Health Status & Quick Stats Section -->
<div class="row g-4 mb-4">
    <!-- WIDGET 5: Health Status Pie Chart -->
    <div class="col-lg-7" data-aos="fade-up" data-aos-delay="500">
        <div class="card h-100">
            <div class="card-body d-flex flex-column">
                <h5 class="card-title mb-3">Health Status Overview</h5>
                <!-- The container for the chart to control its size -->
                <div class="chart-container flex-grow-1">
                    <canvas id="healthStatusChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- WIDGET 6: Quick Stats -->
    <div class="col-lg-5" data-aos="fade-up" data-aos-delay="600">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title mb-4">Quick Stats</h5>
                <div class="quick-stats-grid">
                    <div class="stat-item">
                        <div class="stat-icon" style="background-color: #FEE2E2; color: #991B1B;"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="stat-text">
                            <span class="stat-value"><?php echo htmlspecialchars($chart_data[1] ?? 0); ?></span>
                            <span class="stat-label">Critical</span>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-icon" style="background-color: #FEF3C7; color: #92400E;"><i class="fas fa-exclamation-circle"></i></div>
                        <div class="stat-text">
                            <span class="stat-value"><?php echo htmlspecialchars($chart_data[3] ?? 0); ?></span>
                            <span class="stat-label">Warning</span>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-icon" style="background-color: #D1FAE5; color: #065F46;"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-text">
                            <span class="stat-value"><?php echo htmlspecialchars($chart_data[0] ?? 0); ?></span>
                            <span class="stat-label">Stable</span>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-icon" style="background-color: #DBEAFE; color: #1E40AF;"><i class="fas fa-heart"></i></div>
                        <div class="stat-text">
                            <span class="stat-value"><?php echo htmlspecialchars($chart_data[2] ?? 0); ?></span>
                            <span class="stat-label">Recovered</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

        <!-- Search and Sort Controls -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>Patient Summary</h1>
            <div class="d-flex align-items-center gap-2">
                <input type="text" id="searchInput" placeholder="Search..." class="form-control" style="width: 250px;" onkeyup="searchFunction()">

                <!-- Sort Dropdown -->
            <div class="dropdown">
                <button class="btn btn-light dropdown-toggle sorting-btn" type="button" id="sortMenu" data-bs-toggle="dropdown" aria-expanded="false">
                    Sort by: Default
                    <span id="sortIndicator" class="sort-indicator"></span> <!-- Sorting Indicator -->
                </button>
                <ul class="dropdown-menu" aria-labelledby="sortMenu">
                    <!-- Sorting columns and updating button text -->
                    <li><a class="dropdown-item" href="#" onclick="sortTable(0, 'Patient Name'); return false;">Patient Name</a></li>
                    <li><a class="dropdown-item" href="#" onclick="sortTable(1, 'CGM Level'); return false;">CGM Level</a></li>
                    <li><a class="dropdown-item" href="#" onclick="sortTable(2, 'Status'); return false;">Status</a></li>
                    <li><a class="dropdown-item" href="#" onclick="sortTable(3, 'Last Updated'); return false;">Last Updated</a></li>
                </ul>
            </div>
        </div>
        </div>
    </div>
    
<!-- Patient Summary Table -->
<div class="table-container">
    <div class="table-responsive">
        <table id="patientTable" class="table table-bordered table-hover">
            <thead><tr><th>Patient Name</th><th>CGM Level</th><th>Status</th><th>Last Updated</th></tr></thead>
            <tbody>
                <?php if (!empty($patients)): ?>
                    <?php foreach ($patients as $patient): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($patient['full_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($patient['cgm_level'] ?? 'N/A'); ?></td>
                            <td>
                                <?php 
                                $status = strtolower($patient['status'] ?? 'unknown');
                                echo "<span class='status-box {$status}'>" . htmlspecialchars(ucfirst($status)) . "</span>";
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($patient['last_updated'] ?? 'N/A'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="text-center">No patient data available.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Welcome Modal -->
<div class="modal fade" id="welcomeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #007bff; color: white;">
                <h5 class="modal-title">Welcome!</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <h4>Hi, <strong><?php echo htmlspecialchars($doctor['username']); ?></strong>!</h4>
                <p>We're glad to have you back.</p>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-primary" data-bs-dismiss="modal">Got it!</button></div>
        </div>
    </div>
</div>

<?php
// Use the dedicated footer that contains ALL necessary scripts
include 'templates/footer_with_doc_dashboard_scripts.php';
?>