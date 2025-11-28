<?php
// File: doc_userprofile.php

/**
 * CORE INCLUSION & AUTHORIZATION
 */
require_once 'functions.php';
secure_session_start();
add_security_headers();
require_doctor();

/**
 * DATA FETCHING LOGIC
 * (This PHP block is unchanged)
 */
$doctor = null;
$doctor_id = $_SESSION['user_id'];

$conn = get_db_connection();
if (!$conn) {
    set_flash_message('Database connection failed. Cannot load profile.', 'danger');
    redirect('doc_dashboard.php');
} else {
    $stmt = $conn->prepare("SELECT username, email, photo, status, phone_number, specialist FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $doctor = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();

    if (!$doctor) {
        set_flash_message('Could not find your profile information.', 'danger');
        redirect('doc_dashboard.php');
    } else {
        $photo = $doctor['photo'];
        if (!empty($photo) && file_exists(UPLOAD_FOLDER . $photo)) {
            $doctor['photo_url'] = 'uploads/' . $photo;
        } else {
            $doctor['photo_url'] = 'https://cdn-icons-png.flaticon.com/512/147/147144.png';
        }
    }
}

/**
 * PRESENTATION (HTML)
 */
$pageTitle = "My Profile";
include 'templates/header_doctor.php'; 
include 'templates/sidebar_doctor.php';
?>

<!-- Main Content -->
<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title mb-0">My Profile</h1>
        <div class="profile-dropdown">
            <div class="dropdown">
                <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user-circle"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="doc_userprofile.php">View Profile</a></li>
                    <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </div>

    <?php if ($doctor): ?>
    <div class="profile-card" data-aos="fade-up">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar-wrapper">
                <img class="profile-avatar" src="<?php echo htmlspecialchars($doctor['photo_url']); ?>" alt="Profile Picture">
            </div>
            <div class="profile-header-text">
                <h2 class="profile-name"><?php echo htmlspecialchars($doctor['username']); ?></h2>
                <span class="status-badge <?php echo ($doctor['status'] == 1) ? 'status-active' : 'status-inactive'; ?>">
                    <i class="fas fa-check-circle me-1"></i> <?php echo ($doctor['status'] == 1) ? 'Active' : 'Inactive'; ?>
                </span>
            </div>
        </div>

        <!-- Profile Body with Details -->
        <div class="profile-body">
            <div class="row g-4">
                <div class="col-md-6" data-aos="fade-up" data-aos-delay="100">
                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-envelope"></i></div>
                        <div class="info-text">
                            <span class="info-label">Email Address</span>
                            <span class="info-value"><?php echo htmlspecialchars($doctor['email']); ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6" data-aos="fade-up" data-aos-delay="200">
                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-phone"></i></div>
                        <div class="info-text">
                            <span class="info-label">Phone Number</span>
                            <span class="info-value"><?php echo htmlspecialchars($doctor['phone_number'] ?? 'Not Provided'); ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6" data-aos="fade-up" data-aos-delay="300">
                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-stethoscope"></i></div>
                        <div class="info-text">
                            <span class="info-label">Specialist</span>
                            <span class="info-value"><?php echo htmlspecialchars($doctor['specialist'] ?? 'Not Specified'); ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6" data-aos="fade-up" data-aos-delay="400">
                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-user-tag"></i></div>
                        <div class="info-text">
                            <span class="info-label">Role</span>
                            <span class="info-value">Doctor</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
        <div class="alert alert-warning">Could not load profile data.</div>
    <?php endif; ?>
</div>

<?php 
include 'templates/footer_doctor.php';
?>