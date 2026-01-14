<?php
// File: templates/footer_debug_scripts.php
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<!-- D3.js Library for Graph Visualization -->
<script src="https://d3js.org/d3.v7.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // --- Element Selections ---
        const runTestBtn = document.getElementById('runTestBtn');
        const patientIdInput = document.getElementById('patientIdInput');
        const outputSection = document.getElementById('outputSection');
        const loadingSpinner = document.getElementById('loadingSpinner');
        const jsonOutput = document.getElementById('jsonOutput');
        const graphContainer = document.getElementById('graphContainer');

        // --- Main Trigger: Run Test Button ---
        runTestBtn.addEventListener('click', function() {
            const patientId = patientIdInput.value;

            if (!patientId) {
                alert('Please enter a Patient ID to test.');
                return;
            }

            // --- Reset UI and Show Spinner ---
            outputSection.style.display = 'block';
            loadingSpinner.style.display = 'block';
            jsonOutput.textContent = 'Fetching...';
            graphContainer.innerHTML = ''; // Clear previous graph

            // --- Make the API Call ---
            // We call the API with "all" timestamps to get the full graph
            fetch(`api/api_get_neo4j_graph.php?patient_id=${patientId}&timestamp_text=all`)
            .then(response => {
                // First, get the raw text of the response to see exactly what the server sent
                return response.text().then(text => {
                    // Try to parse it as JSON
                    try {
                        const data = JSON.parse(text);
                        return data;
                    } catch (e) {
                        // If parsing fails, it means the server sent back an HTML error page
                        throw new Error("Invalid JSON response. Raw server output:\n\n" + text);
                    }
                });
            })
            .then(data => {
                loadingSpinner.style.display = 'none';
                
                // Display the pretty-printed JSON response
                jsonOutput.textContent = JSON.stringify(data, null, 2);

                if (data.error) {
                    jsonOutput.classList.add('alert-danger');
                    jsonOutput.classList.remove('alert-secondary');
                    throw new Error(data.error);
                }
                
                jsonOutput.classList.add('alert-success');
                jsonOutput.classList.remove('alert-secondary');

                // If we have nodes, render the graph
                if (data.nodes && data.nodes.length > 0) {
                    renderGraph(data);
                } else {
                    graphContainer.innerHTML = `<div class='alert alert-warning'>${data.message || 'The query returned no nodes or links.'}</div>`;
                }
            })
            .catch(error => {
                loadingSpinner.style.display = 'none';
                jsonOutput.textContent = error.message;
                jsonOutput.classList.add('alert-danger');
                jsonOutput.classList.remove('alert-secondary');
                console.error('Fetch Error:', error);
            });
        });

        // --- D3.js Graph Rendering Function ---
        // --- D3.js Graph Rendering Function ---
function renderGraph(graph) {
    const width = graphContainer.clientWidth;
    const height = 400;

    const svg = d3.select("#graphContainer").append("svg")
        .attr("width", width)
        .attr("height", height)
        .attr("viewBox", [0, 0, width, height])
        .attr("style", "max-width: 100%; height: auto;");
    
    const color = d3.scaleOrdinal(d3.schemeCategory10);

    const simulation = d3.forceSimulation(graph.nodes)
        .force("link", d3.forceLink(graph.links).id(d => d.id).distance(120))
        .force("charge", d3.forceManyBody().strength(-400))
        .force("center", d3.forceCenter(width / 2, height / 2))
        .on("tick", ticked); // This line calls the ticked function on every simulation step

    const link = svg.append("g")
        .selectAll("line")
        .data(graph.links)
        .join("line")
        .attr("class", "link");

// --- NEW, CORRECTED CODE ---
const nodeGroup = svg.append("g")
    .selectAll("g")
    .data(graph.nodes)
    .join("g");

// Add the circle to the group
nodeGroup.append("circle")
    .attr("class", "node")
    .attr("r", 20)
    .attr("fill", d => color(d.group)); // Use 'group' for color, 'label' for text

// Add the text label to the group
nodeGroup.append("text")
    .attr("class", "node-label")
    .text(d => d.label)
    .attr('text-anchor', 'middle')
    .attr('dy', '0.3em') // Vertically center
    .style('font-size', '10px')
    .style('fill', '#000000ff'); // Make text black for better contrast

    // --- THIS IS THE MISSING FUNCTION ---
    // --- NEW, CORRECTED CODE ---
function ticked() {
    link
        .attr("x1", d => d.source.x)
        .attr("y1", d => d.source.y)
        .attr("x2", d => d.target.x)
        .attr("y2", d => d.target.y);

    // Now we only need to move the entire group
    nodeGroup
        .attr("transform", d => `translate(${d.x},${d.y})`);
}
        }
    });
</script>
</body>
</html>