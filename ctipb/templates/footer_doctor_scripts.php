<?php
// File: templates/footer_doctor_scripts.php (DEFINITIVELY CORRECTED FOR CHART.JS)
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>

<!-- ================================================================== -->
<!-- == NEW & CRITICAL: Chart.js and required Date Adapter CDNs -->
<!-- 1. The main Chart.js library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- 2. The date-fns library (a dependency for the adapter) -->
<script src="https://cdn.jsdelivr.net/npm/date-fns@2.29.3/cdn.min.js"></script>
<!-- 3. The Chart.js adapter for date-fns -->
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@2.0.1/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
<!-- This script tag safely passes the PHP data to JavaScript -->
<script>
    // Safely pass PHP data to JavaScript with proper validation
    const healthDataForChart = <?php echo json_encode($health_data ?? []); ?>;
    console.log('Health Data for Chart:', healthDataForChart); // Debug line
</script>

<script>
    AOS.init({ once: true, duration: 800 });

    // ==================================================================
    // == SINGLE, UNIFIED EVENT LISTENER FOR ALL DOCTOR PAGES
    // ==================================================================
    document.addEventListener('DOMContentLoaded', () => {

        // --- LOGIC FOR PATIENT LIST PAGE (doc_patientlist.php) ---
        const patientTable = document.getElementById('patientTable');
        if (patientTable) {
            const searchInput = document.getElementById('searchInput');
            
            // Make the search function globally accessible for the inline onkeyup
            window.searchFunction = () => {
                const filter = searchInput.value.toLowerCase().trim();
                const rows = patientTable.querySelectorAll('tbody tr');

                rows.forEach(row => {
                    if (row.cells.length > 1) {
                        const rowText = row.textContent.toLowerCase();
                        row.style.display = rowText.includes(filter) ? "" : "none";
                    }
                });
            };

            const viewDetailsButtons = document.querySelectorAll('.view-details-btn');
            
            viewDetailsButtons.forEach(button => {
                button.addEventListener('click', function(event) {
                    // 1. Prevent the link from navigating immediately
                    event.preventDefault(); 
                    
                    const destinationUrl = this.getAttribute('data-href');
                    const parentRow = this.closest('tr');

                    // 2. Add the exit animation class to the row
                    if (parentRow) {
                        parentRow.classList.add('row-exit');
                    }

                    // 3. Wait for the animation to finish (300ms)
                    setTimeout(() => {
                        // 4. Manually navigate to the new page
                        window.location.href = destinationUrl;
                    }, 100); 
                });
            });

            const navButtons = document.querySelectorAll('.manage-anim-btn');
            
            navButtons.forEach(button => {
                button.addEventListener('click', function(event) {
                    // 1. Prevent the link from navigating immediately
                    event.preventDefault(); 
                    
                    const destinationUrl = this.getAttribute('data-href');
                    const parentRow = this.closest('tr');

                    // 2. Add the exit animation class to the row
                    if (parentRow) {
                        parentRow.classList.add('row-exit');
                    }

                    // 3. Wait for the animation to finish
                    // Increased to 300ms so the fade-out is visible to the eye
                    setTimeout(() => {
                        // 4. Manually navigate to the new page
                        window.location.href = destinationUrl;
                    }, 100); 
                });
            });
            
            // Attach the listener directly
            if(searchInput) {
                searchInput.addEventListener('keyup', window.searchFunction);
            }

            // Make the sort function globally accessible for the inline onclick
            const statusOrder = { 'critical': 4, 'warning': 3, 'stable': 2, 'recovered': 1, 'unknown': 0 };
            let currentSortColumn = -1;
            let currentSortDirection = 'desc';

            window.sortTable = (columnIndex, columnName) => {
                const tbody = patientTable.tBodies[0];
                const rows = Array.from(tbody.rows);
                const sortButton = document.getElementById('sortMenu');
                const sortIndicator = document.getElementById('sortIndicator');

                if (rows.length <= 1 && (rows[0]?.cells.length <= 1)) return;

                let direction = (currentSortColumn === columnIndex && currentSortDirection === 'asc') ? 'desc' : 'asc';
                currentSortColumn = columnIndex;
                currentSortDirection = direction;

                if (sortButton) {
                    sortButton.childNodes[0].nodeValue = `Sort by: ${columnName} `;
                }
                if(sortIndicator) {
                   sortIndicator.className = `pro-sort-indicator ${direction === 'asc' ? 'sort-asc' : 'sort-desc'}`;
                }

                rows.sort((rowA, rowB) => {
                    const cellA = rowA.cells[columnIndex].textContent.trim();
                    const cellB = rowB.cells[columnIndex].textContent.trim();
                    let valA, valB;
                    switch (columnIndex) {
                        case 1: valA = parseInt(cellA, 10) || 0; valB = parseInt(cellB, 10) || 0; break;
                        case 4: valA = new Date(cellA.replace(/,/g, '')).getTime() || 0; valB = new Date(cellB.replace(/,/g, '')).getTime() || 0; break;
                        case 3: valA = statusOrder[cellA.toLowerCase()] || 0; valB = statusOrder[cellB.toLowerCase()] || 0; break;
                        default: valA = cellA.toLowerCase(); valB = cellB.toLowerCase(); break;
                    }
                    if (valA < valB) return direction === 'asc' ? -1 : 1;
                    if (valA > valB) return direction === 'asc' ? 1 : -1;
                    return 0;
                });

                tbody.innerHTML = '';
                rows.forEach(row => tbody.appendChild(row));
            };
            
            // Initial sort for the patient list table
            sortTable(4, 'Date Added');
        }

        // --- LOGIC FOR PATIENT DETAIL PAGE (Trend Chart) ---
        const chartCanvas = document.getElementById('patientTrendChart');
        
        if (chartCanvas && typeof Chart !== 'undefined' && healthDataForChart.length > 0) {
            
            console.log('Initializing chart with data:', healthDataForChart); // Debug
            
            const metricSelect = document.getElementById('metricSelect');
            let patientChart = null;

            // FIXED: Robust timestamp parsing for multiple formats
            // FIXED: Robust timestamp parsing that handles single and double digits
            const parseTimestamp = (timestamp) => {
                if (!timestamp) return null;
                
                console.log('Parsing timestamp:', timestamp); // Debug
                
                // Format 1: "DD/MM/YYYY HH:MM" with flexible digit counts
                const parts1 = timestamp.match(/(\d{1,2})\/(\d{1,2})\/(\d{4}) (\d{1,2}):(\d{2})/);
                if (parts1) {
                    // Pad single digits with leading zeros
                    const day = parts1[1].padStart(2, '0');
                    const month = parts1[2].padStart(2, '0');
                    const hour = parts1[4].padStart(2, '0');
                    
                    // Create ISO format: "YYYY-MM-DDTHH:MM:SS"
                    const isoString = `${parts1[3]}-${month}-${day}T${hour}:${parts1[5]}:00`;
                    const date = new Date(isoString);
                    
                    if (date instanceof Date && !isNaN(date)) {
                        console.log('Successfully parsed with Format 1:', isoString, date);
                        return date;
                    }
                }
                
                // Format 2: Try direct parsing as fallback
                const date = new Date(timestamp);
                if (date instanceof Date && !isNaN(date)) {
                    console.log('Successfully parsed with direct parsing:', date);
                    return date;
                }
                
                console.log('Failed to parse timestamp:', timestamp);
                return null;
            };
                        // FIXED: Data preparation with validation
            const prepareChartData = () => {
                return healthDataForChart
                    .map(item => {
                        const parsedTime = parseTimestamp(item.timestamp);
                        return {
                            ...item,
                            parsedTime: parsedTime,
                            cgm_level: parseFloat(item.cgm_level) || null,
                            food_intake: item.food_intake || 'N/A',
                            activity_level: item.activity_level || 'N/A'
                        };
                    })
                    .filter(item => item.parsedTime !== null) // Remove invalid dates
                    .sort((a, b) => a.parsedTime - b.parsedTime); // Sort by date
            };

            const chartData = prepareChartData();
            
            console.log('Prepared chart data:', chartData); // Debug

            // Show warning if no valid data
            if (chartData.length === 0) {
                chartCanvas.closest('.chart-container').innerHTML = 
                    '<div class="alert alert-warning">No valid date data available for chart.</div>';
                return;
            }

            const createOrUpdateChart = (metricKey) => {
            const labels = chartData.map(item => {
                const date = item.parsedTime;
                if (!date) return 'Unknown';

                // Force conversion to Malaysia Time (UTC+8) regardless of browser location
                return date.toLocaleString('en-GB', {
                    timeZone: 'Asia/Kuala_Lumpur',
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false // Change to true if you want AM/PM
                });
            });
                    let yAxisLabel = 'Value';
                    if (metricKey === 'cgm_level') yAxisLabel = 'CGM Level (mmol/L)';
                    else if (metricKey === 'hb1ac') yAxisLabel = 'HbA1c (%)';
                    else if (metricKey === 'heart_rate') yAxisLabel = 'Heart Rate (bpm)';
                    else if (metricKey === 'weight') yAxisLabel = 'Weight (kg)';

    const chartConfig = {
        type: 'line',
        data: {
            labels: labels, // Use string labels instead of time scale
            datasets: [{
                label: metricSelect.options[metricSelect.selectedIndex].text,
                data: chartData.map(item => parseFloat(item[metricKey]) || null),
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 7,
                pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    // Remove time scale configuration
                    title: {
                        display: true,
                        text: 'Date and Time'
                    },
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45
                    }
                },
                y: {
                    beginAtZero: false,
                    title: {
                        display: true,
                        text: 'CGM Level (mmol/L)'
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.dataset.label}: ${context.formattedValue}`;
                        },
                        afterLabel: function(context) {
                            const dataIndex = context.dataIndex;
                            const food = chartData[dataIndex].food_intake || 'N/A';
                            const activity = chartData[dataIndex].activity_level || 'N/A';
                            return `Food: ${food}\nActivity: ${activity}`;
                        }
                    },
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: 'rgba(255, 255, 255, 0.2)',
                    borderWidth: 1
                },
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    };

    if (patientChart) {
        patientChart.data.labels = labels;
        patientChart.data.datasets[0].label = metricSelect.options[metricSelect.selectedIndex].text;
        patientChart.data.datasets[0].data = chartData.map(item => parseFloat(item[metricKey]) || null);
        // Update the Y-axis title dynamically
        patientChart.options.scales.y.title.text = yAxisLabel;
        patientChart.update();
    } else {
        patientChart = new Chart(chartCanvas, chartConfig);
    }
};

            // Initialize chart
            createOrUpdateChart('cgm_level');

            // Event listener for metric selection
            metricSelect.addEventListener('change', (event) => {
                createOrUpdateChart(event.target.value);
            });

        } else if (chartCanvas && healthDataForChart.length === 0) {
            // No data available
            chartCanvas.closest('.chart-container').innerHTML = 
                '<div class="alert alert-info">No health data available for this patient.</div>';
        } else if (chartCanvas && typeof Chart === 'undefined') {
            // Chart.js not loaded
            chartCanvas.closest('.chart-container').innerHTML = 
                '<div class="alert alert-danger">Chart library failed to load. Please refresh the page.</div>';
        }
        // --- LOGIC FOR PATIENT DETAIL PAGE (Recommendation Deletion) ---
const deleteRecModalElement = document.getElementById('deleteRecModal');
if (deleteRecModalElement) {
    const deleteRecModal = new bootstrap.Modal(deleteRecModalElement);
    const confirmDeleteBtn = document.getElementById('confirmDeleteRecBtn');
    let recTypeToDelete = null;
    let recIdToDelete = null;

    // Define the function on the window object so the inline onclick can find it.
    window.confirmRecDelete = (recType, recId) => {
        recTypeToDelete = recType;
        recIdToDelete = recId;
        deleteRecModal.show();
    };

    confirmDeleteBtn.addEventListener('click', () => {
        if (!recTypeToDelete || !recIdToDelete) return;
        
        const originalButtonText = confirmDeleteBtn.innerHTML;
        confirmDeleteBtn.disabled = true;
        confirmDeleteBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Deleting...`;
        
        const formData = new FormData();
        formData.append('rec_type', recTypeToDelete);
        formData.append('rec_id', recIdToDelete);
        formData.append('csrf_token', '<?php echo $_SESSION["csrf_token"] ?? ""; ?>');

        fetch('api/api_delete_recommendation.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove the recommendation item with smooth animation
                const itemToRemove = document.getElementById(`rec-item-${recTypeToDelete}-${recIdToDelete}`);
                if (itemToRemove) {
                    itemToRemove.style.transition = 'opacity 0.4s ease, max-height 0.4s ease';
                    itemToRemove.style.opacity = '0';
                    itemToRemove.style.maxHeight = '0';
                    itemToRemove.style.overflow = 'hidden';
                    
                    setTimeout(() => {
                        itemToRemove.remove();
                        
                        // Update the UI after removal
                        updateRecommendationUI(recTypeToDelete);
                        
                        // Show success message
                        showDeleteSuccessMessage();
                        
                    }, 400);
                }
                deleteRecModal.hide();
            } else {
                alert('Error: ' + (data.error || 'Could not delete recommendation.'));
            }
        })
        .catch(error => {
            console.error('Deletion Error:', error);
            alert('A network error occurred.');
        })
        .finally(() => {
            confirmDeleteBtn.disabled = false;
            confirmDeleteBtn.innerHTML = originalButtonText;
            recTypeToDelete = null;
            recIdToDelete = null;
        });
    });
    /**
     * This function is called by the 'onclick' on the "Explain Spike" button.
     * It MUST be global to be accessible.
     */
window.getSpikeExplanation = (dataId) => {
            const spikeModalElement = document.getElementById('spikeExplanationModal');
            if (!spikeModalElement) {
                console.error("Spike explanation modal not found!");
                return;
            }

            const spikeModal = bootstrap.Modal.getOrCreateInstance(spikeModalElement);
            const spinner = document.getElementById('explanationSpinner');
            const contentDiv = document.getElementById('explanationContent');

            spinner.style.display = 'block';
            contentDiv.style.display = 'none';
            contentDiv.innerHTML = ''; 
            spikeModal.show();

            fetch(`api/api_get_spike_explanation.php?data_id=${dataId}`)
                .then(response => response.json())
                .then(data => {
                    spinner.style.display = 'none';
                    contentDiv.style.display = 'block';

                    if (data.error) {
                        contentDiv.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                        return;
                    }

                    if (data.success && data.explanation) {
                        const ex = data.explanation;
                        let html = `<h6>Analysis of Event at ${ex.time}:</h6>
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item"><i class="fas fa-chart-line text-danger me-2"></i><strong>Glucose Spike:</strong> ${ex.spike_value}</li>
                                        <li class="list-group-item"><i class="fas fa-utensils text-primary me-2"></i><strong>Food Intake:</strong> ${ex.food}</li>
                                        <li class="list-group-item"><i class="fas fa-walking text-success me-2"></i><strong>Activity Level:</strong> ${ex.activity}</li>
                                    </ul>`;
                        if (ex.insight) {
                            html += `<div class="alert alert-light mt-3"><strong><i class="fas fa-lightbulb me-1"></i> Insight:</strong> ${ex.insight}</div>`;
                        }
                        contentDiv.innerHTML = html;
                    }
                })
                .catch(error => {
                    spinner.style.display = 'none';
                    contentDiv.style.display = 'block';
                    contentDiv.innerHTML = `<div class="alert alert-danger">A network error occurred.</div>`;
                    console.error("Spike Explanation Fetch Error:", error);
                });
        };
    // Note: The actual function `confirmRecDelete` is defined inside the listener below
    // to have access to the modal instance.

    // ==================================================================
    // == SINGLE, UNIFIED EVENT LISTENER FOR ALL PAGES
    // ==================================================================
    

    // Function to update the UI after deletion
    function updateRecommendationUI(recType) {
        // Update counters if they exist
        updateRecommendationCounters();
        
        // Check if section is empty and show empty state
        checkEmptySections();
        
        // Update any summary displays
        updateSummaryDisplays();
    }

    // Update recommendation counters
    function updateRecommendationCounters() {
        // Update PPR count
        const pprItems = document.querySelectorAll('[id^="rec-item-PPR-"]');
        const pprCounter = document.getElementById('ppr-recommendation-count');
        if (pprCounter) {
            pprCounter.textContent = pprItems.length;
        }
        
        // Update Node2Vec count
        const node2vecItems = document.querySelectorAll('[id^="rec-item-Node2Vec-"]');
        const node2vecCounter = document.getElementById('node2vec-recommendation-count');
        if (node2vecCounter) {
            node2vecCounter.textContent = node2vecItems.length;
        }
        
        // Update GAT count
        const gatItems = document.querySelectorAll('[id^="rec-item-GAT-"]');
        const gatCounter = document.getElementById('gat-recommendation-count');
        if (gatCounter) {
            gatCounter.textContent = gatItems.length;
        }
        
        // Update total count
        const totalItems = pprItems.length + node2vecItems.length + gatItems.length;
        const totalCounter = document.getElementById('total-recommendation-count');
        if (totalCounter) {
            totalCounter.textContent = totalItems;
        }
    }

    // Check if sections are empty and show appropriate messages
    function checkEmptySections() {
        const sections = ['PPR', 'Node2Vec', 'GAT'];
        
        sections.forEach(section => {
            const sectionElement = document.getElementById(`${section.toLowerCase()}-recommendations`);
            const items = document.querySelectorAll(`[id^="rec-item-${section}-"]`);
            
            if (sectionElement) {
                const emptyMessage = sectionElement.querySelector('.empty-recommendation-message');
                
                if (items.length === 0) {
                    // Show empty message if it doesn't exist
                    if (!emptyMessage) {
                        const emptyMsg = document.createElement('div');
                        emptyMsg.className = 'empty-recommendation-message alert alert-info mt-3';
                        emptyMsg.innerHTML = `<i class="fas fa-info-circle me-2"></i>No ${section} recommendations available.`;
                        sectionElement.appendChild(emptyMsg);
                    }
                } else {
                    // Remove empty message if items exist
                    if (emptyMessage) {
                        emptyMessage.remove();
                    }
                }
            }
        });
        
        // Check if all sections are empty
        const allItems = document.querySelectorAll('[id^="rec-item-"]');
        const mainEmptyState = document.getElementById('no-recommendations-message');
        
        if (allItems.length === 0 && mainEmptyState) {
            mainEmptyState.style.display = 'block';
        } else if (mainEmptyState) {
            mainEmptyState.style.display = 'none';
        }
    }

    // Update any summary displays
    function updateSummaryDisplays() {
        // Update last updated time
        const lastUpdated = document.getElementById('recommendations-last-updated');
        if (lastUpdated) {
            lastUpdated.textContent = 'Last updated: ' + new Date().toLocaleString();
        }
    }

    // Show success message
    function showDeleteSuccessMessage() {
        // Create or update a toast/success message
        const existingToast = document.getElementById('delete-success-toast');
        
        if (existingToast) {
            // Update existing toast
            const toast = new bootstrap.Toast(existingToast);
            toast.show();
        } else {
            // Create new toast
            const toastHtml = `
                <div id="delete-success-toast" class="toast align-items-center text-white bg-success border-0 position-fixed top-0 end-0 m-3" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="fas fa-check-circle me-2"></i>Recommendation deleted successfully
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', toastHtml);
            const toastElement = document.getElementById('delete-success-toast');
            const toast = new bootstrap.Toast(toastElement);
            toast.show();
            
            // Remove toast from DOM after it hides
            toastElement.addEventListener('hidden.bs.toast', () => {
                toastElement.remove();
            });
        }
    }
}
});
</script>
</body>
</html>