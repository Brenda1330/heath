<?php
// File: homepage.php

/**
 * CORE INCLUSION & AUTHORIZATION
 * This block initializes the application and handles redirects for logged-in users.
 */
require_once 'functions.php';
secure_session_start();
add_security_headers();

// --- Redirect Logic ---
// If a user is already logged in, send them directly to their dashboard.
if (!empty($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        redirect('admin_dashboard.php');
    } elseif ($_SESSION['role'] === 'doctor') {
        redirect('doc_dashboard.php');
    }
    // Add redirects for other roles if necessary
}

$pageTitle = "Add Doctor Account";
include 'templates/header.php';
?>

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="homepage.php">
                <i class="fas fa-heartbeat me-2 text-primary"></i>HealthTrack Pro
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="homepage.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about">About Us</a></li>
                    <li class="nav-item ms-lg-3"><a class="btn btn-primary btn-rounded" href="login.php">Login</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="hero-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <h1 class="display-3 fw-bold mb-4" data-aos="fade-up">Revolutionizing Patient Care with Advanced Health Monitoring</h1>
                    <p class="lead mb-5" data-aos="fade-up" data-aos-delay="200">Empowering doctors with real-time data, predictive analytics, and seamless patient management. Join the future of healthcare.</p>
                    <a href="login.php" class="btn btn-lg btn-light-alt btn-rounded px-5 py-3 me-3" data-aos="fade-up" data-aos-delay="400">
                        <i class="fas fa-sign-in-alt me-2"></i>Access Doctor Portal
                    </a>
                    <a href="#features" class="btn btn-lg btn-outline-light btn-rounded px-5 py-3" data-aos="fade-up" data-aos-delay="500">
                        Learn More <i class="fas fa-arrow-down ms-2"></i>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Features Section -->
    <section id="features" class="py-5">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <h2 class="fw-bold">Platform Key Features</h2>
                <p class="text-muted">Discover the tools designed to enhance your medical practice.</p>
            </div>
            <div class="row g-4">
                <!-- Feature Cards -->
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="100"><div class="card feature-card h-100 shadow-sm"><div class="card-body text-center"><div class="feature-icon bg-primary text-white mb-3"><i class="fas fa-chart-line"></i></div><h5 class="card-title">Real-time CGM Tracking</h5><p class="card-text">Continuous Glucose Monitoring with instant alerts and historical data analysis.</p></div></div></div>
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="200"><div class="card feature-card h-100 shadow-sm"><div class="card-body text-center"><div class="feature-icon bg-success text-white mb-3"><i class="fas fa-users"></i></div><h5 class="card-title">Comprehensive Patient Lists</h5><p class="card-text">Manage all your patients efficiently with detailed profiles and status updates.</p></div></div></div>
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="300"><div class="card feature-card h-100 shadow-sm"><div class="card-body text-center"><div class="feature-icon bg-info text-white mb-3"><i class="fas fa-brain"></i></div><h5 class="card-title">AI-Powered Recommendations</h5><p class="card-text">Leverage predictive analytics for personalized patient treatment suggestions.</p></div></div></div>
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="400"><div class="card feature-card h-100 shadow-sm"><div class="card-body text-center"><div class="feature-icon bg-warning text-white mb-3"><i class="fas fa-file-alt"></i></div><h5 class="card-title">Customizable Reports</h5><p class="card-text">Generate and export detailed patient health reports in various formats.</p></div></div></div>
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="500"><div class="card feature-card h-100 shadow-sm"><div class="card-body text-center"><div class="feature-icon bg-danger text-white mb-3"><i class="fas fa-shield-alt"></i></div><h5 class="card-title">Secure Data Management</h5><p class="card-text">Ensuring patient data privacy and security with robust measures.</p></div></div></div>
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="600"><div class="card feature-card h-100 shadow-sm"><div class="card-body text-center"><div class="feature-icon bg-secondary text-white mb-3"><i class="fas fa-tachometer-alt"></i></div><h5 class="card-title">Dashboard Insights</h5><p class="card-text">Powerful administrative tools for system overview and management.</p></div></div></div>
            </div>
        </div>
    </section>

    <!-- About Us Section -->
    <section id="about" class="py-5 bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6" data-aos="fade-right">
                    <h2 class="fw-bold mb-3">About HealthTrack Pro</h2>
                    <p class="text-muted lead">We are a dedicated team of healthcare professionals and technologists committed to advancing medical practice through innovative digital solutions. Our platform is designed to provide doctors with powerful tools to monitor patient health, predict potential issues, and deliver personalized care more effectively.</p>
                    <p class="text-muted">Our mission is to bridge the gap between technology and healthcare, creating a future where every patient receives the best possible outcome through data-driven insights and proactive management.</p>
                </div>
                <div class="col-lg-6 text-center" data-aos="fade-left">
                    <img src="static/uploads/aboutus.png" alt="About Us illustration" class="img-fluid">
                </div>
            </div>
        </div>
    </section>

   <!-- Footer -->
    <footer class="py-4 bg-dark text-white text-center">
        <div class="container">
            <p class="mb-0">
                &copy; <span id="currentYear"></span> HealthTrack Pro. All Rights Reserved. | 
                <a href="#" class="text-white-50" data-bs-toggle="modal" data-bs-target="#privacyPolicyModal">Privacy Policy</a>
            </p>
        </div>
    </footer>

    <!-- Privacy Policy Modal -->
    <div class="modal fade" id="privacyPolicyModal" tabindex="-1" aria-labelledby="privacyPolicyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title" id="privacyPolicyModalLabel">Privacy Policy</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body">
                    <p class="text-muted small">Last Updated: <?php echo date('d M Y'); ?></p>
                    <p><strong>This is a placeholder notice and not a comprehensive legal Privacy Policy.</strong></p>
                    <h5>Data Collection (Illustrative)</h5>
                    <ul>
                        <li>Account information for test users (e.g., doctor names, emails).</li>
                        <li>Sample patient data entered for demonstration purposes.</li>
                        <li>System usage logs for debugging and improvement.</li>
                    </ul>
                    <p>No real patient Protected Health Information (PHI) should be entered into this demonstration system.</p>
                    <h5>Data Usage, Security, and Your Understanding</h5>
                    <p>Any data handled is strictly for the purpose of providing and maintaining this demonstration Service. By using this service, you acknowledge that this is not a production system and the data handling practices are for development and testing purposes only.</p>
                    <h5>Contact (For Demo Inquiries)</h5>
                    <p>For questions regarding this demo, please contact: [demo-contact@healthtrackpro-dev.com]</p>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        AOS.init({
            duration: 1000,
            once: true, 
        });
        document.getElementById('currentYear').textContent = new Date().getFullYear();
    </script>
</body>
</html>