<?php
require_once "../includes/auth.php";
require_role("patient");
include "../db.php";

$user_id = $_SESSION["user_id"];

$page_title = "My Medical Records";
$active = "pt_records";
$base = "../";
include "../includes/layout.php";
?>
<div class="card p-3">
<h5>My Medical Records</h5>
<div class="table-responsive">
<table class="table table-bordered">
<thead><tr><th>ID</th><th>Date</th><th>Doctor</th><th>Diagnosis</th><th>Treatment</th><th>Prescription</th></tr></thead>
<tbody id="recordsBody">
  <tr><td colspan="6" class="text-center">Loading...</td></tr>
</tbody>
</table>
</div>
</div>

<script>
(function() {
    let polling = false;

    function renderRecords(records) {
        const tbody = document.getElementById('recordsBody');
        if (!tbody) return;

        if (!records || records.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">No records.</td></tr>';
            return;
        }

        let html = '';
        records.forEach(r => {
            html += `
                <tr>
                    <td>${Realtime.esc(r.id)}</td>
                    <td>${Realtime.esc(r.record_date)}</td>
                    <td>${Realtime.esc(r.doctor_name || '')}</td>
                    <td>${Realtime.esc(r.diagnosis)}</td>
                    <td>${Realtime.esc(r.treatment)}</td>
                    <td>${Realtime.esc(r.prescription)}</td>
                </tr>`;
        });
        tbody.innerHTML = html;
    }

    async function loadRecords() {
        if (polling) return;
        polling = true;

        try {
            const resp = await fetch('../api/patient_medical_records.php?limit=100', { credentials: 'same-origin' });
            if (resp.ok) {
                const data = await resp.json();
                if (data && !data.error) {
                    renderRecords(data.records);
                }
            }
        } catch (e) {
            console.error('Failed to load medical records:', e);
        } finally {
            polling = false;
        }
    }

    // Initial load
    loadRecords();

    // Poll for real-time updates every 10 seconds
    Realtime.startPolling('patient_medical_records', '../api/patient_medical_records.php?limit=100',
        (data) => {
            if (data && !data.error) {
                renderRecords(data.records);
            }
        }, 10000);
})();
</script>
<?php include "../includes/layout_footer.php"; ?>