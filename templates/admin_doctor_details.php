<?php
// doctor_details.php
// Secure Doctor Details listing (prepared for OWASP ZAP scanning).
// Update DB credentials and $UPLOAD_DIR as needed.

declare(strict_types=1);

// -------------------------
// Config
// -------------------------
$DB_DSN = 'mysql:host=localhost;dbname=your_db;charset=utf8mb4';
$DB_USER = 'localhost';
$DB_PASS = 'password';

// Where photo files are stored (RECOMMENDATION: outside webroot)
$UPLOAD_DIR = __DIR__ . '/../uploads/doctor_photos';

// -------------------------
// Session & secure cookie params (set BEFORE session_start)
// -------------------------
$cookieParams = session_get_cookie_params();
session_set_cookie_params([
    'lifetime' => $cookieParams['lifetime'] ?? 0,
    'path' => $cookieParams['path'] ?? '/',
    'domain' => $cookieParams['domain'] ?? '',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

// -------------------------
// Security headers
// -------------------------
header_remove('X-Powered-By');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer-when-downgrade');
header('Permissions-Policy: geolocation=(), microphone=()');

// HSTS only when HTTPS is used
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=63072000; includeSubDomains; preload');
}

// Create a CSP nonce for inline-safe scripts
$csp_nonce = base64_encode(random_bytes(16));
$csp = "default-src 'self'; img-src 'self' data:; style-src 'self' https://fonts.googleapis.com https://cdn.jsdelivr.net; font-src 'self' https://fonts.gstatic.com; script-src 'self' 'nonce-{$csp_nonce}';";
header("Content-Security-Policy: {$csp}");

// -------------------------
// Utility: escape output
// -------------------------
function e($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// -------------------------
// Fetch doctors from DB (server-side)
// -------------------------
$doctors = [];
try {
    $pdo = new PDO($DB_DSN, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Minimal fields necessary; photo_filename stores server filename (not full path)
    $stmt = $pdo->query('SELECT id AS user_id, name AS username, email, photo_filename, is_active FROM users WHERE role = "doctor" ORDER BY name LIMIT 1000');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // normalize/convert values
        $doctors[] = [
            'user_id' => (int)$row['user_id'],
            'username' => $row['username'],
            'email' => $row['email'],
            'photo_filename' => $row['photo_filename'] ?? null,
            'status' => (bool)$row['is_active'],
        ];
    }
} catch (PDOException $ex) {
    error_log('DB error in doctor_details.php: ' . $ex->getMessage());
    // Graceful degradation; don't show DB details to user
    $doctors = [];
}

// -------------------------
// Page HTML
// -------------------------
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Doctor Details</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Poppins', sans-serif; background: linear-gradient(120deg,#f6f7f8,#eaeff5); margin:0; }
    .sidebar { width:250px; position:fixed; height:100vh; background:linear-gradient(180deg,#1b1f3a,#2c3553); color:#fff; padding:30px 20px; box-shadow:2px 0 8px rgba(0,0,0,0.1); }
    .main { margin-left:270px; padding:40px; }
    .doctor-card { background:#fff; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.1); padding:20px; margin-bottom:20px; display:flex; align-items:center; transition:transform .3s ease, box-shadow .3s ease;}
    .doctor-card img { width:80px; height:80px; border-radius:50%; margin-right:20px; object-fit:cover; background:#f0f0f0; }
    .status-badge { display:inline-block; padding:4px 10px; border-radius:12px; font-size:12px; font-weight:bold; color:#fff; }
    .active { background-color:#28a745; }
    .inactive { background-color:#ff9800; }
    .search-container { display:flex; justify-content:flex-end; margin-bottom:20px; }
    .fab { position:fixed; bottom:30px; right:30px; background:#007bff; color:#fff; border-radius:50%; padding:18px 22px; font-size:20px; box-shadow:0 4px 12px rgba(0,0,0,0.3); z-index:1000; }
  </style>
</head>
<body>
  <div class="sidebar" data-aos="fade-right">
    <div class="sidebar-header text-center">
      <h4><i class="fas fa-cogs me-2"></i> Admin Panel</h4>
    </div>
    <ul class="nav flex-column">
      <li class="nav-item"><a href="admin_dashboard.php" class="nav-link"><i class="fas fa-th-large me-2"></i> Dashboard</a></li>
      <li class="nav-item"><a href="add_doctor.php" class="nav-link"><i class="fas fa-user-plus me-2"></i> Add Doctor</a></li>
      <li class="nav-item"><a href="system_logs.php" class="nav-link"><i class="fas fa-file-alt me-2"></i> System Logs</a></li>
      <li class="nav-item"><a href="security_audit.php" class="nav-link"><i class="fas fa-shield-alt me-2"></i> Security Audit</a></li>
      <li class="nav-item"><a href="doctor_details.php" class="nav-link active"><i class="fas fa-user-md me-2"></i> Doctor Details</a></li>
      <li class="nav-item"><a href="manage_user.php" class="nav-link"><i class="fas fa-users-cog me-2"></i> Manage User</a></li>
    </ul>
  </div>

  <div class="profile-dropdown position-absolute" style="top:20px; right:30px;">
    <div class="dropdown">
      <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fas fa-user-circle"></i>
      </button>
      <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
      </ul>
    </div>
  </div>

  <div class="main">
    <h4><strong>Doctor Details</strong></h4>

    <div class="search-container">
      <input type="text" id="searchInput" placeholder="Search..." class="form-control form-control-sm" style="max-width:300px" />
    </div>

    <div id="doctorList">
      <?php if (!empty($doctors)): ?>
        <?php foreach ($doctors as $doctor): ?>
          <div class="doctor-card" data-aos="fade-up" data-username="<?= e(strtolower($doctor['username'])) ?>">
            <?php
              // Build a safe image URL: serve_image.php?id=<id>&fn=<filename>
              // We include the id so serve_image.php can do extra validation if desired.
              $imgUrl = 'serve_image.php?id=' . urlencode((string)$doctor['user_id']);
              if (!empty($doctor['photo_filename'])) {
                  $imgUrl .= '&fn=' . urlencode($doctor['photo_filename']);
              }
            ?>
            <img src="<?= e($imgUrl) ?>" alt="Dr. <?= e($doctor['username']) ?>" loading="lazy" width="80" height="80">
            <div class="doctor-info">
              <a href="admin_doctor_profile.php?doctor_id=<?= e($doctor['user_id']) ?>"><?= e($doctor['username']) ?></a>
              <p class="mb-0">Email: <?= e($doctor['email']) ?></p>
              <p class="mb-0">Availability:
                <span class="status-badge <?= $doctor['status'] ? 'active' : 'inactive' ?>">
                  <?= $doctor['status'] ? 'Active' : 'Inactive' ?>
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

  <a href="add_doctor.php" class="fab" aria-label="Add doctor"><i class="fas fa-plus"></i></a>

  <!-- AOS + Bootstrap JS -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js" nonce="<?= e($csp_nonce) ?>"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/js/bootstrap.bundle.min.js" nonce="<?= e($csp_nonce) ?>"></script>

  <!-- Inline script uses nonce (allowed by CSP). It only manipulates DOM and reads no server secrets. -->
  <script nonce="<?= e($csp_nonce) ?>">
    AOS.init();

    (function () {
      const search = document.getElementById('searchInput');
      search.addEventListener('input', function () {
        const q = this.value.trim().toLowerCase();
        document.querySelectorAll('.doctor-card').forEach(card => {
          const name = (card.getAttribute('data-username') || '').toLowerCase();
          card.style.display = name.includes(q) ? 'flex' : 'none';
        });
      });
    })();
  </script>
</body>
</html>
