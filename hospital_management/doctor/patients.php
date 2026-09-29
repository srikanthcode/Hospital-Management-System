<?php
require_once "../includes/auth.php";
require_role("doctor");
include "../db.php";

$user_id = $_SESSION["user_id"];

$page_title = "My Patients";
$active = "doc_patients";
$base = "../";
include "../includes/layout.php";
?>
<div class="card p-3">
<div class="d-flex justify-content-between mb-2">
  <h5>Patients Assigned To You</h5>
  <form method="get" class="mb-0" id="searchForm">
    <div class="input-group">
      <input type="text" class="form-control" name="search" id="searchInput" placeholder="Search by name, phone, email..." value="<?php echo e($_GET['search'] ?? ''); ?>">
      <button class="btn btn-outline-danger" type="submit">Search</button>
      <a href="patients.php" class="btn btn-outline-secondary">Reset</a>
    </div>
  </form>
</div>
<div class="table-responsive">
<table class="table table-bordered table-hover">
<thead><tr><th>ID</th><th>Name</th><th>Age</th><th>Phone</th><th>Email</th><th>Blood Group</th></tr></thead>
<tbody id="patientsBody">
  <tr><td colspan="6" class="text-center">Loading...</td></tr>
</tbody>
</table>
</div>
</div>

<script>
(function() {
    let currentSearch = '<?php echo e($_GET['search'] ?? ''); ?>';
    let polling = false;

    function renderPatients(patients) {
        const tbody = document.getElementById('patientsBody');
        if (!tbody) return;

        if (!patients || patients.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">No patients yet.</td></tr>';
            return;
        }

        let html = '';
        patients.forEach(p => {
            html += `
                <tr>
                    <td>${Realtime.esc(p.id)}</td>
                    <td>${Realtime.esc(p.name)}</td>
                    <td>${Realtime.esc(p.age)}</td>
                    <td>${Realtime.esc(p.phone)}</td>
                    <td>${Realtime.esc(p.email)}</td>
                    <td>${Realtime.esc(p.blood_group)}</td>
                </tr>`;
        });
        tbody.innerHTML = html;
    }

    async function loadPatients() {
        if (polling) return;
        polling = true;

        try {
            const params = new URLSearchParams();
            if (currentSearch) params.set('search', currentSearch);
            params.set('limit', 100);
            params.set('offset', 0);

            const resp = await fetch('../api/doctor_patients.php?' + params.toString(), { credentials: 'same-origin' });
            if (resp.ok) {
                const data = await resp.json();
                if (data && !data.error) {
                    renderPatients(data.patients);
                }
            }
        } catch (e) {
            console.error('Failed to load patients:', e);
        } finally {
            polling = false;
        }
    }

    // Initial load
    loadPatients();

    // Search form handler
    const searchForm = document.getElementById('searchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            currentSearch = document.getElementById('searchInput').value.trim();
            history.replaceState(null, '', currentSearch ? '?search=' + encodeURIComponent(currentSearch) : 'patients.php');
            loadPatients();
        });
    }

    // Poll for real-time updates every 10 seconds
    Realtime.startPolling('doctor_patients', '../api/doctor_patients.php?' + new URLSearchParams({limit: 100, offset: 0}).toString(),
        (data) => {
            if (data && !data.error) {
                renderPatients(data.patients);
            }
        }, 10000);
})();
</script>
<?php include "../includes/layout_footer.php"; ?>