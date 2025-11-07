<?php
// File: templates/footer_doctor_scripts.php (CORRECTED)
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script>
    AOS.init({ once: true, duration: 800 });

    // The searchFunction is correct and needs no changes.
    function searchFunction() {
        const input = document.getElementById('searchInput');
        const filter = input.value.toLowerCase().trim();
        const table = document.getElementById('patientTable');
        const rows = table.querySelectorAll('tbody tr');

        rows.forEach(row => {
            if (row.cells.length > 1) {
                const tdName = row.cells[0].textContent.toLowerCase();
                const tdAge = row.cells[1].textContent.toLowerCase(); // Changed variable name for clarity
                const tdStatus = row.cells[3].textContent.toLowerCase();
                const tdAdded = row.cells[4].textContent.toLowerCase();

                if (
                    tdName.includes(filter) ||
                    tdStatus.includes(filter) ||
                    tdAge.includes(filter) || // Search by age
                    tdAdded.includes(filter)
                ) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            }
        });
    }

    // Advanced Sorting Functionality
    const statusOrder = { 'critical': 4, 'warning': 3, 'stable': 2, 'recovered': 1, 'unknown': 0 };
    let currentSortColumn = -1;
    let currentSortDirection = 'desc';

    function sortTable(columnIndex, columnName) {
        const table = document.getElementById('patientTable');
        const tbody = table.tBodies[0];
        const rows = Array.from(tbody.rows);
        const sortButton = document.getElementById('sortMenu');
        const sortIndicator = document.getElementById('sortIndicator');

        if (rows.length <= 1 && (rows[0]?.cells.length <= 1)) return;

        let direction = 'asc';
        if (currentSortColumn === columnIndex) {
            direction = currentSortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            direction = (columnIndex === 1 || columnIndex === 3 || columnIndex === 4) ? 'desc' : 'asc'; // Age, Status, Date default desc
        }
        
        currentSortColumn = columnIndex;
        currentSortDirection = direction;

        sortButton.childNodes[0].nodeValue = `Sort by: ${columnName} `;
        sortIndicator.className = `sort-indicator ${direction === 'asc' ? 'sort-asc' : 'sort-desc'}`;

        rows.sort((rowA, rowB) => {
            const cellA = rowA.cells[columnIndex].textContent.trim();
            const cellB = rowB.cells[columnIndex].textContent.trim();
            let valA, valB;

            // ===================================================================
            // === THE FIX: SEPARATE THE LOGIC FOR AGE, DATES, AND STATUS ===
            // ===================================================================
            switch (columnIndex) {
                case 1: // Age
                    // Convert the age string to an integer for correct numerical sorting.
                    valA = parseInt(cellA, 10) || 0;
                    valB = parseInt(cellB, 10) || 0;
                    break;
                
                case 4: // Date Added
                    // This logic is correct for date strings.
                    valA = new Date(cellA).getTime() || 0;
                    valB = new Date(cellB).getTime() || 0;
                    break;
                    
                case 3: // Status
                    // This logic is correct for custom status order.
                    valA = statusOrder[cellA.toLowerCase()] || 0;
                    valB = statusOrder[cellB.toLowerCase()] || 0;
                    break;
                    
                default: // Patient Name, Gender (string sorting)
                    valA = cellA.toLowerCase();
                    valB = cellB.toLowerCase();
                    break;
            }
            // ===================================================================
            // === END OF FIX ===
            // ===================================================================

            if (valA < valB) return direction === 'asc' ? -1 : 1;
            if (valA > valB) return direction === 'asc' ? 1 : -1;
            return 0;
        });

        tbody.innerHTML = '';
        rows.forEach(row => tbody.appendChild(row));
    }

    // Default sort on page load - this is correct.
    window.onload = function() {
        if (document.getElementById('patientTable')) {
             sortTable(4, 'Date Added'); // Default sort by "Date Added" descending
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        const patientSelect = document.getElementById('patientSelect');
        const profileDiv = document.getElementById('patientProfile');
        const timestampSelect = document.getElementById('timestampSelect');
        const algorithmSelect = document.getElementById('algorithmSelect');
        const recommendationSection = document.getElementById('recommendationSection');
        const recommendationText = document.getElementById('recommendationText');
        const loadingSpinner = document.getElementById('loadingSpinner');

        const secureFetch = (url) => {
            return fetch(url).then(response => {
                if (!response.ok) {
                    // If we get a 404 Not Found, 500 Server Error, etc.
                    throw new Error(`Network response was not ok: ${response.statusText}`);
                }
                return response.json();
            });
        };

        patientSelect.addEventListener('change', function() {
            const patientId = this.value;
            // Reset state
            profileDiv.style.display = 'none';
            timestampSelect.innerHTML = '<option value="" selected disabled>-- Loading... --</option>';
            timestampSelect.disabled = true;
            algorithmSelect.disabled = true;
            recommendationSection.style.display = 'none';

            if (!patientId) return;

            // --- Chain fetches with improved error handling ---
            secureFetch(`api/get_patient_profile.php?id=${patientId}`)
            .then(data => {
                if (data.error) { throw new Error(data.error); }
                
                // Update profile display
                document.getElementById('profileFullName').textContent = data.full_name || 'N/A';
                document.getElementById('profileDOB').textContent = data.dob || 'N/A';
                document.getElementById('profileGender').textContent = data.gender || 'N/A';
                document.getElementById('profileStatus').textContent = data.status || 'N/A';
                const gender = (data.gender || '').toLowerCase();
                document.getElementById('profileImage').src = gender === 'male' ? 'static/images/male.png' : (gender === 'female' ? 'static/images/female.png' : 'static/images/default.png');
                profileDiv.style.display = 'block';

                // If profile fetch is successful, fetch timestamps
                return secureFetch(`api/get_patient_timestamps.php?id=${patientId}`);
            })
            .then(data => {
                if (data.error) { throw new Error(data.error); }

                timestampSelect.innerHTML = '<option value="" selected disabled>-- Select a Timestamp --</option>';
                if (data.timestamps && data.timestamps.length > 0) {
                    data.timestamps.forEach(ts => {
                        const option = new Option(ts.timestamp, ts.data_id);
                        timestampSelect.appendChild(option);
                    });
                    timestampSelect.disabled = false;
                } else {
                    timestampSelect.innerHTML = '<option value="" selected disabled>-- No timestamps found --</option>';
                }
            })
            .catch(error => {
                // This will catch errors from ANY of the fetch calls
                alert('An error occurred while fetching patient data: ' + error.message);
                console.error('Fetch Chain Error:', error);
            });
        });
        
         timestampSelect.addEventListener('change', () => {
            algorithmSelect.disabled = !timestampSelect.value;
            recommendationSection.style.display = 'none';
        });

        algorithmSelect.addEventListener('change', function() {
            const patientId = patientSelect.value;
            const dataId = timestampSelect.value;
            const algorithm = this.value;

            if (!patientId || !dataId || !algorithm) return;

            // Show loading spinner and hide previous text
            loadingSpinner.style.display = 'block';
            recommendationText.style.display = 'none';
            recommendationSection.style.display = 'block';

            // Fetch recommendation from our secure API
            fetch(`api/get_recommendation.php?patient_id=${patientId}&data_id=${dataId}&algorithm=${algorithm}`)
            .then(response => response.json())
            .then(data => {
                loadingSpinner.style.display = 'none';
                recommendationText.style.display = 'block';
                recommendationText.classList.remove('alert-danger', 'alert-success', 'alert-light');

                if (data.error) {
                    recommendationText.textContent = data.error;
                    recommendationText.classList.add('alert-danger');
                    return;
                }
                
                recommendationText.textContent = data.recommendations.join(', ');
                recommendationText.classList.add('alert-success');
            })
            .catch(error => {
                loadingSpinner.style.display = 'none';
                recommendationText.style.display = 'block';
                recommendationText.classList.remove('alert-success', 'alert-light');
                recommendationText.classList.add('alert-danger');
                recommendationText.textContent = 'Failed to fetch recommendations due to a network error.';
                console.error('Fetch Error:', error);
            });
        });
    });
</script>
</body>
</html>