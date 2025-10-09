console.log('=== SYNC.JS STARTED ===');

require('dotenv').config();
const express = require('express');
const mysql = require('mysql2/promise');
const neo4j = require('neo4j-driver');
const { spawn } = require('child_process');
const fs = require('fs');
const path = require('path');

const app = express();
const PORT = 3000;

const LAST_SYNC_FILE = path.join(__dirname, 'last_synced_id.txt');

// === MySQL config ===
const MYSQL = {
  host: 'localhost',    // was: RDS endpoint
  port: 3306,           // default MySQL port
  user: 'root',         // or your local MySQL user
  password: '',
  database: 'health'
};

// === Neo4j config ===
const driver = neo4j.driver(
  'neo4j+s://340bc0f6.databases.neo4j.io',
  neo4j.auth.basic('neo4j', 'BT-cX-O2JPYbbcFJc_y7gEof-EpMyK9_EXfsw9Cf27E')
);

// === Helper: Convert MySQL timestamp to ISO 8601 ===
function parseMySQLDatetime(mysqlDatetime, data_id) {
  if (!mysqlDatetime) throw new Error("Empty timestamp");

  // If it's already a JS Date object, convert directly
  if (mysqlDatetime instanceof Date) {
    return mysqlDatetime.toISOString();
  }

  // Otherwise, assume it's a string and try regex parsing
  const str = String(mysqlDatetime);

  // Format 1: DD/MM/YYYY HH:mm
  let match = str.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4}) (\d{1,2}):(\d{2})$/);
  if (match) {
    const [_, day, month, year, hour, minute] = match;
    return new Date(
      `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}T${hour.padStart(2, '0')}:${minute}:00Z`
    ).toISOString();
  }

  // Format 2: YYYY-MM-DD HH:mm[:ss]
  match = str.match(/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2})(?::(\d{2}))?$/);
  if (match) {
    const [_, year, month, day, hour, minute, second = '00'] = match;
    return new Date(
      `${year}-${month}-${day}T${hour}:${minute}:${second}Z`
    ).toISOString();
  }

  console.log(`❌ data_id ${data_id}: Failed parsing`, mysqlDatetime);
  throw new Error("Invalid time format: " + mysqlDatetime);
}

async function syncHealthDataToNeo4j() {
  console.log(`🔄 Syncing ALL health records with data_id >= 10000 from MySQL to Neo4j...`);

  const connection = await mysql.createConnection(MYSQL);

  // Pull everything, no LIMIT, no lastSyncedId filter
  const [rows] = await connection.query(
    `SELECT * FROM health_data 
     WHERE data_id >= 10000 
     ORDER BY data_id ASC`
  );

  console.log(`🚦 Pulled ${rows.length} row(s) from MySQL`);
  if (rows.length === 0) {
    await connection.end();
    return [];
  }

  const session = driver.session();
  let processed = 0;
  let maxDataId = 0;

  for (const row of rows) {
    try {
      console.log(`\n🔄 Processing record #${processed + 1} — data_id ${row.data_id}`);
      const isoTime = parseMySQLDatetime(row.timestamp, row.data_id);

      await session.run(`MERGE (p:Patient {id: $patient_id})`, {
        patient_id: row.patient_id.toString()
      });

      const queries = [
        ['GlucoseReading', 'level', row.cgm_level],
        ['BloodPressure', 'value', row.blood_pressure],
        ['HeartRate', 'bpm', row.heart_rate],
        ['Cholesterol', 'value', row.cholesterol],
        ['InsulinIntake', 'units', row.insulin_intake],
        ['Meal', 'description', row.food_intake],
        ['Activity', 'description', row.activity_level],
        ['Weight', 'kg', row.weight],
        ['HbA1c', 'percentage', row.hb1ac]
      ];

      for (const [label, prop, value] of queries) {
        if (value === null || value === undefined || value === '') continue;
        await session.run(
          `MERGE (n:${label} {data_id: $data_id})
           SET n.${prop} = $value,
               n.time = datetime($timestamp)
           WITH n
           MATCH (p:Patient {id: $patient_id})
           MERGE (p)-[:RECORDS_FOR]->(n)`,
          {
            data_id: row.data_id,
            value,
            timestamp: isoTime,
            patient_id: row.patient_id.toString()
          }
        );
      }

      processed++;
      if (row.data_id > maxDataId) maxDataId = row.data_id;
    } catch (e) {
      console.error(`❌ Failed for data_id ${row.data_id}:`, e.message);
    }
  }

  console.log('\n🔗 Linking health nodes by patient & timestamp...');
  const result = await session.run(`
    MATCH (p:Patient)-[:RECORDS_FOR]->(a), (p)-[:RECORDS_FOR]->(b)
    WHERE a <> b AND a.time = b.time AND NOT (a)-[:RELATED_TO]-(b)
    MERGE (a)-[:RELATED_TO]-(b)
  `);

  console.log(`✅ Linked nodes: ${result.summary.counters.updates().relationshipsCreated} new RELATED_TO relationships`);

  await session.close();
  await connection.end();

  // No more writing lastSyncedId
  console.log(`✅ Sync complete.`);
  return rows.map(r => r.data_id);
}

app.get('/sync', async (req, res) => {
  console.log('➡️  /sync invoked');
  let newDataIds;
  try {
    newDataIds = await syncHealthDataToNeo4j();
  } catch (err) {
    console.error('❌ Error syncing data:', err);
    return res.status(500).json({ message: 'Sync error', error: err.toString() });
  }

  if (newDataIds.length === 0) {
    console.log('ℹ️ No new records to process.');
    return res.status(200).json({ message: 'No new records to sync' });
  }

  console.log('💻 Running run_algorithms.py...');
  const py = spawn(
  '/opt/anaconda3/envs/sync-env/bin/python3',
  ['run_algorithms.py'],
  { cwd: __dirname }
);

  // Optional: Stream python output to console for debugging
  py.stdout.on('data', data => process.stdout.write(data));
  py.stderr.on('data', data => process.stderr.write(data));

  py.on('close', code => {
    if (code === 0) {
      console.log('✅ run_algorithms.py finished successfully.');
      res.status(200).json({ message: 'Sync complete — algorithms run' });
    } else {
      console.error('❌ run_algorithms.py failed with code', code);
      res.status(500).json({ message: 'Sync done, but run_algorithms.py failed', code });
    }
  });
});


// --- CLI MODE: RUN SYNC IF CALLED VIA "node sync.js" ---
if (require.main === module) {
  syncHealthDataToNeo4j()
    .then((newDataIds) => {
      if (newDataIds.length === 0) {
        console.log("ℹ️ No new records to process.");
        process.exit(0);
      }
      // Run the Python algorithm script after syncing
      console.log('💻 Running run_algorithms.py...');
      const py = spawn(
        '/opt/anaconda3/envs/sync-env/bin/python3',
        ['run_algorithms.py'],
        { cwd: __dirname }
      );
      py.stdout.on('data', data => process.stdout.write(data));
      py.stderr.on('data', data => process.stderr.write(data));
      py.on('close', code => {
        if (code === 0) {
          console.log('✅ run_algorithms.py finished successfully.');
          process.exit(0);
        } else {
          console.error('❌ run_algorithms.py failed with code', code);
          process.exit(1);
        }
      });
    })
    .catch((err) => {
      console.error("❌ Error during sync:", err);
      process.exit(1);
    });
}

