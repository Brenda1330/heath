<?php
// File: templates/footer_with_doc_dashboard_scripts.php
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        AOS.init({ duration: 800, once: true });

        // --- Welcome Modal Logic ---
        <?php if (isset($_SESSION['show_welcome_modal'])): ?>
            const welcomeModal = new bootstrap.Modal(document.getElementById('welcomeModal'));
            welcomeModal.show();
            <?php unset($_SESSION['show_welcome_modal']); // Important: Unset after showing ?>
        <?php endif; ?>

        // --- Live Search Functionality ---
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            const tableRows = document.querySelectorAll('#patientTable tbody tr');
            searchInput.addEventListener('keyup', function() {
                const query = this.value.toLowerCase().trim();
                tableRows.forEach(row => {
                    // Check if the first cell (colspan) exists or not
                    if (row.cells.length > 1) {
                         row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
                    }
                });
            });
        }
        
        // --- Health Status Pie Chart ---
        const chartData = <?php echo json_encode($chart_data ?? [0,0,0,0]); ?>;
        const ctx = document.getElementById('healthStatusChart');
        if (ctx && chartData.some(v => v > 0)) { // Only render if there's data
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: ['Stable', 'Critical', 'Recovered', 'Warning'],
                    datasets: [{
                        label: 'Health Status',
                        data: chartData,
                        backgroundColor: ['#198754', '#dc3545', '#0dcaf0', '#ffc107']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }
                }
            });
        }

        // --- Calendar and Clock Function ---
        const generateCalendar = () => {
            const now = new Date(), MTime = new Date(now.toLocaleString("en-US", {timeZone: "Asia/Kuala_Lumpur"})), year = MTime.getFullYear(), month = MTime.getMonth(), today = MTime.getDate();
            const months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
            const days = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
            document.getElementById('currentMonthYear').textContent = `${months[month]} - ${year}`;
            const firstDay = new Date(year, month, 1).getDay(), totalDays = new Date(year, month + 1, 0).getDate();
            const grid = document.getElementById('calendarDays');
            grid.innerHTML = "";
            days.forEach(d => { const cell = document.createElement('div'); cell.style.fontWeight='bold'; cell.textContent=d; grid.appendChild(cell); });
            for (let i=0; i<firstDay; i++) grid.appendChild(document.createElement('div'));
            for (let d=1; d<=totalDays; d++) { const cell = document.createElement('div'); cell.textContent=d; if (d===today) cell.classList.add('highlight'); grid.appendChild(cell); }
        };
        const updateTime = () => { document.getElementById('malaysiaTime').textContent = "Malaysia Time: " + new Date(new Date().toLocaleString("en-US", {timeZone: "Asia/Kuala_Lumpur"})).toLocaleTimeString(); };
        generateCalendar();
        updateTime();
        setInterval(updateTime, 1000);
    });

    
let currentSortColumn = -1;
let currentSortDirection = 'desc'; // Default to descending for Status

// Custom sorting order for Status (Higher number = higher priority)
const statusOrder = {
    'critical': 4, // Changed from 'Critical' to lowercase
    'warning': 3,  // Changed from 'Warning' to lowercase
    'stable': 2,   // Changed from 'Stable' to lowercase
    'recovered': 1,// Changed from 'Recovered' to lowercase
    'unknown': 0   // Handle unknown/default case
};

// Sorting Functionality
function sortTable(columnIndex, columnName) { // Pass columnName directly
    const table = document.getElementById('patientTable');
    const tbody = table.getElementsByTagName('tbody')[0];
    const rows = Array.from(tbody.rows);
    const sortButton = document.getElementById('sortMenu');
    const sortIndicator = document.getElementById('sortIndicator');

    // Handle "No patient data" row
    if (rows.length === 1 && rows[0].getElementsByTagName('td').length === 1 && rows[0].getElementsByTagName('td')[0].getAttribute('colspan') === "4") {
        // Update button text but don't sort
        sortButton.childNodes[0].nodeValue = `Sort by: ${columnName} `;
        sortIndicator.className = 'sort-indicator'; // Clear indicator
        return;
    }


    // Determine sort direction
    let direction = 'desc'; // Default direction
    if (currentSortColumn === columnIndex) {
        direction = currentSortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        // Default direction based on column
        if (columnIndex === 2) { // Default Status to descending (Critical first)
            direction = 'desc';
        } else if (columnIndex === 3) { // Default Last Updated to descending (Newest first)
             direction = 'desc';
        }
        else { // Default others to ascending
            direction = 'asc';
        }
    }

    currentSortColumn = columnIndex;
    currentSortDirection = direction;

     sortButton.textContent = `Sort by: ${columnName} `; // Use textContent for simplicity
    sortIndicator.className = `sort-indicator ${direction === 'asc' ? 'sort-asc' : 'sort-desc'}`;
    sortButton.appendChild(sortIndicator); // Re-append the indicator span

    rows.sort((rowA, rowB) => {
        const cellA = rowA.cells[columnIndex];
        const cellB = rowB.cells[columnIndex];
        let valA, valB;

        // Handle sorting logic based on column type
        switch (columnIndex) {
            case 0: // Patient Name (String)
                valA = (cellA.textContent || cellA.innerText || "").trim().toLowerCase();
                valB = (cellB.textContent || cellB.innerText || "").trim().toLowerCase();
                break;
            case 1: // CGM Level (Number)
                valA = parseFloat((cellA.textContent || cellA.innerText).match(/[\d\.]+/)?.[0]) || 0;
                valB = parseFloat((cellB.textContent || cellB.innerText).match(/[\d\.]+/)?.[0]) || 0;
                break;
            case 2: // Status (Custom Order) - **FIXED**
                // Get the status text from the span inside the cell (use lowercase for matching)
                const statusSpanA = cellA.querySelector('.status-box');
                const statusSpanB = cellB.querySelector('.status-box');
                const statusTextA = statusSpanA ? (statusSpanA.innerText || statusSpanA.textContent).trim().toLowerCase() : 'unknown';
                const statusTextB = statusSpanB ? (statusSpanB.innerText || statusSpanB.textContent).trim().toLowerCase() : 'unknown';
                valA = statusOrder[statusTextA] !== undefined ? statusOrder[statusTextA] : statusOrder['unknown']; // Use lookup value or default
                valB = statusOrder[statusTextB] !== undefined ? statusOrder[statusTextB] : statusOrder['unknown']; // Use lookup value or default
                break;
            case 3: // Last Updated (Date)
                // Attempt to parse date, provide a fallback for invalid dates
                const dateA = new Date(cellA.textContent || cellA.innerText);
                const dateB = new Date(cellB.textContent || cellB.innerText);
                valA = !isNaN(dateA) ? dateA.getTime() : 0; // Use 0 for invalid dates
                valB = !isNaN(dateB) ? dateB.getTime() : 0; // Use 0 for invalid dates
                break;
            default: // Should not happen
                valA = (cellA.textContent || cellA.innerText || "").trim().toLowerCase();
                valB = (cellB.textContent || cellB.innerText || "").trim().toLowerCase();
                break;
        }

        let comparison = 0;
        if (valA > valB) comparison = 1;
        else if (valA < valB) comparison = -1;

        return direction === 'asc' ? comparison : comparison * -1; // Apply direction
    });

    // Re-append sorted rows
    while (tbody.firstChild) tbody.removeChild(tbody.firstChild);
    rows.forEach(row => tbody.appendChild(row));
}

// Initialize the page with default sorting by Status (Descending)
window.onload = function() {
    // Set initial active link based on current URL (optional but good practice)
    const currentPath = window.location.pathname.split("/").pop(); // Get the filename
    const links = document.querySelectorAll('.sidebar a');
    links.forEach(link => {
        if (link.getAttribute('href') === currentPath) {
            link.classList.add('active');
            // If the active link is inside a collapsed section, expand it
            const collapseParent = link.closest('.collapse');
            if (collapseParent) {
                new bootstrap.Collapse(collapseParent, { toggle: false }).show();
                // Also activate the parent toggle link
                const toggler = document.querySelector(`a[href="#${collapseParent.id}"]`);
                if (toggler) toggler.classList.add('active'); // You might not want this visually
            }
        } else {
            link.classList.remove('active');
        }
    });
    // Ensure the main dashboard link is active if no specific match or on root path
     if (!document.querySelector('.sidebar a.active') && (currentPath === 'doc_dashboard.html' || currentPath === '')) {
         const dashboardLink = document.querySelector('.sidebar a[href="doc_dashboard.html"]');
         if(dashboardLink) dashboardLink.classList.add('active');
     }

    // Default sort by Status on load
    sortTable(2, 'Status'); // Sort by Status (index 2), pass name for button text
};
</script>
</body>
</html>