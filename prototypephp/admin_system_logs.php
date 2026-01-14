<?php
// File: admin_system_logs.php

/**
 * CORE INCLUSION & AUTHORIZATION
 */
require_once 'functions.php';
secure_session_start();
add_security_headers();
require_admin(); // Gatekeeper: Halts if user is not a logged-in admin.

/**
 * DATA FETCHING LOGIC
 */
$logs = [];
$conn = get_db_connection();
if (!$conn) {
    set_flash_message('Database connection failed. Could not load system logs.', 'danger');
} else {
    // Fetch a reasonable number of recent logs to avoid performance issues.
    $sql = "SELECT log_id, username, action, target, status, time 
            FROM system_logs 
            ORDER BY time DESC 
            LIMIT 200";
            
    if ($result = $conn->query($sql)) {
        $logs = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        set_flash_message('Failed to retrieve system logs from the database.', 'danger');
    }
    $conn->close();
}

/**
 * PRESENTATION (HTML)
 */
$pageTitle = "System Logs";
include 'templates/header.php';
include 'templates/sidebar_admin.php';
?>
<!-- Main Content -->
<div class="main-content">
  <h3>System Log Viewer</h3>

  <?php display_flash_messages(); ?>

  <!-- Search -->
  <input type="text" class="form-control search-bar mb-4" placeholder="Search by user, action, or target..." id="searchInput"/>

  <!-- Log Table -->
  <div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th>ID</th>
          <th>User</th>
          <th>Action</th>
          <th>Target</th>
          <th>Time</th>
          <th class="text-center">Status</th>
        </tr>
      </thead>
      <tbody id="logTableBody">
        <?php if (!empty($logs)): ?>
          <?php foreach ($logs as $log): ?>
            <tr>
              <td><?php echo htmlspecialchars($log['log_id']); ?></td>
              <td><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></td>
              <td><?php echo htmlspecialchars($log['action']); ?></td>
              <td><?php echo htmlspecialchars($log['target'] ?? '-'); ?></td>
              <td><?php echo !empty($log['time']) ? htmlspecialchars(date('d M Y, H:i:s', strtotime($log['time']))) : 'N/A'; ?></td>
              <td class="text-center">
                <?php
                  $status = strtolower($log['status'] ?? '');
                  $badge_class = 'bg-secondary';
                  if ($status === 'success') {
                      $badge_class = 'bg-success';
                  } elseif ($status === 'fail' || $status === 'error') {
                      $badge_class = 'bg-danger';
                  } elseif ($status === 'info' || $status === 'in progress') {
                      $badge_class = 'bg-info text-dark';
                  }
                ?>
                <span class="badge <?php echo $badge_class; ?>">
                  <?php echo htmlspecialchars(ucfirst($log['status'])); ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="6" class="text-center">No system logs found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<?php
// Includes the JS for the live search filter
include 'templates/footer_with_search_script.php'; 
?>