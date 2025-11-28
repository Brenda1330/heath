<?php
// File: templates/footer_doctor.php
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    AOS.init({
        duration: 800,
        once: true
    });

    // Simple script to keep the active link highlighted in the sidebar
    document.addEventListener('DOMContentLoaded', () => {
        const currentPage = window.location.pathname.split("/").pop();
        const sidebarLinks = document.querySelectorAll('.sidebar .nav-link');
        sidebarLinks.forEach(link => {
            if (link.getAttribute('href') === currentPage) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
    });
</script>
</body>
</html>