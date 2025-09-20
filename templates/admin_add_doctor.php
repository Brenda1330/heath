<?php
// add_doctor.php
// Single-file Add Doctor form + processing with secure defaults for scanning with OWASP ZAP.
// IMPORTANT: update DB config and $UPLOAD_DIR to your environment before use.

session_start();

// -------------------------
// Security response headers
// -------------------------
header_remove('X-Powered-By'); // hide PHP version
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer-when-downgrade');
header("Permissions-Policy: geolocation=(), microphone=()");
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=63072000; includeSubDomains; preload');
}
// CSP: adjust as required for your assets (here minimal; inline scripts/styles are avoided)
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' https://fonts.googleapis.com https://cdn.jsdelivr.net; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:");

// -------------------------
// Config (change to fit your env)
// -------------------------
$DB_DSN = 'mysql:host=localhost;dbname=your_db;charset=utf8mb4';
$DB_USER = 'your_user';
$DB_PASS = 'your_pass';

// File upload settings
$UPLOAD_DIR = __DIR__ . '/../uploads/doctor_photos'; // recommended: outside webroot or protected directory
$MAX_FILE_BYTES = 2 * 1024 * 1024; // 2 MB
$ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/gif'];

// create upload dir if missing (ensure this directory is not executable and not served directly)
if (!is_dir($UPLOAD_DIR)) {
    mkdir($UPLOAD_DIR, 0750, true);
}

// -------------------------
// CSRF: token generation
// -------------------------
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Utility: output-escape
function e($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// -------------------------
// Processing POST
// -------------------------
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic rate-limiting hint: could implement IP-based or user-based throttling here (not implemented)
    // Validate CSRF
    $posted_csrf = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $posted_csrf)) {
        $errors[] = 'Invalid CSRF token.';
    }

    // Validate inputs
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = 'doctor';

    if ($username === '' || mb_strlen($username) < 2 || mb_strlen($username) > 100) {
        $errors[] = 'Full name must be between 2 and 100 characters.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
        $errors[] = 'Please provide a valid email address.';
    }

    if (mb_strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }

    // File upload handling (optional)
    $photo_path_for_db = null;
    if (!empty($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['photo'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Failed to upload file.';
        } else {
            // size check
            if ($file['size'] > $MAX_FILE_BYTES) {
                $errors[] = 'Uploaded photo exceeds 2 MB limit.';
            } else {
                // use getimagesize to validate and get MIME
                $img_info = @getimagesize($file['tmp_name']);
                if ($img_info === false) {
                    $errors[] = 'Uploaded file is not a valid image.';
                } else {
                    $mime = $img_info['mime'] ?? '';
                    if (!in_array($mime, $ALLOWED_MIMES, true)) {
                        $errors[] = 'Only JPG, PNG, GIF images are allowed.';
                    } else {
                        // generate random filename, preserve extension
                        $ext = image_type_to_extension($img_info[2], false); // e.g. "jpeg" or "png"
                        $random = bin2hex(random_bytes(16));
                        $safe_filename = $random . '.' . $ext;
                        $destination = $UPLOAD_DIR . DIRECTORY_SEPARATOR . $safe_filename;

                        // move uploaded file
                        if (!move_uploaded_file($file['tmp_name'], $destination)) {
                            $errors[] = 'Failed to store uploaded image.';
                        } else {
                            // OPTIONAL: further hardening - re-encode the image server-side to strip metadata (not implemented here)
                            $photo_path_for_db = $safe_filename; // store filename (not full path) in DB
                            // set safe permissions
                            chmod($destination, 0640);
                        }
                    }
                }
            }
        }
    }

    // If no errors, insert into DB (use PDO + prepared statements)
    if (empty($errors)) {
        try {
            $pdo = new PDO($DB_DSN, $DB_USER, $DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);

            // Check for existing email
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'Email already in use.';
            } else {
                // secure password hashing
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, photo_filename, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
                $stmt->execute([$username, $email, $password_hash, $role, $photo_path_for_db]);
                $success = true;

                // rotate CSRF token after a successful POST to prevent double-posts
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $csrf_token = $_SESSION['csrf_token'];
            }
        } catch (PDOException $ex) {
            // Don't leak DB errors to users; log them server-side
            error_log('DB error in add_doctor.php: ' . $ex->getMessage());
            $errors[] = 'Internal server error. Please try again later.';
        }
    }

    // Clear sensitive vars from memory
    $password = null;
}

// -------------------------
// HTML output (form), all user data escaped
// -------------------------
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Add Doctor Account</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
  <style>
    /* (same styling as your original; trimmed for brevity) */
    body { margin:0; font-family: 'Poppins', sans-serif; background:#f9f9fc; }
    .sidebar { width:250px; position:fixed; height:100vh; background:linear-gradient(180deg,#1b1f3a,#2c3553); color:#fff; padding:30px 20px; box-shadow:2px 0 8px rgba(0,0,0,0.1); }
    .main-content { margin-left:250px; padding:40px; }
    .card { background: rgba(255,255,255,0.8); border-radius:16px; padding:40px; max-width:600px; margin:auto; }
    .btn-submit { background:linear-gradient(to right,#68c4af,#7cd4e1); border:none; color:#fff; font-weight:600; padding:10px 25px; border-radius:30px; }
    .btn-cancel { background:#ccc; color:#333; border:none; border-radius:30px; padding:10px 25px; font-weight:600; }
  </style>
</head>
<body>
  <div class="sidebar" data-aos="fade-right">
    <div class="sidebar-header text-center">
      <h4><i class="fas fa-cogs me-2"></i> Admin Panel</h4>
    </div>
    <ul class="nav flex-column">
      <li class="nav-item"><a href="admin_dashboard.php" class="nav-link"><i class="fas fa-th-large me-2"></i> Dashboard</a></li>
      <li class="nav-item"><a href="add_doctor.php" class="nav-link active"><i class="fas fa-user-plus me-2"></i> Add Doctor</a></li>
      <li class="nav-item"><a href="system_logs.php" class="nav-link"><i class="fas fa-file-alt me-2"></i> System Logs</a></li>
      <li class="nav-item"><a href="security_audit.php" class="nav-link"><i class="fas fa-shield-alt me-2"></i> Security Audit</a></li>
      <li class="nav-item"><a href="doctor_details.php" class="nav-link"><i class="fas fa-user-md me-2"></i> Doctor Details</a></li>
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

  <div class="main-content">
    <h3>Add Doctor Account</h3>
    <div class="card" data-aos="fade-up">
      <?php if ($success): ?>
        <div class="alert alert-success">Doctor account successfully created.</div>
      <?php endif; ?>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
          <ul class="mb-0">
            <?php foreach ($errors as $err): ?>
              <li><?= e($err) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form action="add_doctor.php" method="POST" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>"/>
        <div class="mb-3">
          <label for="username" class="form-label">Full Name</label>
          <input type="text" id="username" name="username" class="form-control" placeholder="Enter doctor's name" required value="<?= e($_POST['username'] ?? '') ?>">
        </div>

        <div class="mb-3">
          <label for="email" class="form-label">Email</label>
          <input type="email" id="email" name="email" class="form-control" placeholder="Enter email address" required value="<?= e($_POST['email'] ?? '') ?>">
        </div>

        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <input type="password" id="password" name="password" class="form-control" placeholder="Create a password" required>
          <div class="form-text">At least 8 characters recommended.</div>
        </div>

        <div class="mb-3">
          <label for="photo" class="form-label">Profile Photo</label>
          <input class="form-control" type="file" id="photo" name="photo" accept="image/*">
          <div class="form-text">Optional: JPG, PNG, GIF – max 2 MB. System will auto-resize.</div>
        </div>

        <div class="mb-4">
          <label class="form-label">Role</label>
          <select class="form-select" disabled>
            <option selected>Doctor</option>
          </select>
          <input type="hidden" name="role" value="doctor">
        </div>

        <div class="d-flex justify-content-end gap-2">
          <a href="admin_dashboard.php" class="btn btn-cancel">Cancel</a>
          <button type="submit" class="btn btn-submit">Create Account</button>
        </div>
      </form>
    </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    AOS.init({ duration: 800, once: true });
    // Avoid inline JS that uses unescaped server values.
  </script>
</body>
</html>
