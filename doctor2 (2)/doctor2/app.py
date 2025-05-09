from flask import Flask, render_template, request, jsonify, redirect, session, flash, url_for, make_response
from neo4j import GraphDatabase
import mysql.connector
import csv
from reportlab.lib.pagesizes import letter
from reportlab.pdfgen import canvas
from io import BytesIO, StringIO
from datetime import timedelta, datetime
from itsdangerous import URLSafeTimedSerializer


app = Flask(__name__)
app.secret_key = 'password'  # Secure the session with a secret key
app.config['SESSION_TYPE'] = 'filesystem'
# Initialize the Neo4j driver once
neo4j_driver = GraphDatabase.driver(
    "neo4j+s://8a6d0f01.databases.neo4j.io",
    auth=("neo4j", "QQoj0RBDAwoTv3n2uFu4ZvvvethNy6mZsQ_jpN7W-U4")
)

# Setup the serializer
s = URLSafeTimedSerializer(app.config['SECRET_KEY'])

# MySQL connection configuration
def get_db_connection():
    return mysql.connector.connect(
        host='localhost',
        user='root',
        password='',
        database='health'
    )

def log_action(user_id, action, target_id=None, status='success'):
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute("""
            INSERT INTO system_logs (user_id, action, target_id, timestamp, status)
            VALUES (%s, %s, %s, NOW(), %s)
        """, (user_id, action, target_id, status))
        conn.commit()
        cursor.close()
        conn.close()
    except Exception as e:
        print(f"[LOGGING ERROR] Failed to insert log: {e}")


@app.route('/')
def home():
    remember_token = request.cookies.get('remember_token')  # Retrieve the cookie
    if remember_token:
        print("Remember Me cookie is present.")
        try:
            # Decrypt the token to get the user_id
            user_id = s.loads(remember_token, max_age=timedelta(days=30))
            print(f"User ID from Remember Me token: {user_id}")

            conn = get_db_connection()
            cursor = conn.cursor(dictionary=True)
            cursor.execute("SELECT user_id, username FROM users WHERE user_id = %s", (remember_token,))
            doctor = cursor.fetchone()
            cursor.close()
            conn.close()

            if doctor:
                session['user_id'] = doctor['user_id']
                session['username'] = doctor['username']
                return redirect('/doc_dashboard.html')  # Redirect to doctor dashboard if token is valid

            else:
                print("Invalid session or cookie. Please log in again.")
        except Exception as e:
            print(f"Error loading user from token: {e}")
            flash("Session expired or invalid. Please log in again.")
            return redirect('/login')
    return render_template('login.html')

@app.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        email = request.form['email']
        password = request.form['password']

        # Debugging: Print email and password
        print(f"Email: {email}, Password: {password}")

        # Connect to the database and validate login
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT user_id, username, role FROM users WHERE email = %s AND password_hash = %s", (email, password))
        user = cursor.fetchone()
        cursor.close()
        conn.close()

        if user:
            # ✅ Log successful login
            log_action(user['user_id'], action='Login', status='success')

            session['user_id'] = user['user_id']
            session['username'] = user['username']

            remember_me = request.form.get('remember_me')
            if remember_me:
                token = s.dumps(user['user_id'])
                print(f"Generated token: {token}")

                if user["role"] == "doctor":
                    resp = make_response(redirect('/doc_dashboard.html'))
                    resp.set_cookie('remember_token', token, max_age=timedelta(days=30))
                    print("Remember me cookie set")
                    return resp
                elif user["role"] == "admin":
                    resp = make_response(redirect('/admin_dashboard.html'))
                    resp.set_cookie('remember_token', token, max_age=timedelta(days=30))
                    print("Remember me cookie set")
                    return resp

            if user['role'] == 'doctor':
                flash(f"Welcome, {user['username']}!")
                return redirect('/doc_dashboard.html')
            elif user['role'] == 'admin':
                flash(f"Welcome, {user['username']}!")
                return redirect('/admin_dashboard.html')

        else:
            # ✅ Log failed login attempt (user_id is None)
            log_action(user_id=None, action='Login', status='fail')

            flash('Invalid credentials, please try again.')
            return render_template('login.html')

    return render_template('login.html')


@app.route('/doc_dashboard.html')
def dashboard_page():
    if 'user_id' not in session:
        remember_token = request.cookies.get('remember_token')
        if remember_token:
            try:
                # Decrypt the token to get the user_id
                user_id = s.loads(remember_token, max_age=timedelta(days=30))
                session['user_id'] = user_id  # Store in session

                # Fetch the username associated with the user_id
                conn = get_db_connection()
                cursor = conn.cursor(dictionary=True)
                cursor.execute("SELECT username FROM users WHERE user_id = %s", (user_id,))
                doctor = cursor.fetchone()
                cursor.close()
                conn.close()

                if doctor:
                    session['username'] = doctor['username']
                else:
                    flash('Invalid session or cookie. Please log in again.')
                    return redirect('/login')

            except:
                flash('Session expired or invalid. Please log in again.')
                return redirect('/login')
        else:
            flash('You need to log in first.')
            return redirect('/login')

    user_id = session['user_id']  # Retrieve the user_id from session
    print(f"Logged in user_id: {user_id}")

    conn = None
    doctor = None
    patients = []

    try:
        # Connect to the database and fetch the doctor's full name from users table
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT username FROM users WHERE user_id = %s", (user_id,))
        doctor = cursor.fetchone()  # Fetch the doctor's data

        if doctor is None:
            flash('Doctor not found')
            return redirect('/login')

        # Fetch the patient's data for the logged-in doctor
        cursor.execute("""
            SELECT p.patient_id, p.full_name, p.dob, p.gender, p.status, p.created_at,
                h.cgm_level, h.timestamp AS last_updated
            FROM patients p
            LEFT JOIN (
                SELECT patient_id, cgm_level, timestamp
                FROM health_data
                WHERE (patient_id, timestamp) IN (
                    SELECT patient_id, MAX(timestamp)
                    FROM health_data
                    GROUP BY patient_id
                )
            ) h ON p.patient_id = h.patient_id
            WHERE p.doctor_id = %s
        """, (user_id,))
        
        patients = cursor.fetchall()

        # Format the 'created_at' and 'last_updated' fields for each patient
        for patient in patients:
            # Format created_at
            if patient.get('created_at'):
                patient['created_at'] = patient['created_at'].strftime('%d %b %Y')

            # Format last_updated
            raw = patient.get('last_updated')
            dt = None

            if raw:
                if isinstance(raw, str):
                    # Try different date formats
                    try:
                        dt = datetime.strptime(raw, '%d/%m/%Y %H:%M')
                    except ValueError:
                        try:
                            dt = datetime.fromisoformat(raw)
                        except ValueError:
                            try:
                                dt = datetime.strptime(raw, '%Y-%m-%d %H:%M:%S')
                            except ValueError:
                                dt = None
                elif isinstance(raw, datetime):
                    dt = raw

            # Format or mark as N/A
            if dt:
                patient['last_updated'] = dt.strftime('%d %b %Y %H:%M')
            else:
                patient['last_updated'] = "N/A"

    except mysql.connector.Error as e:
        flash(f"Database error: {e}")
        return redirect('/login')

    finally:
        if conn:
            conn.close()

    return render_template('doc_dashboard.html', doctor=doctor, patients=patients)



@app.route('/doc_addpatient.html', methods=['GET', 'POST'])
def add_patient_page():
    if 'user_id' not in session:
        flash('You need to log in first.')
        return redirect('/login')  # Ensure doctor is logged in

    user_id = session['user_id']  # Doctor's user_id from session
    
    if request.method == 'POST':
        full_name = request.form.get('full_name')
        dob = request.form.get('dob')
        gender = request.form.get('gender')
        status = request.form.get('status')

        if not full_name or not dob or not gender or not status:
            flash('Please fill out all the fields.')
            return render_template('doc_addpatient.html')

        conn = get_db_connection()
        cursor = conn.cursor()

        try:
            cursor.execute("""
                INSERT INTO patients (full_name, dob, gender, status, doctor_id)
                VALUES (%s, %s, %s, %s, %s)
            """, (full_name, dob, gender, status, user_id))

            new_patient_id = cursor.lastrowid  # 🔹 Capture new patient's ID
            conn.commit()

            # 🔹 Log the action
            log_action(user_id, action='Add Patient', target_id=new_patient_id, status='success')

            flash('Patient added successfully!')

        except mysql.connector.Error as e:
            flash(f"Database error: {e}")
        finally:
            cursor.close()
            conn.close()

        return redirect('/doc_patientlist.html')

    return render_template('doc_addpatient.html')


@app.route('/doc_patientlist.html')
def patient_list_page():
    if 'user_id' not in session:
        flash('You need to log in first.')
        return redirect('/login')  # Ensure doctor is logged in

    user_id = session['user_id']  # Get the user_id from the session

    # Fetch the list of patients for this specific doctor
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT patient_id, full_name, dob, gender, status, created_at FROM patients WHERE doctor_id = %s", (user_id,))    
    patients = cursor.fetchall()
    cursor.close()
    conn.close()

     # Format 'created_at' into 'last_created' for easier display
    for patient in patients:
        if patient['created_at']:
            patient['last_created'] = patient['created_at'].strftime('%d %b %Y %H:%M')  # Format the 'created_at' date

    return render_template('doc_patientlist.html', patients=patients)

@app.route('/doc_patientdetail.html')
def patient_detail():
    patient_id = request.args.get('patient_id')  # Get patient_id from URL parameters
    if not patient_id:
        flash('Patient ID is required.')
        return redirect(url_for('patient_list'))

    # Fetch patient details from the patients table
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute("SELECT * FROM patients WHERE patient_id = %s", (patient_id,))
    patient = cursor.fetchone()

    # If no patient found, return an error message
    if not patient:
        flash('Patient not found.')
        return redirect(url_for('patient_list'))

    # Fetch health data for the selected patient
    cursor.execute("SELECT * FROM health_data WHERE patient_id = %s", (patient_id,))
    health_data = cursor.fetchall()

    # Debugging: Print patient and health data
    print(patient)  # Should print patient data
    print(health_data)  # Should print the health data for the patient

    cursor.close()
    conn.close()

    # Render the patient detail page with fetched data
    return render_template('doc_patientdetail.html', patient=patient, health_data=health_data)

@app.route('/doc_importdata.html', methods=['GET', 'POST'])
def import_data_page():
    if request.method == 'POST':
        # Assuming you are uploading a CSV file
        file = request.files['file']
        if file:
            file_data = file.read().decode('utf-8').splitlines()
            csv_reader = csv.reader(file_data)
            next(csv_reader)  # Skip header
            conn = get_db_connection()
            cursor = conn.cursor()

            for row in csv_reader:
                cursor.execute("""
                    INSERT INTO patients (full_name, dob, gender, status)
                    VALUES (%s, %s, %s, %s)
                """, row)
            conn.commit()
            cursor.close()
            conn.close()

            return redirect('/doc_patientlist.html')

    return render_template('doc_importdata.html')

@app.route('/doc_recommendation.html')
def recommendation_page():
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)

    # Example: Fetch patient recommendations
    cursor.execute("SELECT * FROM recommendations LIMIT 5")  # Example query
    recommendations = cursor.fetchall()
    cursor.close()
    conn.close()

    return render_template('doc_recommendation.html', recommendations=recommendations)

@app.route('/doc_graphexp.html')
@app.route('/doc_graphexp')
def graph_explorer():
    if 'user_id' not in session:
        flash('Login required')
        return redirect(url_for('login'))

    doc_id = session['user_id']

    # 1) MySQL → patients
    conn = get_db_connection()
    cur = conn.cursor(dictionary=True)
    cur.execute(
        "SELECT patient_id, full_name FROM patients WHERE doctor_id=%s",
        (doc_id,)
    )
    patients = cur.fetchall()
    cur.close()
    conn.close()

    # 2) Render the teammate’s template, passing ONLY patients
    return render_template(
        'doc_graphexp.html',
        patients=patients
    )
@app.route('/api/timestamps')
def api_timestamps():
    pid = request.args.get('patient_id')
    if not pid or 'user_id' not in session:
        return jsonify(timestamps=[])
    # Fetch the raw string timestamps from MySQL
    conn = get_db_connection()
    cur  = conn.cursor()
    cur.execute("""
      SELECT DISTINCT `timestamp`
        FROM health_data
       WHERE patient_id = %s
       ORDER BY STR_TO_DATE(`timestamp`, '%%d/%%m/%%Y %%H:%%i') DESC
    """, (pid,))
    rows = cur.fetchall()
    cur.close()
    conn.close()
    return jsonify(timestamps=[r[0] for r in rows])


@app.route('/api/graph_data')
def api_graph_data():
    pid = request.args.get('patient_id')
    ts  = request.args.get('timestamp')  # e.g. "20/1/2023 8:00" or "all"
    if not pid:
        return jsonify(nodes=[], edges=[])

    pid_val = float(pid)

    with neo4j_driver.session() as sess:
        if ts in (None, 'all'):
            cypher = """
            MATCH (p:Patient {id:$pid})-[r:RECORDS_FOR]->(m)
            RETURN p, collect({relType:type(r), meas:m}) AS items
            """
            params = {"pid": pid_val}
        else:
            cypher = """
            MATCH (p:Patient {id:$pid})-[r:RECORDS_FOR]->(m)
            WHERE m.timestamp = $ts
            RETURN p, collect({relType:type(r), meas:m}) AS items
            """
            params = {"pid": pid_val, "ts": ts}

        rec = sess.run(cypher, params).single()

    if not rec:
        return jsonify(nodes=[], edges=[])

    p_node = rec["p"]
    items  = rec["items"]

    # Build the root patient node, including all its properties
    p_props = dict(p_node)
    nodes = [{
        "id":         p_node.id,
        "label":      p_node.get("full_name", f"Patient {int(pid_val)}"),
        "group":      "Patient",
        "properties": p_props
    }]
    edges = []

    for item in items:
        m    = item["meas"]
        r    = item["relType"]
        lbl  = list(m.labels)[0]
        disp = m.get("level") or m.get("data_id") or ""
        props = dict(m)   # capture every property on this node
        nodes.append({
            "id":         m.id,
            "label":      str(disp),
            "group":      lbl,
            "properties": props
        })
        edges.append({
            "from": p_node.id,
            "to":   m.id,
            "label": r
        })

    return jsonify(nodes=nodes, edges=edges)

@app.route('/api/patient_insights')
def api_patient_insights():
    pid = request.args.get('patient_id')
    if not pid:
        return jsonify({})
    with neo4j_driver.session() as sess:
        rec = sess.run(
            """
            MATCH (p:Patient {id:$pid})
            RETURN p.ppr_score AS ppr,
                   p.node2vec_embedding AS n2v,
                   p.gat_embedding AS gat
            """,
            {"pid": float(pid)}
        ).single()
    if not rec:
        return jsonify({})
    def parse_list(s):
        try:
            import json
            return json.loads(s)
        except:
            return []
    return jsonify({
        "ppr_score": rec["ppr"],
        "node2vec":  parse_list(rec["n2v"]),
        "gat":       parse_list(rec["gat"])
    })
@app.route('/doc_algorithm.html')
def algorithm_page():
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)

    # Fetch patient health data for analysis
    cursor.execute("SELECT * FROM health_data LIMIT 10")
    health_data = cursor.fetchall()
    cursor.close()
    conn.close()

    # Perform your algorithm here (e.g., analyzing patient data)
    # For now, just passing the data to the template
    return render_template('doc_algorithm.html', health_data=health_data)

@app.route('/doc_reports.html')
def export_report_page():
    return render_template('doc_reports.html')

@app.route('/export_report', methods=['POST'])
def export_report():
    time_range = request.form.get('timeRange')
    report_format = request.form.get('format')
    
    # Fetch the patient data from the database
    filtered_data = filter_patient_data(time_range)
    
    # Export CSV or PDF based on selected format
    if report_format == 'csv':
        return export_csv(filtered_data)
    elif report_format == 'pdf':
        return export_pdf(filtered_data)

@app.route('/doc_userprofile.html')
def user_profile_page():
    user_id = session.get('user_id')  # Get user_id from the session or cookie
    if not user_id:
        flash('You need to log in first.')
        return redirect('/login')  # Redirect to login if no user_id found in session

    # Fetch the user's data (email, phone_number, specialist, etc.)
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT username, email, photo, status, phone_number, specialist FROM users WHERE user_id = %s", (user_id,))
    doctor = cursor.fetchone()  # Fetch the doctor's profile data
    cursor.close()
    conn.close()

    if not doctor:
        flash('Doctor not found.')
        return redirect('/login')  # If no doctor found, redirect to login

    photo = doctor.get('photo')
    if photo and photo.startswith('http'):
        doctor['photo_url'] = photo
    elif photo:
        doctor['photo_url'] = f"/static/uploads/{photo}"
    else:
        doctor['photo_url'] = 'https://cdn-icons-png.flaticon.com/512/921/921137.png'

    # Render the profile page with the fetched doctor data
    return render_template('doc_userprofile.html', doctor=doctor)

def filter_patient_data(time_range):
    # Connect to the database
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    
    query = "SELECT * FROM health_data"
    
    if time_range == 'weekly':
        query += " WHERE timestamp >= CURDATE() - INTERVAL 7 DAY"
    elif time_range == 'monthly':
        query += " WHERE timestamp >= CURDATE() - INTERVAL 1 MONTH"
    
    cursor.execute(query)
    patients = cursor.fetchall()
    cursor.close()
    conn.close()
    
    print(f"Filtered Data: {patients}")  # Debug: print the fetched data
    
    return patients

def export_csv(data):
    output = StringIO()
    
    # Ensure these fieldnames exist in your data structure
    writer = csv.DictWriter(output, fieldnames=["data_id", "patient_id", "timestamp", "cgm_level", "blood_pressure", "heart_rate", "cholesterol", "insulin_intake", "food_intake", "activity_level", "weight", "hb1ac"])
    writer.writeheader()
    writer.writerows(data)
    
    output.seek(0)
    
    return send_file(
        BytesIO(output.getvalue().encode()), 
        as_attachment=True, 
        download_name="patient_report.csv",
        mimetype='text/csv'
    )

def export_pdf(data):
    # Create a PDF using ReportLab
    buffer = BytesIO()
    c = canvas.Canvas(buffer, pagesize=letter)
    
    c.drawString(100, 750, "Patient Report")
    y_position = 730
    
    # Add data to the PDF
    for patient in data:
        c.drawString(100, y_position, f"Name: {patient['patient_id']}")  # Replace with actual field names
        y_position -= 20
        c.drawString(100, y_position, f"Timestamp: {patient['timestamp']}")  # Replace with actual field names
        y_position -= 40
    
    c.save()
    
    buffer.seek(0)
    
    return send_file(
        buffer,
        as_attachment=True,
        download_name="patient_report.pdf",
        mimetype="application/pdf"
    )

#Admin dashboard

@app.route('/admin_dashboard.html')
def admin_dashboard():
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)

    # CGM Trend for the last 7 days
    cursor.execute("""
    SELECT DATE(STR_TO_DATE(timestamp, '%d/%m/%Y %H:%i')) AS date, ROUND(AVG(cgm_level), 1) AS avg_cgm 
    FROM health_data 
    GROUP BY DATE(STR_TO_DATE(timestamp, '%d/%m/%Y %H:%i'))
    ORDER BY date DESC 
    LIMIT 7
    """)
    trend_data = cursor.fetchall()
    labels = [row['date'].strftime('%Y-%m-%d') if row['date'] else 'Unknown' for row in reversed(trend_data)]
    data = [row['avg_cgm'] for row in reversed(trend_data)]

    # Doctor & Patient counts
    cursor.execute("SELECT COUNT(*) AS count FROM users WHERE role = 'doctor'")
    doctor_count = cursor.fetchone()['count']

    cursor.execute("SELECT COUNT(*) AS count FROM patients")
    patient_count = cursor.fetchone()['count']

    # ✅ NEW: Recent system activity logs
    cursor.execute("""
        SELECT u.username, sl.action, sl.timestamp, sl.status
        FROM system_logs sl
        LEFT JOIN users u ON sl.user_id = u.user_id
        ORDER BY sl.timestamp DESC
        LIMIT 5
    """)
    recent_logs = cursor.fetchall()

    cursor.close()
    conn.close()

    return render_template(
        'admin_dashboard.html',
        labels=labels,
        data=data,
        doctor_count=doctor_count,
        patient_count=patient_count,
        recent_logs=recent_logs  # ✅ pass to template
    )



@app.route('/admin_add_doctor.html', methods=['GET', 'POST'])
def admin_add_doctor():
    if request.method == 'POST':
        username = request.form.get('username', '').strip()
        email = request.form.get('email', '').strip()
        password = request.form.get('password', '')
        role = 'doctor'  # hardcoded like in your form

        if not username or not email or not password:
            flash("Name, email, and password are required.")
            return redirect(url_for('admin_add_doctor'))

        photo_name = None
        if 'photo' in request.files:
            photo = request.files['photo']
            if photo.filename:
                from PIL import Image
                import os
                import time
                import random
                from werkzeug.utils import secure_filename

                img = Image.open(photo)
                max_dim = 800
                img.thumbnail((max_dim, max_dim))

                ext = os.path.splitext(photo.filename)[1]
                photo_name = f"{int(time.time())}_{random.randint(10000, 99999)}{ext}"
                save_path = os.path.join('static/uploads', secure_filename(photo_name))
                os.makedirs(os.path.dirname(save_path), exist_ok=True)
                img.save(save_path)

        password_hash = password  # optional: use hashing if needed
        conn = get_db_connection()
        cursor = conn.cursor()

        try:
            # Insert the doctor
            cursor.execute("""
                INSERT INTO users (username, email, password_hash, photo, role)
                VALUES (%s, %s, %s, %s, %s)
            """, (username, email, password_hash, photo_name, role))

            new_doctor_id = cursor.lastrowid  # 🔹 Get new doctor's user_id
            conn.commit()

            # 🔹 Log the action
            admin_id = session.get('user_id')
            log_action(admin_id, 'Add Doctor', target_id=new_doctor_id, status='success')

            flash("Doctor account created successfully!")
            return redirect('/admin_doctor_details.html')  # update as needed

        except mysql.connector.Error as e:
            flash(f"Database error: {e}")
        finally:
            cursor.close()
            conn.close()

    return render_template('admin_add_doctor.html')


@app.route('/admin_doctor_details.html')
def admin_doctor_details():
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT user_id, username, email, photo, status FROM users WHERE role = 'doctor'")
    doctors = cursor.fetchall()
    cursor.close()
    conn.close()

    for d in doctors:
        photo = d.get('photo')
        if photo and (photo.startswith('http://') or photo.startswith('https://')):
            d['photo_url'] = photo
        elif photo:
            d['photo_url'] = f"/static/uploads/{photo}"
        else:
            d['photo_url'] = 'https://cdn-icons-png.flaticon.com/512/921/921137.png'

    return render_template('admin_doctor_details.html', doctors=doctors)

@app.route('/doctor/<int:doctor_id>')
def admin_doctor_profile(doctor_id):
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("""
        SELECT user_id, username, email, photo, status, created_at
        FROM users
        WHERE user_id = %s AND role = 'doctor'
    """, (doctor_id,))
    doctor = cursor.fetchone()
    cursor.close()
    conn.close()

    if not doctor:
        flash("Doctor not found.")
        return redirect(url_for('admin_doctor_details'))

    photo = doctor.get('photo')
    if photo and photo.startswith('http'):
        doctor['photo_url'] = photo
    elif photo:
        doctor['photo_url'] = f"/static/uploads/{photo}"
    else:
        doctor['photo_url'] = 'https://cdn-icons-png.flaticon.com/512/921/921137.png'

    if doctor.get('created_at'):
        doctor['created_at'] = doctor['created_at'].strftime('%d %b %Y')

    return render_template('admin_doctor_profile.html', doctor=doctor)


@app.route('/admin_manage_user.html')
def admin_manage_user():
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT user_id, username, email, role, status FROM users ORDER BY user_id ASC")
    users = cursor.fetchall()
    cursor.close()
    conn.close()
    return render_template('admin_manage_user.html', users=users)

@app.route('/delete_user/<int:user_id>', methods=['POST'])
def admin_delete_user(user_id):
    conn = get_db_connection()
    conn.execute('DELETE FROM users WHERE user_id = ?', (user_id,))
    conn.commit()
    conn.close()
    flash('User deleted successfully.')
    return redirect(url_for('admin_manage_user'))


@app.route('/admin_edit_user/<int:user_id>', methods=['GET', 'POST'])
def admin_edit_user(user_id):  # <- Match the name used in the template
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)

    if request.method == 'POST':
        username = request.form['username']
        email = request.form['email']
        role = request.form['role']
        status = 1 if request.form['status'] == 'Active' else 0

        update_query = """
            UPDATE users
            SET username = %s, email = %s, role = %s, status = %s
            WHERE user_id = %s
        """
        cursor.execute(update_query, (username, email, role, status, user_id))
        conn.commit()
        cursor.close()
        conn.close()
        return redirect(url_for('admin_manage_user'))  # Adjust to your actual route
    else:
        cursor.execute("SELECT * FROM users WHERE user_id = %s", (user_id,))
        user = cursor.fetchone()
        cursor.close()
        conn.close()

        if not user:
            return "User not found", 404

        return render_template('admin_edit_user.html', user=user)


@app.route('/admin_security_audit.html')
def admin_security_audit():
    # This would be dynamic based on audit results
    audit_data = {
        'audit_date': '2025-04-20 10:20 AM',
        'issues_critical': 0,
        'issues_medium': 1,
        'issues_low': 2,
    }
    
    return render_template('admin_security_audit.html', **audit_data)

@app.route('/run_audit')
def run_audit():
    # Simulate running an audit
    flash('Audit completed successfully!')
    return redirect(url_for('admin_security_audit'))

@app.route('/admin_system_logs.html', methods=['GET'])
def admin_system_logs():
    try:
        # Connect to the database
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)

        # Get filter parameters from query string
        username_filter = request.args.get('username', '')
        action_filter = request.args.get('action', '')
        start_date = request.args.get('start_date', '')
        end_date = request.args.get('end_date', '')

        # Build the SQL query with dynamic filters
        query = """
            SELECT 
                sl.log_id,
                u.username,
                sl.action,
                sl.target_id,
                sl.timestamp,
                sl.status
            FROM system_logs sl
            LEFT JOIN users u ON sl.user_id = u.user_id
            WHERE 1=1
        """
        params = []

        if username_filter:
            query += " AND u.username LIKE %s"
            params.append(f"%{username_filter}%")
        if action_filter:
            query += " AND sl.action LIKE %s"
            params.append(f"%{action_filter}%")
        if start_date:
            query += " AND sl.timestamp >= %s"
            params.append(start_date)
        if end_date:
            query += " AND sl.timestamp <= %s"
            params.append(end_date + " 23:59:59")

        query += " ORDER BY sl.timestamp DESC"

        # Execute the query
        cursor.execute(query, params)
        logs = cursor.fetchall()

        # Close resources
        cursor.close()
        conn.close()

        # Render the page with logs and filter values
        return render_template('admin_system_logs.html', logs=logs, 
                             username_filter=username_filter, 
                             action_filter=action_filter, 
                             start_date=start_date, 
                             end_date=end_date)

    except Exception as e:
        print(f"Error fetching system logs: {e}")
        flash('An error occurred while fetching the logs. Please try again later.', 'danger')
        return render_template('admin_system_logs.html', logs=[])

@app.route('/logout')
def logout():
    user_id = session.get('user_id')  # Capture before removing it

    # ✅ Log the logout action
    if user_id:
        log_action(user_id, action='Logout', status='success')

    # Clear session and cookie
    session.pop('user_id', None)
    resp = make_response(redirect('/login'))
    resp.delete_cookie('remember_token')

    return resp


if __name__ == '__main__':
    app.run(debug=True)