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
        .table tbody tr:hover { background: linear-gradient(90deg, #a988cc 0%, #a6c6fc 100%); transform: translateX(5px); }
        @keyframes slideIn { to { opacity: 1; transform: translateX(0); } }
        
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

        .table .btn-sm {
            padding: 0.3rem 0.8rem;
            font-size: 0.85rem;
            font-weight: 500;
            border-radius: 50px; /* Creates the modern "pill" shape */
            transition: all 0.2s ease-in-out;
            border: 2px solid transparent; /* Reserve space for the outline border */
            display: inline-flex; /* Crucial for aligning icon and text */
            align-items: center;
            gap: 0.3rem; /* Space between icon and text */
        }

        .table .btn-outline-primary.btn-sm {
            background-color: transparent;
            border-color: #3b82f6; /* Border uses the primary blue color */
            color: #3b82f6; /* Text also uses the primary blue */
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