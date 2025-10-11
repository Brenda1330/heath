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

// Initialize all variables to prevent errors
$doctor = ['username' => 'Doctor'];
$total_patients = 0;
$new_patients_7_days = 0;
$new_patients_30_days = 0;
$avg_cgm_level = 'N/A';
$chart_data = [0, 0, 0, 0]; // Order: Stable, Critical, Recovered, Warning
$patients = [];
$doctor_id = $_SESSION['user_id'];

if (!$conn) {
    $error_message = "Database service is unavailable. Dashboard data could not be loaded.";
    log_system_action('DB Connection Fail', 'error', 'doc_dashboard.php');
} else {
    // --- Fetch Doctor's Name ---
    $stmt = $conn->prepare("SELECT username FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    if ($doc_result = $stmt->get_result()->fetch_assoc()) {
        $doctor = $doc_result;
    }
    $stmt->close();

    // --- Fetch Counter Card & Pie Chart Data (Efficient Query) ---
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
        // The order MUST match the labels in the JavaScript: Stable, Critical, Recovered, Warning
        $chart_data = [
            (int)($counts['stable'] ?? 0),
            (int)($counts['critical'] ?? 0),
            (int)($counts['recovered'] ?? 0),
            (int)($counts['warning'] ?? 0)
        ];
    }
    $stmt_all->close();

    // --- Fetch Average CGM ---
    $stmt_cgm = $conn->prepare("SELECT ROUND(AVG(hd.cgm_level), 1) FROM health_data hd JOIN patients p ON hd.patient_id = p.patient_id WHERE p.doctor_id = ?");
    $stmt_cgm->bind_param("i", $doctor_id);
    $stmt_cgm->execute();
    $avg_cgm_level = $stmt_cgm->get_result()->fetch_row()[0] ?? 'N/A';
    $stmt_cgm->close();
    
    // --- Fetch Main Patient Table Data ---
    $stmt_patients = $conn->prepare("
        SELECT p.patient_id, p.full_name, p.status, h.cgm_level, h.timestamp AS last_updated
        FROM patients p LEFT JOIN (
            SELECT patient_id, cgm_level, timestamp, ROW_NUMBER() OVER(PARTITION BY patient_id ORDER BY STR_TO_DATE(timestamp, '%d/%m/%Y %H:%i') DESC) as rn
            FROM health_data
        ) h ON p.patient_id = h.patient_id AND h.rn = 1
        WHERE p.doctor_id = ? ORDER BY p.full_name ASC
    ");
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
// Use the new, dedicated header for the doctor panel
include 'templates/draft_header_doctor.php'; 
?>
    
<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header text-center">
        <h4><i class="fas fa-stethoscope me-2"></i> Doctor Panel</h4>
    </div>
    <ul class="nav flex-column">
        <li class="nav-item"><a href="doc_dashboard.php" class="nav-link active"><i class="fas fa-th-large me-2"></i><span> Dashboard</span></a></li>
        <li class="nav-item"><a href="doc_addpatient.php" class="nav-link"><i class="fas fa-user-plus me-2"></i><span> Add Patient</span></a></li>
        <li class="nav-item"><a href="doc_patientlist.php" class="nav-link"><i class="fas fa-users"></i><span> Patient List</span></a></li>
        <li class="nav-item"><a href="#patientSubmenu" class="nav-link" data-bs-toggle="collapse"><i class="fas fa-syringe"></i><span>Patient Tools </span><i class="fas fa-chevron-down"></i></a></li>
        <div class="collapse" id="patientSubmenu">
            <ul class="nav flex-column ps-3">
                <li class="nav-item"><a href="doc_importdata.php" class="nav-link"><i class="fas fa-cloud-upload-alt"></i><span> Import Data</span></a></li>
                <li class="nav-item"><a href="doc_recommendation.php" class="nav-link"><i class="fas fa-bolt"></i><span> Recommendation</span></a></li>
                <li class="nav-item"><a href="doc_graphexp.php" class="nav-link"><i class="fas fa-chart-line"></i><span> Graph Explorer</span></a></li>
            </ul>
        </div>
        <li class="nav-item"><a href="doc_reports.php" class="nav-link"><i class="fas fa-clipboard-list"></i><span> Reports</span></a></li>
        <li class="nav-item"><a href="doc_userprofile.php" class="nav-link"><i class="fas fa-user-circle"></i><span> View Profile</span></a></li>
    </ul>
    <p class="text-center text-muted mt-auto"><small>Welcome, <?php echo htmlspecialchars($doctor['username']); ?></small></p>
</div>

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

    <    <!-- Top Row of Cards -->
    <div class="container-fluid px-0">
        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6"><div class="card text-center p-3 h-100"><h6 class="text-muted">Total Patients</h6><h2 class="text-primary"><?php echo htmlspecialchars($total_patients); ?></h2></div></div>
            <div class="col-xl-3 col-md-6"><div class="card text-center p-3 h-100"><h6 class="text-muted">New (7 Days)</h6><h2><?php echo htmlspecialchars($new_patients_7_days); ?></h2></div></div>
            <div class="col-xl-3 col-md-6"><div class="card text-center p-3 h-100"><h6 class="text-muted">Avg. CGM</h6><h2><?php echo htmlspecialchars($avg_cgm_level); ?></h2></div></div>
            <div class="col-xl-3 col-md-6">
                <div class="card calendar-card h-100">
                    <div class="calendar-header" id="currentMonthYear"></div>
                    <div class="calendar-grid" id="calendarDays"></div>
                    <div class="calendar-footer" id="malaysiaTime"></div>
                </div>
            </div>
        </div>
        
        <!-- Pie Chart -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Health Status Overview</h5>
                        <canvas id="healthStatusChart" style="max-height: 250px;"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Patient Summary Section -->
<div class="table-container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Patient Summary</h1>
        <div class="d-flex align-items-center gap-2">
            <input type="text" id="searchInput" placeholder="Search..." class="form-control" style="width: 250px;" onkeyup="searchFunction()">
            <!-- Sorting button can be added here if needed -->
        </div>
    </div>
    
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
                <h5 class="modal-title">Welcome Back!</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <h4>Hi, <strong><?php echo htmlspecialchars($doctor['username']); ?></strong>!</h4>
                <p>We're glad to have you back.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Got it!</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // All of the JavaScript functions (search, sort, chart, calendar, clock, modal) go here
    document.addEventListener('DOMContentLoaded', () => {
        AOS.init({ duration: 800, once: true });

        // --- Welcome Modal Logic ---
        <?php if (isset($_SESSION['show_welcome_modal'])): ?>
            const welcomeModal = new bootstrap.Modal(document.getElementById('welcomeModal'));
            welcomeModal.show();
            <?php unset($_SESSION['show_welcome_modal']); ?>
        <?php endif; ?>

        // --- Live Search ---
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            const tableRows = document.querySelectorAll('#patientTable tbody tr');
            searchInput.addEventListener('keyup', function() {
                const query = this.value.toLowerCase().trim();
                tableRows.forEach(row => {
                    if (row.cells.length > 1) {
                         row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
                    }
                });
            });
        }
        
        // --- Pie Chart ---
        const chartData = <?php echo json_encode($chart_data ?? [0,0,0,0]); ?>;
        const ctx = document.getElementById('healthStatusChart');
        if (ctx && chartData.some(v => v > 0)) {
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: ['Stable', 'Critical', 'Recovered', 'Warning'],
                    datasets: [{
                        label: 'Health Status', data: chartData,
                        backgroundColor: ['#03ff4f', '#FF4C4C', '#29B6F6', '#FFEB3B']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 14 } } } }
                }
            });
        }

        // --- Calendar & Clock ---
        const generateCalendar = ()=>{const a=new Date,b=new Date(a.toLocaleString("en-US",{timeZone:"Asia/Kuala_Lumpur"})),c=b.getFullYear(),d=b.getMonth(),e=b.getDate(),f=["January","February","March","April","May","June","July","August","September","October","November","December"],g=["Su","Mo","Tu","We","Th","Fr","Sa"];document.getElementById("currentMonthYear").textContent=`${f[d]} - ${c}`;const h=(new Date(c,d,1)).getDay(),i=(new Date(c,d+1,0)).getDate(),j=document.getElementById("calendarDays");j.innerHTML="",g.forEach(a=>{const b=document.createElement("div");b.style.fontWeight="bold",b.textContent=a,j.appendChild(b)});for(let a=0;a<h;a++)j.appendChild(document.createElement("div"));for(let a=1;a<=i;a++){const b=document.createElement("div");b.textContent=a,a===e&&b.classList.add("highlight"),j.appendChild(b)}};const updateTime=()=>{document.getElementById("malaysiaTime").textContent="Malaysia Time: "+new Date(new Date().toLocaleString("en-US",{timeZone:"Asia/Kuala_Lumpur"})).toLocaleTimeString()};generateCalendar(),updateTime(),setInterval(updateTime,1e3);
    });
</script>
</body>
</html>