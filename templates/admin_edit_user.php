<?php
// edit_user.php
require_once 'db.php';
session_start();

// ---- CSRF Token Setup ----
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ---- Get user ID ----
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if ($userId <= 0) {
    die("Invalid user ID.");
}

// ---- Fetch User ----
$stmt = $pdo->prepare("SELECT user_id, username, email, role, status FROM users WHERE user_id = :id LIMIT 1");
$stmt->bindValue(':id', $userId, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch();

if (!$user) {
    die("User not found.");
}

// ---- Handle Form Submission ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("Invalid CSRF token.");
    }

    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $role     = $_POST['role'] ?? 'doctor';
    $status   = ($_POST['status'] === "Active") ? 1 : 0;

    if ($username && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $update = $pdo->prepare("UPDATE users SET username = :username, email = :email, role = :role, status = :status WHERE user_id = :id");
        $update->bindValue(':username', $username, PDO::PARAM_STR);
        $update->bindValue(':email', $email, PDO::PARAM_STR);
        $update->bindValue(':role', $role, PDO::PARAM_STR);
        $update->bindValue(':status', $status, PDO::PARAM_INT);
        $update->bindValue(':id', $userId, PDO::PARAM_INT);
        $update->execute();

        header("Location: manage_users.php?msg=User+updated+successfully");
        exit;
    } else {
        $error = "Please enter a valid name and email.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Edit User Account</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet"/>
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(to right, #f0f2f5, #d9e2ec);
      margin: 0;
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .card {
      background: #ffffff;
      border-radius: 20px;
      padding: 40px;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
      width: 100%;
      max-width: 600px;
      transition: transform 0.3s ease;
    }
    .card:hover { transform: translateY(-5px); }
    h3 { font-weight: 600; text-align: center; margin-bottom: 30px; color: #333; }
    .form-label { font-weight: 500; color: #555; }
    .form-control, .form-select {
      border-radius: 12px; padding: 12px; border: 1px solid #ccc; transition: border-color 0.3s ease;
    }
    .form-control:focus, .form-select:focus { border-color: #007bff; box-shadow: none; }
    .btn-save { background-color: #007bff; color: white; border-radius: 10px; font-weight: 500; padding: 12px 24px; }
    .btn-save:hover { background-color: #0056b3; }
    .btn-cancel { background-color: #6c757d; color: white; border-radius: 10px; padding: 12px 24px; }
    .btn-cancel:hover { background-color: #5a6268; }
    .btn-group { display: flex; justify-content: flex-end; gap: 12px; }
  </style>
</head>
<body>
  <div class="card" data-aos="fade-up">
    <h3>Edit User Account</h3>
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST" action="">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"/>
      
      <div class="mb-3">
        <label class="form-label">Full Name</label>
        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Role</label>
        <select name="role" class="form-select">
          <option value="doctor" <?= ($user['role'] === 'doctor') ? 'selected' : '' ?>>Doctor</option>
          <option value="admin" <?= ($user['role'] === 'admin') ? 'selected' : '' ?>>Admin</option>
        </select>
      </div>

      <div class="mb-4">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
          <option value="Active" <?= ($user['status'] == 1) ? 'selected' : '' ?>>Active</option>
          <option value="Inactive" <?= ($user['status'] == 0) ? 'selected' : '' ?>>Inactive</option>
        </select>
      </div>

      <div class="btn-group">
        <a href="manage_users.php" class="btn btn-cancel">Cancel</a>
        <button type="submit" class="btn btn-save">Save Changes</button>
      </div>
    </form>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
  <script>AOS.init();</script>
</body>
</html>
