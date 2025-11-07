<?php
// File: admin_doctor_profile.php (Final Version with Specialist)

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
$doctor = null;
$doctor_id = filter_input(INPUT_GET, 'doctor_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$doctor_id) {
    set_flash_message('Invalid doctor ID.', 'danger');
    redirect('admin_doctor_details.php');
}

$conn = get_db_connection();
if (!$conn) {
    set_flash_message('Database connection failed.', 'danger');
    redirect('admin_doctor_details.php');
} else {
    // --- MODIFIED QUERY: Added 'specialist' to the SELECT statement ---
    $stmt = $conn->prepare(
        "SELECT user_id, username, email, photo, status, created_at, specialist 
         FROM users WHERE user_id = ? AND role = 'doctor'"
    );
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $doctor = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();

    if (!$doctor) {
        set_flash_message('Doctor profile not found.', 'warning');
        redirect('admin_doctor_details.php');
    } else {
        $photo = $doctor['photo'];
        if (!empty($photo) && file_exists(UPLOAD_FOLDER . $photo)) {
            $doctor['photo_url'] = 'static/uploads/' . $photo;
        } else {
            $doctor['photo_url'] = 'https://img.freepik.com/premium-vector/doctor-vector-illustrations-white-background_1062857-258.jpg';
        }
        $doctor['joined_date'] = !empty($doctor['created_at']) ? date('d M Y', strtotime($doctor['created_at'])) : '-';
    }
}

/**
 * PRESENTATION (HTML)
 */
$pageTitle = "Doctor Profile";
include 'templates/header.php';
include 'templates/sidebar_admin.php';
?>

<!-- Main Content -->
<div class="profile-page-container">
    <div class="text-start w-100" style="max-width: 400px; margin-bottom: 1rem;">
        <h3 class="page-title">Doctor Profile</h3>
    </div>

    <div class="profile-card" data-aos="fade-up">
        <?php if ($doctor): ?>
            <div class="profile-card-body">
                <div class="profile-avatar-wrapper">
                    <img src="<?php echo htmlspecialchars($doctor['photo_url']); ?>" alt="Profile Picture">
                </div>

                <h2 class="doctor-name">Dr. <?php echo htmlspecialchars($doctor['username']); ?></h2>
                
                <!-- MODIFIED: Added the specialist field right below the name -->
                <p class="doctor-specialist text-muted">
                    <?php echo htmlspecialchars($doctor['specialist'] ?? 'General Practitioner'); ?>
                </p>

                <div class="status-badge <?php echo ($doctor['status'] == 1) ? 'status-active' : 'status-inactive'; ?>">
                    <?php echo ($doctor['status'] == 1) ? 'Active' : 'Inactive'; ?>
                </div>
                
                <div class="info-list">
                    <p>
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo htmlspecialchars($doctor['email']); ?></span>
                    </p>
                    <p>
                        <span class="info-label">Joined:</span>
                        <span class="info-value"><?php echo htmlspecialchars($doctor['joined_date']); ?></span>
                    </p>
                </div>

                <a href="admin_patient_list.php?doctor_id=<?php echo htmlspecialchars($doctor['user_id']); ?>" class="btn btn-primary btn-view-patients">
                    <i class="fas fa-users me-2"></i> View Patient List
                </a>
            </div>
        <?php else: ?>
            <div class="alert alert-danger m-4">Could not load doctor profile.</div>
        <?php endif; ?>
    </div>
    <div class="text-center mt-4" data-aos="fade-up" data-aos-delay="100">
        <a href="admin_doctor_details.php" class="btn btn-light shadow-sm">← Back to Doctor List</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<?php
include 'templates/footer.php'; 
?>