<?php
// File: admin_system_logs.php

/**
 * CORE INCLUSION & AUTHORIZATION
 */
require_once 'functions.php';
secure_session_start();
add_security_headers();
require_admin();

/**
 * PAGINATION CONFIGURATION
 */
$logs_per_page = 50;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $logs_per_page;

/**
 * DATA FETCHING & SORTING LOGIC
 */
$allowed_sort_columns = ['log_id', 'username', 'action', 'target', 'time', 'status'];
$sort_column = $_GET['sort'] ?? 'log_id';
$sort_order = strtolower($_GET['order'] ?? 'desc');

if (!in_array($sort_column, $allowed_sort_columns)) {
    $sort_column = 'log_id';
}
if (!in_array($sort_order, ['asc', 'desc'])) {
    $sort_order = 'desc';
}

$logs = [];
$total_logs = 0;
$total_pages = 0;
$max_log_id = 0;

$conn = get_db_connection();
if (!$conn) {
    set_flash_message('Database connection failed. Could not load system logs.', 'danger');
} else {
    // Get total number of logs and maximum log ID
    $count_sql = "SELECT COUNT(*) as total, COALESCE(MAX(log_id), 0) as max_id FROM system_logs";
    $count_result = $conn->query($count_sql);
    if ($count_result) {
        $count_data = $count_result->fetch_assoc();
        $total_logs = $count_data['total'];
        $max_log_id = $count_data['max_id'];
        $total_pages = ceil($total_logs / $logs_per_page);
    }

    // Ensure current page is within valid range
    if ($current_page < 1) $current_page = 1;
    if ($current_page > $total_pages && $total_pages > 0) $current_page = $total_pages;

    // Get logs for current page
    $sql = "SELECT log_id, username, action, target, status, time 
            FROM system_logs 
            ORDER BY " . $sort_column . " " . strtoupper($sort_order) . "
            LIMIT " . $logs_per_page . " OFFSET " . $offset;
            
    if ($result = $conn->query($sql)) {
        $logs = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        set_flash_message('Failed to retrieve system logs from the database.', 'danger');
    }
    $conn->close();
}

function sortable_header($title, $column_name, $current_sort_column, $current_sort_order) {
    $new_order = ($column_name === $current_sort_column && $current_sort_order === 'asc') ? 'desc' : 'asc';
    $link = "admin_system_logs.php?sort=" . htmlspecialchars($column_name) . "&order=" . htmlspecialchars($new_order) . "&page=" . ($_GET['page'] ?? 1);
    $icon = '';
    if ($column_name === $current_sort_column) {
        $icon = $current_sort_order === 'asc' ? '<i class="fas fa-chevron-up ms-1 text-primary"></i>' : '<i class="fas fa-chevron-down ms-1 text-primary"></i>';
    }
    echo "<th><a href=\"{$link}\" class=\"d-flex align-items-center justify-content-between text-decoration-none text-dark hover-primary\">{$title}{$icon}</a></th>";
}

function generate_pagination_links($current_page, $total_pages, $sort_column, $sort_order) {
    if ($total_pages <= 1) return '';
    
    $links = '';
    $max_links = 5;
    
    // Previous button
    if ($current_page > 1) {
        $prev_link = "admin_system_logs.php?page=" . ($current_page - 1) . "&sort=$sort_column&order=$sort_order";
        $links .= '<li class="page-item"><a class="page-link" href="' . $prev_link . '">&laquo;</a></li>';
    } else {
        $links .= '<li class="page-item disabled"><span class="page-link">&laquo;</span></li>';
    }
    
    // Calculate start and end pages for pagination
    $start_page = max(1, $current_page - floor($max_links / 2));
    $end_page = min($total_pages, $start_page + $max_links - 1);
    
    // Adjust start page if we're near the end
    if ($end_page - $start_page < $max_links - 1) {
        $start_page = max(1, $end_page - $max_links + 1);
    }
    
    // Page number links
    for ($i = $start_page; $i <= $end_page; $i++) {
        $page_link = "admin_system_logs.php?page=$i&sort=$sort_column&order=$sort_order";
        if ($i == $current_page) {
            $links .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
        } else {
            $links .= '<li class="page-item"><a class="page-link" href="' . $page_link . '">' . $i . '</a></li>';
        }
    }
    
    // Next button
    if ($current_page < $total_pages) {
        $next_link = "admin_system_logs.php?page=" . ($current_page + 1) . "&sort=$sort_column&order=$sort_order";
        $links .= '<li class="page-item"><a class="page-link" href="' . $next_link . '">&raquo;</a></li>';
    } else {
        $links .= '<li class="page-item disabled"><span class="page-link">&raquo;</span></li>';
    }
    
    return $links;
}

// Function to format timestamp in Malaysia timezone
function formatMalaysiaTime($timestamp) {
    if (empty($timestamp)) {
        return 'N/A';
    }
    
    try {
        $timezone = new DateTimeZone('Asia/Kuala_Lumpur');
        $date = new DateTime($timestamp);
        $date->setTimezone($timezone);
        return $date->format('M j, Y H:i');
    } catch (Exception $e) {
        return 'Invalid Date';
    }
}

$pageTitle = "System Logs";
include 'templates/header.php';
include 'templates/sidebar_admin.php';
?>

<style>
.main-content {
    margin-top: 20px;
}

.card {
    border: 1px solid #e3e6f0;
    border-radius: 0.5rem;
    background: #fff;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
}

.card-header {
    background: #f8f9fc;
    border-bottom: 1px solid #e3e6f0;
    padding: 1.25rem 1.5rem;
}

.card-header h4 {
    color: #2e2e2e;
    font-weight: 700;
    font-size: 1.4rem;
}

.table th {
    background: #f8f9fc;
    border-bottom: 2px solid #e3e6f0;
    font-weight: 700;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #2e2e2e;
    padding: 1rem 0.75rem;
}

.table td {
    padding: 1rem 0.75rem;
    vertical-align: middle;
    border-bottom: 1px solid #e3e6f0;
    color: #2e2e2e;
    font-weight: 500;
    font-size: 0.95rem;
}

.table tbody tr:hover {
    background-color: #f8f9fc;
    transform: translateY(-1px);
    transition: all 0.15s ease;
    box-shadow: 0 0.15rem 0.5rem rgba(0, 0, 0, 0.1);
}

.hover-primary:hover {
    color: #4e73df !important;
}

.status-badge {
    font-size: 0.9rem;
    font-weight: 800;
    padding: 0.6em 1em;
    border-radius: 0.4rem;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.bg-success { 
    background: linear-gradient(135deg, #1a8754 0%, #2ecc71 100%) !important; 
    color: white !important;
}
.bg-danger { 
    background: linear-gradient(135deg, #dc3545 0%, #e74a3b 100%) !important; 
    color: white !important;
}
.bg-warning { 
    background: linear-gradient(135deg, #ffc107 0%, #f6c23e 100%) !important; 
    color: #2e2e2e !important;
    font-weight: 800;
}
.bg-info { 
    background: linear-gradient(135deg, #17a2b8 0%, #36b9cc 100%) !important; 
    color: white !important;
}
.bg-secondary { 
    background: linear-gradient(135deg, #6c757d 0%, #858796 100%) !important; 
    color: white !important;
}

.search-container {
    max-width: 300px;
}

.form-control:focus {
    border-color: #4e73df;
    box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
}

.empty-state {
    padding: 3rem 1rem;
    text-align: center;
    color: #6e707e;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: #dddfeb;
}

.pagination-container {
    background: #f8f9fc;
    border-top: 1px solid #e3e6f0;
    padding: 1rem 1.5rem;
}

.page-info {
    font-weight: 600;
    color: #2e2e2e;
    font-size: 0.95rem;
}

.page-link {
    color: #4e73df;
    font-weight: 600;
    border: 1px solid #dee2e6;
}

.page-item.active .page-link {
    background-color: #4e73df;
    border-color: #4e73df;
}

.page-link:hover {
    color: #2e59d9;
    background-color: #e9ecef;
    border-color: #dee2e6;
}

.text-strong {
    color: #2e2e2e !important;
    font-weight: 600;
}

.badge-cell {
    text-align: center;
}

.system-info {
    background: #e8f4fd;
    border: 1px solid #b8daff;
    border-radius: 0.375rem;
    padding: 0.75rem 1rem;
    margin: 0 1rem 1rem 1rem;
    font-size: 0.875rem;
}

.system-info i {
    color: #4e73df;
}

.log-id-highlight {
    background: #f8f9fc;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-family: 'Courier New', monospace;
    font-weight: 700;
}
</style>

<div class="main-content">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center py-3">
            <h4 class="mb-0">
                <i class="fas fa-clipboard-list me-2"></i>
                System Logs
            </h4>
            <div class="d-flex align-items-center">
                <div class="search-container me-3">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="fas fa-search text-gray-400"></i>
                        </span>
                        <input type="text" class="form-control border-start-0 ps-0" 
                               placeholder="Search logs..." id="searchInput">
                    </div>
                </div>
                <div class="page-info text-end">
                    <div class="fw-bold text-dark"><?php echo $total_logs; ?> active logs</div>
                    <?php if ($max_log_id > 0): ?>
                    <div class="text-muted small">Latest log ID: <span class="log-id-highlight">#<?php echo $max_log_id; ?></span></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="card-body p-0">
            <?php display_flash_messages(); ?>
            
            <!-- System Information -->
            <?php if ($max_log_id > $total_logs): ?>
            <div class="system-info">
                <div class="d-flex align-items-center">
                    <i class="fas fa-info-circle me-2"></i>
                    <div>
                        <strong>System Note:</strong> Log IDs are sequential for tracking purposes. 
                        Some IDs may not appear as they represent system-maintained records.
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <?php sortable_header('ID', 'log_id', $sort_column, $sort_order); ?>
                            <?php sortable_header('User', 'username', $sort_column, $sort_order); ?>
                            <?php sortable_header('Action', 'action', $sort_column, $sort_order); ?>
                            <th>Target</th>
                            <?php sortable_header('Timestamp', 'time', $sort_column, $sort_order); ?>
                            <?php sortable_header('Status', 'status', $sort_column, $sort_order); ?>
                        </tr>
                    </thead>
                    <tbody id="logTableBody">
                        <?php if (!empty($logs)): ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td class="fw-bold text-strong">
                                        <span class="log-id-highlight">#<?php echo htmlspecialchars($log['log_id']); ?></span>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-strong">
                                            <?php echo htmlspecialchars($log['username'] ?? 'System'); ?>
                                        </span>
                                    </td>
                                    <td class="text-strong"><?php echo htmlspecialchars($log['action']); ?></td>
                                    <td class="text-strong" title="<?php echo htmlspecialchars($log['target'] ?? '-'); ?>">
                                        <?php echo htmlspecialchars(mb_strimwidth($log['target'] ?? '-', 0, 45, "...")); ?>
                                    </td>
                                    <td class="text-strong">
                                        <?php echo formatMalaysiaTime($log['time']); ?>
                                    </td>
                                    <td class="badge-cell">
                                        <?php
                                            $status = strtolower($log['status'] ?? '');
                                            $badge_class = 'bg-secondary';
                                            if ($status === 'success') $badge_class = 'bg-success';
                                            elseif ($status === 'fail' || $status === 'error') $badge_class = 'bg-danger';
                                            elseif (in_array($status, ['info', 'in progress'])) $badge_class = 'bg-info';
                                            elseif ($status === 'warning') $badge_class = 'bg-warning';
                                        ?>
                                        <span class="badge status-badge <?php echo $badge_class; ?>">
                                            <?php echo htmlspecialchars(ucfirst($log['status'])); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <i class="fas fa-inbox"></i>
                                        <h5 class="text-gray-500">No logs found</h5>
                                        <p class="text-muted">System logs will appear here as activities occur.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination-container d-flex justify-content-between align-items-center">
                <div class="page-info">
                    Page <strong><?php echo $current_page; ?></strong> of <strong><?php echo $total_pages; ?></strong>
                    | Showing <strong><?php echo count($logs); ?></strong> of <strong><?php echo $total_logs; ?></strong> active logs
                </div>
                <nav>
                    <ul class="pagination mb-0">
                        <?php echo generate_pagination_links($current_page, $total_pages, $sort_column, $sort_order); ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const tableBody = document.getElementById('logTableBody');
    const tableRows = tableBody.getElementsByTagName('tr');

    searchInput.addEventListener('keyup', function() {
        const query = searchInput.value.toLowerCase();

        for (let i = 0; i < tableRows.length; i++) {
            const row = tableRows[i];
            const cells = row.getElementsByTagName('td');
            let found = false;
            
            for (let j = 0; j < cells.length; j++) {
                const cellText = cells[j].textContent.toLowerCase();
                if (cellText.includes(query)) {
                    found = true;
                    break;
                }
            }
            
            row.style.display = found ? '' : 'none';
        }
    });

    searchInput.focus();
});
</script>

<?php
include 'templates/footer.php';
?>