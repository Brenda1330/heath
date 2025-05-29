from flask import Flask, render_template, request, send_file, jsonify, redirect, session, flash, url_for, make_response
from neo4j import GraphDatabase
import csv
from reportlab.lib.pagesizes import letter
from reportlab.pdfgen import canvas
from io import BytesIO, StringIO
from datetime import timedelta, datetime
import traceback
import pymysql
from pymysql.cursors import DictCursor  # Import DictCursor
import google.generativeai as genai
import os

from sqlalchemy import text
from flask import jsonify
from flask_sqlalchemy import SQLAlchemy


# *****Security audit *****
from zapv2 import ZAPv2
from datetime import datetime
import time
from flask_wtf.csrf import CSRFProtect
from werkzeug.security import generate_password_hash # Import for password hashing
from werkzeug.security import check_password_hash # Import for checking hashed passwords
from itsdangerous import URLSafeTimedSerializer, SignatureExpired, BadTimeSignature 
from flask_wtf import FlaskForm
from wtforms import StringField, PasswordField
from wtforms.validators import DataRequired

app = Flask(__name__)
# Enable CSRF protection
csrf = CSRFProtect(app)
app.secret_key = 'password'  # Secure the session with a secret key
app.config.update(
    SESSION_COOKIE_SAMESITE="Lax",
    SESSION_COOKIE_SECURE=False,  # Set to True only if you're using HTTPS
    SESSION_COOKIE_DOMAIN=None,   # Avoid domain mismatch
)
app.config['SESSION_TYPE'] = 'filesystem'


# ✅ NEW: Add SQLAlchemy MySQL config
app.config['SQLALCHEMY_DATABASE_URI'] = (
    'mysql+pymysql://admin:Health2025!@my-database-db.cbwgsyiui4n1.ap-southeast-2.rds.amazonaws.com:3306/health'
)
app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False

# Initialize the Neo4j driver once
neo4j_driver = GraphDatabase.driver(
    "neo4j+s://8a6d0f01.databases.neo4j.io",
    auth=("neo4j", "QQoj0RBDAwoTv3n2uFu4ZvvvethNy6mZsQ_jpN7W-U4")
)

# Setup the serializer
db = SQLAlchemy(app)
s = URLSafeTimedSerializer(app.config['SECRET_KEY'])

# Setup Gemini API key (do this once at app start)
genai.configure(api_key="AIzaSyClJYQFCY8JYgxK8UcuwWVC097WYUm8dzY")
model = genai.GenerativeModel("gemini-2.0-flash")

class User(db.Model):
    __tablename__ = 'user'  # 或你的表名
    user_id = db.Column(db.Integer, primary_key=True)
    username = db.Column(db.String(50))
    email = db.Column(db.String(100))
    password_hash = db.Column(db.String(100))
    role = db.Column(db.String(20))
    status = db.Column(db.Integer)

# 数据模型 - 对应 MySQL 中的 health_data 表
class HealthData(db.Model):
    __tablename__ = 'health_data'

    data_id = db.Column(db.Integer, primary_key=True)
    patient_id = db.Column(db.Integer)
    timestamp = db.Column(db.String(50))
    cgm_level = db.Column(db.Float)
    blood_pressure = db.Column(db.String(10))
    heart_rate = db.Column(db.Integer)
    cholesterol = db.Column(db.Float)
    insulin_intake = db.Column(db.Integer)
    weight = db.Column(db.Float)
    hb1ac = db.Column(db.Float)

        # API 路由：提供给前端使用
@app.route('/get_patient_data/<int:patient_id>')
def get_patient_data(patient_id):
    records = HealthData.query.filter_by(patient_id=patient_id).order_by(HealthData.timestamp).all()
    return jsonify([
        {
           'timestamp': r.timestamp,
            'cgm_level': r.cgm_level,
            'blood_pressure': r.blood_pressure,
            'heart_rate': r.heart_rate,
            'cholesterol': r.cholesterol,
            'insulin_intake': r.insulin_intake,
            'weight': r.weight,
            'hb1ac': r.hb1ac
        } for r in records
    ])

# Check if running in ZAP test mode (you'll set this env var when running for ZAP)
if os.environ.get('ZAP_TEST_MODE') == 'true':
    print("!!! RUNNING IN ZAP TEST MODE - USING TEST DATABASE !!!")
    DB_USER = os.environ.get('ZAP_DB_USER', 'test_app_user')
    DB_PASSWORD = os.environ.get('ZAP_DB_PASSWORD', 'test_user_password')
    DB_NAME = os.environ.get('ZAP_DB_NAME', 'test_app_db')
 
@app.after_request
def add_security_headers(response):
    csp_policy = [
        "default-src 'self';",
        "object-src 'none';",
        "frame-ancestors 'self';",
        "base-uri 'self';",

        # Updated to include unpkg.com and allow unsafe-eval for Vis.js
        "script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://kit.fontawesome.com https://unpkg.com 'unsafe-inline' 'unsafe-eval';",
        

        # Include unpkg for AOS.css and similar
        "style-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com https://unpkg.com 'unsafe-inline';",

        "img-src 'self' https://cdn-icons-png.flaticon.com static/uploads/ data:;",
        "font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com;",
        "connect-src 'self';",
        "form-action 'self';",
    ]
    
    response.headers['Content-Security-Policy'] = " ".join(csp_policy)
    response.headers['X-Content-Type-Options'] = 'nosniff'
    response.headers['X-Frame-Options'] = 'SAMEORIGIN'
    response.headers['X-XSS-Protection'] = '1; mode=block'
    response.headers['Referrer-Policy'] = 'strict-origin-when-cross-origin'
    response.headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains'
    return response

def generate_recommendations_for_doctor(doctor_id):
    conn = get_db_connection()
    cursor = conn.cursor(pymysql.cursors.DictCursor)

    # Get health data for patients of this doctor
    cursor.execute("""
        SELECT hd.* FROM health_data hd
        JOIN patients p ON hd.patient_id = p.patient_id
        WHERE p.doctor_id = %s
    """, (doctor_id,))
    health_rows = cursor.fetchall()

    for health in health_rows:
        data_id = health['data_id']
        patient_id = health['patient_id']

        # Get insights for this patient and data
        # Debug: Check DB connection
        cursor.execute("SELECT DATABASE() AS db")
        print("🧠 Connected to DB:", cursor.fetchone()['db'])

        # Actual query
        cursor.execute("""
            SELECT * FROM graph_insights 
            WHERE patient_id = %s AND data_id = %s
        """, (patient_id, data_id))
        insight_rows = cursor.fetchall()

        for insight in insight_rows:
            insight_id = insight['insight_id']
            algorithm = insight['algorithm']
            description = insight['description']

            # Skip if recommendation exists
            cursor.execute("SELECT 1 FROM recommendations WHERE insight_id = %s", (insight_id,))
            if cursor.fetchone():
                continue

            prompt = (
                f"Patient health data:\n"
                f"- CGM Level: {health['cgm_level']}\n"
                f"- Blood Pressure: {health['blood_pressure']}\n"
                f"- Heart Rate: {health['heart_rate']}\n"
                f"- Cholesterol: {health['cholesterol']}\n"
                f"- Insulin Intake: {health['insulin_intake']}\n"
                f"- Food Intake: {health['food_intake']}\n"
                f"- Activity Level: {health['activity_level']}\n"
                f"- Weight: {health['weight']}\n"
                f"- HbA1c: {health['hb1ac']}\n"
                f"\nGraph algorithm result ({algorithm}): {description}\n"
                f"\nBased on this information, use clear and simple english to ensure that individual without prior knowledge of diabetes can easily comprehend the recommendation"
                f"\nFocus on delivering facts in the record provide and actionable advice with example without using any lists"
                f"\nProvide a short health recommendation (50–90 words) starting with "
                f"\"Based on your...\""
            )

            try:
                response = model.generate_content(prompt)
                recommendation_text = response.text.strip()
            except Exception as e:
                recommendation_text = f"ERROR: {str(e)}"

            cursor.execute("""
                INSERT INTO recommendations 
                    (patient_id, insight_id, model_used, prompt_used, recommendation, created_at)
                VALUES (%s, %s, %s, %s, %s, %s)
            """, (
                patient_id,
                insight_id,
                "Gemini",
                prompt,
                recommendation_text,
                datetime.now()
            ))
            conn.commit()

    cursor.close()
    conn.close()

# MySQL connection configuration
def get_db_connection():
    return pymysql.connect(
        host='my-database-db.cbwgsyiui4n1.ap-southeast-2.rds.amazonaws.com',
        port=3306,
        user='admin',
        password='Health2025!',
        database='health',
        cursorclass=pymysql.cursors.DictCursor
    )

@app.route('/')
def home():
    return render_template('homepage.html')

class LoginForm(FlaskForm):
    email = StringField('Email', validators=[DataRequired()])
    password = PasswordField('Password', validators=[DataRequired()])

@app.route('/doc_dashboard.html')
def dashboard_page():
    if 'user_id' not in session:
        remember_token = request.cookies.get('remember_token')
        if remember_token:
            try:
                user_id = s.loads(remember_token, max_age=timedelta(days=30))
                session['user_id'] = user_id

                conn = get_db_connection()
                cursor = conn.cursor(DictCursor)
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

    user_id = session['user_id']
    conn = None
    doctor = None
    patients = []

    try:
        conn = get_db_connection()
        cursor = conn.cursor(DictCursor)

        # 医生名
        cursor.execute("SELECT username FROM users WHERE user_id = %s", (user_id,))
        doctor = cursor.fetchone()
        if not doctor:
            flash('Doctor not found')
            return redirect('/login')

        # 病人资料
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

        # 日期格式化
        for patient in patients:
            if patient.get('created_at'):
                patient['created_at'] = patient['created_at'].strftime('%d %b %Y')
            raw = patient.get('last_updated')
            if raw:
                if isinstance(raw, str):
                    try:
                        dt = datetime.strptime(raw, '%d/%m/%Y %H:%M')
                    except ValueError:
                        try:
                            dt = datetime.fromisoformat(raw)
                        except ValueError:
                            dt = datetime.strptime(raw, '%Y-%m-%d %H:%M:%S')
                else:
                    dt = raw
                patient['last_updated'] = dt.strftime('%d %b %Y %H:%M')

        # 病人总数
        cursor.execute("SELECT COUNT(*) AS total FROM patients WHERE doctor_id = %s", (user_id,))
        total_patients = cursor.fetchone()['total']

        # 各个 status 的数量（小写 key）
        cursor.execute("""
            SELECT status, COUNT(*)
            FROM patients
            WHERE doctor_id = %s
            GROUP BY status
        """, (user_id,))
        status_counts = {row['status'].lower(): row['COUNT(*)'] for row in cursor.fetchall()}

        stable_count = status_counts.get('stable', 0)
        critical_count = status_counts.get('critical', 0)
        recovered_count = status_counts.get('recovered', 0)
        warning_count = status_counts.get('warning', 0)  

        chart_data = [stable_count, critical_count, recovered_count, warning_count]

        today = datetime.today()
        last_7_days = today - timedelta(days=7)
        last_30_days = today - timedelta(days=30)

        # 查询过去 7 天新增病患数
        cursor.execute("""
            SELECT COUNT(*) AS count FROM patients 
            WHERE doctor_id = %s AND created_at >= %s
        """, (user_id, last_7_days))
        new_patients_7_days = cursor.fetchone()['count']

        # 查询过去 30 天新增病患数
        cursor.execute("""
            SELECT COUNT(*) AS count FROM patients 
            WHERE doctor_id = %s AND created_at >= %s
        """, (user_id, last_30_days))
        new_patients_30_days = cursor.fetchone()['count']
        
        cursor.execute("""
        SELECT ROUND(AVG(h.cgm_level), 1) AS avg_cgm
        FROM health_data h
        JOIN (
            SELECT patient_id, MAX(timestamp) AS latest_time
            FROM health_data
            GROUP BY patient_id
        ) latest ON h.patient_id = latest.patient_id AND h.timestamp = latest.latest_time
        WHERE h.patient_id IN (
            SELECT patient_id FROM patients WHERE doctor_id = %s
        )
    """, (user_id,))

        avg_cgm_level = cursor.fetchone()['avg_cgm'] or 0

    except pymysql.MySQLError as e:
        flash(f"Database error: {e}")
        return redirect('/login')

    finally:
        if conn:
            conn.close()

    return render_template("doc_dashboard.html",
                        doctor=doctor,
                        patients=patients,
                        total_patients=total_patients,
                        chart_data=chart_data,
                        new_patients_7_days=new_patients_7_days,
                        new_patients_30_days=new_patients_30_days,
                        avg_cgm_level=avg_cgm_level)
    

@app.route('/login', methods=['GET', 'POST'])
def login():
    form = LoginForm()

    if 'user_id' in session and 'role' in session:
        user_role = session['role']
        if user_role == 'doctor':
            return redirect(url_for('dashboard_page'))
        elif user_role == 'admin':
            return redirect(url_for('admin_dashboard'))
        else:
            return redirect(url_for('home'))

    # --- Phase 2: "Remember Me" cookie check ---
    if request.method == 'GET':
        remember_token = request.cookies.get('remember_token')
        if remember_token:
            conn_rem_local, cursor_rem_local = None, None
            try:
                user_id_from_token = s.loads(remember_token, max_age=timedelta(days=30).total_seconds())
                conn_rem_local = get_db_connection()
                if conn_rem_local:
                    cursor_rem_local = conn_rem_local.cursor(dictionary=True)
                    cursor_rem_local.execute(
                        "SELECT user_id, username, role, email FROM users WHERE user_id = %s AND status = 1",
                        (user_id_from_token,))
                    user_from_token = cursor_rem_local.fetchone()
                    while cursor_rem_local.fetchone(): pass
                    if cursor_rem_local: cursor_rem_local.close()
                    if user_from_token:
                        if conn_rem_local: conn_rem_local.close()
                        session['user_id'] = user_from_token['user_id']
                        session['username'] = user_from_token['username']
                        session['role'] = user_from_token['role']
                        flash(f"Welcome back, {user_from_token['username']}!", "info")
                        if user_from_token['role'] == 'doctor':
                            return redirect(url_for('dashboard_page'))
                        elif user_from_token['role'] == 'admin':
                            return redirect(url_for('admin_dashboard'))
                        return redirect(url_for('home'))
            except Exception as e:
                print("❌ RememberMe error:", e)
            finally:
                if cursor_rem_local: cursor_rem_local.close()
                if conn_rem_local: conn_rem_local.close()

    # --- Phase 3: Handle Login POST ---
    if request.method == 'POST':
        email_candidate = request.form.get('email', '').strip()
        password_candidate = request.form.get('password', '')
        target_info = f"Email: {email_candidate}"

        print("[DEBUG] Login POST reached")
        print("[DEBUG] Input email:", email_candidate)
        print("[DEBUG] Input password:", password_candidate)

        if not email_candidate or not password_candidate:
            flash("Email and password are required.", "warning")
            return redirect(url_for('login'))

        conn_post = None
        cursor_post = None
        try:
            conn_post = get_db_connection()
            cursor_post = conn_post.cursor()
            cursor_post.execute(
                "SELECT user_id, username, role, password_hash, status FROM users WHERE email = %s",
                (email_candidate,))
            user = cursor_post.fetchone()
            while cursor_post.fetchone(): pass
            cursor_post.close()

            print("[DEBUG] User from DB:", user)
            if user:
                print("[DEBUG] Status:", user['status'])
                print("[DEBUG] Password match:", check_password_hash(user['password_hash'], password_candidate))

            if user and user['status'] == 1 and user['password_hash'] == password_candidate:
                session.clear()
                session['user_id'] = user['user_id']
                session['username'] = user['username']
                session['role'] = user['role']
                session.permanent = True

                remember_me_checked = request.form.get('rememberMe')
                destination_url = url_for('dashboard_page') if user['role'] == 'doctor' else url_for('admin_dashboard')

                resp = make_response(redirect(destination_url))
                if remember_me_checked:
                    token = s.dumps({'user_id': user['user_id']})
                    resp.set_cookie('remember_token', token,
                                    max_age=timedelta(days=30).total_seconds(),
                                    httponly=True, samesite='Lax', secure=request.is_secure)
                flash(f"Welcome, {user['username']}!", "success")
                return resp
            else:
                print("❌ Invalid login attempt.")
                flash('Invalid credentials or account inactive. Please try again.', 'danger')
                return redirect(url_for('login'))

        except Exception as e:
            print("❌ Exception during login:", str(e))
            flash("An unexpected error occurred.", "danger")
            return redirect(url_for('login'))
        finally:
            if cursor_post: cursor_post.close()
            if conn_post: conn_post.close()

    # --- Phase 4: Render login form ---
    return render_template('login.html', form=form)

@app.route('/doc_addpatient.html', methods=['GET', 'POST'])
def add_patient_page():
    if 'user_id' not in session:
        flash('You need to log in first.')
        return redirect('/login')  # Ensure doctor is logged in

    user_id = session['user_id']  # Get the user_id from the session
    
    if request.method == 'POST':
        # Get data from the form
        full_name = request.form.get('full_name')
        dob = request.form.get('dob')
        gender = request.form.get('gender')
        status = request.form.get('status')

        # Basic validation to make sure form is not empty
        if not full_name or not dob or not gender or not status:
            flash('Please fill out all the fields.')
            return render_template('doc_addpatient.html')

        # Insert the new patient with the associated doctor_id (use user_id from session)
        conn = get_db_connection()
        cursor = conn.cursor()

        try:
            cursor.execute("""
                INSERT INTO patients (full_name, dob, gender, status, doctor_id)
                VALUES (%s, %s, %s, %s, %s)
            """, (full_name, dob, gender, status, user_id))  # Use user_id as doctor_id
            conn.commit()
            flash('Patient added successfully!')
        except pymysql.MySQLError as e:
            flash(f"Database error: {e}")
        finally:
            cursor.close()
            conn.close()

        return redirect('/doc_patientlist.html')  # Redirect to the patient list page after successful insertion

    return render_template('doc_addpatient.html')

@app.route('/doc_patientlist.html')
def patient_list_page():
    if 'user_id' not in session:
        flash('You need to log in first.')
        return redirect('/login')  # Ensure doctor is logged in

    user_id = session['user_id']  # Get the user_id from the session

    # Fetch the list of patients for this specific doctor
    conn = get_db_connection()
    cursor = conn.cursor(pymysql.cursors.DictCursor)
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
    return render_template(
    'doc_patientdetail.html',
    patient=patient,
    health_data=health_data,
    patient_id=patient_id   # ✅ 这行是关键！
)

@app.route('/doc_importdata.html', methods=['GET', 'POST'])
def import_data_page():
    if 'user_id' not in session:
        flash('Please log in first.')
        return redirect('/login')

    doctor_id = session['user_id']

    conn = get_db_connection()
    cursor = conn.cursor(pymysql.cursors.DictCursor)
    cursor.execute("SELECT patient_id, full_name FROM patients WHERE doctor_id = %s", (doctor_id,))
    patients = cursor.fetchall()

    if request.method == 'POST':
        file = request.files.get('file')
        if not file:
            flash('No file uploaded.', 'warning')
            return redirect('/doc_importdata.html')

        try:
            file_data = file.read().decode('utf-8').splitlines()
            csv_reader = csv.reader(file_data)
            headers = next(csv_reader)  # Read the header row

            expected_headers = [
                "cgm_level", "blood_pressure", "heart_rate",
                "cholesterol", "insulin_intake", "food_intake",
                "activity_level", "weight", "hb1ac"
            ]

            uploaded_headers = [h.strip().lower() for h in headers]
            if uploaded_headers != [h.lower() for h in expected_headers]:
                flash("❌ Incorrect CSV header. Please use the template format.", "danger")
                return redirect('/doc_importdata.html')

            valid_patient_ids = {str(p['patient_id']) for p in patients}
            insert_query = """
                INSERT INTO health_data (
                    patient_id, timestamp, cgm_level, blood_pressure, heart_rate,
                    cholesterol, insulin_intake, food_intake, activity_level, weight, hb1ac
                ) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
            """

            inserted = 0
            patient_id = request.form.get("selected_patient_id")
            date_input = request.form.get("selected_date")

            # Validate patient ID
            valid_patient_ids = {str(p['patient_id']) for p in patients}
            if patient_id not in valid_patient_ids:
                flash("❌ Invalid patient selected.", "danger")
                return redirect('/doc_importdata.html')

            # Parse selected date
            try:
                dt = datetime.strptime(date_input, "%Y-%m-%d")
            except ValueError:
                flash("❌ Invalid date format selected.", "danger")
                return redirect('/doc_importdata.html')

            formatted_timestamp = dt.strftime("%Y-%m-%d %H:%M:%S")

            for row in csv_reader:
                if len(row) != 9:
                    continue  # skip malformed rows

                # Prepend selected patient_id and timestamp
                db_row = [patient_id, formatted_timestamp] + row

                # Skip if record for same patient+date already exists
                cursor.execute("""
                    SELECT COUNT(*) as count FROM health_data
                    WHERE patient_id = %s AND DATE(timestamp) = DATE(%s)
                """, (patient_id, formatted_timestamp))
                if cursor.fetchone()['count'] > 0:
                    continue

                cursor.execute(insert_query, db_row)
                inserted += 1

            conn.commit()
            flash(f"✅ Successfully imported {inserted} record(s).", "success")

            # 🔄 Trigger Neo4j sync
            import requests
            try:
                sync_resp = requests.get('http://13.236.64.35:3000/sync', timeout=10)
                if sync_resp.status_code == 200:
                    flash("🔁 Data synced to Neo4j successfully.", "info")
                else:
                    flash("⚠️ Neo4j sync failed after upload.", "warning")
            except Exception as e:
                flash(f"❌ Neo4j sync error: {e}", "danger")

            return redirect('/doc_patientlist.html')

        except Exception as e:
            flash(f"Error processing CSV: {e}", "danger")
            return redirect('/doc_importdata.html')
        finally:
            cursor.close()
            conn.close()

    return render_template('doc_importdata.html', patients=patients)

@app.route('/doc_recommendation.html', methods=['GET'])
def recommendation_page():
    if 'user_id' not in session:
        flash("Please log in first")
        return redirect('/login')

    doctor_id = session['user_id']

    # Generate new recommendations if any missing for this doctor's patients
    generate_recommendations_for_doctor(doctor_id)

    # Now fetch patients to show on the page
    conn = get_db_connection()
    cursor = conn.cursor(pymysql.cursors.DictCursor)
    cursor.execute("SELECT patient_id, full_name FROM patients WHERE doctor_id = %s", (doctor_id,))
    patients = cursor.fetchall()
    cursor.close()
    conn.close()

    return render_template('doc_recommendation.html', patients=patients)

@app.route('/get_patient_profile/<int:patient_id>')
def get_patient_profile(patient_id):
    if 'user_id' not in session:
        return jsonify({'error': 'Unauthorized'}), 401

    doctor_id = session['user_id']
    conn = get_db_connection()
    cursor = conn.cursor(DictCursor)
    # Ensure the patient belongs to the logged-in doctor
    cursor.execute("SELECT * FROM patients WHERE patient_id = %s AND doctor_id = %s", (patient_id, doctor_id))
    patient = cursor.fetchone()
    cursor.close()
    conn.close()

    if patient is None:
        return jsonify({'error': 'Patient not found or unauthorized'}), 404

    # Format dates if needed
    if patient.get('dob'):
        patient['dob'] = patient['dob'].strftime('%d %b %Y')
    if patient.get('created_at'):
        patient['created_at'] = patient['created_at'].strftime('%d %b %Y')

    return jsonify(patient)
@app.route('/get_patient_timestamps/<int:patient_id>')
def get_patient_timestamps(patient_id):
    if 'user_id' not in session:
        return jsonify({'error': 'Unauthorized'}), 401

    doctor_id = session['user_id']
    conn = get_db_connection()
    cursor = conn.cursor(pymysql.cursors.DictCursor)

    # Verify the patient belongs to the doctor
    cursor.execute("SELECT * FROM patients WHERE patient_id = %s AND doctor_id = %s", (patient_id, doctor_id))
    patient = cursor.fetchone()
    if patient is None:
        cursor.close()
        conn.close()
        return jsonify({'error': 'Patient not found or unauthorized'}), 404

    # Fetch timestamps from health_data
    cursor.execute("SELECT data_id, timestamp FROM health_data WHERE patient_id = %s ORDER BY timestamp DESC", (patient_id,))
    rows = cursor.fetchall()
    cursor.close()
    conn.close()

    # Format timestamps
    # Fix timestamp parsing
    formatted = []
    for row in rows:
        ts = row['timestamp']
        try:
            if isinstance(ts, datetime):
                dt = ts
            elif isinstance(ts, str):
                try:
                    dt = datetime.strptime(ts, "%d/%m/%Y %H:%M")
                except:
                    dt = datetime.strptime(ts, "%Y-%m-%d %H:%M:%S")  # fallback
            else:
                continue

            formatted.append({
                "data_id": row['data_id'],
                "timestamp": dt.strftime('%Y-%m-%d %H:%M:%S')
            })
        except Exception as e:
            print("❌ Timestamp parse failed:", ts, str(e))
            continue
    return jsonify({'timestamps': formatted})

@app.route('/get_recommendation/<int:patient_id>/<int:data_id>/<algorithm>')
def get_recommendation(patient_id, data_id, algorithm):
    if 'user_id' not in session:
        return jsonify({'error': 'Unauthorized'}), 401

    doctor_id = session['user_id']
    conn = get_db_connection()
    cursor = conn.cursor(DictCursor)
    # Ensure the patient belongs to the logged-in doctor
    cursor.execute("SELECT * FROM patients WHERE patient_id = %s AND doctor_id = %s", (patient_id, doctor_id))
    patient = cursor.fetchone()
    if patient is None:
        cursor.close()
        conn.close()
        return jsonify({'error': 'Patient not found or unauthorized'}), 404

    # Fetch insight_id from graph_insight
    cursor.execute("SELECT insight_id FROM graph_insights WHERE patient_id = %s AND data_id = %s AND algorithm = %s", (patient_id, data_id, algorithm))
    insight = cursor.fetchone()
    if insight is None:
        cursor.close()
        conn.close()
        return jsonify({'error': 'Insight not found'}), 404

    insight_id = insight['insight_id']

    # Fetch recommendation from recommendation table
    cursor.execute("SELECT recommendation FROM recommendations WHERE insight_id = %s", (insight_id,))
    rec = cursor.fetchone()
    cursor.close()
    conn.close()

    if rec is None:
        return jsonify({'error': 'Recommendation not found'}), 404

    # Assuming recommendations are stored as a comma-separated string
    recommendations = rec['recommendation'].split(',')

    return jsonify({'recommendations': recommendations})

@app.route('/doc_graphexp.html')
@app.route('/doc_graphexp')
def graph_explorer():
    if 'user_id' not in session:
        flash('Login required')
        return redirect(url_for('login'))

    doc_id = session['user_id']

    # 1) MySQL → patients
    conn = get_db_connection()
    cur = conn.cursor(pymysql.cursors.DictCursor)
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

    conn = get_db_connection()
    cur = conn.cursor(pymysql.cursors.DictCursor)  # <- Use DictCursor
    cur.execute("""
      SELECT DISTINCT timestamp
      FROM health_data
      WHERE patient_id = %s
    """, (pid,))
    rows = cur.fetchall()
    cur.close()
    conn.close()

    iso_timestamps = []
    for row in rows:
        raw = row['timestamp']  # Extract correctly
        try:
            if isinstance(raw, str):
                try:
                    dt = datetime.strptime(raw, "%d/%m/%Y %H:%M")
                except:
                    try:
                        dt = datetime.strptime(raw, "%Y-%m-%d %H:%M:%S")
                    except:
                        print("❌ Failed parsing:", raw)
                        continue
            else:
                dt = raw  # MySQL DATETIME

            iso_timestamps.append(dt.strftime('%Y-%m-%dT%H:%M:%S'))
        except Exception as e:
            print("❌ Timestamp parse error:", raw, str(e))
            continue

    return jsonify(timestamps=iso_timestamps)

@app.route('/api/graph_data')
def api_graph_data():
    import json
    pid = request.args.get('patient_id')
    ts = request.args.get('timestamp')

    if not pid:
        return jsonify(nodes=[], edges=[])

    pid_val = str(pid)  # Neo4j uses patient ID as string

    with neo4j_driver.session() as sess:
        if ts in (None, 'all', ''):
            cypher = """
            MATCH (p:Patient {id:$pid})-[r:RECORDS_FOR]->(m)
            RETURN p, collect({relType:type(r), meas:m}) AS items
            """
            params = {"pid": pid_val}
        else:
            cypher = """
            MATCH (p:Patient {id:$pid})-[r:RECORDS_FOR]->(m)
            WHERE apoc.date.format(datetime(m.time).epochMillis, 'ms', "yyyy-MM-dd'T'HH:mm:ss") = $ts
            RETURN p, collect({relType:type(r), meas:m}) AS items
            """
            params = {"pid": pid_val, "ts": ts}

        print("🧠 Querying with:", params)
        rec = sess.run(cypher, params).single()

    if not rec:
        print("⚠️ No results from Neo4j.")
        return jsonify(nodes=[], edges=[])

    p_node = rec["p"]
    items = rec["items"]

    nodes = [{
        "id":         p_node.id,
        "label":      p_node.get("full_name", "Patient"),
        "group":      "Patient",
        "properties": dict(p_node)
    }]
    edges = []

    for item in items:
        m = item.get("meas")
        if m is None:
            print(f"⚠️ Skipped null measurement node for patient {pid_val}")
            continue

        props = dict(m)

        val = props.get("node2vec_embedding", [])
        if isinstance(val, str):
            try:
                props["node2vec_embedding"] = json.loads(val)
            except Exception:
                props["node2vec_embedding"] = []
        else:
            props["node2vec_embedding"] = val

        val = props.get("gat_embedding", [])
        if isinstance(val, str):
            try:
                props["gat_embedding"] = json.loads(val)
            except Exception:
                props["gat_embedding"] = []
        else:
            props["gat_embedding"] = val

        if m is None:
            continue
        try:
            labels = list(m.labels)
        except:
            labels = []

        node_type = labels[0] if labels else "Unknown"

        label_value = str(
            m.get("level") or
            m.get("value") or
            m.get("cgm_level") or
            m.get("blood_pressure") or
            m.get("heart_rate") or
            m.get("cholesterol") or
            m.get("hb1ac") or
            m.get("insulin_intake") or
            m.get("weight") or
            m.get("food_intake") or
            m.get("activity_level") or
            m.get("bpm") or
            m.get("kg") or
            m.get("percentage") or
            m.get("units") or
            m.get("description") or
            ""
        )

        nodes.append({
            "id":    m.id,
            "label": label_value,
            "group": node_type,
            "properties": {
                "type": node_type,
                "data_id": m.get("data_id", ""),
                "timestamp": str(m.get("time", "")),
                "cgm_level": m.get("cgm_level", ""),
                "blood_pressure": m.get("blood_pressure", ""),
                "heart_rate": m.get("heart_rate", "") or m.get("bpm", ""),
                "cholesterol": m.get("cholesterol", ""),
                "hb1ac": m.get("hb1ac", "") or m.get("percentage", ""),
                "insulin_intake": m.get("insulin_intake", "") or m.get("units", ""),
                "weight": m.get("weight", "") or m.get("kg", ""),
                "food_intake": m.get("food_intake", ""),
                "activity_level": m.get("activity_level", ""),
                "description": m.get("description", ""),
                "node2vec_embedding": props.get("node2vec_embedding", []),
                "ppr_score": props.get("ppr_score", 0),
                "gat_embedding": props.get("gat_embedding", []),
                "value": m.get("value", "")
            }
        })

        edges.append({
            "from": p_node.id,
            "to": m.id,
            "label": item["relType"]
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
    cursor = conn.cursor(pymysql.cursors.DictCursor)

    # Fetch patient health data for analysis
    cursor.execute("SELECT * FROM health_data LIMIT 10")
    health_data = cursor.fetchall()
    cursor.close()
    conn.close()

    # Perform your algorithm here (e.g., analyzing patient data)
    # For now, just passing the data to the template
    return render_template('doc_algorithm.html', health_data=health_data)

@app.route('/api/recommendation', methods=['POST'])
def generate_recommendation():
    import torch
    import json
    import traceback
    from transformers import T5Tokenizer, T5ForConditionalGeneration

    try:
        data = request.json
        print("📥 Incoming data:", data)

        pid = data.get('patient_id')
        ts_raw = data.get('timestamp')
        ppr = data.get('ppr_score', '0')
        n2v_raw = data.get('node2vec_embedding', '')
        gat_raw = data.get('gat_embedding', '')

        if not pid or not ts_raw:
            return jsonify({"error": "Missing patient_id or timestamp"}), 400

        # Parse embeddings safely
        try:
            n2v = json.loads(f"[{n2v_raw}]")[:200] if isinstance(n2v_raw, str) else n2v_raw[:200]
        except Exception as e:
            print("❌ Node2Vec error:", e)
            return jsonify({"error": "Invalid Node2Vec embedding"}), 400

        try:
            gat = json.loads(f"[{gat_raw}]")[:200] if isinstance(gat_raw, str) else gat_raw[:200]
        except Exception as e:
            print("❌ GAT error:", e)
            return jsonify({"error": "Invalid GAT embedding"}), 400

        # Neo4j fetch
        with neo4j_driver.session() as sess:
            cypher = """
                MATCH (p:Patient {id: $pid})-[:RECORDS_FOR]->(m)
                WHERE toString(m.time) = toString(datetime($ts))
                RETURN m
                LIMIT 1
            """
            result = sess.run(cypher, {"pid": float(pid), "ts": ts_raw})
            record = result.single()

        if not record:
            return jsonify({"error": "No health data found in Neo4j"}), 404

        health = dict(record["m"])

        # Create prompt
        summary = f"""
        CGM: {health.get('cgm_level')}, BP: {health.get('blood_pressure')},
        HR: {health.get('heart_rate')}, HbA1c: {health.get('hb1ac')},
        Cholesterol: {health.get('cholesterol')}, Weight: {health.get('weight')},
        Insulin: {health.get('insulin_intake')}, Activity: {health.get('activity_level')}.
        Graph Insights - PPR: {ppr}, Node2Vec: {n2v[:3]}..., GAT: {gat[:3]}...
        """
        prompt = f"""
        Please review this diabetic patient's recent health records and graph insights.
        Provide personalized goals and practical lifestyle advice in plain English.
        Avoid bullet points. Data: {summary}
        """

        print("🧠 Generating...")

        # Load tokenizer + model
        tokenizer = T5Tokenizer.from_pretrained("google/flan-t5-base")
        model = T5ForConditionalGeneration.from_pretrained("google/flan-t5-base").to("cpu")
        model.eval()

        # Generate output
        with torch.no_grad():
            inputs = tokenizer(prompt, return_tensors="pt", truncation=True, max_length=512)
            outputs = model.generate(**inputs, max_length=512)
            text = tokenizer.decode(outputs[0], skip_special_tokens=True)

        print("✅ Recommendation:", text)
        return jsonify({"recommendation": text})

    except Exception as e:
        print("❌ UNEXPECTED ERROR:")
        traceback.print_exc()
        return jsonify({"error": "Model failed internally"}), 500


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
    cursor = conn.cursor(pymysql.cursors.DictCursor)
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
    cursor = conn.cursor(pymysql.cursors.DictCursor)
    
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

@app.route('/admin_dashboard')
def admin_dashboard():
    # ✅ Authorization: Only admin can access
    if 'user_id' not in session or session.get('role') != 'admin':
        flash("You must be an admin to access this page.", "warning")
        return redirect(url_for('login'))

    # ✅ Debug session contents (optional)
    print("🧠 SESSION CONTENT:", session)

    conn = get_db_connection()
    cursor = conn.cursor(pymysql.cursors.DictCursor)

    try:
        # ✅ CGM Trend for the last 7 days
        cursor.execute("""
            SELECT DATE(timestamp) AS date, ROUND(AVG(cgm_level), 1) AS avg_cgm 
            FROM health_data 
            WHERE timestamp IS NOT NULL
            GROUP BY DATE(timestamp) 
            ORDER BY date DESC 
            LIMIT 7
        """)
        trend_data = cursor.fetchall()

        labels = []
        data = []

        for row in reversed(trend_data):
            date_val = row.get('date')
            cgm_val = row.get('avg_cgm')

            try:
                formatted_date = date_val.strftime('%Y-%m-%d') if isinstance(date_val, (datetime, date)) else str(date_val)
            except Exception:
                formatted_date = "Unknown"

            labels.append(formatted_date)
            data.append(cgm_val if cgm_val is not None else 0)

        # ✅ Doctor & Patient counts
        cursor.execute("SELECT COUNT(*) AS count FROM users WHERE role = 'doctor'")
        doctor_count = cursor.fetchone()['count']

        cursor.execute("SELECT COUNT(*) AS count FROM patients")
        patient_count = cursor.fetchone()['count']

        # ✅ Completed audit count (optional - default 0)
        audit_count = session.get('audit_count', 0)

        return render_template('admin_dashboard.html',
                               labels=labels,
                               data=data,
                               doctor_count=doctor_count,
                               patient_count=patient_count,
                               audit_count=audit_count)

    except Exception as e:
        print(f"❌ Error in admin_dashboard: {e}")
        flash("An error occurred while loading the dashboard.", "danger")
        return redirect(url_for('login'))

    finally:
        cursor.close()
        conn.close()

@app.route('/admin_add_doctor.html', methods=['GET', 'POST'])
def admin_add_doctor():
    # --- Authorization Check ---
    if 'user_id' not in session: # Check if user is logged in at all
        flash("Please log in to access this page.", "warning")
        # Optionally log unauthenticated access attempt
        log_system_action(action="Admin Add Doctor Page Access Attempt", status="fail", target="Unauthenticated")
        return redirect(url_for('login'))
    
    if session.get('role') != 'admin': # Check if the logged-in user is an admin
        flash("Unauthorized access. Admin privileges required.", "danger")
        log_system_action(action="Admin Add Doctor Page Access Attempt", status="fail", 
                          target="Unauthorized - Not Admin Role", 
                          username=session.get('username'), user_id=session.get('user_id'))
        return redirect(url_for('home')) # Or to a general access denied page, or their dashboard

    # --- POST Request Handling ---
    if request.method == 'POST':
        username = request.form.get('username', '').strip()
        email = request.form.get('email', '').strip()
        password = request.form.get('password', '') # Get the plain text password
        role = 'doctor'  # Hardcoded as per your original logic

        if not username or not email or not password:
            flash("Name, email, and password are required.", "warning")
            # Log failed attempt due to missing fields
            log_system_action(action="Add Doctor Attempt", status="fail", 
                              target=f"Missing fields for email: {email}", details="Validation error: missing fields",
                              username=session.get('username'), user_id=session.get('user_id'))
            return redirect(url_for('admin_add_doctor')) # Redirect back to the form

        photo_name = None
        if 'photo' in request.files:
            photo_file = request.files['photo'] # Renamed to avoid conflict
            if photo_file.filename:
                from PIL import Image
                import os
                import time
                import random
                from werkzeug.utils import secure_filename

                try:
                    img = Image.open(photo_file)
                    max_dim = 800
                    img.thumbnail((max_dim, max_dim))

                    ext = os.path.splitext(photo_file.filename)[1]
                    photo_name = f"{int(time.time())}_{random.randint(10000, 99999)}{secure_filename(ext)}" # Secure the extension too
                    save_path = os.path.join(app.config.get('UPLOAD_FOLDER', 'static/uploads'), secure_filename(photo_name)) # Use app.config for upload folder
                    
                    # Ensure UPLOAD_FOLDER is configured in your app, e.g., app.config['UPLOAD_FOLDER'] = 'static/uploads'
                    # And ensure this directory exists and is writable by the Flask app.
                    os.makedirs(os.path.dirname(save_path), exist_ok=True)
                    img.save(save_path)
                except Exception as e:
                    flash(f"Error processing photo: {e}", "danger")
                    log_system_action(action="Add Doctor Photo Upload", status="fail", 
                                      target=f"Email: {email}", details=f"Photo processing error: {str(e)}",
                                      username=session.get('username'), user_id=session.get('user_id'))
                    photo_name = None # Reset photo_name if upload failed
                    # Decide if you want to proceed without a photo or redirect
                    # return redirect(url_for('admin_add_doctor'))

        # --- Password Hashing ---
        # NEVER store plain text passwords. Always hash them.
        password_hash_to_store = generate_password_hash(password)

        conn = None # Initialize conn
        try:
            conn = get_db_connection()
            if conn is None:
                flash("Database connection error.", "danger")
                log_system_action(action="Add Doctor DB Connect", status="error", details="Failed to get DB connection",
                                  username=session.get('username'), user_id=session.get('user_id'))
                return render_template('admin_add_doctor.html') # Or redirect

            cursor = conn.cursor()
            cursor.execute("""
                INSERT INTO users (username, email, password_hash, photo, role, status) 
                VALUES (%s, %s, %s, %s, %s, %s) 
            """, (username, email, password_hash_to_store, photo_name, role, 1)) # Assuming new doctors are active (status=1)
            conn.commit()
            flash("Doctor account created successfully!", "success")
            log_system_action(action="Add Doctor", status="success", 
                              target=f"Doctor Email: {email}", 
                              username=session.get('username'), user_id=session.get('user_id'))
            return redirect(url_for('admin_doctor_details'))
        except mysql.connector.IntegrityError as ie: # Catch specific error for duplicate email/username
            conn.rollback() # Rollback transaction
            flash(f"Error: Email or Username already exists. {ie}", "danger")
            log_system_action(action="Add Doctor", status="fail", 
                              target=f"Doctor Email: {email}", details=f"IntegrityError: {str(ie)}",
                              username=session.get('username'), user_id=session.get('user_id'))
        except pymysql.MySQLError as e:
            conn.rollback() # Rollback transaction
            flash(f"Database error creating doctor: {e}", "danger")
            log_system_action(action="Add Doctor", status="fail", 
                              target=f"Doctor Email: {email}", details=f"DB Error: {str(e)}",
                              username=session.get('username'), user_id=session.get('user_id'))
        except Exception as ex: # Catch any other unexpected errors
            if conn: conn.rollback()
            flash(f"An unexpected error occurred: {ex}", "danger")
            log_system_action(action="Add Doctor", status="error", 
                              target=f"Doctor Email: {email}", details=f"Unexpected Error: {str(ex)}",
                              username=session.get('username'), user_id=session.get('user_id'))
        finally:
            try:
                if conn: conn.close()
            except:
                pass

    
    # For GET requests
    return render_template('admin_add_doctor.html')

@app.route('/admin_doctor_details.html')
def admin_doctor_details():
    conn = get_db_connection()
    cursor = conn.cursor(pymysql.cursors.DictCursor)
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
    cursor = conn.cursor(pymysql.cursors.DictCursor)
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
    cursor = conn.cursor(pymysql.cursors.DictCursor)
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
    cursor = conn.cursor(pymysql.cursors.DictCursor)

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

latest_audit_results = {
    'status': 'Not run yet',
    'issues_critical': 0,
    'issues_medium': 0,
    'issues_low': 0,
}

@app.route('/admin_security_audit.html')
def admin_security_audit():
    # Use a local copy for rendering to avoid issues if global is being updated
    results_to_display = latest_audit_results.copy()
    if results_to_display.get('status') != 'Not run yet': # Only calculate status if an audit has run
        if results_to_display['issues_critical'] > 0 or results_to_display['issues_medium'] > 0:
            results_to_display['status_class'] = 'status-issue'
            results_to_display['status_text'] = 'Failed'
        else:
            results_to_display['status_class'] = 'status-ok'
            results_to_display['status_text'] = 'Passed'
    else:
        results_to_display['status_class'] = ''
        results_to_display['status_text'] = 'Not run yet'

    return render_template('admin_security_audit.html', **results_to_display)

# *****Security audit *****

@app.route('/run_audit')
def run_audit():
    # Ensure ZAP is running and its API is configured on 127.0.0.1:8090
    # The 'proxies' dict here tells ZAP to use this proxy for ITS OWN traffic when scanning.
    # The ZAP API client will attempt to connect to ZAP's API endpoint.
    # If ZAP's API is on 127.0.0.1:8090, this should be fine.
    # You might need an apikey='YOUR_ZAP_API_KEY' if you have one set in ZAP.
    zap_api_url = 'http://127.0.0.1:8090' # Explicitly define ZAP API URL
    zap = ZAPv2(proxies={'http': zap_api_url, 'https': zap_api_url}) # ZAP uses itself as proxy
    target = 'http://127.0.0.1:5000/admin_dashboard.html' # Ensure your Flask app runs on port 5000

    global latest_audit_results, latest_report_html
    
    try:
        # Check if ZAP API is reachable
        try:
            zap.core.version
            print(f"Connected to ZAP API version: {zap.core.version}")
        except Exception as e:
            print(f"Could not connect to ZAP API at {zap_api_url}. Is ZAP running and API enabled on this address/port? Error: {e}")
            return f"Could not connect to ZAP API. Ensure ZAP is running and its API is enabled on {zap_api_url}. Error: {e}", 500

        print("Starting spider scan...")
        scan_id = zap.spider.scan(target)
        max_wait_spider = 300  # Max 5 minutes for spider
        wait_time = 0
        while int(zap.spider.status(scan_id)) < 100 and wait_time < max_wait_spider:
            print(f"Spider scan progress: {zap.spider.status(scan_id)}%")
            time.sleep(5) # Check every 5 seconds
            wait_time += 5
        if wait_time >= max_wait_spider and int(zap.spider.status(scan_id)) < 100:
            print(f"Spider scan timed out after {max_wait_spider} seconds. Progress: {zap.spider.status(scan_id)}%")
            # Optionally, you can decide to stop here or proceed
            # return "Spider scan timed out", 500
        print("Spider scan completed or timed out.")

        print("Starting active scan...")
        ascan_id = zap.ascan.scan(target)
        max_wait_ascan = 600  # Max 10 minutes for active scan
        wait_time = 0
        while int(zap.ascan.status(ascan_id)) < 100 and wait_time < max_wait_ascan:
            print(f"Active scan progress: {zap.ascan.status(ascan_id)}%")
            time.sleep(10) # Check every 10 seconds
            wait_time += 10
        if wait_time >= max_wait_ascan and int(zap.ascan.status(ascan_id)) < 100:
            print(f"Active scan timed out after {max_wait_ascan} seconds. Progress: {zap.ascan.status(ascan_id)}%")
            # Optionally, you can decide to stop here or proceed
            # return "Active scan timed out", 500
        print("Active scan completed or timed out.")


        alerts = zap.core.alerts(baseurl=target)
        issues_critical = 0
        issues_medium = 0
        issues_low = 0

        for alert in alerts:
            # The 'count' field might not exist, or 'instances' might be more appropriate
            # Depending on ZAP version and how alerts are structured.
            # Let's assume each alert dictionary entry is a unique type of alert,
            # and 'instances' list within it tells how many times it occurred.
            # If 'instances' is not available, we can count 'count' or default to 1.
            
            num_instances = 1 # Default
            if 'instances' in alert and isinstance(alert['instances'], list):
                num_instances = len(alert['instances'])
            elif 'count' in alert:
                try:
                    num_instances = int(alert.get('count', 1))
                except ValueError:
                    num_instances = 1
            
            risk = alert['risk']
            if risk == 'High':
                issues_critical += num_instances
            elif risk == 'Medium':
                issues_medium += num_instances
            elif risk == 'Low':
                issues_low += num_instances

        current_time = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        session['audit_date'] = current_time
        session['issues_critical'] = issues_critical
        session['issues_medium'] = issues_medium
        session['issues_low'] = issues_low
        
        latest_audit_results = {
            'audit_date': current_time,
            'issues_critical': issues_critical,
            'issues_medium': issues_medium,
            'issues_low': issues_low,
        }

        print("Generating HTML report...")
        latest_report_html = zap.core.htmlreport()
        session['audit_count'] = session.get('audit_count', 0) + 1
        print("HTML report generated.")

        # DO NOT SHUTDOWN ZAP if you want to export report later or run more scans
        # zap.core.shutdown() # Commented out: Keep ZAP running

        # Instead of returning the report here, redirect to the audit page
        # The user can then click "Export Report"
        # Or, if you want to offer immediate download:
        response = make_response(latest_report_html)
        response.headers['Content-Type'] = 'text/html'
        response.headers['Content-Disposition'] = 'attachment; filename=audit_report_run_audit.html'
        print("Audit complete. Report ready for download.")
        return response
        # Alternatively, redirect back to the audit page to show results:
        # return redirect(url_for('admin_security_audit'))

    except Exception as e:
        error_message = f"Error running audit: {e}"
        print(error_message)
        # Update status to reflect error
        latest_audit_results = {
            'audit_date': datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
            'issues_critical': 'Error',
            'issues_medium': 'Error',
            'issues_low': 'Error',
            'status': 'Error during audit'
        }
        return error_message, 500

@app.route('/export_audit_report')
def export_audit_report():
    global latest_report_html
    if latest_report_html:
        response = make_response(latest_report_html)
        response.headers['Content-Type'] = 'text/html'
        response.headers['Content-Disposition'] = 'attachment; filename=audit_report_export.html'
        return response
    else:
        return "No audit report available. Please run an audit first.", 404


@app.route('/admin_system_logs.html')
def admin_system_logs():
    # Add authorization check: only admins should see this
    if 'user_id' not in session or session.get('role') != 'admin':
        flash("You are not authorized to view this page.", "danger")
        # Log this unauthorized attempt
        log_system_action(
            action="System Logs Access Attempt", 
            status="fail", 
            target="Unauthorized",
            username=session.get('username', 'Unknown/Guest'), # Get username if available
            user_id=session.get('user_id')
        )
        return redirect(url_for('login')) # Or admin_dashboard if they are logged in but not admin

    logs = [] # Initialize to empty list
    conn = None # Initialize conn
    try:
        conn = get_db_connection()
        if conn is None:
            flash('Database connection error. Cannot fetch logs.', 'danger')
            return render_template('admin_system_logs.html', logs=logs)

        cursor = conn.cursor(pymysql.cursors.DictCursor)
        # Query to fetch system logs, ordered by time descending
        cursor.execute("""
            SELECT log_id, username, action, target, 
                   DATE_FORMAT(time, '%Y-%m-%d %H:%i:%S') as time, 
                   status 
            FROM system_logs 
            ORDER BY time DESC
            LIMIT 200 
        """) # Added LIMIT for performance on large log tables
        logs = cursor.fetchall()
    except pymysql.MySQLError as e:
        print(f"Error fetching system logs: {e}")
        flash('An error occurred while fetching the logs. Please try again later.', 'danger')
        # Log this error to the logs themselves if possible, or to a file
        log_system_action(action="Fetch System Logs", status="error", details=str(e))
    except Exception as e: # Catch other potential errors
        print(f"Unexpected error fetching system logs: {e}")
        flash('An unexpected error occurred. Please try again later.', 'danger')
        log_system_action(action="Fetch System Logs", status="error", details=str(e))
    finally:
        if conn:
            if 'cursor' in locals() and cursor:
                cursor.close()
            conn.close()

            
    return render_template('admin_system_logs.html', logs=logs)

def log_system_action(action, status, target=None, user_id=None, username=None, details=None):
    """
    Logs an action to the system_logs table.
    """
    current_username = username
    current_user_id = user_id

    if request and 'user_id' in session and user_id is None: current_user_id = session['user_id']
    if request and 'username' in session and username is None: current_username = session['username']
    ip_addr = request.remote_addr if request else None
    conn_log, cursor_log = None, None
    try:
        conn_log = get_db_connection()
        if not conn_log: return
        cursor_log = conn_log.cursor()
        sql = "INSERT INTO system_logs (user_id, username, action, target, status, ip_address, details, time) VALUES (%s,%s,%s,%s,%s,%s,%s,%s)"
        cursor_log.execute(sql, (current_user_id, current_username, action, target, status, ip_addr, details, datetime.now()))
        conn_log.commit()
    except Exception as e_log: print(f"Error in log_system_action: {e_log}")
    finally:
        if cursor_log:
            cursor_log.close()
        if conn_log:
            conn_log.close()

@app.route('/logout')
def logout():
    session.pop('user_id', None)  # Remove the user_id from the session
    resp = make_response(redirect('/login'))  # Redirect to login page
    resp.delete_cookie('remember_token')  # Delete the remember me cookie
    return resp

@app.route("/whoami")
def whoami():
    return f"Session: {dict(session)}"

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)
