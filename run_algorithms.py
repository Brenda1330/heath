#!/usr/bin/env python3
import sys
import json
import numpy as np
import networkx as nx
import torch
from node2vec import Node2Vec
from torch_geometric.nn import GATConv
from torch_geometric.data import Data
from neo4j import GraphDatabase
import mysql.connector
from datetime import datetime

# === Neo4j credentials ===
NEO4J_URI = "neo4j+s://340bc0f6.databases.neo4j.io"
NEO4J_USER = "neo4j"
NEO4J_PASS = "BT-cX-O2JPYbbcFJc_y7gEof-EpMyK9_EXfsw9Cf27E"

# === MySQL credentials ===
MYSQL = {
    "host": "localhost",
    "port": 3306,
    "user": "root",
    "password": "",
    "database": "health"
}

# === Connect to Neo4j and fetch graph ===
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
    node2vec = Node2Vec(G, dimensions=128, walk_length=10, num_walks=100, workers=1)
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
    print("💾 Saving to MySQL...")
    conn = mysql.connector.connect(**MYSQL)
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
            print(f"⚠️ Skipped {data_id} — patient_id not found.")
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
            print(f"⚠️ Skipped {data_id} due to error: {err}")
            continue

    conn.commit()
    cursor.close()
    conn.close()
    print(f"✅ Inserted or updated {inserted} rows.")

# === Save Insights to Neo4j ===
def save_to_neo4j(insights):
    print("💾 Saving to Neo4j...")
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
    print("✅ Insights updated in Neo4j")

# === Main Runner ===
def run_algorithms(data_ids=None):
    print("🔄 Fetching graph from Neo4j...")
    G = fetch_graph()
    print(f"✅ Graph loaded: {len(G.nodes())} nodes, {len(G.edges())} edges")

    if data_ids:
        # Only process nodes that exist in both data_ids and G
        nodes_to_process = [str(d) for d in data_ids if str(d) in G.nodes()]
    else:
        nodes_to_process = list(G.nodes())


    print(f"✅ Processing {len(nodes_to_process)} nodes")

    print("🧠 Running Node2Vec...")
    n2v = run_node2vec(G, nodes_to_process)

    print("📈 Running PPR...")
    ppr = run_ppr(G, nodes_to_process)

    print("🧠 Running GAT...")
    gat = run_gat(G, nodes_to_process)

    insights = []
    for node in nodes_to_process:
        insights.append({
            "data_id": node,
            "node2vec_embedding": n2v.get(node, []),
            "ppr_score": ppr.get(node, 0),
            "gat_embedding": gat.get(node, [])
        })

    save_to_mysql(insights)
    save_to_neo4j(insights)

if __name__ == "__main__":
    # Accept JSON string argument from command line for new data IDs
    if len(sys.argv) > 1:
        try:
            data_ids = json.loads(sys.argv[1])
        except Exception:
            print("❌ Failed to parse data IDs argument")
            data_ids = None
    else:
        data_ids = None

    run_algorithms(data_ids)
