<?php
// templates/footer.php or a similar file
?>
    </div>

    <!-- =======================================================
         STEP 1: LOAD ALL EXTERNAL LIBRARIES FIRST
    ======================================================== -->
    
    <!-- AOS (Animations) Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    
    <!-- Chart.js Library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Bootstrap JS Bundle (Loaded ONCE and LAST among libraries) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- =======================================================
         STEP 2: LOAD YOUR CUSTOM SCRIPT THAT USES THE LIBRARIES
    ======================================================== -->
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        // This code can now safely use bootstrap, AOS, and Chart.js
        // because their libraries were loaded before this script runs.

        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        const sidebarToggle = document.getElementById('sidebarToggle');

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('sidebar-collapsed');
            });
        }

        AOS.init({ duration: 800, once: true });

        // Animated counter functionality
        const counters = document.querySelectorAll('.count');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const counter = entry.target;
                    const target = +counter.getAttribute('data-target');
                    const updateCount = () => {
                        const current = +counter.innerText;
                        const increment = Math.ceil(target / 100) || 1;
                        if (current < target) {
                            counter.innerText = Math.min(current + increment, target);
                            setTimeout(updateCount, 20);
                        } else {
                            counter.innerText = target;
                        }
                    };
                    updateCount();
                    observer.unobserve(counter);
                }
            });
        }, { threshold: 0.7 });
        counters.forEach(counter => observer.observe(counter));

        // Chart.js rendering
        const chartLabels = <?php echo json_encode($labels ?? []); ?>;
        const chartDataValues = <?php echo json_encode($chart_data_points ?? []); ?>;
        
        const ctx = document.getElementById('cgmChart');
        if (ctx && chartLabels.length > 0) {
            let existingChart = Chart.getChart(ctx);
            if (existingChart) existingChart.destroy();
            
            new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        label: 'Avg CGM Level',
                        data: chartDataValues,
                        fill: true,
                        borderColor: '#5f9eff',
                        backgroundColor: 'rgba(95, 158, 255, 0.2)',
                        tension: 0.4,
                        spanGaps: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { y: { beginAtZero: false } },
                    plugins: { legend: { display: true } }
                }
            });
        }
    });

    /**
     * This function handles the delete confirmation modal.
     * @param {number} userId - The ID of the user to be deleted.
     */
    function confirmDelete(userId) {
        // 1. Find the hidden input field in the modal's form
        const userIdInput = document.getElementById('userIdToDelete');
        
        // 2. Set its value to the userId passed from the button
        userIdInput.value = userId;

        // 3. Create a Bootstrap Modal instance and show it
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
        deleteModal.show();
    }

    /**
     * This function handles the live search functionality for the table.
     */
    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.getElementById('searchInput');
        const table = document.getElementById('userTable');
        const rows = table.getElementsByTagName('tr');

        searchInput.addEventListener('keyup', function() {
            const filter = searchInput.value.toLowerCase();

            // Loop through all table rows (skip the header row at index 0)
            for (let i = 1; i < rows.length; i++) {
                const nameColumn = rows[i].getElementsByTagName('td')[1]; // Username column
                const emailColumn = rows[i].getElementsByTagName('td')[2]; // Email column
                
                if (nameColumn || emailColumn) {
                    const nameText = nameColumn.textContent || nameColumn.innerText;
                    const emailText = emailColumn.textContent || emailColumn.innerText;

                    if (nameText.toLowerCase().indexOf(filter) > -1 || emailText.toLowerCase().indexOf(filter) > -1) {
                        rows[i].style.display = ""; // Show the row
                    } else {
                        rows[i].style.display = "none"; // Hide the row
                    }
                }
            }
        });
    });
    </script>
</body>
</html>