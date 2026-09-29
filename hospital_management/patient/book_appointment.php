<?php
require_once "../includes/auth.php";
require_role("patient");
include "../db.php";

$user_id = $_SESSION["user_id"];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        flash_set('danger', 'Invalid CSRF token. Please try again.');
        header('Location: book_appointment.php');
        exit();
    }

    $doctor_id   = (int)($_POST['doctor_id'] ?? 0);
    $service_id  = (int)($_POST['service_id'] ?? 0);
    $date        = trim($_POST['appointment_date'] ?? '');
    $time        = trim($_POST['appointment_time'] ?? '');
    $notes       = trim($_POST['notes'] ?? '');

    $stmt = mysqli_prepare($conn, "SELECT id FROM patients WHERE user_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $patient = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    $patient_id = $patient ? (int)$patient['id'] : 0;

    $error = '';
    if (!$patient_id) {
        $error = 'Your patient profile could not be found. Please contact the hospital.';
    } elseif ($doctor_id <= 0) {
        $error = 'Please select a doctor.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id FROM doctors WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $doctor_id);
        mysqli_stmt_execute($stmt);
        if (!mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) $error = 'Selected doctor does not exist.';
    }

    $date_obj = DateTime::createFromFormat('Y-m-d', $date);
    if ($error === '' && (!$date_obj || $date_obj->format('Y-m-d') !== $date)) {
        $error = 'Please choose a valid date.';
    } elseif ($error === '' && $date < date('Y-m-d')) {
        $error = 'Appointment date cannot be in the past.';
    }

    if ($error === '') {
        if (!preg_match('/^([01]?\d|2[0-3]):[0-5]\d$/', $time)) $error = 'Please choose a valid time.';
        else $time .= ':00';
    }

    if ($error === '' && $service_id > 0) {
        $stmt = mysqli_prepare($conn, "SELECT id FROM services WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $service_id);
        mysqli_stmt_execute($stmt);
        if (!mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) $service_id = 0;
    }

    if ($error !== '') {
        flash_set('danger', $error);
        header('Location: book_appointment.php');
        exit();
    }

    $status = 'Pending';
    $service_param = ($service_id > 0) ? $service_id : null;
    $stmt = mysqli_prepare($conn, "INSERT INTO appointments (patient_id, doctor_id, service_id, appointment_date, appointment_time, status, notes) VALUES (?,?,?,?,?,?,?)");
    mysqli_stmt_bind_param($stmt, "iisssss", $patient_id, $doctor_id, $service_param, $date, $time, $status, $notes);

    if (mysqli_stmt_execute($stmt)) {
        $appointment_id = (int)mysqli_insert_id($conn);

        // Tell the doctor straight away - the dashboard polls notifications
        // and appointments every few seconds, so this shows up as live data.
        $dstmt = mysqli_prepare($conn, "SELECT user_id, name FROM doctors WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($dstmt, "i", $doctor_id);
        mysqli_stmt_execute($dstmt);
        $doc = mysqli_fetch_assoc(mysqli_stmt_get_result($dstmt));

        $pstmt = mysqli_prepare($conn, "SELECT name FROM patients WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($pstmt, "i", $patient_id);
        mysqli_stmt_execute($pstmt);
        $pat = mysqli_fetch_assoc(mysqli_stmt_get_result($pstmt));
        $patient_name = $pat['name'] ?? 'A patient';

        if ($doc) {
            $title = 'New Appointment Booking';
            $message = "{$patient_name} booked an appointment for {$date} at " . date('h:i A', strtotime($time));
            $type = 'appointment';
            $ref_type = 'appointment';
            $nstmt = mysqli_prepare($conn, "INSERT INTO notifications (user_id, type, title, message, reference_id, reference_type) VALUES (?,?,?,?,?,?)");
            mysqli_stmt_bind_param($nstmt, "isssis", $doc['user_id'], $type, $title, $message, $appointment_id, $ref_type);
            mysqli_stmt_execute($nstmt);
        }

        $action = 'create';
        $entity = 'appointment';
        $description = "Appointment booked with Dr. " . ($doc['name'] ?? '') . " on {$date} {$time}";
        $color = 'success';
        $astmt = mysqli_prepare($conn, "INSERT INTO activity_logs (user_id, user_name, action, entity_type, entity_id, description, color) VALUES (?,?,?,?,?,?,?)");
        mysqli_stmt_bind_param($astmt, "issssss", $user_id, $_SESSION['name'], $action, $entity, $appointment_id, $description, $color);
        mysqli_stmt_execute($astmt);

        flash_set('success', "Appointment booked for {$date} at " . date('h:i A', strtotime($time)) . ". The doctor has been notified.");
        header('Location: appointments.php');
    } else {
        flash_set('danger', 'Could not book the appointment. Please try again.');
        header('Location: book_appointment.php');
    }

    exit();
}

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