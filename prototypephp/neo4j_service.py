# File: neo4j_service.py (FINAL CORRECTED VERSION)

import sys
import json
import datetime
from neo4j import GraphDatabase
from neo4j.time import DateTime as Neo4jDateTime, Date as Neo4jDate, Time as Neo4jTime, Duration as Neo4jDuration

# --- Configuration ---
NEO4J_URI = "neo4j+s://340bc0f6.databases.neo4j.io"
NEO4J_USER = "neo4j"
NEO4J_PASSWORD = "BT-cX-O2JPYbbcFJc_y7gEof-EpMyK9_EXfsw9Cf27E"

# --- Error Logging ---
LOG_FILE = "neo4j_service_error.log"
def log_error(message):
    with open(LOG_FILE, "a", encoding="utf-8") as f:
        f.write(f"{datetime.datetime.now()}: {message}\n")

# --- Custom JSON Encoder (Unchanged) ---
class DateTimeEncoder(json.JSONEncoder):
    def default(self, obj):
        if isinstance(obj, (datetime.datetime, datetime.date, datetime.time)):
            return obj.isoformat()
        if isinstance(obj, (Neo4jDateTime, Neo4jDate, Neo4jTime)):
            return obj.iso_format()
        if isinstance(obj, Neo4jDuration):
            return str(obj)
        return super().default(obj)

def main():
    try:
        # 1. Read and validate arguments
        patient_id_str = str(sys.argv[1])
        timestamp_str = sys.argv[2] if len(sys.argv) > 2 else None

        # 2. Connect to Neo4j
        driver = GraphDatabase.driver(NEO4J_URI, auth=(NEO4J_USER, NEO4J_PASSWORD))
        
        with driver.session() as session:
            # 3. Choose and run the correct Cypher query
            if timestamp_str in (None, 'all', ''):
                # This query fetches ALL measurements for the "View All" option
                cypher = """
                MATCH (p:Patient {id: $pid})-[:RECORDS_FOR]->(m)
                WITH p, m ORDER BY m.time ASC
                WITH p, collect(m) as measurements
                RETURN p, measurements
                """
                params = {"pid": patient_id_str}
            else:
                # ===================================================================
                # START: THIS IS THE CORRECTED QUERY FOR A SINGLE DAY
                # ===================================================================
                cypher = """
                MATCH (p:Patient {id: $pid})-[:RECORDS_FOR]->(m)
                // This WHERE clause finds all records within the 24-hour period
                // starting from the provided timestamp ($ts).
                WHERE m.time = datetime($ts)
                // Collect the ordered results into a list
                WITH p, collect(m) as measurements
                RETURN p, measurements
                """
                # ===================================================================
                # END: CORRECTED QUERY
                # ===================================================================
                params = {"pid": patient_id_str, "ts": timestamp_str}

            result = session.run(cypher, params).single()

        driver.close()

        if not result or not result["measurements"]:
            print(json.dumps({"nodes": [], "links": [], "message": "No graph data found for these parameters."}))
            return

        # 4. Process the results to build the graph (This part is now the same for both cases)
        p_node = result["p"]
        measurements = result["measurements"] 

        nodes = [{
            "id": p_node.element_id, 
            "label": p_node.get("full_name", "Patient"),
            "group": "Patient",
            "properties": dict(p_node)
        }]
        links = []

        for m in measurements:
            props = dict(m)
            node_type = list(m.labels)[0]

             # 1. Find the primary value of the node, just like before.
            primary_value = (
                props.get("level") or props.get("cgm_level") or props.get("value") or
                props.get("blood_pressure") or props.get("heart_rate") or props.get("bpm") or 
                props.get("cholesterol") or props.get("hb1ac") or props.get("percentage") or 
                props.get("insulin_intake") or props.get("units") or props.get("weight") or 
                props.get("kg") or props.get("food_intake") or props.get("activity_level") or 
                props.get("description")
            )

            # 2. Create a clean, readable label.
            # If a primary value exists, combine it with the node type.
            # Otherwise, just use the node type.
            if primary_value is not None:
                # This will create labels like "GlucoseReading: 8.7" or "HeartRate: 76"
                label_value = f"{node_type}: {primary_value}"
            else:
                # Fallback for nodes that might not have a simple value
                label_value = node_type
                    
            # ===================================================================
            # END: CORRECTED LABEL GENERATION LOGIC
            # ===================================================================

            nodes.append({
                "id": m.element_id,
                "label": label_value,  # Use our new, improved label
                "group": node_type,
                "properties": props
            })

            links.append({
                "source": p_node.element_id,
                "target": m.element_id,
                "type": "RECORDS_FOR"
            })

        if len(measurements) > 1:
            for i in range(len(measurements) - 1):
                source_node = measurements[i]
                target_node = measurements[i+1]
                
                links.append({
                    "source": source_node.element_id,
                    "target": target_node.element_id,
                    "type": "NEXT" 
                })

        print(json.dumps({"nodes": nodes, "links": links}, cls=DateTimeEncoder))

    except Exception as e:
        log_error(str(e))
        print(json.dumps({"error": f"Neo4j Python Service Error: {e}"}))


if __name__ == "__main__":
    main()