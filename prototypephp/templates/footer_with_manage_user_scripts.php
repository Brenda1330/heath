<!-- File: templates/footer_with_manage_user_scripts.php -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Live search functionality
  const searchInput = document.getElementById('searchInput');
  if (searchInput) {
      const tableRows = document.querySelectorAll('tbody tr');
      searchInput.addEventListener('input', function () {
          const value = this.value.toLowerCase().trim();
          tableRows.forEach(row => {
              const text = row.textContent.toLowerCase();
              row.style.display = text.includes(value) ? '' : 'none';
          });
      });
  }

  // Delete confirmation modal logic
  const deleteModalEl = document.getElementById('deleteConfirmModal');
  if (deleteModalEl) {
      const deleteModal = new bootstrap.Modal(deleteModalEl);
      const form = document.getElementById('deleteUserForm');
      const userIdInput = document.getElementById('userIdToDelete');
      
      window.confirmDelete = function(userId) {
          if (form && userIdInput) {
              userIdInput.value = userId;
              deleteModal.show();
          }
      }
  }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>