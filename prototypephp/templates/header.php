<?php
    include 'profile_dropdown.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Admin Panel'; ?></title>
  
  <!-- External Stylesheets -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet"/>
  
  <!-- Google Font: Poppins (as requested) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">

  <style>
    /* --- CORE LAYOUT & ORIGINAL STYLES --- */
    :root {
      /* Define colors from your original screenshots for consistency */
      --primary-color: #3B82F6;
      --primary-hover: #2563EB;
      --teal-color: #10B981; /* A nice green for create buttons */
      --teal-hover: #059669;
      --secondary-btn-bg: #E5E7EB;
      --secondary-btn-hover: #D1D5DB;
      --text-primary: #1F2937;
      --text-secondary: #6B7280;
        --accent-color: #3498db;
        --stable-color: #2ecc71;
        --recovered-color: #007bff;
        --critical-color: #e74c3c;
        --warning-color: #e78c3cff;
        --border-color: rgba(255, 255, 255, 0.8);
        --shadow-color: rgba(0, 0, 0, 0.1);
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(120deg, #f6f7f8, #eaeff5);
      margin: 0;
    }
    .sidebar {
      width: 250px;
      height: 100vh;
      position: fixed;
      background: linear-gradient(180deg, #1b1f3a, #2c3553);
      color: white;
      top: 0;
      left: 0;
      padding: 30px 20px;
      box-shadow: 2px 0 8px rgba(0, 0, 0, 0.1);
      z-index: 100;
      transition: all 0.3s ease-in-out;
    }
    .sidebar-header h4 {
      font-size: 22px;
      font-weight: 600;
      margin-bottom: 30px;
    }
    .sidebar .nav-link {
      color: rgba(255, 255, 255, 0.9);
      padding: 12px 20px;
      border-radius: 10px;
      margin-bottom: 10px;
      transition: background 0.3s ease, padding-left 0.3s ease;
      font-size: 15px;
      display: flex;
      align-items: center;
    }
    .sidebar .nav-link i {
      margin-right: 10px;
    }
    .sidebar .nav-link:hover,
    .sidebar .nav-link.active {
      background-color: rgba(255, 255, 255, 0.2);
      padding-left: 25px;
      color: #fff;
      text-decoration: none;
    }
    .main {
      margin-left: 270px;
      padding: 40px;
    }
    .doctor-card {
      background-color: #fff;
      border-radius: 10px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      padding: 20px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .doctor-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
    }
    .doctor-card img {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      margin-right: 20px;
      object-fit: cover;
    }
    .doctor-info {
      flex-grow: 1;
    }
    .doctor-info a {
      font-weight: bold;
      font-size: 18px;
      color: #333;
      text-decoration: none;
    }
    .doctor-info a:hover {
      text-decoration: underline;
    }
    .status-badge {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: bold;
      color: #fff;
    }
    .status-badge.active {
      background-color: #28a745;
    }
    .status-badge.inactive {
      background-color: #ff9800;
    }
    .search-container {
      display: flex;
      justify-content: flex-end;
      margin-bottom: 20px;
    }
    .search-container input {
      border-radius: 20px;
      border: 1px solid #ccc;
      padding: 5px 10px;
    }
    .fab {
      position: fixed;
      bottom: 30px;
      right: 30px;
      background: #007bff;
      color: white;
      border-radius: 50%;
      padding: 18px 22px;
      font-size: 20px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
      transition: background 0.3s ease;
      z-index: 1000;
    }
    .fab:hover {
      background: #0056b3;
      text-decoration: none;
    }
    
    .main-content {
      margin-left: 250px;
      padding: 40px;
      /* Animation for a modern feel */
      animation: fadeInSlideUp 0.6s ease-out forwards;
    }
    @keyframes fadeInSlideUp {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* --- STYLES FOR FORMS & CARDS --- */
    .page-title {
      font-weight: 700;
      margin-bottom: 30px;
      color: #1f2b3a;
      text-align: center;
    }
    .form-card {
      background: white;
      border-radius: 16px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.06);
      border: 1px solid rgba(0, 0, 0, 0.05);
      padding: 40px;
      max-width: 600px;
      margin: auto;
    }
    .form-label {
      font-weight: 600;
      color: #3b3f50;
    }
    .form-control, .form-select {
        border-radius: 12px;
        padding: 10px 15px;
        border: 1px solid #cbd3df;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--teal-color);
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.15);
    }
    
    /* Floating Label Adjustments */
    .form-floating > .form-control { height: 58px; }
    .form-floating > label { color: var(--text-secondary); }
    .form-floating > .form-control:focus, .form-floating > .form-control:not(:placeholder-shown) {
        padding-top: 1.625rem; padding-bottom: 0.625rem;
    }
    .form-floating > .form-control:focus ~ label, .form-floating > .form-control:not(:placeholder-shown) ~ label {
        opacity: 0.65; transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem);
    }

    .btn {
      font-weight: 600;
      padding: 10px 25px;
      border-radius: 30px;
      border: none;
      transition: all 0.2s ease;
    }
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .btn:active {
        transform: translateY(0);
        box-shadow: none;
    }

    .btn-cancel {
      background-color: var(--secondary-btn-bg);
      color: var(--text-secondary);
    }
    .btn-cancel:hover { background-color: var(--secondary-btn-hover); }

    .btn-create {
      background: var(--teal-color);
      color: white;
    }
    .btn-create:hover { background: var(--teal-hover); }
 /* --- Profile Dropdown Component --- */
.profile-dropdown { 
    position: absolute; 
    top: 20px; /* Adjusted for better spacing */
    right: 30px; 
    z-index: 1050; /* Ensures it's above other content */
}

.profile-dropdown .dropdown-toggle { 
    background: transparent; 
    border: none; 
    color: #495057; /* Softer color */
    font-size: 24px; /* Larger icon */
    padding: 0;
}

.profile-dropdown .dropdown-toggle::after {
    display: none; /* Hides the default Bootstrap dropdown arrow */
}

.profile-dropdown .dropdown-menu { 
    border-radius: 10px; 
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); 
    border: 1px solid #e9ecef;
}

    .navbar { transition: background-color 0.3s ease-in-out, box-shadow 0.3s ease-in-out; }
        .navbar-brand { font-size: 1.5rem; color: #0d6efd; }
        .navbar-brand .fa-heartbeat { color: #dc3545; }
        .nav-link { font-weight: 500; transition: color 0.2s ease; }
        .nav-link:hover, .nav-link.active { color: #0d6efd !important; }
        .btn-rounded { border-radius: 50px; }
        .hero-section {
            min-height: 100vh;
            /* IMPORTANT: For PHP, define a static background image path or use a CSS variable */
            background: linear-gradient(rgba(27, 31, 58, 0.7), rgba(44, 53, 83, 0.85)), url('static/uploads/hero-background.png') no-repeat center center;
            background-size: cover;
            padding-top: 120px;
            padding-bottom: 60px;
            display: flex;
            align-items: center;
            text-align: center;
            color: white;
        }

        /* === ADD THIS: Modern Profile Page Styles === */

.profile-page-container {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    padding: 2rem;
    background-color: #f0f2f5; /* A soft, professional background */
}

.profile-card {
    background: var(--card-bg);
    border-radius: 1.5rem; /* More rounded */
    box-shadow: 0 10px 40px rgba(0,0,0,0.08);
    width: 100%;
    max-width: 400px; /* Optimal width for this design */
    text-align: center;
    border: 1px solid var(--border-color);
    padding: 2.5rem 2rem 2rem 2rem;
}
.profile-avatar-wrapper {
    margin-top: -95px; /* Pull the image up */
    margin-bottom: 1rem;
}
.profile-avatar-wrapper img {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    border: 6px solid var(--card-bg);
    box-shadow: 0 5px 20px rgba(0,0,0,0.15);
}
.doctor-name {
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}
.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.8rem;
    font-weight: 600;
    display: inline-block;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.status-badge.status-active { background-color: #dcfce7; color: #166534; }
.status-badge.status-inactive { background-color: #fef3c7; color: #92400e; }

.info-list {
    margin-top: 1.5rem;
    margin-bottom: 2rem;
    text-align: left;
}
.info-list p {
    margin-bottom: 1rem;
    font-size: 1rem;
    color: var(--text-secondary);
    border-bottom: 1px solid #f0f2f5;
    padding-bottom: 1rem;
}
.info-list p:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}
.info-list .info-label {
    font-weight: 400; /* Lighter label */
    display: block;
    font-size: 0.9rem;
    margin-bottom: 0.1rem;
}
.info-list .info-value {
    font-weight: 600;
    color: var(--text-primary);
}

.btn-view-patients {
    background-color: var(--primary-color);
    border: none;
    font-weight: 500;
    width: 100%;
    padding: 0.75rem;
    border-radius: 0.5rem;
}
.btn-view-patients:hover {
    background-color: var(--primary-hover);
}

.doctor-specialist {
    font-size: 1rem;
    font-weight: 500;
    margin-top: -0.5rem; /* Pull it closer to the name */
    margin-bottom: 1rem; /* Add space above the status badge */
}

    .main-content {
        padding: 40px;
        animation: fadeInSlideUp 0.6s ease-out forwards;
    }

    .page-header {
        margin-bottom: 30px;
    }

    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        color: var(--primary-text-color);
        font-weight: 500;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
    }

    .back-link:hover {
        color: var(--accent-color);
        transform: translateX(-5px);
    }

    .page-title {
        font-weight: 700;
        font-size: 2.5rem;
        color: var(--primary-text-color);
    }

    .page-header p {
        color: var(--secondary-text-color);
        font-size: 1.1rem;
    }

    .patient-list-card {
        background: var(--card-bg-color);
        border-radius: 20px;
        border: 1px solid var(--border-color);
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        box-shadow: 0 8px 32px 0 var(--shadow-color);
        overflow: hidden;
    }
    
    .card-body {
        padding: 0;
    }

    .table-responsive {
        padding: 20px;
    }

    .table {
        border-collapse: separate;
        border-spacing: 0 10px;
    }
    
    .table thead th {
        border: 0;
        color: var(--secondary-text-color);
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        padding: 1rem 1.5rem;
    }

    .table tbody tr {
        background-color: rgba(255, 255, 255, 0.5);
        border-radius: 10px;
        transition: all 0.3s ease;
        opacity: 0;
        animation: fadeInSlideUp 0.5s ease-out forwards;
    }
    
    <?php if (!empty($patients)): ?>
        <?php foreach (array_keys($patients) as $index): ?>
    .table tbody tr:nth-child(<?php echo $index + 1; ?>) {
        animation-delay: <?php echo $index * 0.08; ?>s;
    }
        <?php endforeach; ?>
    <?php endif; ?>

    .table tbody tr:hover {
        transform: translateY(-5px) scale(1.02);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        background-color: #fff;
    }

    .table td {
        border: 0;
        padding: 1.25rem 1.5rem;
        vertical-align: middle;
    }

    .table td:first-child { border-top-left-radius: 10px; border-bottom-left-radius: 10px; }
    .table td:last-child { border-top-right-radius: 10px; border-bottom-right-radius: 10px; }

    .status-badge {
        display: inline-block;
        padding: 0.5em 1.2em;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        color: #fff;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
    }
    
    .status-recovered { background: linear-gradient(45deg, var(--recovered-color), #65a9f3); box-shadow: 0 2px 10px rgba(0, 123, 255, 0.3); }
    .status-stable { background: linear-gradient(45deg, var(--stable-color), #58d68d); box-shadow: 0 2px 10px rgba(46, 204, 113, 0.3); }
    .status-critical { background: linear-gradient(45deg, var(--critical-color), #f1948a); box-shadow: 0 2px 10px rgba(231, 76, 60, 0.3); animation: pulse 2s infinite; }
    .status-warning { background: linear-gradient(45deg, var(--warning-color), #d6bf58ff); box-shadow: 0 2px 10px rgba(204, 199, 46, 0.3); }
    .hero-section h1 { font-weight: 700; text-shadow: 1px 1px 3px rgba(0,0,0,0.2); }
    .hero-section .lead { font-size: 1.25rem; font-weight: 300; max-width: 700px; margin: 0 auto; }
    .btn-light-alt { background-color: rgba(255, 255, 255, 0.9); color: #1b1f3a; border: 1px solid transparent; transition: all 0.3s ease; }
    .btn-light-alt:hover { background-color: #fff; color: #0d6efd; transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
    .btn-outline-light:hover { background-color: #fff; color: #0d6efd; }
    #features { padding: 80px 0; }
    .feature-card { border: none; border-radius: 15px; transition: transform 0.3s ease, box-shadow 0.3s ease; background-color: #fff; }
    .feature-card:hover { transform: translateY(-10px); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1) !important; }
    .feature-icon { width: 70px; height: 70px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 1.8rem; margin-bottom: 1.5rem; }
    .feature-card .card-title { font-weight: 600; margin-bottom: 0.75rem; }
    #about .img-fluid { border-radius: 15px; max-height: 400px; }
    footer a:hover { text-decoration: underline !important; }
    .modal-body p, .modal-body ul { text-align: left; margin-bottom: 1rem; }
    .modal-body h5 { margin-top: 1.5rem; margin-bottom: 0.5rem; font-weight: 600; }
    .modal-body ul { padding-left: 20px; }
</style>
</head>
<body>
  <?php
    include 'footer.php';
?>