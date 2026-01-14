<?php
// File: admin_manage_user.php

/**
 * CORE INCLUSION & AUTHORIZATION
 */
require_once 'functions.php';
secure_session_start();
add_security_headers();
require_admin();

/**
 * CSRF TOKEN GENERATION FOR DELETE FORM
 */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

/**
 * DATA FETCHING LOGIC
 */
$users = [];
$conn = get_db_connection();
if (!$conn) {
    set_flash_message('Database connection failed. Could not load user list.', 'danger');
} else {
    $sql = "SELECT user_id, username, email, role, status FROM users ORDER BY user_id ASC";
    if ($result = $conn->query($sql)) {
        $users = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        set_flash_message('Failed to retrieve user records.', 'danger');
    }
    $conn->close();
}

/**
 * PRESENTATION (HTML)
 */
$pageTitle = "Manage Users";
include 'templates/header.php';
include 'templates/sidebar_admin.php';
?>


<!-- Main Content -->
<div class="main-content">
  <h3>Manage User Accounts</h3>

  <?php display_flash_messages(); ?>

  <!-- Search Bar -->
  <input type="text" class="form-control search-bar mb-4" placeholder="Search by name or email..." id="searchInput"/>

  <!-- User Table -->
  <div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Email</th>
          <th>Role</th>
          <th>Status</th>
          <th class="text-center">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($users)): ?>
          <?php foreach ($users as $user): ?>
            <tr>
              <td><?php echo htmlspecialchars($user['user_id']); ?></td>
              <td><?php echo htmlspecialchars($user['username']); ?></td>
              <td><?php echo htmlspecialchars($user['email']); ?></td>
              <td>
                <span class="badge <?php echo ($user['role'] === 'admin') ? 'bg-primary' : 'bg-info text-dark'; ?>">
                  <?php echo htmlspecialchars(ucfirst($user['role'])); ?>
                </span>
              </td>
              <td>
                <span class="badge <?php echo ($user['status'] == 1) ? 'bg-success' : 'bg-secondary'; ?>">
                  <?php echo ($user['status'] == 1) ? 'Active' : 'Inactive'; ?>
                </span>
              </td>
              <td class="text-center">
                <a href="admin_edit_user.php?user_id=<?php echo htmlspecialchars($user['user_id']); ?>" class="btn btn-sm btn-primary">
                  <i class="fas fa-edit"></i> Edit
                </a>
                <!-- Prevent admin from deleting themselves -->
                <?php if ($_SESSION['user_id'] !== $user['user_id']): ?>
                <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo htmlspecialchars($user['user_id']); ?>)">
                  <i class="fas fa-trash-alt"></i> Delete
                </button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="6" class="text-center">No users found in the database.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="deleteConfirmModalLabel">Confirm Deletion</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to permanently delete this user account? This action cannot be undone.
      </div>
      <div class="modal-footer">
        <form id="deleteUserForm" method="POST" action="admin_delete_user.php">
            <!-- CSRF Token for security -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="user_id" id="userIdToDelete" value="">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-danger">Yes, Delete</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>


<?php
// Includes the JS for search and delete modal
include 'templates/footer.php';
?>