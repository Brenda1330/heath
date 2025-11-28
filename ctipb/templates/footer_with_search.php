<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    AOS.init({ once: true, duration: 600 });
});

// Client-side search function. This is secure as it operates on text
// that has already been sanitized by the server before being displayed.
function searchDoctor() {
  const query = document.getElementById('searchInput').value.toLowerCase();
  const doctorCards = document.querySelectorAll('.doctor-card');
  
  let found = false;
  doctorCards.forEach(card => {
    const nameElement = card.querySelector('.doctor-info a');
    if (nameElement) {
        const name = nameElement.innerText.toLowerCase();
        // If the doctor's name includes the query, show the card, otherwise hide it.
        if (name.includes(query)) {
            card.style.display = 'flex';
            found = true;
        } else {
            card.style.display = 'none';
        }
    }
  });

  // Optional: Show a "not found" message
  let notFoundMsg = document.getElementById('notFoundMessage');
  if (!found && !notFoundMsg) {
      notFoundMsg = document.createElement('div');
      notFoundMsg.id = 'notFoundMessage';
      notFoundMsg.className = 'alert alert-warning text-center';
      notFoundMsg.innerText = 'No doctors match your search.';
      document.getElementById('doctorList').appendChild(notFoundMsg);
  } else if (found && notFoundMsg) {
      notFoundMsg.remove();
  }
}
</script>
</body>
</html>