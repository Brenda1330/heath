<?php
// File: admin_doctor_details.php

declare(strict_types=1);
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once 'functions.php';
secure_session_start();
add_security_headers();
require_admin();

$doctors = [];
$conn = get_db_connection();
if (!$conn) {
    set_flash_message('Database connection failed. Could not load doctor details.', 'danger');
} else {
    $sql = "SELECT user_id, username, email, photo, status FROM users WHERE role = 'doctor' ORDER BY username ASC";
    $result = $conn->query($sql);
    if ($result) {
        $doctors_raw = $result->fetch_all(MYSQLI_ASSOC);
        $default_image_url = 'https://img.freepik.com/premium-vector/doctor-vector-illustrations-white-background_1062857-258.jpg';
        foreach ($doctors_raw as $doc) {
            $photo_value = $doc['photo'] ?? null;
            if (filter_var($photo_value, FILTER_VALIDATE_URL)) {
                $doc['photo_url'] = $photo_value;
            } else {
                $local_file_path = 'static/uploads/' . $photo_value;
                if (!empty($photo_value) && file_exists($local_file_path)) {
                    $doc['photo_url'] = $local_file_path;
                } else {
                    $doc['photo_url'] = $default_image_url;
                }
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

<!-- Main Content -->
<div class="main">
    <h4><strong>Doctor Details</strong></h4>
    <?php display_flash_messages(); ?>
    <div class="search-container">
      <input type="text" id="searchInput" placeholder="Search by name or email..." onkeyup="searchDoctor()" />
    </div>

    <div id="doctorList">
      <?php if (!empty($doctors)): ?>
        <?php foreach ($doctors as $doctor): ?>
          <!-- FIX 11: Make Entire Doctor Card Clickable -->
          <a href="admin_doctor_profile.php?doctor_id=<?php echo htmlspecialchars((string)$doctor['user_id']); ?>" class="doctor-card-link">
            <div class="doctor-card" data-aos="fade-up" data-aos-duration="800">
              <img src="<?php echo htmlspecialchars($doctor['photo_url']); ?>" alt="Dr. <?php echo htmlspecialchars($doctor['username']); ?>">
              <div class="doctor-info">
                <strong class="doctor-name"><?php echo htmlspecialchars($doctor['username']); ?></strong>
                <p class="doctor-email">Email: <?php echo htmlspecialchars($doctor['email']); ?></p>
                <p>Availability:
                  <span class="status-badge <?php echo $doctor['status'] ? 'active' : 'inactive'; ?>">
                    <?php echo $doctor['status'] ? 'Active' : 'Inactive'; ?>
                  </span>
                </p>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No doctor records found in the database.</p>
      <?php endif; ?>
    </div>
</div>

<!-- Floating Button -->
<!-- FIX 16: Add Helper Text / Tooltips -->
<a href="admin_add_doctor.php" class="fab" data-bs-toggle="tooltip" data-bs-placement="left" title="Add New Doctor">
    <i class="fas fa-plus"></i>
</a>

<!-- Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/js/bootstrap.bundle.min.js"></script>

<style>
    .doctor-card-link {
        text-decoration: none;
        color: inherit;
    }
    .doctor-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
</style>

<script>
    AOS.init();

    // FIX 16: Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // FIX 10 & 17: Enhance and Standardize Search Function
    function searchDoctor() {
      const query = document.getElementById('searchInput').value.toLowerCase().trim();
      document.querySelectorAll('.doctor-card-link').forEach(cardLink => {
        const card = cardLink.querySelector('.doctor-card');
        const name = card.querySelector('.doctor-name').innerText.toLowerCase();
        const email = card.querySelector('.doctor-email').innerText.toLowerCase();
        
        // Show card if name OR email includes the query
        if (name.includes(query) || email.includes(query)) {
          cardLink.style.display = 'block';
        } else {
          cardLink.style.display = 'none';
        }
      });
    }
</script>

<?php
include 'templates/footer.php';
?>