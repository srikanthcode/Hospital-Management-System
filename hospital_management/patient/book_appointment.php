<?php
require_once "../includes/auth.php";
require_role("patient");
include "../db.php";

$user_id = $_SESSION["user_id"];

$page_title = "Book Appointment";
$active = "pt_book";
$base = "../";
include "../includes/layout.php";
?>
<div class="card p-4">
<h5>Book Appointment</h5>
<form method="post" id="bookForm">
  <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
  <div class="row g-2">
    <div class="col-md-6 mb-2">
      <label class="form-label">Doctor *</label>
      <select name="doctor_id" class="form-select" required id="doctorSelect">
        <option value="">-- Select --</option>
      </select>
    </div>
    <div class="col-md-6 mb-2">
      <label class="form-label">Service</label>
      <select name="service_id" class="form-select" id="serviceSelect">
        <option value="">-- Optional --</option>
      </select>
    </div>
    <div class="col-md-6 mb-2">
      <label class="form-label">Date *</label>
      <input type="date" name="appointment_date" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
    </div>
    <div class="col-md-6 mb-2">
      <label class="form-label">Time *</label>
      <input type="time" name="appointment_time" class="form-control" required>
    </div>
    <div class="col-12 mb-2">
      <label class="form-label">Notes</label>
      <textarea name="notes" class="form-control" rows="2"></textarea>
    </div>
  </div>
  <button class="btn pink-btn" type="submit">Book Appointment</button>
  <a href="appointments.php" class="btn btn-secondary">My Appointments</a>
</form>
</div>

<script>
(function() {
    let polling = false;
    let doctorsCache = [];
    let servicesCache = [];

    function populateDoctors() {
        const select = document.getElementById('doctorSelect');
        if (select && doctorsCache.length) {
            select.innerHTML = '<option value="">-- Select --</option>' +
                doctorsCache.map(d => '<option value="' + Realtime.esc(d.id) + '">' + Realtime.esc(d.name + ' - ' + d.specialization) + '</option>').join('');
        }
    }

    function populateServices() {
        const select = document.getElementById('serviceSelect');
        if (select && servicesCache.length) {
            select.innerHTML = '<option value="">-- Optional --</option>' +
                servicesCache.map(s => '<option value="' + Realtime.esc(s.id) + '">' + Realtime.esc(s.name) + '</option>').join('');
        }
    }

    async function loadDropdowns() {
        if (polling) return;
        polling = true;

        try {
            const [docResp, svcResp] = await Promise.all([
                fetch('../api/doctors.php?limit=1000', { credentials: 'same-origin' }),
                fetch('../api/services.php?limit=1000', { credentials: 'same-origin' })
            ]);

            if (docResp.ok) {
                const d = await docResp.json();
                if (d && !d.error) { doctorsCache = d.doctors || []; populateDoctors(); }
            }

            if (svcResp.ok) {
                const s = await svcResp.json();
                if (s && !s.error) { servicesCache = s.services || []; populateServices(); }
            }
        } catch (e) {
            console.error('Failed to load dropdowns:', e);
        } finally {
            polling = false;
        }
    }

    // Initial load
    loadDropdowns();

    // Refresh dropdowns every 60 seconds
    Realtime.startPolling('book_appointment_dropdowns', '../api/doctors.php?limit=1000',
        (data) => {
            if (data && !data.error) {
                doctorsCache = data.doctors || [];
                populateDoctors();
            }
        }, 60000);

    Realtime.startPolling('book_appointment_services', '../api/services.php?limit=1000',
        (data) => {
            if (data && !data.error) {
                servicesCache = data.services || [];
                populateServices();
            }
        }, 60000);
})();
</script>
<?php include "../includes/layout_footer.php"; ?>