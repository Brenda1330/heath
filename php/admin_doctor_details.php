<?php
// File: admin_doctor_details.php

/**
 * -----------------------------------------------------------------------------
 * STRICT TYPING & ERROR REPORTING
 * -----------------------------------------------------------------------------
 * Enforces strict type checking and sets up error reporting for development.
 * For production, it's recommended to log errors and not display them.
 */
declare(strict_types=1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

/**
 * -----------------------------------------------------------------------------
 * CORE INCLUSION & AUTHORIZATION
 * -----------------------------------------------------------------------------
 * Initializes the application and ensures only authorized admins can access.
 */
require_once 'functions.php';
secure_session_start();
add_security_headers(); // Includes Content-Security-Policy and other security headers
require_admin(); // Gatekeeper: Halts if user is not a logged-in admin.

/**
 * -----------------------------------------------------------------------------
 * DATA FETCHING LOGIC
 * -----------------------------------------------------------------------------
 * Fetches all users with the 'doctor' role from the database.
 */
$doctors = [];
$conn = get_db_connection();
if (!$conn) {
    // Set a flash message if the DB connection fails
    set_flash_message('Database connection failed. Could not load doctor details.', 'danger');
} else {
    // This query is safe as it doesn't use any external input.
    // For queries with user input, prepared statements should be used to prevent SQL injection.
    $sql = "SELECT user_id, username, email, photo, status FROM users WHERE role = 'doctor' ORDER BY username ASC";
    $result = $conn->query($sql);
    if ($result) {
        $doctors_raw = $result->fetch_all(MYSQLI_ASSOC);
        
        // Process the raw data to create safe, usable photo URLs
        foreach ($doctors_raw as $doc) {
            $photo = $doc['photo'];
            $upload_path = 'uploads/' . $photo;
            if (!empty($photo) && file_exists($upload_path)) {
                // If the photo exists locally, create a relative path to it
                $doc['photo_url'] = $upload_path;
            } else {
                // Otherwise, provide a default placeholder image
                $doc['photo_url'] = 'https://via.placeholder.com/80/5f9eff/ffffff?text=' . strtoupper(substr($doc['username'], 0, 1));
            }
            $doctors[] = $doc;
        }
    } else {
        set_flash_message('Failed to retrieve doctor records from the database.', 'danger');
    }
    $conn->close();
}

$pageTitle = "Doctor Details";
include 'templates/header.php';
include 'templates/sidebar_admin.php';
?>

  <!-- Profile Dropdown (Top Right) -->
  <div class="profile-dropdown position-absolute" style="top: 20px; right: 30px;">
    <div class="dropdown">
      <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fas fa-user-circle"></i>
      </button>
      <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
      </ul>
    </div>
  </div>

  <!-- Main Content -->
  <div class="main">
    <h4><strong>Doctor Details</strong></h4>

    <?php display_flash_messages(); ?>

    <div class="search-container">
      <input type="text" id="searchInput" placeholder="Search..." onkeyup="searchDoctor()" />
    </div>

    <div id="doctorList">
      <?php if (!empty($doctors)): ?>
        <?php foreach ($doctors as $doctor): ?>
          <div class="doctor-card" data-aos="fade-up" data-aos-duration="800">
            <img src="<?php echo htmlspecialchars($doctor['photo_url'], ENT_QUOTES, 'UTF-8'); ?>" alt="Dr. <?php echo htmlspecialchars($doctor['username'], ENT_QUOTES, 'UTF-8'); ?>">
            <div class="doctor-info">
              <a href="admin_doctor_profile.php?doctor_id=<?php echo htmlspecialchars((string)$doctor['user_id'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($doctor['username'], ENT_QUOTES, 'UTF-8'); ?></a>
              <p>Email: <?php echo htmlspecialchars($doctor['email'], ENT_QUOTES, 'UTF-8'); ?></p>
              <p>Availability:
                <span class="status-badge <?php echo $doctor['status'] ? 'active' : 'inactive'; ?>">
                  <?php echo $doctor['status'] ? 'Active' : 'Inactive'; ?>
                </span>
              </p>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No doctor records found in the database.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Floating Button -->
  <a href="admin_add_doctor.php" class="fab"><i class="fas fa-plus"></i></a>

  <!-- Scripts -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    AOS.init();

    function searchDoctor() {
      const q = document.getElementById('searchInput').value.toLowerCase();
      document.querySelectorAll('.doctor-card').forEach(card => {
        const name = card.querySelector('.doctor-info a').innerText.toLowerCase();
        card.style.display = name.includes(q) ? 'flex' : 'none';
      });
    }
  </script>

  <?php
include 'templates/footer.php';
?>