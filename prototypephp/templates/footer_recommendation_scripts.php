<?php
// File: templates/footer_recommendation_scripts.php
?>
<!-- This includes Bootstrap's JS for dropdowns and modals -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- This is for the animations -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<!-- D3.js Library for Graph Visualization -->
<script src="https://d3js.org/d3.v7.min.js"></script>

<script>
    // Initialize animations
    AOS.init({ once: true, duration: 800 });

    document.addEventListener('DOMContentLoaded', () => {
        // --- Element Selections ---
        const patientSelect = document.getElementById('patientSelect');
        const profileDiv = document.getElementById('patientProfile');
        const timestampSelect = document.getElementById('timestampSelect');
        const algorithmSelect = document.getElementById('algorithmSelect');
        const algorithmSelectionWrapper = document.getElementById('algorithmSelectionWrapper');
        const outputSection = document.getElementById('outputSection');
        const graphContainer = document.getElementById('graphContainer');
        const graphSpinner = document.getElementById('graphLoadingSpinner');
        const recommendationSection = document.getElementById('recommendationSection');
        const recommendationText = document.getElementById('recommendationText');
        const recommendationSpinner = document.getElementById('recommendationLoadingSpinner');
        const summaryHint = document.getElementById('summaryHint');
        
        // --- Helper for secure API calls (unchanged) ---
        const secureFetch = (url) => {
            return fetch(url).then(response => {
                if (!response.ok) { throw new Error(`Server responded with an error: ${response.statusText} (${response.status})`); }
                return response.json().catch(() => { throw new Error("Invalid JSON response from server. Check API file for PHP errors."); });
            });
        };

        // --- Event Listener for Patient Dropdown ---
        patientSelect.addEventListener('change', function() {
            const patientId = this.value;
            // UI Reset
            profileDiv.style.display = 'none';
            timestampSelect.innerHTML = '<option value="" selected disabled>-- Loading... --</option>';
            timestampSelect.disabled = true;
            outputSection.style.display = 'none';
            summaryHint.style.display = 'none'; 

            if (!patientId) return;

            // --- First API Call: Get Patient Profile ---
            secureFetch(`api/get_patient_profile.php?id=${patientId}`)
            .then(data => {
                if (data.error) { throw new Error(data.error); }
                
                // Populate basic info
                document.getElementById('profileFullName').textContent = data.full_name || 'N/A';
                document.getElementById('profileAge').textContent = data.age || 'N/A';
                document.getElementById('profileGender').textContent = data.gender || 'N/A';
                const gender = (data.gender || '').toLowerCase();
                document.getElementById('profileImage').src = gender === 'male' ? 'static/uploads/male.png' : (gender === 'female' ? 'static/uploads/female.png' : 'static/uploads/patient.jpg');

                // ===================================================================
                // === THIS IS THE CORRECTED LOGIC IN THE CORRECT LOCATION ===
                // ===================================================================
                const statusText = data.status ? data.status.toLowerCase() : 'unknown';
                const statusBox = document.getElementById('profileStatusBox');

                // 1. Set the text inside the box (e.g., "Stable")
                statusBox.textContent = statusText;

                // 2. Set the class of the box to apply the correct color
                statusBox.className = 'status-box ' + statusText;
                // ===================================================================

                profileDiv.style.display = 'block';
                
                // --- Chain to the second API Call: Get Timestamps ---
                return secureFetch(`api/get_patient_timestamps.php?id=${patientId}`);
            })
            .then(data => {
                // This block now ONLY handles timestamps
                if (data.error) { throw new Error(data.error); }

                timestampSelect.innerHTML = '<option value="" selected disabled>-- Select a Timestamp --</option>';
                if (data.timestamps && data.timestamps.length > 0) {
                    const allOption = new Option("View All Timestamps", "all");
                    timestampSelect.appendChild(allOption);
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
                alert('An error occurred while fetching patient data: ' + error.message);
                console.error("Fetch Chain Error:", error);
            });
        });

        // --- Main Trigger 1: Timestamp Dropdown (Fetches the Graph) ---
        timestampSelect.addEventListener('change', function() {
            const patientId = patientSelect.value;
            const selectedOption = this.options[this.selectedIndex];
            const timestampText = selectedOption.value === 'all' ? 'all' : selectedOption.text;

            // UI Reset for new selection
            outputSection.style.display = 'none';
            algorithmSelectionWrapper.style.display = 'none';
            recommendationSection.style.display = 'none';
            summaryHint.style.display = 'none';
            algorithmSelect.selectedIndex = 0; // Reset algorithm dropdown

            if (!patientId || !this.value) return;

            // Show the main section and the graph spinner
            outputSection.style.display = 'block';
            graphSpinner.style.display = 'block';
            graphContainer.innerHTML = '';
            graphContainer.style.display = 'none';

            const graphApiUrl = `api/api_get_neo4j_graph.php?patient_id=${patientId}&timestamp_text=${encodeURIComponent(timestampText)}`;

            // Fetch ONLY the graph data
            secureFetch(graphApiUrl)
            .then(graphData => {
                if (graphData.error) { throw new Error(graphData.error); }
                
                graphSpinner.style.display = 'none';
                graphContainer.style.display = 'block';
                renderGraph(graphData); // Render the graph

                // SUCCESS: Now show the algorithm dropdown for the next step
                algorithmSelectionWrapper.style.display = 'block';
                algorithmSelect.disabled = false;
            })
            .catch(error => {
                graphSpinner.style.display = 'none';
                // Display the error in the recommendation text area as it's a prominent spot
                recommendationSection.style.display = 'block';
                recommendationText.style.display = 'block';
                recommendationText.className = 'alert alert-danger';
                recommendationText.textContent = 'Failed to load knowledge graph: ' + error.message;
                console.error('Graph Fetch Error:', error);
            });
        });

        // --- Main Trigger 2: Algorithm Dropdown (Fetches the Recommendation) ---
        algorithmSelect.addEventListener('change', function() {
            const patientId = patientSelect.value;
            const dataId = timestampSelect.value; // The value is the data_id or 'all'
            const algorithm = this.value;

            // UI Reset
            recommendationSection.style.display = 'none';
            summaryHint.style.display = 'none';

            if (!patientId || !dataId || !algorithm) return;

            // Show the recommendation section and its spinner
            recommendationSection.style.display = 'block';
            recommendationSpinner.style.display = 'block';
            recommendationText.style.display = 'none';

            const recommendationApiUrl = `api/api_get_recommendation.php?patient_id=${patientId}&data_id=${dataId}&algorithm=${algorithm}`;

            // Fetch ONLY the recommendation data
            secureFetch(recommendationApiUrl)
            .then(recData => {
                recommendationSpinner.style.display = 'none';
                recommendationText.style.display = 'block';
                recommendationText.className = 'alert'; // Reset classes

                if (dataId === 'all') {
                    summaryHint.style.display = 'block';
                }

                if (recData.error) {
                    recommendationText.textContent = recData.error;
                    recommendationText.classList.add('alert-danger');
                    return;
                }
                recommendationText.textContent = recData.recommendations.join('\n');
                recommendationText.classList.add('alert-success');
            })
            .catch(error => {
                recommendationSpinner.style.display = 'none';
                recommendationText.style.display = 'block';
                recommendationText.className = 'alert alert-danger';
                recommendationText.textContent = 'Failed to generate recommendation: ' + error.message;
                console.error('Recommendation Fetch Error:', error);
            });
        });

       function renderGraph(graphData) {
    const nodes = graphData.nodes;
    const links = graphData.links;
    
    const container = document.getElementById('graphContainer');
    const width = container.clientWidth;
    const height = 1000;

    d3.select(container).select("svg").remove();

    const svg = d3.select(container).append("svg")
        .attr("width", width)
        .attr("height", height)
        .attr("viewBox", [0, 0, width, height])
        .attr("style", "max-width: 100%; height: auto;");

    const color = d3.scaleOrdinal(d3.schemeCategory10);

    const simulation = d3.forceSimulation(nodes)
        .force("link", d3.forceLink(links).id(d => d.id).distance(150))
        .force("charge", d3.forceManyBody().strength(-500))
        .force("center", d3.forceCenter(width / 2, height / 2));

    const link = svg.append("g")
        .attr("stroke", "#999")
        .attr("stroke-opacity", 0.6)
      .selectAll("line")
      .data(links)
      .join("line")
        .attr("stroke-width", 1.5);

    const node = svg.append("g")
        .attr("stroke", "#fff")
        .attr("stroke-width", 1.5)
      .selectAll("circle")
      .data(nodes)
      .join("circle")
        .attr("r", d => d.group === 'Patient' ? 25 : 20)
        .attr("fill", d => color(d.group));

    const labels = svg.append("g")
        .attr("class", "node-labels")
      .selectAll("text")
      .data(nodes)
      .join("text")
        .text(d => d.label)
        .attr("text-anchor", "middle")
        .attr("dy", d => d.group === 'Patient' ? 35 : -25)
        .style("font-size", "11px")
        .style("fill", "#333")
        .style("pointer-events", "none");

    node.append("title")
    .text(d => {
        let tooltip = `Group: ${d.group}\nLabel: ${d.label}`;
        if (d.properties && d.properties.time) {
            
            const date = new Date(d.properties.time);

            // We use the same robust formatting as the details panel
            const formattedTime = date.toLocaleString('en-GB', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric', 
                hour: '2-digit', 
                minute: '2-digit', 
                timeZone: 'UTC', // Force the output to be in UTC
                hour12: false
            }) + " UTC";

            tooltip += `\nTime: ${formattedTime}`;
        }
        return tooltip;
    });

    simulation.on("tick", () => {
        link.attr("x1", d=>d.source.x).attr("y1", d=>d.source.y).attr("x2", d=>d.target.x).attr("y2", d=>d.target.y);
        node.attr("cx", d=>d.x).attr("cy", d=>d.y);
        labels.attr("x", d=>d.x).attr("y", d=>d.y);
    });

    // =======================================================
    // START: SINGLE, CORRECT CLICK AND DRAG HANDLER
    // =======================================================
    
    // Function to show the details panel
    function showNodeDetails(d) {
                // 1. Remove the 'selected-node' class from all circles
        node.classed("selected-node", false);
        
        // 2. Find the specific circle that was clicked and add the class to it.
        // We filter the D3 selection to find the node with the matching data ID.
        node.filter(node_d => node_d.id === d.id)
            .classed("selected-node", true);
        const detailPanel = document.getElementById('graphNodeDetailPanel');
        const detailContent = document.getElementById('nodeDetailContent');
        const detailTitle = document.getElementById('nodeDetailTitle');
        
        detailTitle.textContent = `${d.label} Details`;
        
        let htmlContent = '';
        const properties = d.properties;

        htmlContent += `<div class="detail-item"><span class="detail-key">&lt;id&gt;:</span><span>${d.id}</span></div>`;

        if (properties && Object.keys(properties).length > 0) {
            Object.keys(properties).forEach(key => {
                let value = properties[key];

                if (key.includes('_embedding') && Array.isArray(value)) {
                    value = `[${value.slice(0, 3).join(', ')}, ...]`;
                }
                
                if (key === 'time' || key === 'created_at') {
                    // Just display the ISO string, which includes the 'Z' for UTC
                    value = String(value); 
                }

                htmlContent += `<div class="detail-item">
                                  <span class="detail-key">${key}:</span>
                                  <span>${String(value)}</span>
                                </div>`;
            });
        }
        
        detailContent.innerHTML = htmlContent;
        detailPanel.style.display = 'block';
    }

    // Add listener to the panel's close button
    document.getElementById('closeDetailPanel').addEventListener('click', () => {
        document.getElementById('graphNodeDetailPanel').style.display = 'none';
        
        // --- NEW: Also remove the highlight when the panel is closed ---
        node.classed("selected-node", false);
    });

    // Define the D3 drag behavior that ALSO handles clicks
    const dragHandler = d3.drag()
        .on("start", (event, d) => {
            if (!event.active) simulation.alphaTarget(0.3).restart();
            d.fx = d.x;
            d.fy = d.y;
            d._dragged = false; // Custom flag to track if a drag occurred
        })
        .on("drag", (event, d) => {
            d._dragged = true; // If drag event fires, set flag to true
            d.fx = event.x;
            d.fy = event.y;
        })
        .on("end", (event, d) => {
            if (!event.active) simulation.alphaTarget(0);
            
            // If the node was NOT dragged, treat the 'end' event as a 'click'.
            if (!d._dragged) { 
                showNodeDetails(event.subject); // Use event.subject to get the correct data
            }

            d.fx = null;
            d.fy = null;
        });

    // Apply the single, correct handler to the nodes
    node.call(dragHandler);

    // =======================================================
    // END: SINGLE, CORRECT HANDLER
    // =======================================================
}
    });
</script>
</body>
</html>