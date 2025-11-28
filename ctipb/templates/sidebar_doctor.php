<?php
// File: templates/sidebar_doctor.php

// Get the current page's filename (e.g., "doc_dashboard.php")
$currentPage = basename($_SERVER['SCRIPT_NAME']); 

// Define which pages are under Patient Tools
$patientToolsPages = ['doc_importdata.php', 'doc_recommendation.php'];
$isPatientToolsPage = in_array($currentPage, $patientToolsPages);
?>
<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header text-center">
        <h4><i class="fas fa-stethoscope me-2"></i> Doctor Panel</h4>
    </div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a href="doc_dashboard.php" class="nav-link <?php echo ($currentPage === 'doc_dashboard.php') ? 'active' : ''; ?>">
                <i class="fas fa-th-large me-2"></i><span> Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="doc_addpatient.php" class="nav-link <?php echo ($currentPage === 'doc_addpatient.php') ? 'active' : ''; ?>">
                <i class="fas fa-user-plus me-2"></i><span> Add Patient</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="doc_patientlist.php" class="nav-link <?php echo ($currentPage === 'doc_patientlist.php') ? 'active' : ''; ?>">
                <i class="fas fa-users"></i><span> Patient List</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="#patientSubmenu" class="nav-link <?php echo $isPatientToolsPage ? 'active' : ''; ?>" data-bs-toggle="collapse">
                <i class="fas fa-syringe"></i><span>Patient Tools </span><i class="fas fa-chevron-down"></i>
            </a>
        </li>
        <div class="collapse <?php echo $isPatientToolsPage ? 'show' : ''; ?>" id="patientSubmenu">
            <ul class="nav flex-column ps-3">
                <li class="nav-item">
                    <a href="doc_importdata.php" class="nav-link <?php echo ($currentPage === 'doc_importdata.php') ? 'active' : ''; ?>">
                        <i class="fas fa-cloud-upload-alt"></i><span> Enter Data</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="doc_recommendation.php" class="nav-link <?php echo ($currentPage === 'doc_recommendation.php') ? 'active' : ''; ?>">
                        <i class="fas fa-bolt"></i><span> Recommendation</span>
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- ADDED: Risk Monitor Link -->
        <li class="nav-item">
            <a href="doc_risk_monitor.php" class="nav-link <?php echo ($currentPage === 'doc_risk_monitor.php') ? 'active' : ''; ?>">
                <i class="fas fa-shield-alt me-2"></i><span> Risk Monitor</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="doc_userprofile.php" class="nav-link <?php echo ($currentPage === 'doc_userprofile.php') ? 'active' : ''; ?>">
                <i class="fas fa-user-circle"></i><span> View Profile</span>
            </a>
        </li>
    </ul>
</div>