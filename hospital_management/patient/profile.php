<?php
require_once "../includes/auth.php";
require_role("patient");
include "../db.php";

$user_id = $_SESSION["user_id"];

$page_title = "My Profile";
$active = "pt_profile";
$base = "../";
include "../includes/layout.php";
?>
<div class="card p-4">
<h5>My Profile</h5>
<form method="post" id="profileForm">
  <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
  <div class="row g-2">
    <div class="col-md-6 mb-2"><label class="form-label">Name</label>
      <input class="form-control" name="name" id="profileName" required>
    </div>
    <div class="col-md-3 mb-2"><label class="form-label">Age</label>
      <input type="number" class="form-control" name="age" id="profileAge">
    </div>
    <div class="col-md-3 mb-2"><label class="form-label">Blood Group</label>
      <input class="form-control" name="blood_group" id="profileBlood">
    </div>
    <div class="col-md-6 mb-2"><label class="form-label">Phone</label>
      <input class="form-control" name="phone" id="profilePhone">
    </div>
    <div class="col-md-6 mb-2"><label class="form-label">Email</label>
      <input type="email" class="form-control" name="email" id="profileEmail">
    </div>
    <div class="col-12 mb-2"><label class="form-label">Address</label>
      <textarea class="form-control" name="address" id="profileAddress" rows="2"></textarea>
    </div>
  </div>
  <button class="btn pink-btn" type="submit">Save Changes</button>
</form>
</div>

<script>
(function() {
    let polling = false;

    function populateProfile(patient) {
        if (!patient) return;
        document.getElementById('profileName').value = patient.name || '';
        document.getElementById('profileAge').value = patient.age || '';
        document.getElementById('profileBlood').value = patient.blood_group || '';
        document.getElementById('profilePhone').value = patient.phone || '';
        document.getElementById('profileEmail').value = patient.email || '';
        document.getElementById('profileAddress').value = patient.address || '';
    }

    async function loadProfile() {
        if (polling) return;
        polling = true;

        try {
            const resp = await fetch('../api/patient_profile.php', { credentials: 'same-origin' });
            if (resp.ok) {
                const data = await resp.json();
                if (data && !data.error && data.patient) {
                    populateProfile(data.patient);
                }
            }
        } catch (e) {
            console.error('Failed to load profile:', e);
        } finally {
            polling = false;
        }
    }

    // Initial load
    loadProfile();

    // Poll for real-time updates every 30 seconds (profile changes infrequently)
    Realtime.startPolling('patient_profile', '../api/patient_profile.php',
        (data) => {
            if (data && !data.error && data.patient) {
                populateProfile(data.patient);
            }
        }, 30000);

    // Handle form submission
    const form = document.getElementById('profileForm');
    if (form) {
        form.addEventListener('submit', function() {
            setTimeout(loadProfile, 500);
        });
    }
})();
</script>
<?php include "../includes/layout_footer.php"; ?>