from flask import Flask, render_template, request, send_file, redirect, session, flash, url_for, make_response
import mysql.connector
import csv
from reportlab.lib.pagesizes import letter
from reportlab.pdfgen import canvas
from io import BytesIO, StringIO
from datetime import timedelta
from itsdangerous import URLSafeTimedSerializer

app = Flask(__name__)
app.secret_key = 'password'  # Secure the session with a secret key
app.config['SESSION_TYPE'] = 'filesystem'

# Setup the serializer
s = URLSafeTimedSerializer(app.config['SECRET_KEY'])

# MySQL connection configuration
def get_db_connection():
    return mysql.connector.connect(
        host='localhost',
        user='root',
        password='toor',
        database='health'
    )

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
        cursor.execute("SELECT user_id, username FROM users WHERE email = %s AND password_hash = %s AND role = 'doctor'", (email, password))
        doctor = cursor.fetchone()
        cursor.close()
        conn.close()

        if doctor:
            session['user_id'] = doctor['user_id']  # Store user_id in session
            session['username'] = doctor['username']  # Store username in session

            # If 'Remember Me' is checked, store a persistent cookie
            remember_me = request.form.get('remember_me')
            if remember_me:
                # Create a secure token using itsdangerous serializer
                token = s.dumps(doctor['user_id'])  # Serialize the user_id
                print(f"Generated token: {token}")  # Debugging line to check the generated token
                # Set the token as a cookie that will expire in 30 days
                resp = make_response(redirect('/doc_dashboard.html'))
                resp.set_cookie('remember_token', token, max_age=timedelta(days=30))
                print("Remember me cookie set")  # Debugging line to confirm cookie is set
                return resp
            
            # Set a flag in session to show a one-time message
            flash(f"Welcome, {doctor['username']}!")  # Flash the welcome message
            return redirect('/doc_dashboard.html')  # Redirect to doctor dashboard

        else:
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
            if patient['created_at']:
                patient['created_at'] = patient['created_at'].strftime('%d %b %Y')  # Format the 'created_at' date
            if patient['last_updated']:
                patient['last_updated'] = patient['last_updated'].strftime('%d %b %Y %H:%M')  # Format 'last_updated' timestamp

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
        except mysql.connector.Error as e:
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
def graph_exp_page():
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT timestamp, cgm_level FROM health_data LIMIT 10")  # Example query
    data = cursor.fetchall()
    cursor.close()
    conn.close()

    # Generate a graph using matplotlib/plotly (example: CGM levels over time)
    # Use Plotly or Matplotlib to create the graph here

    return render_template('doc_graphexp.html', data=data)

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
    cursor.execute("SELECT username, email, phone_number, specialist FROM users WHERE user_id = %s", (user_id,))
    doctor = cursor.fetchone()  # Fetch the doctor's profile data
    cursor.close()
    conn.close()

    if not doctor:
        flash('Doctor not found.')
        return redirect('/login')  # If no doctor found, redirect to login

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

@app.route('/logout')
def logout():
    session.pop('user_id', None)  # Remove the user_id from the session
    resp = make_response(redirect('/login'))  # Redirect to login page
    resp.delete_cookie('remember_token')  # Delete the remember me cookie
    return resp

if __name__ == '__main__':
    app.run(debug=True)
