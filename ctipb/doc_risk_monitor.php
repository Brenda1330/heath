<?php
// File: doc_risk_monitor.php (TRUTHFUL VERSION)

require_once 'functions.php';
secure_session_start();
add_security_headers();
require_doctor();

$pageTitle = "Clinical Risk Monitoring Dashboard";
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

<div class="main-content">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title">Clinical Risk Monitoring Dashboard</h1>
        <p class="text-muted mb-0">Rule-based patient risk assessment and pattern monitoring</p> <!-- Added "pattern" -->
    </div>
    <div class="text-end">
        <span class="badge bg-secondary fs-6">Rule-Based Analysis</span>
        <small class="d-block text-muted">Last updated: 
            <?php 
                // Create a DateTime object for "now" in the Malaysia timezone
                $now = new DateTime("now", new DateTimeZone('Asia/Kuala_Lumpur'));
                echo $now->format('M j, Y g:i A'); 
            ?>
        </small>
    </div>
</div>

    <!-- System Overview Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-light border-0">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h5 class="card-title mb-2"><i class="fas fa-info-circle me-2 text-primary"></i>How This Dashboard Works</h5>
                            <p class="card-text mb-0">
                                This dashboard applies predefined clinical rules and statistical analysis to identify patient risks. 
                                It monitors patterns in glucose levels, activity data, and meal information to flag concerning trends.
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="border rounded p-2 bg-white">
                                        <i class="fas fa-ruler fa-2x text-primary mb-2"></i>
                                        <div class="fw-bold">Rules</div>
                                        <small class="text-muted">Clinical Logic</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-2 bg-white">
                                        <i class="fas fa-chart-bar fa-2x text-success mb-2"></i>
                                        <div class="fw-bold">Stats</div>
                                        <small class="text-muted">Trend Analysis</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-2 bg-white">
                                        <i class="fas fa-shapes fa-2x text-warning mb-2"></i>
                                        <div class="fw-bold">Patterns</div>
                                        <small class="text-muted">Behavioral Trends</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Patient Risk Profiles -->
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0"><i class="fas fa-id-card-alt me-2"></i>Patient Risk Profiles</h5>
                        <small class="opacity-75">Classification based on behavioral and metabolic patterns</small>
                    </div>
                    <button class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#archetypeHelp">
                        <i class="fas fa-info-circle"></i>
                    </button>
                </div>
                <div class="collapse" id="archetypeHelp">
                    <div class="card-body bg-light">
                        <h6><i class="fas fa-lightbulb me-2 text-warning"></i>How Risk Profile Detection Works</h6>
                        <p class="mb-2">This dashboard classifies patients based on predefined clinical criteria:</p> 
                        <ul class="mb-0 small">
                            <li><strong>Sedentary & High-Spike:</strong> Average CGM > 10.0 mmol/L AND activity recorded in less than 30% of entries</li>
                            <li><strong>Diet-Driven Spikes:</strong> Average CGM > 9.0 mmol/L AND high-carb meals in over 60% of food records</li>
                        </ul>
                        <p class="mt-2 mb-0 small text-muted"><strong>Note:</strong> Analysis of <?php echo date('F Y'); ?> patient data</p> 
                    </div>
                </div>
                <div id="archetype-container" class="card-body">
                    <div class="text-center p-4">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted">Analyzing patient patterns...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Behavioral Pattern Alerts -->
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0"><i class="fas fa-bell me-2"></i>Behavioral Pattern Alerts</h5>
                        <small class="opacity-75">Detection of significant lifestyle changes and patterns</small>
                    </div>
                    <button class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#behavioralHelp">
                        <i class="fas fa-info-circle"></i>
                    </button>
                </div>
                <div class="collapse" id="behavioralHelp">
                    <div class="card-body bg-light">
                        <h6><i class="fas fa-lightbulb me-2 text-warning"></i>Pattern Detection Logic</h6>
                        <p class="mb-2">This dashboard monitors for specific concerning patterns using these rules:</p> 
                        <ul class="mb-0 small">
                            <li><strong>Activity Decline:</strong> 7-day activity count drops below 50% of 30-day weekly average</li>
                            <li><strong>Negative Food-Activity Patterns:</strong> High-carb meal + no activity + glucose spike (>10.0 mmol/L)</li>
                        </ul>
                        <p class="mt-2 mb-0 small text-muted"><strong>Data source:</strong> Direct analysis of patient health records</p> 
                    </div>
                </div>
                <div id="anomaly-alerts-container" class="card-body">
                    <div class="text-center p-4">
                        <div class="spinner-border text-info" role="status"></div>
                        <p class="mt-2 text-muted">Analyzing recent patient patterns...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- High-Risk Patients Card -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>High-Risk Patients</h5>
                        <small class="opacity-75">Patients with concerning recent patterns</small>
                    </div>
                    <button class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#highRiskHelp">
                        <i class="fas fa-info-circle"></i>
                    </button>
                </div>
                <div class="collapse" id="highRiskHelp">
                    <div class="card-body bg-light">
                        <h6><i class="fas fa-lightbulb me-2 text-warning"></i>High-Risk Detection Logic</h6>
                        <p class="mb-2 small">This dashboard flags patients as high-risk based on recent 7-day patterns:</p>
                        <ul class="mb-0 small">
                            <li><strong>Elevated Glucose:</strong> Average CGM > 10.0 mmol/L in last 7 days</li>
                            <li><strong>Low Activity:</strong> Physical activity in less than 30% of recent entries</li>
                            <li><strong>High-Carb Patterns:</strong> Multiple high-carb meals without activity</li>
                            <li><strong>Spike Patterns:</strong> High glucose readings following specific meals</li>
                        </ul>
                        <p class="mt-2 mb-0 small text-muted"><strong>Note:</strong> Recent positive behaviors (like walking) don't automatically remove patients from high-risk status if concerning patterns exist in their recent history.</p>
                    </div>
                </div>
                <div id="high-risk-list-container" class="card-body">
                    <div class="text-center p-4">
                        <div class="spinner-border text-danger" role="status"></div>
                    </div>
                </div>
            </div>
        </div>  

        <!-- Clinically Significant Readings -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0"><i class="fas fa-search-location me-2"></i>Clinically Significant Readings</h5>
                        <small class="opacity-75">Extreme glucose values requiring attention</small>
                    </div>
                    <button class="btn btn-sm btn-dark" data-bs-toggle="collapse" data-bs-target="#readingsHelp">
                        <i class="fas fa-info-circle"></i>
                    </button>
                </div>
                <div class="collapse" id="readingsHelp">
                <div class="card-body bg-light">
                    <h6><i class="fas fa-lightbulb me-2 text-warning"></i>Clinical Thresholds</h6>
                    <p class="mb-2 small">This dashboard flags readings that exceed established clinical thresholds:</p> <!-- Added "This dashboard flags" -->
                    <ul class="mb-0 small">
                        <li><strong>Hyperglycemia:</strong> CGM readings > 14.0 mmol/L</li>
                        <li><strong>Hypoglycemia:</strong> CGM readings < 4.0 mmol/L</li>
                        <li><strong>Statistical outliers:</strong> Values significantly deviating from patient's personal baseline</li>
                    </ul>
                    <p class="mt-2 mb-0 small text-muted"><strong>Method:</strong> Threshold-based detection with individual baseline comparison</p> <!-- Good as is -->
                </div>
            </div>
                <div id="anomaly-list-container" class="card-body">
                    <div class="text-center p-4">
                        <div class="spinner-border text-warning" role="status"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Capabilities & Methodology -->
        <div class="col-12">
            <div class="card border-0 bg-light">
                <div class="card-body">
                    <h5 class="card-title mb-3"><i class="fas fa-microscope me-2 text-primary"></i>Dashboard Capabilities & Methodology</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="p-3 bg-white rounded border h-100"> <!-- Changed from h-50 to h-100 -->
                                <h6 class="fw-bold text-success mb-3"><i class="fas fa-check-circle me-2"></i>What This Dashboard Does</h6>
                                <ul class="small mb-0">
                                    <li class="mb-2">Applies predefined clinical rules to patient data analysis</li> 
                                    <li class="mb-2">Monitors activity patterns and identifies significant declines</li> 
                                    <li class="mb-2">Correlates meal composition with glucose responses</li> 
                                    <li class="mb-2">Flags extreme glucose values using clinical thresholds</li>
                                    <li class="mb-2">Classifies patients based on behavioral and metabolic patterns</li> 
                                    <li>Provides trend analysis over 7 to 30-day periods</li>
                                </ul>
                            </div>
                        </div> <!-- Added missing closing div -->
                        <div class="col-md-6 mb-3">
                            <div class="p-3 bg-white rounded border h-100">
                                <h6 class="fw-bold text-info mb-3"><i class="fas fa-info-circle me-2"></i>Current Limitations</h6>
                                <ul class="small mb-0">
                                    <li class="mb-2">Rule-based analysis</li>
                                    <li class="mb-2">Limited to available data fields in health_data table</li>
                                    <li class="mb-2">Does not predict future events, it detects current patterns</li>
                                    <li class="mb-2">Analysis based on manual clinical threshold setting</li>
                                    <li class="mb-2">No integration with advanced graph neural networks</li>
                                    <li>Requires manual review for clinical decision making</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer_doctor_scripts.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Element selections
    const highRiskContainer = document.getElementById('high-risk-list-container');
    const anomalyContainer = document.getElementById('anomaly-list-container');
    const anomalyAlertsContainer = document.getElementById('anomaly-alerts-container');
    const archetypeContainer = document.getElementById('archetype-container');
    
    // --- Fetch Patient Risk Profiles ---
    fetch('api/api_get_patient_archetypes.php')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                archetypeContainer.innerHTML = `
                    <div class="alert alert-danger m-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        ${data.error}
                    </div>`;
                return;
            }

            if (data.at_risk_patients && data.at_risk_patients.length > 0) {
                let html = '<div class="row g-3">';
                data.at_risk_patients.forEach(patient => {
                    const color = patient.archetype === 'Sedentary & High-Spike' ? 'danger' : 'warning';
                    const icon = patient.archetype === 'Sedentary & High-Spike' ? 'fa-bed' : 'fa-pizza-slice';
                    const riskLevel = patient.archetype === 'Sedentary & High-Spike' ? 'High Risk' : 'Moderate Risk';
                    
                    html += `
                        <div class="col-lg-6">
                            <div class="card border h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <span class="badge bg-${color} mb-2">${riskLevel}</span>
                                            <h6 class="card-title mb-1">${patient.full_name}</h6>
                                        </div>
                                        <i class="fas ${icon} text-${color} fa-lg"></i>
                                    </div>
                                    <p class="card-text small text-muted mb-2">${patient.insight}</p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">Identified via pattern analysis</small>
                                        <a href="doc_patientdetail.php?patient_id=${patient.patient_id}" class="btn btn-sm btn-outline-${color}">
                                            Review <i class="fas fa-arrow-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                archetypeContainer.innerHTML = html;
            } else {
                archetypeContainer.innerHTML = `
                    <div class="text-center p-5">
                        <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                        <h5 class="text-success">No High-Risk Patterns Detected</h5>
                        <p class="text-muted">Current patient data shows balanced behavioral patterns.</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            archetypeContainer.innerHTML = `
                <div class="alert alert-danger m-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Unable to load risk profile analysis.
                </div>`;
            console.error('Risk Profile Fetch Error:', error);
        });

    // --- Fetch Behavioral Pattern Alerts ---
    fetch('api/api_get_anomaly_alerts.php')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                anomalyAlertsContainer.innerHTML = `
                    <div class="alert alert-danger m-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        ${data.error}
                    </div>`;
                return;
            }
            
            if (data.anomaly_alerts && data.anomaly_alerts.length > 0) {
                let html = '<div class="list-group list-group-flush">';
                data.anomaly_alerts.forEach(alert => {
                    const icon = alert.anomaly_type.includes('Activity') ? 'fa-walking' : 'fa-utensils';
                    const color = alert.anomaly_type.includes('Negative Pattern') ? 'danger' : 'warning';
                    const urgency = alert.anomaly_type.includes('Negative Pattern') ? 'High Priority' : 'Monitor';
                    
                    html += `
                        <div class="list-group-item border-0 px-0">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0">
                                    <span class="badge bg-${color} p-2">
                                        <i class="fas ${icon}"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <h6 class="mb-1">${alert.full_name}</h6>
                                        <small class="badge bg-${color === 'danger' ? 'danger' : 'warning'}">${urgency}</small>
                                    </div>
                                    <p class="mb-1">${alert.message}</p>
                                    <small class="text-muted">Detected via rule-based pattern analysis</small>
                                </div>
                                <a href="doc_patientdetail.php?patient_id=${alert.patient_id}" class="btn btn-sm btn-outline-secondary ms-3">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                anomalyAlertsContainer.innerHTML = html;
            } else {
                anomalyAlertsContainer.innerHTML = `
                    <div class="text-center p-5">
                        <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                        <h5 class="text-success">No Behavioral Alerts</h5>
                        <p class="text-muted">No significant pattern deviations detected in recent data.</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            anomalyAlertsContainer.innerHTML = `
                <div class="alert alert-danger m-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Unable to load behavioral pattern analysis.
                </div>`;
            console.error('Behavioral Alerts Fetch Error:', error);
        });

    // --- Combined fetch for high-risk and clinical readings ---
Promise.all([
    fetch('api/api_get_watchlist_data.php').then(r => r.json()),
])
.then(([watchlistData]) => {
    // Handle high-risk patients with BETTER EXPLANATIONS
    if (watchlistData.high_risk_patients && watchlistData.high_risk_patients.length > 0) {
        let html = `
            <div class="alert alert-warning alert-dismissible fade show mb-3" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Note:</strong> Patients are flagged based on recent patterns (7-day analysis). Recent positive behaviors don't automatically remove patients if concerning patterns exist in their history.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <div class="list-group list-group-flush">
        `;
        
        watchlistData.high_risk_patients.forEach((p, index) => {
            const rank = index + 1;
            
            html += `
                <div class="list-group-item border-0 px-0">
                    <div class="d-flex align-items-start">
                        <div class="flex-shrink-0">
                            <span class="badge bg-danger rounded-circle p-2">${rank}</span>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <h6 class="mb-1 text-danger">${p.full_name}</h6>
                                <span class="badge bg-danger">Review Recommended</span>
                            </div>
                            <p class="mb-1 small">${p.explanation}</p>
                            <div class="small text-muted">
                                <span class="me-3"><i class="fas fa-utensils me-1"></i>${p.last_food || 'No recent meals'}</span>
                                <span><i class="fas fa-running me-1"></i>${p.last_activity || 'No recent activity'}</span>
                            </div>
                            ${p.avg_glucose_7d ? `<small class="text-warning"><i class="fas fa-chart-line me-1"></i>7-day avg: ${p.avg_glucose_7d} mmol/L</small>` : ''}
                        </div>
                        <a href="doc_patientdetail.php?patient_id=${p.patient_id}" class="btn btn-sm btn-outline-danger ms-3" title="Review patient details">
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        highRiskContainer.innerHTML = html;
    } else {
        highRiskContainer.innerHTML = `
            <div class="text-center p-5">
                <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                <h5 class="text-success">No High-Risk Patients</h5>
                <p class="text-muted">No patients currently meet high-risk criteria based on recent patterns.</p>
            </div>
        `;
    }
    
    // Handle clinical readings (unchanged)
    if (anomalyContainer && watchlistData.outlier_events && watchlistData.outlier_events.length > 0) {
        let html = '<div class="list-group list-group-flush">';
        watchlistData.outlier_events.forEach(r => {
            const isHyper = r.cgm_level > 14.0;
            const eventType = isHyper ? 'Hyperglycemia' : 'Hypoglycemia';
            const badgeColor = isHyper ? 'danger' : 'primary';
            const icon = isHyper ? 'fa-arrow-up' : 'fa-arrow-down';
            
            html += `
                <div class="list-group-item border-0 px-0">
                    <div class="d-flex align-items-start">
                        <div class="flex-shrink-0">
                            <span class="badge bg-${badgeColor} p-2">
                                <i class="fas ${icon}"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <h6 class="mb-1">${r.full_name}</h6>
                                <span class="badge bg-${badgeColor}">${eventType}</span>
                            </div>
                            <p class="mb-1">CGM reading of <strong>${r.cgm_level} mmol/L</strong> detected</p>
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>${r.timestamp} | 
                                ${r.historical_avg_cgm ? `Baseline: ${r.historical_avg_cgm} mmol/L` : 'Exceeds clinical thresholds'}
                            </small>
                        </div>
                        <a href="doc_patientdetail.php?patient_id=${r.patient_id}" class="btn btn-sm btn-outline-${badgeColor} ms-3">
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                    </div>
                </div>`;
        });
        html += '</div>';
        anomalyContainer.innerHTML = html;
    } else if (anomalyContainer) {
        anomalyContainer.innerHTML = `
            <div class="text-center p-5">
                <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                <h5 class="text-success">No Critical Readings</h5>
                <p class="text-muted">No extreme glucose values detected in recent data.</p>
            </div>
        `;
    }
})
.catch(error => {
    const errorHtml = `
        <div class="alert alert-danger m-3">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Unable to load monitoring data.
        </div>`;
    highRiskContainer.innerHTML = errorHtml;
    if (anomalyContainer) anomalyContainer.innerHTML = errorHtml;
    console.error('Monitoring Data Fetch Error:', error);
});
});
</script>
