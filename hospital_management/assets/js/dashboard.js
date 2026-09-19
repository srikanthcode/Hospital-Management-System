/**
 * Dashboard live updates for Lotus Hospital
 * Updates stat cards, activity timeline, and today's appointments
 */
const Dashboard = {
    role: '',
    userId: 0,

    init(role, userId) {
        this.role = role;
        this.userId = userId;
        this.startStatsPolling();
        // The activity timeline is admin-only data; do not poll it for
        // other roles (the API also enforces this server-side).
        if (this.role === 'admin') this.startActivityPolling();
        this.startAppointmentsPolling();
    },

    startStatsPolling() {
        Realtime.startPolling('stats', 'api/dashboard_stats.php', (data) => {
            if (this.role === 'admin') this.updateAdminStats(data);
            else if (this.role === 'doctor') this.updateDoctorStats(data);
            else if (this.role === 'patient') this.updatePatientStats(data);
            else if (this.role === 'nurse') this.updateNurseStats(data);
        }, 10000);
    },

    startActivityPolling() {
        Realtime.startPolling('activity', 'api/activity.php', (data) => {
            this.renderActivity(data.activities || []);
        }, 10000);
    },

    startAppointmentsPolling() {
        Realtime.startPolling('appointments', 'api/today_appointments.php', (data) => {
            this.renderTodayAppointments(data.appointments || []);
        }, 5000);
    },

    updateAdminStats(d) {
        Realtime.updateText('statDoctors', d.doctors || 0);
        Realtime.updateText('statPatients', d.patients || 0);
        Realtime.updateText('statAppointments', d.appointments || 0);
        Realtime.updateText('statBeds', d.available_beds || 0);
        Realtime.updateText('statEmergencies', d.active_emergencies || 0);
    },

    updateDoctorStats(d) {
        Realtime.updateText('statPatients', d.my_patients || 0);
        Realtime.updateText('statTodayAppts', d.today_appointments || 0);
        Realtime.updateText('statTotalAppts', d.total_appointments || 0);
        Realtime.updateText('statRecords', d.medical_records || 0);
    },

    updatePatientStats(d) {
        Realtime.updateText('statAppts', d.my_appointments || 0);
        Realtime.updateText('statRecords', d.medical_records || 0);
        Realtime.updateText('statFollowups', d.follow_ups || 0);

        // Update next appointment card
        if (d.next_appointment) {
            const na = d.next_appointment;
            const nextEl = document.getElementById('nextAppointment');
            if (nextEl) {
                // Parse the DATE column as local time (splitting avoids the
                // UTC-midnight shift that new Date('Y-m-d') applies).
                const dp = (na.date || '').split('-');
                const dObj = dp.length === 3
                    ? new Date(+dp[0], +dp[1] - 1, +dp[2])
                    : null;
                const doctor = Realtime.esc(na.doctor_name || 'Doctor');
                const status = Realtime.esc(na.status);
                nextEl.innerHTML = `
                    <div class="d-flex align-items-center gap-3">
                        <div class="text-center bg-danger text-white rounded p-2">
                            <div class="fw-bold" style="font-size:20px">${dObj ? dObj.getDate() : '--'}</div>
                            <div style="font-size:11px">${dObj ? dObj.toLocaleDateString('en', {month:'short'}) : ''}</div>
                        </div>
                        <div>
                            <h6 class="mb-0">${doctor}</h6>
                            <small class="text-muted">${na.time ? Realtime.formatTime(na.time) : ''}</small>
                        </div>
                        <div class="ms-auto">
                            <span class="badge bg-${na.status === 'Confirmed' ? 'success' : na.status === 'Completed' ? 'secondary' : 'warning'}">${status}</span>
                        </div>
                    </div>`;
            }
        }
    },

    updateNurseStats(d) {
        Realtime.updateText('statShift', d.shift || '-');
        Realtime.updateText('statDepartment', d.department || '-');
        Realtime.updateText('statDuty', d.duty_assignment || '-');
        // The "Patient Care" card holds a duty description string, not a
        // count - do not overwrite it with a number.
        Realtime.updateText('statPatients', d.patient_care || '-');
    },

    renderActivity(logs) {
        const container = document.getElementById('activityTimeline');
        if (!container) return;

        if (logs.length === 0) {
            container.innerHTML = '<div class="text-center text-muted p-3">No recent activity</div>';
            return;
        }

        let html = '';
        logs.forEach(log => {
            html += `
                <div class="d-flex gap-3 mb-3">
                    <div>
                        <span style="color:${this.getColor(log.color)}; font-size:18px">&#11044;</span>
                    </div>
                    <div class="flex-grow-1">
                        <div class="small fw-semibold">${Realtime.esc(log.user_name || 'System')}</div>
                        <div class="text-muted small">${Realtime.esc(log.description)}</div>
                        <div class="text-muted" style="font-size:10px">${Realtime.relativeTime(log.created_at)}</div>
                    </div>
                </div>`;
        });
        container.innerHTML = html;
    },

    renderTodayAppointments(appts) {
        const container = document.getElementById('todayAppointments');
        if (!container) return;

        if (appts.length === 0) {
            container.innerHTML = '<div class="text-center text-muted p-3">No appointments today</div>';
            return;
        }

        let html = '<div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Time</th><th>Patient</th><th>Doctor</th><th>Status</th></tr></thead><tbody>';
        appts.forEach(a => {
            const statusClass = a.status === 'Confirmed' ? 'success' : a.status === 'Completed' ? 'secondary' : a.status === 'Cancelled' ? 'danger' : 'warning';
            html += `<tr>
                <td>${a.time ? Realtime.formatTime(a.time) : '-'}</td>
                <td>${Realtime.esc(a.patient_name) || '-'}</td>
                <td>${Realtime.esc(a.doctor_name) || '-'}</td>
                <td><span class="badge bg-${statusClass}">${Realtime.esc(a.status)}</span></td>
            </tr>`;
        });
        html += '</tbody></table></div>';
        container.innerHTML = html;
    },

    getColor(color) {
        const colors = {
            'primary': '#2196F3',
            'success': '#4CAF50',
            'warning': '#FF9800',
            'danger': '#F44336',
            'info': '#00BCD4',
            'purple': '#9C27B0'
        };
        return colors[color] || colors['primary'];
    }
};
