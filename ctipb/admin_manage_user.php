<?php
// File: admin_manage_user.php

require_once 'functions.php';
secure_session_start();
add_security_headers();
require_admin();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

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

$pageTitle = "Manage Users";
include 'templates/header.php';
include 'templates/sidebar_admin.php';
?>

<!-- Main Content -->
<div class="main-content">
  <h3>Manage User Accounts</h3>
  <?php display_flash_messages(); ?>

  <!-- Search Bar -->
  <input type="text" class="form-control search-bar mb-4" placeholder="Search by name, email, or role..." id="searchInput" onkeyup="searchUsers()"/>

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
      <tbody id="userTableBody">
        <?php if (!empty($users)): ?>
          <?php foreach ($users as $user): ?>
            <tr id="user-row-<?php echo htmlspecialchars($user['user_id']); ?>">
              <td><?php echo htmlspecialchars($user['user_id']); ?></td>
              <td class="user-name"><?php echo htmlspecialchars($user['username']); ?></td>
              <td class="user-email"><?php echo htmlspecialchars($user['email']); ?></td>
              <td class="user-role">
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
                  <?php if ($_SESSION['user_id'] != $user['user_id']): ?>
                  <!-- Buttons for other users -->
                  <a href="admin_edit_user.php?user_id=<?php echo htmlspecialchars($user['user_id']); ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Edit User">
                      <i class="fas fa-edit"></i>
                  </a>
                  <button class="btn btn-sm btn-danger delete-user-btn" 
                          data-user-id="<?php echo htmlspecialchars($user['user_id']); ?>" 
                          data-user-name="<?php echo htmlspecialchars($user['username']); ?>"
                          data-bs-toggle="tooltip" 
                          title="Delete User">
                      <i class="fas fa-trash-alt"></i>
                  </button>
                  <?php else: ?>
                  <!-- Completely hidden for current user - just show indicator -->
                  <span class="text-muted small">(Current User)</span>
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
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Confirm Deletion</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="deleteModalBody"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteButton">Yes, Delete</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// User Management System - Isolated functionality
(function() {
    'use strict';
    
    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        initUserManagement();
    });
    
    function initUserManagement() {
        // Initialize tooltips
        initTooltips();
        
        // Initialize delete functionality
        initDeleteFunctionality();
        
        // Initialize search
        initSearch();
    }
    
    function initTooltips() {
        const tooltipElements = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltipElements.forEach(el => {
            new bootstrap.Tooltip(el);
        });
    }
    
    function initDeleteFunctionality() {
        const deleteModalElement = document.getElementById('deleteConfirmModal');
        const confirmDeleteBtn = document.getElementById('confirmDeleteButton');
        
        if (!deleteModalElement || !confirmDeleteBtn) {
            console.error('Delete modal elements not found');
            return;
        }
        
        const deleteModal = new bootstrap.Modal(deleteModalElement);
        let userIdToDelete = null;
        
        // Add click events to delete buttons
        document.addEventListener('click', function(e) {
            if (e.target.closest('.delete-user-btn')) {
                const button = e.target.closest('.delete-user-btn');
                const userId = button.getAttribute('data-user-id');
                const userName = button.getAttribute('data-user-name');
                
                if (userId && userName) {
                    userIdToDelete = userId;
                    document.getElementById('deleteModalBody').textContent = 
                        `Are you sure you want to permanently delete the user '${userName}'? This action cannot be undone.`;
                    deleteModal.show();
                }
            }
        });
        
        // Confirm delete action
        confirmDeleteBtn.addEventListener('click', function() {
            if (!userIdToDelete) return;
            
            const button = this;
            const originalText = button.innerHTML;
            
            // Show loading state
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Deleting...';
            
            // Send delete request
            fetch('admin_delete_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `user_id=${userIdToDelete}&csrf_token=<?php echo $csrf_token; ?>`
            })
            .then(response => {
                if (!response.ok) throw new Error('Network error');
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Remove user row
                    const userRow = document.getElementById(`user-row-${userIdToDelete}`);
                    if (userRow) {
                        userRow.remove();
                        checkEmptyTable();
                    }
                    deleteModal.hide();
                    showMessage(data.message, 'success');
                } else {
                    showMessage(data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Delete error:', error);
                showMessage('An unexpected error occurred. Please try again.', 'danger');
            })
            .finally(() => {
                // Reset button
                button.disabled = false;
                button.innerHTML = originalText;
                userIdToDelete = null;
            });
        });
        
        // Reset modal when closed
        deleteModalElement.addEventListener('hidden.bs.modal', function() {
            userIdToDelete = null;
            confirmDeleteBtn.disabled = false;
            confirmDeleteBtn.innerHTML = 'Yes, Delete';
        });
    }
    
    function initSearch() {
        const searchInput = document.getElementById('searchInput');
        if (!searchInput) return;
        
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            const rows = document.querySelectorAll('#userTableBody tr');
            
            let hasVisibleRows = false;
            
            rows.forEach(row => {
                // Skip the "no users found" row
                if (row.cells.length === 1 && row.cells[0].colSpan === 6) {
                    row.style.display = 'none';
                    return;
                }
                
                if (!row.querySelector('.user-name')) return;
                
                const name = row.querySelector('.user-name').textContent.toLowerCase();
                const email = row.querySelector('.user-email').textContent.toLowerCase();
                const role = row.querySelector('.user-role').textContent.toLowerCase();
                
                const matches = name.includes(query) || email.includes(query) || role.includes(query);
                row.style.display = matches ? '' : 'none';
                
                if (matches) hasVisibleRows = true;
            });
            
            // Show "no results" message if no rows match search
            showNoResultsMessage(!hasVisibleRows && query.length > 0);
        });
    }
    
    function checkEmptyTable() {
        const tbody = document.getElementById('userTableBody');
        if (!tbody) return;
        
        // Count all user rows (excluding the "no users" message row)
        const userRows = tbody.querySelectorAll('tr:not(.no-users-row)');
        const hasUserRows = userRows.length > 0;
        
        showNoUsersMessage(!hasUserRows);
    }
    
    function showNoUsersMessage(show) {
        const tbody = document.getElementById('userTableBody');
        if (!tbody) return;
        
        // Remove existing "no users" message
        const existingMessage = tbody.querySelector('.no-users-row');
        if (existingMessage) {
            existingMessage.remove();
        }
        
        // Add message if needed
        if (show) {
            const messageRow = document.createElement('tr');
            messageRow.className = 'no-users-row';
            messageRow.innerHTML = '<td colspan="6" class="text-center">No users found in the database.</td>';
            tbody.appendChild(messageRow);
        }
    }
    
    function showNoResultsMessage(show) {
        const tbody = document.getElementById('userTableBody');
        if (!tbody) return;
        
        // Remove existing "no results" message
        const existingMessage = tbody.querySelector('.no-results-row');
        if (existingMessage) {
            existingMessage.remove();
        }
        
        // Add message if needed
        if (show) {
            const messageRow = document.createElement('tr');
            messageRow.className = 'no-results-row';
            messageRow.innerHTML = '<td colspan="6" class="text-center">No users match your search criteria.</td>';
            tbody.appendChild(messageRow);
        }
    }
    
    function showMessage(message, type) {
        // Remove existing messages
        const existingAlerts = document.querySelectorAll('.alert-dismissible');
        existingAlerts.forEach(alert => alert.remove());
        
        // Create new message
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.innerHTML = `
            <strong>${type === 'success' ? 'Success!' : 'Error!'}</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Insert after page title
        const pageTitle = document.querySelector('h3');
        if (pageTitle && pageTitle.parentNode) {
            pageTitle.parentNode.insertBefore(alert, pageTitle.nextSibling);
        }
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 5000);
    }
})();
</script>

<?php include 'templates/footer.php'; ?>