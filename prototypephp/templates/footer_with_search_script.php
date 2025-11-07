<?php
// File: templates/footer_with_search_script.php
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script>
  AOS.init({ once: true, duration: 800 });

  // Live search functionality. This is secure because it only filters
  // text that has already been safely sanitized by PHP.
  const searchInput = document.getElementById('searchInput');
  if (searchInput) {
      const tableBody = document.getElementById('logTableBody');
      const tableRows = tableBody.querySelectorAll('tr');

      searchInput.addEventListener('input', function () {
          const query = this.value.toLowerCase().trim();
          tableRows.forEach(row => {
              // Hides the row if its text content doesn't include the query
              row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
          });
      });
  }
</script>
</body>
</html>