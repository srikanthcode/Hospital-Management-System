/**
 * Real-time polling engine for Lotus Hospital Management
 * Uses native JavaScript fetch() - no frameworks needed
 *
 * Design notes:
 *  - Recursive setTimeout (not setInterval) so a slow response can never
 *    overlap a faster one and repaint the UI out of order.
 *  - Exponential backoff after consecutive failures, capped at 60s, so a
 *    downed server is not hammered at full rate forever.
 *  - HTTP 401 stops all polling and redirects to login.php, so an expired
 *    session can never leave a frozen, stale dashboard behind.
 *  - Polling pauses while the tab is hidden and resumes when visible.
 *  - esc() escapes any DB value before it is interpolated into innerHTML.
 */
const Realtime = {
    intervals: {},   // name -> { timer, active }
    configs: {},     // name -> { url, callback, intervalMs } (used to resume)
    failures: {},    // name -> consecutive failure count
    baseUrl: '',
    redirecting: false,

    init(base) {
        this.baseUrl = base || '';
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.stopAll();
            } else {
                this.resumeAll();
            }
        });
    },

    /**
     * HTML-escape a value coming from the API before it is placed into
     * innerHTML. Prevents stored XSS via user-controlled names/notes.
     */
    esc(value) {
        const div = document.createElement('div');
        div.textContent = (value === null || value === undefined) ? '' : String(value);
        return div.innerHTML;
    },

    async fetchJson(url, options) {
        try {
            const resp = await fetch(this.baseUrl + url, Object.assign(
                { credentials: 'same-origin' },
                options || {}
            ));
            if (resp.status === 401) {
                this.handleUnauthorized();
                return null;
            }
            if (!resp.ok) return null;
            return await resp.json();
        } catch (e) {
            return null;
        }
    },

    /**
     * POST form-encoded params with the CSRF header attached.
     */
    async postForm(url, params) {
        const body = new URLSearchParams(params || {});
        return this.fetchJson(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-Token': (window.CSRF_TOKEN || '')
            },
            body: body.toString()
        });
    },

    handleUnauthorized() {
        if (this.redirecting) return;
        this.redirecting = true;
        this.stopAll();
        window.location.href = this.baseUrl + 'login.php';
    },

    startPolling(name, url, callback, intervalMs) {
        this.stopPolling(name);
        this.configs[name] = { url, callback, intervalMs };
        this.failures[name] = 0;
        this.intervals[name] = { timer: null, active: true };

        const poll = async () => {
            let entry = this.intervals[name];
            if (!entry || !entry.active) return;

            const data = await this.fetchJson(url);

            entry = this.intervals[name];
            if (!entry || !entry.active) return;

            if (data && !data.error) {
                this.failures[name] = 0;
                callback(data);
            } else {
                this.failures[name] = (this.failures[name] || 0) + 1;
            }

            entry = this.intervals[name];
            if (!entry || !entry.active) return;

            const fails = this.failures[name] || 0;
            const delay = fails > 0
                ? Math.min(intervalMs * Math.pow(2, fails), 60000)
                : intervalMs;
            entry.timer = setTimeout(poll, delay);
        };

        poll();
    },

    stopPolling(name) {
        const entry = this.intervals[name];
        if (entry) {
            entry.active = false;
            if (entry.timer) clearTimeout(entry.timer);
            delete this.intervals[name];
        }
    },

    stopAll() {
        Object.keys(this.intervals).forEach(name => this.stopPolling(name));
    },

    resumeAll() {
        if (this.redirecting) return;
        Object.keys(this.configs).forEach(name => {
            if (!this.intervals[name]) {
                const c = this.configs[name];
                this.startPolling(name, c.url, c.callback, c.intervalMs);
            }
        });
    },

    updateText(id, value) {
        const el = document.getElementById(id);
        if (el && el.textContent != value) {
            el.textContent = value;
            el.classList.add('pulse');
            setTimeout(() => el.classList.remove('pulse'), 600);
        }
    },

    updateHtml(id, html) {
        const el = document.getElementById(id);
        if (el) el.innerHTML = html;
    },

    formatTime(timeStr) {
        if (!timeStr) return '';
        const parts = timeStr.split(':');
        let h = parseInt(parts[0]);
        const m = parts[1];
        const ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        return h + ':' + m + ' ' + ampm;
    },

    relativeTime(dateStr) {
        if (!dateStr) return '';
        const now = new Date();
        const date = new Date(dateStr);
        const diff = Math.floor((now - date) / 1000);
        if (diff < 60) return 'Just now';
        if (diff < 3600) return Math.floor(diff / 60) + ' min ago';
        if (diff < 86400) return Math.floor(diff / 3600) + ' hours ago';
        return Math.floor(diff / 86400) + ' days ago';
    }
};
