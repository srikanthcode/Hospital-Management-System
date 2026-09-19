/**
 * Notification system for Lotus Hospital
 * Polls for new notifications and shows bell badge
 */
const Notifications = {
    lastCount: 0,
    started: false,

    init() {
        this.startPolling();
    },

    startPolling() {
        if (this.started) return;
        this.started = true;
        // Register with the shared Realtime registry so the poll is subject
        // to the same pause/resume/backoff/401 handling as everything else.
        Realtime.startPolling('notifications', 'api/notifications.php',
            (data) => this.handleData(data), 5000);
    },

    handleData(data) {
        if (!data || data.error) return;
        const count = data.unread_count || 0;
        this.updateBadge(count);
        this.updateDropdown(data.notifications || []);
    },

    async refresh() {
        const data = await Realtime.fetchJson('api/notifications.php');
        this.handleData(data);
    },

    updateBadge(count) {
        const badge = document.getElementById('notifBadge');
        if (badge) {
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        }
        // Play sound on new notification
        if (count > this.lastCount && this.lastCount > 0) {
            this.playAlert();
        }
        this.lastCount = count;
    },

    updateDropdown(notifications) {
        const list = document.getElementById('notifList');
        if (!list) return;

        if (notifications.length === 0) {
            list.innerHTML = '<div class="text-center text-muted p-3">No notifications</div>';
            return;
        }

        let html = '';
        notifications.forEach(n => {
            const icon = this.getIcon(n.type);
            const readClass = n.is_read ? '' : 'fw-bold';
            const time = Realtime.relativeTime(n.created_at);
            const nid = Realtime.esc(n.id);
            html += `
                <div class="notif-item ${readClass} p-2 border-bottom" data-id="${nid}" onclick="Notifications.markRead(${nid})">
                    <div class="d-flex align-items-start gap-2">
                        <span class="notif-icon">${icon}</span>
                        <div class="flex-grow-1">
                            <div class="small">${Realtime.esc(n.title)}</div>
                            <div class="text-muted" style="font-size:11px">${Realtime.esc(n.message || '')}</div>
                            <div class="text-muted" style="font-size:10px">${time}</div>
                        </div>
                    </div>
                </div>`;
        });
        list.innerHTML = html;
    },

    getIcon(type) {
        const icons = {
            'info': '<span style="color:#2196F3">&#11044;</span>',
            'success': '<span style="color:#4CAF50">&#11044;</span>',
            'warning': '<span style="color:#FF9800">&#11044;</span>',
            'danger': '<span style="color:#F44336">&#11044;</span>',
            'emergency': '<span style="color:#F44336">&#128680;</span>'
        };
        return icons[type] || icons['info'];
    },

    async markRead(id) {
        await Realtime.postForm('api/notifications.php', { action: 'mark_one', id: id });
        this.refresh();
    },

    async markAllRead() {
        await Realtime.postForm('api/notifications.php', { action: 'mark_read' });
        this.refresh();
    },

    playAlert() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.value = 800;
            gain.gain.value = 0.1;
            osc.start();
            osc.stop(ctx.currentTime + 0.15);
        } catch (e) {}
    }
};
