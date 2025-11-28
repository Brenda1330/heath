<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Doctor Panel'; ?></title>
    
    <!-- External Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css" rel="stylesheet"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet"/>
    
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* --- STYLES FOR DOCTOR DASHBOARD - EXACTLY AS PER YOUR HTML --- */
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f7f7f7;
        }

        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, #1b1f3a, #2c3553);
            color: white;
            border-right: 1px solid #ddd;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            padding: 30px 20px;
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.1);
            z-index: 100;
        }
        .sidebar-header h4 { font-size: 22px; font-weight: 600; margin-bottom: 30px; }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.9); padding: 12px 20px;
            border-radius: 10px; margin-bottom: 10px;
            transition: background 0.3s ease, padding-left 0.3s ease;
            font-size: 15px; display: flex; align-items: center;
        }
        .sidebar .nav-link i { margin-right: 10px; }
        .sidebar .nav-link i.fa-chevron-down { margin-left: auto; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2); padding-left: 25px;
            color: #fff; text-decoration: none;
        }

/* === MODIFIED: Professional Dashboard Header === */
.dashboard-header {
    margin-left: 270px; /* Keep this */
    padding: 20px;
    margin-top: 50px; /* INCREASED: Adds space at the top */
    background-color: #ffffff; /* Sets a clean card-like background */
    border-radius: 12px; /* Soft rounded corners */
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); /* Softer, more modern shadow */
    border-bottom: 1px solid #e9ecef; /* Subtle separator line */
}

.dashboard-header h1 { 
    font-size: 32px; 
    font-weight: 700;
    /* NEW: Updated gradient for a more professional and vibrant look */
    background: linear-gradient(90deg, #1e3a8a, #3b82f6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    text-shadow: none; /* Removed previous shadow for a cleaner look */
}

/* === MODIFIED: Widget Card Centering & Styling === */
/* NOTE: This requires adding 'widget-card' class and wrapping content in 'card-body' in doc_dashboard.php */
/* === MODIFIED: Widget Card Styling & Color === */
.card.widget-card {
    border: none;
    border-left: 5px solid #3b82f6; /* NEW: Professional color accent */
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%); /* NEW: Subtle gradient */
    transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
    overflow: hidden; /* Ensures pseudo-elements stay within card */
}

.card.widget-card:hover {
    transform: translateY(-8px) scale(1.02); /* NEW: More dynamic hover effect */
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    border-color: #1e3a8a; /* NEW: Border color changes on hover */
}

.widget-card .card-body {
    display: flex;
    /* Aligns icon and text side-by-side */
    align-items: center; 
    justify-content: center;
    gap: 15px; /* Adds space between icon and text */
    height: 100%;
    padding: 1.5rem; /* Increased padding */
    position: relative;
    z-index: 2;
}

/* NEW: Styling for the optional icons */
.widget-icon {
    font-size: 2rem; /* Icon size */
    color: #3b82f6;  /* Icon color */
    background-color: rgba(59, 130, 246, 0.1); /* Soft background circle */
    border-radius: 50%;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.card.widget-card:hover .widget-icon {
    transform: rotate(360deg) scale(1.1); /* Fun animation on hover */
    color: #1e3a8a;
}

.widget-card h6 {
    margin-bottom: 0.25rem;
    text-align: left; /* Aligns text neatly next to icon */
}

.widget-card h2 {
    font-size: 2.25rem; /* Adjusted for icon layout */
    text-align: left;
    transition: color 0.3s ease;
}

.card.widget-card:hover h2 {
    color: #1e3a8a; /* Number color changes on hover */
    transform: none; /* Disabled previous scale to avoid conflict */
}


.chart-container {
    position: relative;
    height: 250px; /* Set a minimum height for the chart */
    width: 100%;
}

/* Quick Stats Styling */
.quick-stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr; /* Two columns */
    gap: 1.5rem; /* Space between items */
}
.stat-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: transform 0.2s ease;
}
.stat-item:hover {
    transform: scale(1.05); /* Subtle zoom effect on hover */
}
.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}
.stat-text {
    display: flex;
    flex-direction: column;
}
.stat-value {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1;
}
.stat-label {
    font-size: 0.9rem;
    color: var(--text-secondary);
}

/* Calendar Card - Refined Styles */
.card.calendar-card {
    background: #ffffff;
    transition: all 0.3s ease; /* Add transition for hover effect */
}
.card.calendar-card:hover {
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    transform: translateY(-5px);
}
.calendar-header {
    font-size: 1rem;
    font-weight: 600;
    color: #3b82f6; /* Use primary color for header */
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #e9ecef;
}
.calendar-grid {
    gap: 8px; /* Increase spacing between dates */
    font-size: 0.85rem;
}
.calendar-grid .day-name {
    font-weight: 500;
    color: #9ca3af; /* Softer color for day names */
}
.calendar-grid div {
    height: 32px;
    width: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto; /* Center the date cells */
    transition: all 0.2s ease;
}
.calendar-grid .highlight {
    background-color: #3b82f6;
    color: white;
    border-radius: 50%;
    font-weight: 600;
    transform: scale(1.1);
    box-shadow: 0 4px 10px rgba(59, 130, 246, 0.4);
}
.calendar-footer {
    font-size: 0.8rem;
    color: #9ca3af;
}


/* === CORRECTED: Final Dropdown Arrow Styling === */
/* === CORRECTED: Final Sorting Button and Icon Styling === */

.sorting-btn {
    background-color: #f8f9fa;
    padding: 8px 16px; /* A little more padding */
    cursor: pointer;
    border: 1px solid #dee2e6;
    font-size: 14px;
    border-radius: .375rem; /* Bootstrap's default 'form-control' radius */
    
    /* Use Flexbox for perfect alignment */
    display: flex;
    align-items: center;
    gap: 0.5rem; /* Creates clean space between text and icon */
}

.sorting-btn:hover {
    background-color: #e9ecef;
}

/* Hide the original Bootstrap arrow */
.sorting-btn.dropdown-toggle::after {
    display: none;
}

/* Styles for our custom sort direction icon container */
.sort-indicator {
    /* This span will hold the icon */
    display: inline-flex; /* Use flex to center the icon inside */
    align-items: center;
    font-family: "Font Awesome 6 Free";
    font-weight: 900;
    transition: transform 0.2s ease-in-out;
}

/* Style for the Ascending (Up) Arrow */
.sort-indicator.sort-asc::before {
    content: "\f0de"; /* Font Awesome unicode for sort-up icon (▲) */
}

/* Style for the Descending (Down) Arrow */
.sort-indicator.sort-desc::before {
    content: "\f0dd"; /* Font Awesome unicode for sort-down icon (▼) */
}

        .table-container { margin-left: 270px; padding: 20px; }
        .table th, .table td { text-align: center; vertical-align: middle; }
        .table th { background-color: #f4f4f4; color: #333; font-weight: bold; }

        .status-box {
            display: inline-block; padding: 8px 18px; font-weight: bold; color: white;
            border-radius: 10px; text-align: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); min-width: 100px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .status-box.critical { background-color: #FF4C4C; }
        .status-box.warning { background-color: #FFEB3B; color: #333; }
        .status-box.stable { background-color: #03ff4f; color: #333; }
        .status-box.recovered { background-color: #29B6F6; }
        .status-box.unknown { background-color: #6c757d; }
        .status-box:hover { transform: scale(1.05); box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3); }

        .table { background-color: white; border-radius: 6px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05); }
        .table tbody tr {
            opacity: 0; transform: translateX(-100px);
            animation: slideIn 0.5s forwards;
            transition: background-color 0.3s ease, transform 0.5s ease;
        }
.table tbody tr:hover {
    background-color: #f8fafc;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}        @keyframes slideIn { to { opacity: 1; transform: translateX(0); } }
        
        .sorting-btn {
            background-color: #f8f9fa; padding: 8px 12px; cursor: pointer;
            border: 1px solid #dee2e6; font-size: 14px; border-radius: .25rem;
        }
        .sorting-btn:hover { background-color: #e9ecef; }
        
        .profile-dropdown { position: absolute; top: 10px; right: 30px; }
        .profile-dropdown .dropdown-toggle { background: transparent; border: none; color: #19213e; font-size: 20px; }
        .profile-dropdown .dropdown-menu { border-radius: 10px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }

        /* Calendar Styles from original HTML (if needed on other pages) */
        .calendar-card { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); max-width: 300px; padding: 16px; margin: auto; }
        .calendar-header { display: flex; justify-content: space-between; color: #0062cc; font-weight: bold; margin-bottom: 10px; }
        .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; text-align: center; font-size: 0.8rem; }
        .calendar-grid .highlight { background-color: #0062cc; color: white; font-weight: bold; border-radius: 50%; }
        .calendar-footer { text-align: center; margin-top: 10px; color: #666; font-size: 0.8rem; }

        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .form-header {
            text-align: center;
            margin-bottom: 20px;
            margin-top: 50px;
        }
        .form-header h1 {
            font-size: 28px;
            font-weight: 600;
            color: #333;
        }
        .form-container {
            background-color: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 0 auto;
        }
        .form-label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
            font-size: 1rem;
        }
        .form-control, .form-select {
            padding: 10px;
            border-radius: 8px;
            font-size: 1rem;
            width: 100%;
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #8e2de2;
            box-shadow: 0 0 8px rgba(142, 45, 226, 0.3);
        }
        .dropdown-wrapper {
            position: relative;
        }
        .dropdown-wrapper i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1rem;
            color: #555;
            pointer-events: none; /* Allows clicks to pass through to the select */
        }
        select.form-control {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }
        .btn-primary {
            background: linear-gradient(to right, #202c58ff, #38255eff);
            border: none;
            font-size: 1rem;
            padding: 12px;
            border-radius: 50px;
            transition: background 0.3s ease, transform 0.3s ease;
        }
        .btn-primary:hover {
            background: linear-gradient(to right, #4a00e0, #8e2de2);
            transform: scale(1.03);
        }
        /* The creative "fill-in" hover effect for the secondary button */
        .table .btn-outline-primary.btn-sm:hover {
            background-color: #3b82f6; /* On hover, it fills with the primary color */
            color: white; /* The text becomes white to be readable */
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(59, 130, 246, 0.3);
        }

        /* --- NEW PROFILE CARD STYLES --- */
    .profile-card {
        background: var(--card-bg);
        border-radius: 1rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.07);
        overflow: hidden; /* Important for border-radius on children */
    }
    .profile-header {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        padding: 2rem;
        background-color: #f8f9fa;
        border-bottom: 1px solid var(--border-color);
    }
    .profile-avatar-wrapper {
        position: relative;
        flex-shrink: 0;
    }
    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 5px solid white;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }
    .profile-avatar-wrapper:hover .profile-avatar {
        transform: scale(1.05);
    }
    .profile-name {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
    }

    .patient-profile-banner .status-box {
    padding: 0.4rem 0.9rem;
    font-size: 0.9rem;
    font-weight: 600;
    border-radius: 10px; /* A slightly softer radius */
    text-transform: lowercase; /* Make text lowercase as per your image */
    box-shadow: none; /* Remove the heavy shadow for a flatter, modern look */
    min-width: 80px;
}

/* --- Lighter/Softer Color Palette --- */

.patient-profile-banner .status-box.stable {
    background-color: #a6f3bdff; /* The bright gree*/
    color: #0a521aff; /* A dark, readable green for the text */
}

.patient-profile-banner .status-box.critical {
    background-color: #fde2e4; /* Soft red */
    color: #b71c1c; /* Dark red text */
}

.patient-profile-banner .status-box.warning {
    background-color: #fff9c4; /* Soft yellow */
    color: #827717; /* Dark yellow/brown text */
}

.patient-profile-banner .status-box.recovered {
    background-color: #e3f2fd; /* Soft blue */
    color: #0d47a1; /* Dark blue text */
}

.patient-profile-banner .status-box.unknown {
    background-color: #e9ecef; /* Soft grey */
    color: #495057; /* Dark grey text */
}

.status-badge {
    display: inline-flex; /* Use flexbox for perfect alignment */
    align-items: center;
    gap: 0.4rem; /* Space between dot, icon, and text */
    padding: 0.3rem 0.85rem;
    border-radius: 9999px; /* This creates the "pill" shape */
    font-weight: 600;
    font-size: 0.9rem;
    text-transform: capitalize;
}


/* --- Style for the ACTIVE status --- */
.status-badge.status-active {
    background-color: #e7f5ec; /* A soft, professional green */
    color: #1d6c41; /* A dark, readable green */
}

/* --- Style for the INACTIVE status --- */
.status-badge.status-inactive {
    background-color: #e9ecef; /* A soft, neutral grey */
    color: #495057; /* A dark, readable grey */
}
    .profile-body {
        padding: 2.5rem;
    }
    .info-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        background-color: #f8f9fa;
        padding: 1rem;
        border-radius: 0.75rem;
        border: 1px solid var(--border-color);
    }
    .info-icon {
        font-size: 1.25rem;
        color: var(--primary-color);
        background-color: var(--primary-light);
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .info-label {
        font-size: 0.9rem;
        color: var(--text-secondary);
        margin-bottom: 0.1rem;
    }
    .info-value {
        font-size: 1.1rem;
        font-weight: 500;
        color: var(--text-primary);
        word-break: break-all;
    }

    
/* --- Patient Header Section --- */
.patient-header {
    margin-bottom: 2rem;
}

.patient-header .patient-name {
    font-size: 2.5rem;
    font-weight: 700;
    color: #212529;
}

.patient-header .patient-meta {
    font-size: 1rem;
    color: #6c757d;
}

/* --- Patient Info Card --- */
.patient-info-card {
    background-color: #ffffff;
    border: none;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    padding: 2rem;
    display: flex;
    align-items: center;
    gap: 2rem;
}

.patient-info-card .patient-avatar {
    width: 120px;
    height: 120px;
    object-fit: cover;
    border-radius: 50%;
    border: 4px solid #e9ecef;
}

.info-item {
    margin-bottom: 0.75rem;
}

.info-label {
    font-weight: 600;
    color: #6c757d;
    margin-right: 0.5rem;
}

.info-value {
    font-weight: 500;
    color: #343a40;
}

/* --- Content Cards for Tables and Other Sections --- */
.content-card {
    background-color: #ffffff;
    border: none;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    padding: 1.5rem 2rem;
    margin-top: 2rem;
}

/* --- Modern Health Data Table Styling --- */
.health-data-table {
    border-collapse: separate;
    border-spacing: 0;
    width: 100%;
}

.health-data-table thead th {
    background-color: #f8f9fa;
    border-bottom: 2px solid #e9ecef;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 0.75rem;
    color: #6c757d;
    padding: 1rem;
}

.health-data-table tbody td {
    padding: 1rem;
    vertical-align: middle;
    border-bottom: 1px solid #f1f3f5;
    color: #495057;
}

/* The subtle row hover animation you wanted */
.health-data-table tbody tr {
    transition: all 0.2s ease-in-out;
}

.health-data-table tbody tr:hover {
    background-color: #f1f3f5;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.04);
}

.health-data-table tbody tr:last-child td {
    border-bottom: none;
}

/* File: assets/css/style.css (or your main stylesheet) */

/* File: assets/css/style.css (or your primary stylesheet) */

/* --- Page Layout & Background --- */
.main-content {
    background-color: #f8f9fa; /* A very light, professional grey background */
    padding: 2rem 3rem; /* More generous padding */
    min-height: 100vh;
}

/* --- Page Header (Back Link & Patient Name) --- */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2.5rem; /* Increased space below the header */
}

.page-header .back-link {
    display: inline-flex;
    align-items: center;
    text-decoration: none;
    color: #6c757d;
    font-weight: 500;
    transition: color 0.2s ease;
}

.page-header .back-link:hover {
    color: #0d6efd; /* Bootstrap primary blue */
}

.page-header .patient-name {
    font-size: 2.5rem;
    font-weight: 700;
    color: #212529;
}

/* --- Patient Profile Card --- */
.patient-profile-card {
    background-color: #ffffff;
    border-radius: 20px; /* More rounded corners */
    padding: 2.5rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.07); /* Softer, more pronounced shadow */
    display: flex;
    align-items: center;
    gap: 3rem; /* More space between avatar and details */
}

.patient-avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #e9ecef;
    color: #adb5bd;
    font-weight: 500;
    font-size: 0.9rem;
    flex-shrink: 0; /* Prevents the avatar from shrinking */
    border: 6px solid #f8f9fa; /* Border matches the page background */
}

.patient-details {
    display: grid;
    grid-template-columns: repeat(3, 1fr); /* Creates a 3-column layout */
    gap: 1.5rem;
    width: 100%;
}

.info-block {
    background-color: #f8f9fa;
    border-radius: 12px;
    padding: 1rem 1.5rem;
}

.info-block .label {
    display: block;
    color: #6c757d;
    font-weight: 500;
    font-size: 0.85rem;
    margin-bottom: 0.25rem;
}

.info-block .value {
    color: #212529;
    font-weight: 600;
    font-size: 1.1rem;
}

/* --- Generic Content Card for other sections --- */
.content-card {
    background-color: #ffffff;
    border-radius: 20px;
    padding: 2.5rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.07);
    margin-top: 2.5rem;
}
/* --- Refined Content Card Header --- */
.content-card h3 {
    font-size: 1.75rem;
    font-weight: 700; /* Bolder for more emphasis */
    color: #2c3e50; /* A softer, more professional dark blue/grey */
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e9ecef; /* Subtle separator line */
}

/* Smooth deletion animations */
/* --- Recommendation Item Layout --- */
.recommendation-item {
    display: flex;
    gap: 1rem;
    background: #ffffff;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.recommendation-item:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.rec-icon {
    flex-shrink: 0;
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
}

.rec-content {
    flex: 1;
    min-width: 0; /* Prevents content from overflowing */
}

.rec-content p {
    margin: 0 0 0.75rem 0;
    color: #333;
    line-height: 1.5;
    font-size: 0.95rem;
}

.rec-content small.text-muted {
    font-size: 0.85rem;
}

/* --- Meta and Actions Container --- */
.rec-meta-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

/* Algorithm Badge Styling */
.algorithm-badge {
    font-size: 0.8rem;
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-weight: 600;
}

/* Delete Button Styling */
.rec-actions .btn-outline-danger {
    border-radius: 20px;
    padding: 0.3rem 0.7rem;
    border-width: 1.5px;
    font-size: 0.8rem;
    transition: all 0.2s ease;
}

.rec-actions .btn-outline-danger:hover {
    transform: scale(1.05);
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
}

/* Smooth deletion animation */
.recommendation-item.fade-out {
    opacity: 0;
    max-height: 0;
    margin: 0;
    padding: 0;
    border: none;
    transform: scale(0.8);
}

/* Responsive Design */
@media (max-width: 768px) {
    .rec-meta-actions {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .rec-actions {
        align-self: flex-end;
    }
}

/* Toast styling */
.toast {
    z-index: 9999;
}

/* Smooth deletion animation */
.recommendation-item.fade-out {
    opacity: 0;
    max-height: 0;
    margin: 0;
    padding: 0;
    border: none;
}
.graph-display-wrapper {
    display: flex;
    gap: 1.5rem;
    margin-top: 1.5rem;
}

/* --- Graph Container Styling (Unchanged) --- */
.graph-container {
    flex: 1;
    border: 1px solid #dee2e6;
    border-radius: 12px;
    background-color: #f8f9fa;
    min-height: 800px;
    position: relative;
}

.zoom-controls rect:hover {
    fill: #f8f9fa;
    stroke: #007bff;
}

.zoom-controls text:hover {
    fill: #007bff;
}

/* ======================================================= */
/* START: CORRECTED NODE & PANEL STYLES                      */
/* ======================================================= */

/* --- FIX #1: Prevent Node Shrinking on Click --- */
/* This rule targets the circle elements being manipulated by D3 */
.graph-container svg circle {
    /* Add a transition for smooth scaling */
    transition: transform 0.2s ease-in-out, stroke 0.2s ease-in-out;
}

.graph-container svg circle.selected-node {
    /* 1. Scale the node up to make it bigger */
    transform: scale(1.2);

    /* 2. Add a prominent colored border to highlight it */
    stroke: #0d6efd; /* Bootstrap primary blue */
    stroke-width: 3px;
}
/* By setting the 'active' state, we prevent D3's default drag behavior from shrinking the node */
.graph-container svg circle:active {
    r: 25; /* Force the radius to stay large on click */
}
.graph-container svg circle[r="20"]:active {
    r: 20; /* Specifically for non-patient nodes */
}


/* --- FIX #2: Wider, More Professional Details Panel --- */
.node-detail-panel {
    width: 400px; /* INCREASED: Give the panel more width */
    flex-shrink: 0;
    background-color: #ffffff;
    border-radius: 12px;
    border: 1px solid #dee2e6;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    display: flex;
    flex-direction: column;
    max-height: 800px; /* Match the graph height */
}

/* Panel Header (Unchanged) */
.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e9ecef;
    background-color: #f8f9fa;
    flex-shrink: 0;
}
.panel-header h6 {
    margin: 0;
    font-weight: 600;
    font-size: 1.1rem;
    color: #343a40;
    word-break: break-word; /* Allow long titles to wrap */
}
.close-btn {
    border: none; background: none; font-size: 1.75rem;
    cursor: pointer; color: #6c757d; line-height: 1; padding: 0;
}

/* --- IMPROVED Panel Content & Item Layout --- */
.panel-content {
    padding: 0; /* Remove padding to let items control it */
    font-size: 0.9rem;
    overflow-y: auto;
    flex-grow: 1;
}
.detail-item {
    display: grid;
    /* NEW: Auto-fit the key, let the value take the rest of the space */
    grid-template-columns: max-content 1fr; 
    gap: 1rem; /* More space between key and value */
    padding: 0.75rem 1.25rem; /* Padding on each item */
    border-bottom: 1px solid #f1f3f5;
    word-break: break-word;
    align-items: start; /* Align items to the top */
}
.detail-item:last-child {
    border-bottom: none;
}
.detail-key {
    font-weight: 600;
    color: #495057;
    white-space: nowrap; /* Prevent keys from wrapping */
}
    

/* --- Professional Table Styling (Replaces .modern-table) --- */
.modern-table {
    width: 100%;
    border-collapse: collapse; /* Ensures borders behave predictably */
}

/* Table Header Styling */
.modern-table thead th {
    background-color: #f8f9fa; /* Very light grey header background */
    text-transform: uppercase;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.75px;
    color: #86909c; /* Lighter, less prominent text color */
    padding: 1rem 1.5rem;
    text-align: center;
    border-bottom: 2px solid #e9ecef; /* A thicker but light-colored bottom border */
    border-top: 1px solid #e9ecef;
}

/* Table Body Styling */
.modern-table tbody td {
    padding: 1.25rem 1.5rem; /* Increased vertical padding for more breathing room */
    border-bottom: 1px solid #f1f3f5; /* Very subtle line between rows */
    color: #495057;
    font-weight: 500;
    font-size: 0.9rem;
    transition: background-color 0.2s ease-in-out; /* Smooth transition for hover */
}

/* Remove border from the very last row */
.modern-table tbody tr:last-child td {
    border-bottom: none;
}

/* Subtle Hover Effect for Rows */
.modern-table tbody tr:hover {
    background-color: #f8f9fa; /* Highlights the row on mouse-over */
}

.patient-avatar {
        /* 1. Set a fixed size for the container */
        width: 100px;
        height: 100px;

        /* 2. Make the container a perfect circle */
        border-radius: 50%;

        /* 3. Add a subtle border and shadow to lift it off the card */
        border: 4px solid #fff;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);

        /* 4. This is crucial: it hides the parts of the square image
              that go outside our new circular container */
        overflow: hidden;
    }

    .patient-avatar img {
        /* 5. Make the image fill its new circular container */
        width: 100%;
        height: 100%;

        /* 6. The most important part: This prevents the image from
              stretching or squishing. It will zoom and crop to fit. */
        object-fit: cover;
    }
    /* === Modern Recommendation Page Styles === */
    /* --- Main Card --- */
    .recommendation-card {
        background-color: #ffffff;
        border: none;
        border-radius: 20px; /* Softer, larger radius */
        padding: 2.5rem; /* More internal spacing */
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08); /* A more pronounced, modern shadow */
    }

    /* --- Card Title --- */
    .recommendation-card .card-title {
        font-size: 1.75rem;
        font-weight: 700;
        color: #2c3e50; /* A professional dark blue/grey */
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #e9ecef; /* Subtle separator line */
    }

    /* --- Step Badge & Label --- */
    .step-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: linear-gradient(45deg, #1e3a8a, #3b82f6); /* Gradient background */
        color: white;
        font-weight: 700;
        font-size: 1rem;
        margin-right: 0.75rem;
        box-shadow: 0 4px 10px rgba(59, 130, 246, 0.3); /* Add a glow */
    }

    .form-label {
        font-weight: 600;
        font-size: 1rem;
        color: #495057;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
    }

    /* === PROFESSIONAL TABLE STYLES - UNIQUE CLASSES === */
.pro-table-container {
    margin-left: 270px;
    padding: 25px;
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    margin-top: 20px;
    border: 1px solid #e2e8f0;
    animation: proSlideInUp 0.6s ease-out;
}

.pro-table-responsive {
    overflow-x: auto;
    border-radius: 12px;
}

.pro-data-table tbody tr.row-exit {
    opacity: 0;
    transform: scale(0.95);
    transition: opacity 0.3s ease-out, transform 0.3s ease-out;
}

.pro-data-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: #ffffff;
    border-radius: 12px;
    overflow: hidden;
    font-family: 'Poppins', sans-serif;
    animation: proFadeIn 0.8s ease-out;
}

.pro-data-table thead {
    background: linear-gradient(135deg, #14244fff 0%, #325894ff 100%);
}

/* New class for the row exit animation */
.pro-data-table tbody tr.row-exit {
    opacity: 0;
    transform: scale(0.95);
    transition: opacity 0.3s ease-out, transform 0.3s ease-out;
}

/* New class for Manage button click: Dim the row, don't hide it */
tr.row-loading {
    opacity: 0.4;             /* Make it 40% visible (ghost effect) */
    filter: grayscale(100%);  /* Turn it grey to show it's inactive */
    transform: scale(0.98);   /* Shrink slightly */
    transition: all 0.3s ease; /* Smooth transition */
    pointer-events: none;     /* Prevent double-clicking */
    background-color: #e9ecef; /* Light grey background */
}

.pro-data-table thead th {
    color: #ffffff;
    font-weight: 600;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 1.25rem 1.5rem;
    border: none;
    text-align: center;
    position: relative;
    transition: all 0.3s ease;
}

.pro-data-table thead th:hover {
    background: linear-gradient(135deg, #14244fff 0%, #325894ff 100%);
    transform: translateY(-1px);
}

.pro-data-table thead th:not(:last-child)::after {
    content: '';
    position: absolute;
    right: 0;
    top: 25%;
    height: 50%;
    width: 1px;
    background: rgba(255, 255, 255, 0.3);
}

.pro-data-table tbody tr {
    transition: all 0.3s ease;
    border-bottom: 1px solid #f1f5f9;
    animation: proRowSlideIn 0.5s ease-out forwards;
    opacity: 0;
    transform: translateY(20px);
}

.pro-data-table tbody tr:nth-child(1) { animation-delay: 0.1s; }
.pro-data-table tbody tr:nth-child(2) { animation-delay: 0.15s; }
.pro-data-table tbody tr:nth-child(3) { animation-delay: 0.2s; }
.pro-data-table tbody tr:nth-child(4) { animation-delay: 0.25s; }
.pro-data-table tbody tr:nth-child(5) { animation-delay: 0.3s; }
.pro-data-table tbody tr:nth-child(6) { animation-delay: 0.35s; }
.pro-data-table tbody tr:nth-child(7) { animation-delay: 0.4s; }
.pro-data-table tbody tr:nth-child(8) { animation-delay: 0.45s; }

.pro-data-table tbody tr:last-child {
    border-bottom: none;
}

.pro-data-table tbody tr:hover {
    background-color: #f8fafc;
    transform: translateY(-2px) scale(1.01);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    z-index: 1;
    position: relative;
}

.pro-data-table tbody td {
    padding: 1.25rem 1.5rem;
    vertical-align: middle;
    border: none;
    font-size: 0.95rem;
    color: #374151;
    text-align: center;
    font-weight: 500;
    transition: all 0.3s ease;
}

.pro-data-table tbody td:first-child {
    font-weight: 600;
    color: #1e293b;
}

/* === TABLE BUTTON STYLES - Ensure narrow pill shape === */
.pro-data-table .btn-sm {
    padding: 0.3rem 0.8rem !important;
    font-size: 0.85rem !important;
    font-weight: 500 !important;
    border-radius: 50px !important; /* Creates the modern "pill" shape */
    transition: all 0.2s ease-in-out !important;
    border: 2px solid transparent !important; /* Reserve space for the outline border */
    display: inline-flex !important; /* Crucial for aligning icon and text */
    align-items: center !important;
    gap: 0.3rem !important; /* Space between icon and text */
}

.pro-data-table .btn-primary.btn-sm {
    background: linear-gradient(to right, #202c58ff, #38255eff) !important;
    border: none !important;
    font-size: 0.85rem !important;
    padding: 0.3rem 0.8rem !important;
    border-radius: 50px !important;
    transition: background 0.3s ease, transform 0.3s ease !important;
}

.pro-data-table .btn-primary.btn-sm:hover {
    background: linear-gradient(to right, #4a00e0, #8e2de2) !important;
    transform: scale(1.03) !important;
}

.pro-data-table .btn-outline-primary.btn-sm {
    background-color: transparent !important;
    border-color: #3b82f6 !important; /* Border uses the primary blue color */
    color: #3b82f6 !important; /* Text also uses the primary blue */
}

/* The creative "fill-in" hover effect for the secondary button */
.pro-data-table .btn-outline-primary.btn-sm:hover {
    background-color: #3b82f6 !important; /* On hover, it fills with the primary color */
    color: white !important; /* The text becomes white to be readable */
    transform: translateY(-2px) !important;
    box-shadow: 0 4px 10px rgba(59, 130, 246, 0.3) !important;
}

/* Ensure button container maintains proper spacing */
.pro-data-table td:last-child {
    padding: 1rem 1.5rem !important;
}

.pro-data-table .d-flex.gap-2 {
    gap: 0.5rem !important;
}

/* === PROFESSIONAL FORM STYLES === */
.pro-form-container {
    margin-left: 270px;
    padding: 30px;
    margin-top: 60px;
    animation: proSlideInUp 0.6s ease-out;
}

.pro-form-header {
    text-align: center;
    margin-bottom: 2.5rem;
    animation: proSlideInDown 0.5s ease-out;
}

.pro-form-header h1 {
    font-size: 2.5rem;
    font-weight: 700;
    background: linear-gradient(90deg, #1e3a8a, #3b82f6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 0.5rem;
    animation: proTextGlow 2s ease-in-out infinite alternate;
}

.pro-form-card {
    background: #ffffff;
    border-radius: 20px;
    padding: 3rem;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
    max-width: 600px;
    margin: 0 auto;
    border: 1px solid #e2e8f0;
    animation: proFadeIn 0.8s ease-out;
    transition: all 0.3s ease;
}

.pro-form-card:hover {
    box-shadow: 0 15px 50px rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
}

.pro-form-group {
    margin-bottom: 1.75rem;
    animation: proSlideInUp 0.6s ease-out;
}

.pro-form-group:nth-child(1) { animation-delay: 0.1s; }
.pro-form-group:nth-child(2) { animation-delay: 0.15s; }
.pro-form-group:nth-child(3) { animation-delay: 0.2s; }
.pro-form-group:nth-child(4) { animation-delay: 0.25s; }

.pro-form-label {
    font-weight: 600;
    margin-bottom: 0.75rem;
    color: #1e293b;
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.pro-form-label::before {
    font-family: "Font Awesome 6 Free";
    font-weight: 900;
    font-size: 0.9rem;
    color: #3b82f6;
}

.pro-form-label[data-icon="user"]::before { content: "\f007"; }
.pro-form-label[data-icon="gender"]::before { content: "\f224"; }
.pro-form-label[data-icon="status"]::before { content: "\f21e"; }
.pro-form-label[data-icon="calendar"]::before { content: "\f073"; }
.pro-form-control, .pro-form-select {
    padding: 12px 16px;
    border-radius: 12px;
    font-size: 1rem;
    width: 100%;
    transition: all 0.3s ease;
    border: 2px solid #e2e8f0;
    background: #ffffff;
    font-family: 'Poppins', sans-serif;
}

.pro-form-control:focus, .pro-form-select:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1), 0 4px 15px rgba(59, 130, 246, 0.2);
    transform: translateY(-1px);
    outline: none;
}

.pro-form-control:hover, .pro-form-select:hover {
    border-color: #cbd5e1;
}

.pro-form-select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 12px center;
    background-repeat: no-repeat;
    background-size: 16px;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
}

.pro-form-text {
    font-size: 0.85rem;
    color: #64748b;
    margin-top: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.pro-form-text::before {
    content: "💡";
    font-size: 0.8rem;
}

.pro-invalid-feedback {
    font-size: 0.85rem;
    color: #dc2626;
    margin-top: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    animation: proShake 0.5s ease-in-out;
}

.pro-invalid-feedback::before {
    content: "⚠";
    font-size: 0.8rem;
}

.pro-form-button {
    background: linear-gradient(135deg, #141f3dff 0%, #607393ff 100%);
    border: none;
    font-size: 1.1rem;
    padding: 14px 30px;
    border-radius: 50px;
    transition: all 0.3s ease;
    color: white;
    font-weight: 600;
    width: 100%;
    margin-top: 1rem;
    position: relative;
    overflow: hidden;
}

.pro-form-button::before {
    content: "";
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.pro-form-button:hover {
    background: linear-gradient(135deg, #1e3a8a 0%, #102043ff 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(23, 71, 148, 0.4);
}

.pro-form-button:hover::before {
    left: 100%;
}

.pro-form-button:active {
    transform: translateY(0);
}

/* === SPIKE EXPLANATION BUTTON STYLES === */
.btn-outline-danger {
    border-color: #dc2626;
    color: #dc2626;
    background-color: transparent;
    transition: all 0.3s ease;
}

.btn-outline-danger:hover {
    background-color: #dc2626;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
}

.btn-outline-warning {
    border-color: #d97706;
    color: #d97706;
    background-color: transparent;
    transition: all 0.3s ease;
}

.btn-outline-warning:hover {
    background-color: #d97706;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(217, 119, 6, 0.3);
}

.btn-outline-info {
    border-color: #0369a1;
    color: #0369a1;
    background-color: transparent;
    transition: all 0.3s ease;
}

.btn-outline-info:hover {
    background-color: #0369a1;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(3, 105, 161, 0.3);
}

/* Ensure proper spacing for the button groups */
.d-flex.gap-2 {
    gap: 0.5rem !important;
}

/* Highlight critical rows */
.modern-table tbody tr:has(.badge.bg-danger) {
    background-color: rgba(220, 38, 38, 0.05) !important;
    border-left: 4px solid #dc2626;
}

.modern-table tbody tr:has(.badge.bg-warning) {
    background-color: rgba(217, 119, 6, 0.05) !important;
    border-left: 4px solid #d97706;
}

.modern-table tbody tr:has(.badge.bg-info) {
    background-color: rgba(3, 105, 161, 0.05) !important;
    border-left: 4px solid #0369a1;
}

/* Animation for critical alerts */
@keyframes criticalPulse {
    0%, 100% { 
        box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.7);
    }
    70% { 
        box-shadow: 0 0 0 10px rgba(220, 38, 38, 0);
    }
}

.badge.bg-danger {
    animation: criticalPulse 2s infinite;
}

/* Form validation states */
.pro-form-control:valid {
    border-color: #10b981;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%2310b981' d='M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e");
    background-position: right calc(0.375em + 0.1875rem) center;
    background-repeat: no-repeat;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

.pro-form-control:invalid:not(:focus):not(:placeholder-shown) {
    border-color: #dc2626;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc2626'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5 5 2 2m0-2-2 2'/%3e%3c/svg%3e");
    background-position: right calc(0.375em + 0.1875rem) center;
    background-repeat: no-repeat;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

/* Additional animations */
@keyframes proShake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

/* Responsive design */
@media (max-width: 768px) {
    .pro-form-container {
        margin-left: 0;
        padding: 20px;
    }
    
    .pro-form-card {
        padding: 2rem;
    }
    
    .pro-form-header h1 {
        font-size: 2rem;
    }
}

/* Success state animation */
.pro-form-success {
    animation: proSuccessPulse 2s ease-in-out;
}

@keyframes proSuccessPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.02); }
}

/* === PREVENT INITIAL INVALID STATES === */
.pro-form-control:invalid,
.pro-form-select:invalid {
    border-color: #e2e8f0 !important;
    background-image: none !important;
}

.pro-form-control:focus:invalid,
.pro-form-select:focus:invalid {
    border-color: #3b82f6 !important;
    background-image: none !important;
}

/* Hide invalid feedback by default */
.pro-invalid-feedback {
    display: none;
    opacity: 0;
    transform: translateY(-10px);
    transition: all 0.3s ease;
}

/* Show validation only after form submission attempt */
.was-validated .pro-form-control:invalid,
.was-validated .pro-form-select:invalid {
    border-color: #dc2626 !important;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc2626'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5 5 2 2m0-2-2 2'/%3e%3c/svg%3e") !important;
    background-position: right calc(0.375em + 0.1875rem) center !important;
    background-repeat: no-repeat !important;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem) !important;
    padding-right: calc(1.5em + 0.75rem) !important;
}

.was-validated .pro-form-control:valid,
.was-validated .pro-form-select:valid {
    border-color: #10b981 !important;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%2310b981' d='M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e") !important;
    background-position: right calc(0.375em + 0.1875rem) center !important;
    background-repeat: no-repeat !important;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem) !important;
    padding-right: calc(1.5em + 0.75rem) !important;
}

/* Show invalid feedback only after form validation */
.was-validated .pro-form-control:invalid ~ .pro-invalid-feedback,
.was-validated .pro-form-select:invalid ~ .pro-invalid-feedback {
    display: flex;
    opacity: 1;
    transform: translateY(0);
}

/* Remove default validation icons until form is validated */
.pro-form-control,
.pro-form-select {
    background-image: none !important;
    padding-right: 16px !important;
}

/* Keep the normal focus states */
.pro-form-control:focus,
.pro-form-select:focus {
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1), 0 4px 15px rgba(59, 130, 246, 0.2) !important;
    transform: translateY(-1px) !important;
    background-image: none !important;
}

/* === KEEP ORIGINAL STATUS BOX STYLES  === */
.status-box {
    display: inline-block; 
    padding: 8px 18px; 
    font-weight: bold; 
    color: white;
    border-radius: 10px; 
    text-align: center;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); 
    min-width: 100px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    animation: proPulse 2s infinite;
}
.status-box.critical { background-color: #FF4C4C; animation: proPulseCritical 1.5s infinite; }
.status-box.warning { background-color: #FFEB3B; color: #333; }
.status-box.stable { background-color: #03ff4f; color: #333; }
.status-box.recovered { background-color: #29B6F6; }
.status-box.unknown { background-color: #6c757d; }
.status-box:hover { 
    transform: scale(1.05); 
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3); 
    animation: none;
}

/* === PROFESSIONAL SEARCH AND SORT STYLES === */
.pro-search-sort-container {
    margin-left: 270px;
    padding: 30px 20px 0;
    animation: proSlideInDown 0.5s ease-out;
}

.pro-search-sort-container h1 {
    font-size: 2rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
    animation: proTextGlow 2s ease-in-out infinite alternate;
}

.pro-search-input {
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 15px;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    width: 280px;
    font-family: 'Poppins', sans-serif;
    animation: proInputSlideIn 0.6s ease-out;
}

.pro-search-input:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1), 0 4px 15px rgba(59, 130, 246, 0.2);
    transform: translateY(-2px);
    outline: none;
}

.pro-sorting-btn {
    background-color: #ffffff;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 20px;
    font-size: 0.95rem;
    color: #374151;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-family: 'Poppins', sans-serif;
    animation: proButtonSlideIn 0.7s ease-out;
    
    /* Hide default Bootstrap dropdown arrow */
    background-image: none !important;
}

/* Remove Bootstrap's default dropdown arrow */
.pro-sorting-btn.dropdown-toggle::after {
    display: none !important;
}

.pro-sorting-btn:hover {
    background-color: #f8fafc;
    border-color: #3b82f6;
    color: #1e3a8a;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.15);
}

/* Custom sort indicator */
.pro-sort-indicator {
    font-family: "Font Awesome 6 Free";
    font-weight: 900;
    font-size: 0.8rem;
    margin-left: 0.5rem;
    transition: all 0.3s ease;
    opacity: 0.7;
}

.pro-sort-indicator.sort-asc::before {
    content: "\f0de"; /* Font Awesome sort-up */
    color: #3b82f6;
}

.pro-sort-indicator.sort-desc::before {
    content: "\f0dd"; /* Font Awesome sort-down */
    color: #3b82f6;
}

/* === ANIMATION KEYFRAMES === */
@keyframes proSlideInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes proSlideInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes proFadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes proRowSlideIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes proPulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.02);
    }
}

@keyframes proPulseCritical {
    0%, 100% {
        transform: scale(1);
        box-shadow: 0 4px 8px rgba(255, 76, 76, 0.4);
    }
    50% {
        transform: scale(1.03);
        box-shadow: 0 6px 15px rgba(255, 76, 76, 0.6);
    }
}

@keyframes proTextGlow {
    from {
        text-shadow: 0 0 10px rgba(59, 130, 246, 0.3);
    }
    to {
        text-shadow: 0 0 20px rgba(59, 130, 246, 0.6);
    }
}

@keyframes proInputSlideIn {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes proButtonSlideIn {
    from {
        opacity: 0;
        transform: translateX(20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes proHoverFloat {
    0% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-3px);
    }
    100% {
        transform: translateY(0);
    }
}

/* === ENHANCED TABLE FEATURES === */
.pro-table-empty-state {
    padding: 3rem 1rem;
    text-align: center;
    color: #64748b;
    animation: proPulse 3s ease-in-out infinite;
}

.pro-table-empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
    animation: proPulse 2s ease-in-out infinite;
}

.pro-table-empty-state p {
    font-size: 1.1rem;
    margin-bottom: 0.5rem;
}

.pro-table-empty-state small {
    font-size: 0.9rem;
}

/* === RESPONSIVE DESIGN === */
@media (max-width: 768px) {
    .pro-table-container,
    .pro-search-sort-container {
        margin-left: 0;
        padding: 15px;
    }
    
    .pro-search-input {
        width: 100%;
        margin-bottom: 1rem;
    }
    
    .pro-data-table {
        font-size: 0.875rem;
    }
    
    .pro-data-table thead th,
    .pro-data-table tbody td {
        padding: 1rem 0.75rem;
    }
}

/* === ACCESSIBILITY IMPROVEMENTS === */
.pro-data-table tbody tr:focus-within {
    background-color: #e0f2fe;
    outline: 2px solid #0369a1;
    animation: proHoverFloat 2s ease-in-out infinite;
}

.pro-search-input:focus,
.pro-sorting-btn:focus {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}

/* === PRINT STYLES === */
@media print {
    .pro-table-container {
        box-shadow: none;
        border: 1px solid #d1d5db;
    }
    
    .pro-data-table thead {
        background: #6b7280 !important;
        -webkit-print-color-adjust: exact;
        color-adjust: exact;
    }
    
    /* Disable animations for print */
    .pro-table-container,
    .pro-data-table,
    .pro-data-table tbody tr,
    .status-box {
        animation: none !important;
    }
}

    /* --- Section for each step in the workflow --- */
.analysis-step {
    margin-top: 3.5rem; /* INCREASED: This adds more vertical space */
    padding-top: 2rem;
    border-top: 1px solid #e9ecef;
}
    .analysis-step:first-child {
        margin-top: 0;
        padding-top: 0;
        border-top: none;
    }

    /* --- The title for each step (replaces the badge) --- */
    .step-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: #34495e; /* A professional dark blue/grey */
        margin-bottom: 0.5rem;
    }

    /* --- The descriptive text below each title --- */
    .step-description {
        color: #6c757d; /* A secondary, muted text color */
        margin-bottom: 1.5rem;
        max-width: 800px; /* Keeps the line length readable */
    }

    /* --- Specific adjustment for the algorithm dropdown --- */
    #algorithmSelectionWrapper .form-select-lg {
        max-width: 500px; /* Prevents the dropdown from being excessively wide */
    }

    /* --- Dropdown Select Styling --- */
    .form-select-lg {
        font-size: 1.1rem;
        border-radius: 12px; /* Softer radius */
        border: 1px solid #ced4da;
        background-color: #f8f9fa; /* Light background for contrast */
        transition: all 0.2s ease-in-out;
    }
    .form-select-lg:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2);
        background-color: #ffffff;
    }
    .form-select-lg:disabled {
        background-color: #e9ecef;
        opacity: 0.7;
    }


    /* --- Patient Profile Display --- */
    .patient-profile-layout {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        background-color: #f8f9fa; /* Light background */
        padding: 1.5rem;
        border-radius: 16px; /* Match other elements */
        border: 1px solid #e9ecef;
    }

    .profile-image-container {
        width: 90px;
        height: 90px;
        border-radius: 50%;
        overflow: hidden;
        flex-shrink: 0;
        border: 4px solid #ffffff;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    .profile-image-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .profile-info h6 {
        font-size: 1.25rem;
        font-weight: 700;
        color: #212529;
        margin-bottom: 0.25rem;
    }
    .profile-info span {
        display: block;
        color: #6c757d;
        font-size: 0.95rem;
    }

    /* --- Recommendation Text --- */
    #recommendationText.alert-success {
        background-color: #e7f5ec; /* Softer green */
        color: #1d6c41;
        border: 1px solid #b7e4c7;
        font-size: 1.1rem;
        line-height: 1.7;
        padding: 1.5rem;
        border-radius: 16px;
    }
    #recommendationText.alert-danger {
        background-color: #fde2e4; /* Softer red */
        color: #8c1c13;
        border: 1px solid #f9c6c9;
    }

    /* --- Summary Hint --- */
    #summaryHint {
        border-radius: 16px;
        border-left: 5px solid #0dcaf0; /* Info color accent */
    }

    /* --- Section Title (for Graph and Recommendation) --- */
    #outputSection h5.card-title {
        font-size: 1.5rem; /* Slightly smaller than main title */
        color: #34495e; /* Another professional color */
    }

        .profile-image-container {
            /* 1. Set a fixed size */
            width: 80px;  /* Slightly smaller to fit the layout */
            height: 80px;

            /* 2. Make it a circle */
            border-radius: 50%;

            /* 3. Add a subtle shadow */
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);

            /* 4. Crucial for clipping the image to the circle */
            overflow: hidden;
        }

        /* Target the image inside the container */
        .profile-image-container img {
            /* 5. Make the image fill the container */
            width: 100%;
            height: 100%;

            /* 6. Prevent the image from stretching */
            object-fit: cover;
        }
        
    .recommendation-card {
        padding: 2rem;
    }

    .patient-profile-banner {
        display: flex;
        align-items: center;
        gap: 1.5rem; /* Space between image and info */
        background-color: #f8f9fa; /* A very light, clean grey */
        padding: 1.5rem;
        border-radius: 16px; /* Softer, more modern corners */
        border: 1px solid #e9ecef;
        transition: all 0.3s ease;
    }

    /* --- The Avatar/Image --- */
    .profile-banner-avatar {
        width: 90px;
        height: 90px;
        border-radius: 50%;
        overflow: hidden; /* This is crucial for making the image a circle */
        flex-shrink: 0; /* Prevents the avatar from squishing */
        border: 4px solid #ffffff; /* A clean white border */
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1); /* Lifts the image off the card */
    }

    .profile-banner-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover; /* Prevents the image from stretching or squishing */
    }

    /* --- The Text Information --- */
    .profile-banner-info {
        flex-grow: 1; /* Allows this section to take up the remaining space */
    }

    .profile-banner-info h4#profileFullName {
        font-size: 1.75rem; /* Larger, more prominent name */
        font-weight: 700;
        color: #2c3e50; /* A professional dark blue/grey */
        margin: 0;
    }

    /* --- Meta Details (Gender, Age) --- */
    .profile-meta-details {
        display: flex;
        align-items: center;
        gap: 1.5rem; /* Space between each meta item */
        margin-top: 0.5rem;
        color: #6c757d; /* A secondary text color */
    }

    .meta-item {
        display: flex;
        align-items: center;
        gap: 0.5rem; /* Space between icon and text */
        font-size: 0.95rem;
    }

    .meta-item i {
        font-size: 1.1rem;
        color: #3b82f6; /* A touch of brand color */
    }

    /* Define colors for each status */
    .status-badge.critical { background-color: #fde2e4; color: #b71c1c; }
    .status-badge.warning  { background-color: #fff9c4; color: #827717; }
    .status-badge.stable   { background-color: #e7f5ec; color: #1d6c41; }
    .status-badge.recovered{ background-color: #e3f2fd; color: #0d47a1; }
    .status-badge.unknown  { background-color: #e9ecef; color: #495057; }


    .form-label {
        font-weight: 600;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
    }

    .patient-profile-layout {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 0.75rem;
        border: 1px solid var(--border-color);
    }

    .profile-image-container img {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid white;
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }

    .profile-info h6 {
        font-weight: 600;
        margin-bottom: 0.25rem;
    }

    .profile-info span {
        display: block;
        color: var(--text-secondary);
        font-size: 0.9rem;
    }

    #recommendationText {
        font-size: 1.1rem;
        line-height: 1.6;
        text-align: justify;
        padding: 1.5rem;
        transition: all 0.3s ease;
    }
    </style>
</head>
<body>