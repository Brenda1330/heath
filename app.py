from sqlalchemy import text
from flask import Flask, render_template, request, jsonify, redirect, session, flash, url_for, make_response
from neo4j import GraphDatabase
import pymysql
from pymysql.cursors import DictCursor  # Import DictCursor
import csv
from flask import jsonify
from reportlab.lib.pagesizes import letter
from reportlab.pdfgen import canvas
from io import BytesIO, StringIO
from datetime import timedelta, datetime
from itsdangerous import URLSafeTimedSerializer
from flask_sqlalchemy import SQLAlchemy
import google.generativeai as genai
import smtplib
import random
import string
import hashlib
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText

app = Flask(__name__)
app.secret_key = 'password'
app.config['SESSION_TYPE'] = 'filesystem'
app.config['SQLALCHEMY_DATABASE_URI'] = 'mysql+pymysql://root:@localhost/health'
app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False
# Initialize the Neo4j driver once
neo4j_driver = GraphDatabase.driver(
    "neo4j+s://8a6d0f01.databases.neo4j.io",
    auth=("neo4j", "QQoj0RBDAwoTv3n2uFu4ZvvvethNy6mZsQ_jpN7W-U4")
)

s = URLSafeTimedSerializer(app.secret_key)

db = SQLAlchemy(app)


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




# ✅ 邮件发送函数
def send_invoice_email(to_email, html_content):
    sender_email = "supercleanzsystem@gmail.com"
    sender_password = "seah kfro xmup zwwf"  # 用你的 Gmail App Password

    msg = MIMEMultipart()
    msg['From'] = sender_email
    msg['To'] = to_email
    msg['Subject'] = '🔐 Reset Your Password - Healt Track Pro'

    msg.attach(MIMEText(html_content, 'html'))

    try:
        server = smtplib.SMTP_SSL('smtp.gmail.com', 465)
        server.login(sender_email, sender_password)
        server.sendmail(sender_email, to_email, msg.as_string())
        server.quit()
        print("✅ Email sent to", to_email)
        return True
    except Exception as e:
        print("❌ Email error:", str(e))
        return False


# ✅ 检查 email 是否存在（被 JS 调用）
@app.route('/check_email', methods=['POST'])
def check_email():
    try:
        data = request.get_json()
        email = data.get('email')
        print(f"🔍 Checking email: {email}")

        result = db.session.execute(
            text("SELECT * FROM users WHERE email = :email"),
            {"email": email}
        ).fetchone()

        return jsonify({'found': result is not None})
    except Exception as e:
        print(f"[ERROR] check_email: {e}")
        return jsonify({'error': 'Server error'}), 500

@app.route('/forgot_password', methods=['POST'])
def forgot_password():
    try:
        data = request.get_json()
        email = data.get('email')

        # 查找用户（字段名来自你的表结构）
        result = db.session.execute(
            text("SELECT user_id, username FROM users WHERE LOWER(email) = LOWER(:email)"),
            {"email": email}
        ).fetchone()

        if result:
            user_id = result.user_id
            username = result.username

            # ✅ 生成 10 位随机密码
            new_password = ''.join(random.choices(string.ascii_letters + string.digits, k=10))
            hashed_password = hashlib.md5(new_password.encode()).hexdigest()

            # ✅ 更新数据库（字段是 password_hash）
            db.session.execute(
                text("UPDATE users SET password_hash = :pw WHERE user_id = :uid"),
                {"pw": hashed_password, "uid": user_id}
            )
            db.session.commit()

            # ✅ 邮件内容（用 HTML 格式）
            html_content = f"""
                <h3>Password Reset</h3>
                <p>Hi <b>{username}</b>,</p>
                <p>Your new temporary password is:</p>
                <p style="font-size: 18px; color: #2c3e50;"><b>{new_password}</b></p>
                <p>Please log in using this password and <b>change it immediately</b> after login.</p>
                <br>
                <p>Regards,<br>Health Track Pro Team</p>
            """

            # ✅ 发邮件
            send_invoice_email(email, html_content)
            return jsonify({'success': True, 'message': 'A new password has been sent to your email.'})
        else:
            return jsonify({'success': False, 'message': 'Email not found.'})

    except Exception as e:
        print("[ERROR] forgot_password:", e)
        return jsonify({'success': False, 'message': 'Server error'}), 500




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

s = URLSafeTimedSerializer(app.secret_key)

# Setup Gemini API key (do this once at app start)
genai.configure(api_key="AIzaSyClJYQFCY8JYgxK8UcuwWVC097WYUm8dzY")
model = genai.GenerativeModel("gemini-2.0-flash")

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
                datetime.datetime.now()
            ))
            conn.commit()

    cursor.close()
    conn.close()

def get_db_connection():
    return pymysql.connect(
        host='localhost',
        user='root',
        password='',  # Add your database password here if any
        database='health'
    )

@app.route('/')
def home():
    remember_token = request.cookies.get('remember_token')
    if remember_token:
        print("Remember Me cookie is present.")
        try:
            user_id = s.loads(remember_token, max_age=30 * 24 * 60 * 60)  # 30 days
            print(f"User ID from Remember Me token: {user_id}")

            conn = get_db_connection()
            cursor = conn.cursor(DictCursor)
            cursor.execute("SELECT user_id, username, role FROM users WHERE user_id = %s", (user_id,))
            user = cursor.fetchone()
            cursor.close()
            conn.close()

            if user:
                session['user_id'] = user['user_id']
                session['username'] = user['username']

                if user['role'] == 'doctor':
                    return redirect('/doc_dashboard.html')
                elif user['role'] == 'admin':
                    return redirect('/admin_dashboard.html')
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
        cursor = conn.cursor(DictCursor)
        cursor.execute("SELECT user_id, username, role FROM users WHERE email = %s AND password_hash = %s" , (email, password))
        user = cursor.fetchone()
        cursor.close()
        conn.close()

        if user:
            session['user_id'] = user['user_id']  # Store user_id in session
            session['username'] = user['username']  # Store username in session

            # If 'Remember Me' is checked, store a persistent cookie
            remember_me = request.form.get('remember_me')
            if remember_me:
                # Create a secure token using itsdangerous serializer
                token = s.dumps(user['user_id'])  # Serialize the user_id
                print(f"Generated token: {token}")  # Debugging line to check the generated token
                # Set the token as a cookie that will expire in 30 days
                
                if (user["role"]=="doctor"):
                    resp = make_response(redirect('/doc_dashboard.html'))
                    resp.set_cookie('remember_token', token, max_age=timedelta(days=30))
                    print("Remember me cookie set")  # Debugging line to confirm cookie is set
                    return resp
                elif (user["role"]=="admin"):
                    resp = make_response(redirect('/admin_dashboard.html'))
                    resp.set_cookie('remember_token', token, max_age=timedelta(days=30))
                    print("Remember me cookie set")  # Debugging line to confirm cookie is set
                    return resp
            
            # Set a flag in session to show a one-time message
            if (user['role'] == 'doctor'):
                flash(f"Welcome, {user['username']}!")  # Flash the welcome message
                return render_template('login.html', login_success=True)
                return redirect('/doc_dashboard.html') 
            
            elif (user['role'] == 'admin'):
                    flash(f"Welcome, {user['username']}!")  # Flash the welcome message
                    return render_template('login.html', login_admin_success=True)

        else:
            
            return render_template('login.html', login_error_message=True)

    return render_template('login.html')

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
            SELECT ROUND(AVG(cgm_level), 1) AS avg_cgm
             FROM health_data
        """)
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
    cursor = conn.cursor(DictCursor)
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
    cursor = conn.cursor(DictCursor)
    # Ensure the patient belongs to the logged-in doctor
    cursor.execute("SELECT * FROM patients WHERE patient_id = %s AND doctor_id = %s", (patient_id, doctor_id))
    patient = cursor.fetchone()
    if patient is None:
        cursor.close()
        conn.close()
        return jsonify({'error': 'Patient not found or unauthorized'}), 404

    # Fetch timestamps from health_data
    cursor.execute("SELECT data_id, timestamp FROM health_data WHERE patient_id = %s ORDER BY timestamp DESC", (patient_id,))
    timestamps = cursor.fetchall()
    cursor.close()
    conn.close()

    return jsonify({'timestamps': timestamps})

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
    cur = conn.cursor(DictCursor)
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
    cur = conn.cursor()
    cur.execute("""
      SELECT DISTINCT `timestamp`
      FROM health_data
      WHERE patient_id = %s
    """, (pid,))
    rows = cur.fetchall()
    cur.close()
    conn.close()

    iso_timestamps = []
    for (raw,) in rows:
        try:
            # handle both slash or MySQL DATETIME format
            try:
                dt = datetime.strptime(raw, "%d/%m/%Y %H:%M")
            except:
                dt = datetime.strptime(raw, "%Y-%m-%d %H:%M:%S")
            iso = dt.isoformat() + "Z"
            iso_timestamps.append(iso)
        except Exception as e:
            print("❌ Timestamp parse error:", raw, str(e))
            continue

    print("✅ Available ISO timestamps:", iso_timestamps)
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
            WHERE m.time = datetime($ts)
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
        m = item["meas"]
        props = dict(m)

        # ✅ FIXED: Safe embedding parsing
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

        node_type = list(m.labels)[0]

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
    cursor = conn.cursor(DictCursor)

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
    cursor = conn.cursor(DictCursor)
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
    cursor = conn.cursor(DictCursor)
    
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
    cursor = conn.cursor(DictCursor)

    # CGM Trend for the last 7 days
    cursor.execute("""
        SELECT DATE(timestamp) AS date, ROUND(AVG(cgm_level), 1) AS avg_cgm 
        FROM health_data 
        GROUP BY DATE(timestamp) 
        ORDER BY date DESC 
        LIMIT 7
    """)
    trend_data = cursor.fetchall()
    labels = [row['date'].strftime('%Y-%m-%d') for row in reversed(trend_data)]
    data = [row['avg_cgm'] for row in reversed(trend_data)]

    # Doctor & Patient counts
    cursor.execute("SELECT COUNT(*) AS count FROM users WHERE role = 'doctor'")
    doctor_count = cursor.fetchone()['count']

    cursor.execute("SELECT COUNT(*) AS count FROM patients")
    patient_count = cursor.fetchone()['count']

    cursor.close()
    conn.close()

    return render_template('admin_dashboard.html', labels=labels, data=data,
                           doctor_count=doctor_count, patient_count=patient_count)

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
            cursor.execute("""
                INSERT INTO users (username, email, password_hash, photo, role)
                VALUES (%s, %s, %s, %s, %s)
            """, (username, email, password_hash, photo_name, role))
            conn.commit()
            flash("Doctor account created successfully!")
            return redirect('/admin_doctor_details.html')  # update as needed
        except pymysql.MySQLError as e:
            flash(f"Database error: {e}")
        finally:
            cursor.close()
            conn.close()

    return render_template('admin_add_doctor.html')

@app.route('/admin_doctor_details.html')
def admin_doctor_details():
    conn = get_db_connection()
    cursor = conn.cursor(DictCursor)
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
    cursor = conn.cursor(DictCursor)
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
    cursor = conn.cursor(DictCursor)
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
    cursor = conn.cursor(DictCursor)

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

@app.route('/admin_system_logs.html')
def admin_system_logs():
    try:
        # Connect to the database
        conn = get_db_connection()
        cursor = conn.cursor(DictCursor)

        # Query to fetch system logs from the database
        cursor.execute("""
            SELECT username, action, target, time, status FROM system_logs ORDER BY time DESC
        """)
        logs = cursor.fetchall()

        # Close the connection and cursor
        cursor.close()
        conn.close()

        # Pass the logs to the template for rendering
        return render_template('admin_system_logs.html', logs=logs)
    
    except Exception as e:
        print(f"Error fetching system logs: {e}")
        flash('An error occurred while fetching the logs. Please try again later.', 'danger')
        return render_template('admin_system_logs.html', logs=[])

@app.route('/logout')
def logout():
    session.pop('user_id', None)  # Remove the user_id from the session
    resp = make_response(redirect('/login'))  # Redirect to login page
    resp.delete_cookie('remember_token')  # Delete the remember me cookie
    return resp

if __name__ == '__main__':
    app.run(debug=True)