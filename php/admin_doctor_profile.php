<?php
// File: admin_doctor_profile.php

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
    set_flash_message('Invalid doctor ID provided.', 'danger');
    redirect('admin_doctor_details.php');
}

$conn = get_db_connection();
if (!$conn) {
    set_flash_message('Database connection failed.', 'danger');
    redirect('admin_doctor_details.php');
} else {
    $stmt = $conn->prepare("SELECT user_id, username, email, photo, status, created_at FROM users WHERE user_id = ? AND role = 'doctor'");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $doctor = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();

    if (!$doctor) {
        set_flash_message('No doctor profile found for this ID.', 'warning');
        redirect('admin_doctor_details.php');
    } else {
        $photo = $doctor['photo'];
        if (!empty($photo) && file_exists(UPLOAD_FOLDER . $photo)) {
            $doctor['photo_url'] = 'uploads/' . $photo;
        } else {
            // Using a default avatar image that matches the style
            $doctor['photo_url'] = 'https://cdn-icons-png.flaticon.com/512/147/147144.png'; 
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
<div class="main-content">
  <h3 class="page-title">Doctor Profile</h3>

  <?php display_flash_messages(); ?>

  <?php if ($doctor): ?>
  <!-- MODIFIED CARD STRUCTURE TO MATCH THE IMAGE -->
  <div class="profile-card">
    <div class="profile-card-header">
      <img src="<?php echo htmlspecialchars($doctor['photo_url']); ?>" alt="Profile Picture">
      <div class="profile-name-status">
        <h4 class="doctor-name">Dr. <?php echo htmlspecialchars($doctor['username']); ?></h4>
        <span class="status-badge <?php echo ($doctor['status'] == 1) ? 'status-active' : 'status-inactive'; ?>">
          <?php echo ($doctor['status'] == 1) ? 'Active' : 'Inactive'; ?>
        </span>
      </div>
    </div>
    <div class="profile-card-body">
      <p class="info-item">
        <span class="info-label">Email:</span>
        <span class="info-value"><?php echo htmlspecialchars($doctor['email']); ?></span>
      </p>
      <p class="info-item">
        <span class="info-label">Joined:</span>
        <span class="info-value"><?php echo htmlspecialchars($doctor['joined_date']); ?></span>
      </p>
    </div>
    <div class="profile-card-footer">
      <a href="admin_patient_list.php?doctor_id=<?php echo htmlspecialchars($doctor['user_id']); ?>" class="btn btn-primary btn-view-patients">
        <i class="fas fa-users me-2"></i>View Patient List
      </a>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php
// Use the simplified footer, as no special scripts are needed for this page
include 'templates/footer.php'; 
?>