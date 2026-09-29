<?php
require_once "../includes/auth.php";
require_role("patient");
include "../db.php";

$user_id = $_SESSION["user_id"];

$page_title = "My Follow-ups";
$active = "pt_followups";
$base = "../";
include "../includes/layout.php";
?>
<div class="card p-3">
<h5>My Follow-ups</h5>
<div class="table-responsive">
<table class="table table-bordered">
<thead><tr><th>ID</th><th>Date</th><th>Doctor</th><th>Remarks</th><th>Status</th></tr></thead>
<tbody id="followupsBody">
  <tr><td colspan="5" class="text-center">Loading...</td></tr>
</tbody>
</table>
</div>
</div>

<script>
(function() {
    let polling = false;

    function getStatusBadge(status) {
        return '<span class="badge bg-' + (status === 'Done' ? 'success' : 'warning') + '">' + Realtime.esc(status) + '</span>';
    }

    function renderFollowups(followups) {
        const tbody = document.getElementById('followupsBody');
        if (!tbody) return;

        if (!followups || followups.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center">No follow-ups.</td></tr>';
            return;
        }

        let html = '';
        followups.forEach(r => {
            const badge = getStatusBadge(r.status);
            html += `
                <tr>
                    <td>${Realtime.esc(r.id)}</td>
                    <td>${Realtime.esc(r.follow_up_date)}</td>
                    <td>${Realtime.esc(r.doctor_name || '')}</td>
                    <td>${Realtime.esc(r.remarks)}</td>
                    <td>${getStatusBadge(r.status)}</td>
                </tr>`;
        });
        tbody.innerHTML = html;
    }

    async function loadFollowups() {
        if (polling) return;
        polling = true;

        try {
            const resp = await fetch('../api/patient_followups.php?limit=100', { credentials: 'same-origin' });
            if (resp.ok) {
                const data = await resp.json();
                if (data && !data.error) {
                    renderFollowups(data.followups);
                }
            }
        } catch (e) {
            console.error('Failed to load follow-ups:', e);
        } finally {
            polling = false;
        }
    }

    // Initial load
    loadFollowups();

    // Poll for real-time updates every 10 seconds
    Realtime.startPolling('patient_followups', '../api/patient_followups.php?limit=100',
        (data) => {
            if (data && !data.error) {
                renderFollowups(data.followups);
            }
        }, 10000);
})();
</script>
<?php include "../includes/layout_footer.php"; ?>