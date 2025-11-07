# File: sync_from_mysql.py (FINAL CORRECTED VERSION)

import sys
import json
import mysql.connector
from neo4j import GraphDatabase
from datetime import datetime
import subprocess
import os 

import numpy as np
import networkx as nx
import torch
from node2vec import Node2Vec
from torch_geometric.nn import GATConv
from torch_geometric.data import Data

# === MySQL Credentials ===
MYSQL_CONFIG = {
    "host": "localhost",
    "user": "root",
    "password": "toor", # Your XAMPP MySQL password (often blank)
    "database": "health"
}

# === Neo4j Credentials ===
NEO4J_URI = "neo4j+s://340bc0f6.databases.neo4j.io"
NEO4J_USER = "neo4j"
NEO4J_PASS = "BT-cX-O2JPYbbcFJc_y7gEof-EpMyK9_EXfsw9Cf27E"

# === File Paths ===
# Use absolute path for reliability
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
PYTHON_EXECUTABLE = "C:/xamp/htdocs/ctipb/venv312/Scripts/python.exe"

def fetch_graph():
    driver = GraphDatabase.driver(NEO4J_URI, auth=(NEO4J_USER, NEO4J_PASS))
    G = nx.Graph()

    with driver.session() as session:
        result = session.run("""
        MATCH (n)
        WHERE n.data_id IS NOT NULL
        RETURN DISTINCT n.data_id AS id
        """)
        for record in result:
            G.add_node(str(record["id"]))

        result = session.run("""
            MATCH (a)-[:RELATED_TO]-(b)
            WHERE a.data_id IS NOT NULL AND b.data_id IS NOT NULL
            RETURN a.data_id AS a, b.data_id AS b
        """)
        for record in result:
            G.add_edge(str(record["a"]), str(record["b"]))

    driver.close()
    return G

# === GAT Model ===
class GAT(torch.nn.Module):
    def __init__(self, in_channels, out_channels):
        super(GAT, self).__init__()
        self.gat1 = GATConv(in_channels, 64, heads=2, dropout=0.2)
        self.gat2 = GATConv(64 * 2, out_channels, heads=1, concat=False, dropout=0.2)

    def forward(self, x, edge_index):
        x = self.gat1(x, edge_index)
        x = torch.nn.functional.elu(x)
        x = self.gat2(x, edge_index)
        return x

# === Graph Algorithms ===
def run_node2vec(G, filter_nodes=None):
    node2vec = Node2Vec(G, dimensions=128, walk_length=10, num_walks=100, workers=1, quiet=True)
    model = node2vec.fit()
    if filter_nodes is None:
        return {n: model.wv[n].tolist() for n in G.nodes()}
    else:
        return {n: model.wv[n].tolist() for n in filter_nodes if n in model.wv}

def run_ppr(G, filter_nodes=None, target_node=None):
    if target_node is None:
        target_node = list(G.nodes())[0]
    personalization = {node: 0 for node in G.nodes()}
    personalization[target_node] = 1
    full_ppr = nx.pagerank(G, personalization=personalization)
    if filter_nodes is None:
        return full_ppr
    else:
        return {n: full_ppr.get(n, 0) for n in filter_nodes}

def run_gat(G, filter_nodes=None):
    idx_map = {node: i for i, node in enumerate(G.nodes())}
    edge_index = []
    for u, v in G.edges():
        edge_index.append([idx_map[u], idx_map[v]])
        edge_index.append([idx_map[v], idx_map[u]])

    if not edge_index:
        return {node: [0.0] * 128 for node in (filter_nodes or G.nodes())}

    edge_index = torch.tensor(edge_index, dtype=torch.long).t().contiguous()
    x = torch.eye(len(G.nodes()), dtype=torch.float)

    data = Data(x=x, edge_index=edge_index)
    model = GAT(in_channels=len(G.nodes()), out_channels=128)

    model.eval()
    with torch.no_grad():
        embeddings = model(data.x, data.edge_index).numpy()

    if filter_nodes is None:
        return {node: embeddings[idx_map[node]].tolist() for node in G.nodes()}
    else:
        return {node: embeddings[idx_map[node]].tolist() for node in filter_nodes if node in idx_map}

# === Save Insights to MySQL ===
def save_to_mysql(insights):
    print("Saving to MySQL...", file=sys.stderr)
    conn = mysql.connector.connect(**MYSQL_CONFIG)
    cursor = conn.cursor()

    sql = """
        INSERT INTO graph_insights (patient_id, data_id, algorithm, description, created_at)
        VALUES (%s, %s, %s, %s, %s)
        ON DUPLICATE KEY UPDATE
            description = VALUES(description),
            created_at = VALUES(created_at)
    """

    inserted = 0
    skipped = 0

    for item in insights:
        data_id = item["data_id"]

        # Get corresponding patient_id
        cursor.execute("SELECT patient_id FROM health_data WHERE data_id = %s", (data_id,))
        result = cursor.fetchone()

        if result is None:
            print(f"Skipped {data_id} — patient_id not found.")
            skipped += 1
            continue

        patient_id = result[0]
        created_at = datetime.now()

        try:
            cursor.execute(sql, (patient_id, data_id, "node2vec", json.dumps(item["node2vec_embedding"]), created_at))
            cursor.execute(sql, (patient_id, data_id, "ppr", str(item["ppr_score"]), created_at))
            cursor.execute(sql, (patient_id, data_id, "gat", json.dumps(item["gat_embedding"]), created_at))
            inserted += 3
        except mysql.connector.Error as err:
            print(f"Skipped {data_id} due to error: {err}")
            continue

    conn.commit()
    cursor.close()
    conn.close()
    print(f"Inserted or updated {inserted} rows.", file=sys.stderr)

# === Save Insights to Neo4j ===
def save_to_neo4j(insights):
    print("Saving to Neo4j...", file=sys.stderr)
    driver = GraphDatabase.driver(NEO4J_URI, auth=(NEO4J_USER, NEO4J_PASS))
    with driver.session() as session:
        for item in insights:
            session.run(
                """
                MATCH (n) WHERE toString(n.data_id) = $data_id
                SET n.node2vec_embedding = $node2vec,
                    n.ppr_score = $ppr,
                    n.gat_embedding = $gat
                """,
                {
                    "data_id": str(item["data_id"]),
                    "node2vec": item["node2vec_embedding"],
                    "ppr": item["ppr_score"],
                    "gat": item["gat_embedding"]
                }
            )
    driver.close()
    print("Insights updated in Neo4j", file=sys.stderr)

def run_algorithms(data_ids=None):
    print("Fetching graph from Neo4j for algorithm run...", file=sys.stderr)
    G = fetch_graph()
    if not G.nodes():
        print("WARNING: Graph is empty. Cannot run algorithms.", file=sys.stderr)
        return
        
    print(f"Graph loaded: {len(G.nodes())} nodes, {len(G.edges())} edges", file=sys.stderr)

    nodes_to_process = [str(d) for d in data_ids if str(d) in G.nodes()] if data_ids else list(G.nodes())
    if not nodes_to_process:
        print("INFO: No new nodes found in the graph to process.", file=sys.stderr)
        return

    print(f"Processing {len(nodes_to_process)} nodes with algorithms...", file=sys.stderr)
    print("Running Node2Vec...", file=sys.stderr)
    n2v = run_node2vec(G, nodes_to_process)
    print("Running PPR...", file=sys.stderr)
    ppr = run_ppr(G, nodes_to_process)
    print("Running GAT...", file=sys.stderr)
    gat = run_gat(G, nodes_to_process)

    insights = []
    for node in nodes_to_process:
        insights.append({
            "data_id": node,
            "node2vec_embedding": n2v.get(node, []), "ppr_score": ppr.get(node, 0), "gat_embedding": gat.get(node, [])
        })

    if insights:
        save_to_mysql(insights)
        save_to_neo4j(insights)
    else:
        print("No insights were generated.")

# ===================================================================
# MAIN ORCHESTRATOR FUNCTION
# ===================================================================
def main():
    try:
        # 1. Fetch new data from MySQL using the 'is_synced' flag.
        print("Fetching unsynced data from MySQL...", file=sys.stderr)
        mysql_conn = mysql.connector.connect(**MYSQL_CONFIG)
        cursor = mysql_conn.cursor(dictionary=True)
        
        # The SQL query is changed to select rows where is_synced is 0.
        cursor.execute("SELECT * FROM health_data WHERE is_synced = 0 ORDER BY data_id ASC")
        rows = cursor.fetchall()
        
        cursor.close()
        mysql_conn.close()

        if not rows:
            print(json.dumps({"message": "No new health data to sync."}))
            return

        print(f"Found {len(rows)} new records to sync.", file=sys.stderr)
        # Collect the data_ids of the rows we are about to process.
        data_ids_to_update = [row['data_id'] for row in rows]

        # 3. Sync each row to Neo4j
        neo4j_driver = GraphDatabase.driver(NEO4J_URI, auth=(NEO4J_USER, NEO4J_PASS))

        with neo4j_driver.session() as session:
            for row in rows:
                # Create Patient Node
                session.run('MERGE (p:Patient {id: $pid})', pid=str(row['patient_id']))

                # --- FIX #3: Completed the health nodes map ---
                health_nodes_map = {
                    'GlucoseReading': {'prop': 'level', 'value': row.get('cgm_level')},
                    'BloodPressure':  {'prop': 'value', 'value': row.get('blood_pressure')},
                    'HeartRate':      {'prop': 'bpm', 'value': row.get('heart_rate')},
                    'Cholesterol':    {'prop': 'value', 'value': row.get('cholesterol')},
                    'InsulinIntake':  {'prop': 'units', 'value': row.get('insulin_intake')},
                    'Meal':           {'prop': 'description', 'value': row.get('food_intake')},
                    'Activity':       {'prop': 'description', 'value': row.get('activity_level')},
                    'Weight':         {'prop': 'kg', 'value': row.get('weight')},
                    'HbA1c':          {'prop': 'percentage', 'value': row.get('hb1ac')}
                }
                
                # This correctly parses the full timestamp like "05/11/2025 04:30"
                date_obj = datetime.strptime(row['timestamp'], '%d/%m/%Y %H:%M')

                # This now correctly converts the full date and time into the ISO 8601 format.
                iso_time = date_obj.isoformat() + "Z"

                for label, data in health_nodes_map.items():
                    if data['value'] is not None and data['value'] != '':
                        session.run(
                            f"MERGE (n:{label} {{data_id: $did}}) "
                            f"SET n.{data['prop']} = $val, n.time = datetime($ts) "
                            "WITH n "
                            "MATCH (p:Patient {id: $pid}) "
                            "MERGE (p)-[:RECORDS_FOR]->(n)",
                            did=row['data_id'], val=data['value'], ts=iso_time, pid=str(row['patient_id'])
                        )
                        
            # Link related nodes
            session.run(
                "MATCH (p:Patient)-[:RECORDS_FOR]->(a), (p)-[:RECORDS_FOR]->(b) "
                "WHERE a <> b AND a.time = b.time AND NOT (a)-[:RELATED_TO]-(b) "
                "MERGE (a)-[:RELATED_TO]-(b)"
            )
        neo4j_driver.close()

        # NEW: Update the 'is_synced' flag in MySQL for the processed rows.
        print(f"Updating is_synced flag for {len(data_ids_to_update)} records...", file=sys.stderr)
        mysql_conn_update = mysql.connector.connect(**MYSQL_CONFIG)
        cursor_update = mysql_conn_update.cursor()
        
        # This creates a safe query like "UPDATE ... WHERE data_id IN (%s, %s, %s)"
        format_strings = ','.join(['%s'] * len(data_ids_to_update))
        update_query = f"UPDATE health_data SET is_synced = 1 WHERE data_id IN ({format_strings})"
        
        cursor_update.execute(update_query, tuple(data_ids_to_update))
        mysql_conn_update.commit()
        
        cursor_update.close()
        mysql_conn_update.close()
        print("MySQL flags updated successfully.", file=sys.stderr)

        # 5. Call the algorithm function DIRECTLY
        new_data_ids = [row['data_id'] for row in rows]
        run_algorithms(new_data_ids)

        print(json.dumps({"message": f"{len(rows)} record(s) synced and analyzed successfully."}))
        
    except Exception as e:
        # Print a clear JSON error if anything fails
        print(json.dumps({"error": f"An error occurred in sync_from_mysql.py: {type(e).__name__} - {str(e)}"}))

if __name__ == "__main__":
    main()