<?php $currentPage = basename($_SERVER['SCRIPT_NAME']); ?>
  <!-- Sidebar -->
  <div class="sidebar" data-aos="fade-right">
    <div class="sidebar-header text-center">
      <h4><i class="fas fa-cogs me-2"></i> Admin Panel</h4>
    </div>
  <ul class="nav flex-column">
    <li class="nav-item"><a href="admin_dashboard.php" class="nav-link <?php echo ($currentPage === 'admin_dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-th-large me-2"></i> Dashboard</a></li>
    <li class="nav-item"><a href="admin_add_doctor.php" class="nav-link <?php echo ($currentPage === 'admin_add_doctor.php') ? 'active' : ''; ?>"><i class="fas fa-user-plus me-2"></i> Add Doctor</a></li>
    <li class="nav-item"><a href="admin_system_logs.php" class="nav-link <?php echo ($currentPage === 'admin_system_logs.php') ? 'active' : ''; ?>"><i class="fas fa-file-alt me-2"></i> System Logs</a></li>
    <!--<li class="nav-item"><a href="admin_security_audit.php" class="nav-link <?php echo ($currentPage === 'admin_security_audit.php') ? 'active' : ''; ?>"><i class="fas fa-shield-alt me-2"></i> Security Audit</a></li> -->
    <li class="nav-item"><a href="admin_doctor_details.php" class="nav-link <?php echo ($currentPage === 'admin_doctor_details.php') ? 'active' : ''; ?>"><i class="fas fa-user-md me-2"></i> Doctor Details</a></li>
    <li class="nav-item"><a href="admin_manage_user.php" class="nav-link <?php echo ($currentPage === 'admin_manage_user.php') ? 'active' : ''; ?>"><i class="fas fa-users-cog me-2"></i> Manage User</a></li>
    </ul>
  </div>
