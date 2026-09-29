/**
 * Real time OTP flow (registration + forgot password).
 *
 * Requires window.OTP_CONFIG = { endpoint, purpose, csrf, resendSeconds }.
 * Works with the markup rendered by includes/otp_widgets.php.
 *
 * States: 1 -> Gmail address, 2 -> 6 digit code, 3 -> page owned form.
 */
const OtpAuth = {
    cfg: null,
    email: '',
    busy: false,
    expiryTimer: null,
    resendTimer: null,
    expiresAt: 0,
    resendUntil: 0,

    init() {
        this.cfg = window.OTP_CONFIG || {};
        if (!this.cfg.endpoint) return;

        const $ = (id) => document.getElementById(id);
        this.el = {
            alert: $('otpAlert'),
            step1: $('otpStep1'),
            step2: $('otpStep2'),
            step3: $('otpStep3'),
            email: $('otpEmail'),
            send: $('otpSendBtn'),
            verify: $('otpVerifyBtn'),
            resend: $('otpResendBtn'),
            resendCount: $('otpResendCount'),
            change: $('otpChangeEmail'),
            countdown: $('otpCountdown'),
            attempts: $('otpAttemptsNote'),
            sentTo: $('otpSentTo'),
            boxes: Array.prototype.slice.call(document.querySelectorAll('#otpBoxes .otp-box')),
            steps: Array.prototype.slice.call(document.querySelectorAll('#otpStepper .otp-step')),
            overlay: $('gmailOverlay')
        };

        if (!this.el.step1 || !this.el.email) return;

        this.el.send.addEventListener('click', () => this.send());
        this.el.verify.addEventListener('click', () => this.verify());
        this.el.resend.addEventListener('click', () => this.send(true));
        this.el.change.addEventListener('click', () => this.backToEmail());

        this.el.email.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') { e.preventDefault(); this.send(); }
        });
        this.el.email.addEventListener('input', () => this.hideAlert());

        this.wireBoxes();

        const gotIt = document.getElementById('gmailGotIt');
        if (gotIt) gotIt.addEventListener('click', () => this.closeGmail());
        const closeX = document.getElementById('gmailCloseX');
        if (closeX) closeX.addEventListener('click', () => this.closeGmail());
        if (this.el.overlay) {
            this.el.overlay.addEventListener('click', (e) => {
                if (e.target === this.el.overlay) this.closeGmail();
            });
        }
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') this.closeGmail();
        });

        // Nothing may be typed into the details form before the Gmail OTP passed.
        const step3 = this.el.step3;
        if (step3) {
            step3.setAttribute('hidden', 'hidden');
            const form = step3.querySelector('form');
            if (form) {
                form.addEventListener('submit', (e) => this.guardSubmit(e, form));
            }
        }
    },

    /* ---------------- helpers ---------------- */

    showAlert(type, message) {
        if (!this.el.alert) return;
        this.el.alert.hidden = false;
        this.el.alert.className = 'otp-alert otp-alert-' + type;
        this.el.alert.textContent = message;
    },

    hideAlert() {
        if (!this.el.alert) return;
        this.el.alert.hidden = true;
        this.el.alert.textContent = '';
    },

    busyOn(button, label) {
        this.busy = true;
        if (button) {
            button.dataset.label = button.textContent;
            button.disabled = true;
            button.innerHTML = '<span class="otp-spinner"></span> ' + label;
        }
    },

    busyOff(button) {
        this.busy = false;
        if (button && button.dataset.label) {
            button.textContent = button.dataset.label;
            button.disabled = false;
        }
    },

    setStep(n) {
        const map = { 1: this.el.step1, 2: this.el.step2, 3: this.el.step3 };
        Object.keys(map).forEach((k) => {
            const node = map[k];
            if (!node) return;
            if (Number(k) === n) node.removeAttribute('hidden');
            else node.setAttribute('hidden', 'hidden');
        });
        this.el.steps.forEach((s) => {
            const step = Number(s.dataset.step);
            s.classList.toggle('active', step === n);
            s.classList.toggle('done', step < n);
        });
    },

    readEmail() {
        return (this.el.email.value || '').trim().toLowerCase();
    },

    validEmail(v) {
        // Strict syntax: dotted domain with a TLD of at least two letters.
        if (!/^[^\s@]+@[^\s@]+\.[a-zA-Z]{2,}$/.test(v)) return false;
        const at = v.lastIndexOf('@');
        const local = v.slice(0, at);
        if (!local || local.startsWith('.') || local.endsWith('.') || local.includes('..')) return false;
        // Creating an account is Gmail only; a password reset may target any
        // provider because existing accounts can hold other addresses.
        if (this.cfg.purpose === 'register') {
            return /@(gmail\.com|googlemail\.com)$/i.test(v);
        }
        return true;
    },

    emailError() {
        return this.cfg.purpose === 'register'
            ? 'Enter your Gmail address, for example you@gmail.com.'
            : 'Please enter a valid email address, for example you@gmail.com.';
    },

    /* ---------------- step 1 : send ---------------- */

    async send(isResend) {
        if (this.busy) return;
        this.hideAlert();

        const email = this.readEmail();
        if (!this.validEmail(email)) {
            this.showAlert('error', this.emailError());
            this.el.email.focus();
            return;
        }
        if (!isResend && this.resendUntil > Date.now()) {
            this.showAlert('error', 'A code was just sent. Please wait for the timer, or use Resend code.');
            return;
        }

        this.email = email;
        this.busyOn(this.el.send, 'Sending...');
        if (isResend && this.el.resend) this.el.resend.disabled = true;

        try {
            const data = await this.post({ action: isResend ? 'resend' : 'send', email: email, purpose: this.cfg.purpose });
            this.busyOff(this.el.send);

            if (data.error) {
                this.showAlert('error', data.error);
                this.startResendTimer();
                return;
            }

            const preview = data.preview || {};
            this.renderGmail(data);
            this.startExpiryTimer(data.expires_in || 600);
            this.startResendTimer();
            this.el.sentTo.textContent = email;
            this.setStep(2);

            if (preview.code) {
                // No mail server configured (local development): show the
                // generated message on screen so the flow stays usable.
                this.showDelivery(data.delivery);
                this.openGmail();
            } else {
                // Really delivered - the code only exists in the mailbox now.
                this.showAlert('success', 'Code emailed to ' + email
                    + '. Open your Gmail inbox and enter the 6 digit code below.');
            }

            // Start polling for delivery status if using Node.js
            if (data.debug && data.debug.use_node && data.debug.mail_configured) {
                this.pollDeliveryStatus(email, this.cfg.purpose);
            }

            this.clearBoxes();
            const first = this.el.boxes[0];
            if (first) setTimeout(() => first.focus(), 250);
        } catch (e) {
            this.busyOff(this.el.send);
            this.showAlert('error', e.message || 'Could not reach the server. Please try again.');
            this.startResendTimer();
        }
    },

    showDelivery(delivery) {
        if (!delivery) return;
        const status = delivery.status || '';
        const message = delivery.message || '';
        if (status === 'sent') {
            this.showAlert('success', 'Code generated and emailed to ' + this.email + '. ' + message);
        } else if (status === 'failed') {
            this.showAlert('error', 'Email delivery failed - ' + message);
        } else {
            this.showAlert('warning', message);
        }
    },

    /* ---------------- step 2 : verify ---------------- */

    wireBoxes() {
        const boxes = this.el.boxes;
        boxes.forEach((box, i) => {
            box.addEventListener('input', () => {
                box.value = box.value.replace(/\D/g, '').slice(0, 1);
                if (box.value && i < boxes.length - 1) boxes[i + 1].focus();
                if (this.code().length === 6) this.verify();
            });
            box.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !box.value && i > 0) {
                    boxes[i - 1].focus();
                    boxes[i - 1].value = '';
                    e.preventDefault();
                }
                if (e.key === 'ArrowLeft' && i > 0) boxes[i - 1].focus();
                if (e.key === 'ArrowRight' && i < boxes.length - 1) boxes[i + 1].focus();
                if (e.key === 'Enter') { e.preventDefault(); this.verify(); }
            });
            box.addEventListener('paste', (e) => {
                e.preventDefault();
                const digits = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
                for (let k = 0; k < digits.length && (i + k) < boxes.length; k++) {
                    boxes[i + k].value = digits[k];
                }
                const next = Math.min(i + digits.length, boxes.length - 1);
                boxes[next].focus();
                if (this.code().length === 6) this.verify();
            });
            box.addEventListener('focus', () => box.select());
        });
    },

    code() {
        return this.el.boxes.map((b) => b.value).join('');
    },

    clearBoxes() {
        this.el.boxes.forEach((b) => { b.value = ''; });
    },

    async verify() {
        if (this.busy) return;
        this.hideAlert();

        const code = this.code();
        if (code.length !== 6) {
            this.showAlert('error', 'Enter all 6 digits of the code from your Gmail.');
            return;
        }
        if (this.expiresAt && Date.now() > this.expiresAt) {
            this.showAlert('error', 'This code expired. Please request a new one.');
            return;
        }

        this.busyOn(this.el.verify, 'Verifying...');
        try {
            const data = await this.post({
                action: 'verify',
                email: this.email,
                purpose: this.cfg.purpose,
                code: code
            });
            this.busyOff(this.el.verify);

            if (data.error) {
                this.showAlert('error', data.error);
                if (/expired|new code|already used|wrong attempts/i.test(data.error)) this.clearBoxes();
                else if (this.el.boxes[5]) this.el.boxes[5].focus();
                return;
            }

            if (this.expiryTimer) clearInterval(this.expiryTimer);
            if (this.resendTimer) clearInterval(this.resendTimer);

            this.showAlert('success', data.message || 'Gmail verified.');
            this.syncForms(data.email);
            this.setStep(3);
            this.closeGmail();

            const focusTarget = this.el.step3
                ? (this.el.step3.querySelector('input:not([type=hidden]):not([type=submit]), select, textarea') || null)
                : null;
            if (focusTarget) focusTarget.focus();
        } catch (e) {
            this.busyOff(this.el.verify);
            this.showAlert('error', e.message || 'Verification failed. Please try again.');
        }
    },

    syncForms(email) {
        const value = email || this.email;
        document.querySelectorAll('input[name="otp_email"], input[name="verified_email"]').forEach((input) => {
            input.value = value;
            input.readOnly = true;
        });
        document.querySelectorAll('input[name="otp_verified"]').forEach((input) => {
            input.value = '1';
        });
        const done = document.getElementById('otpDoneMail');
        if (done) {
            if (done.tagName === 'INPUT') done.value = value;
            else done.textContent = value;
        }
    },

    guardSubmit(e, form) {
        const field = form.querySelector('input[name="otp_email"], input[name="verified_email"]');
        if (field && !field.value) {
            e.preventDefault();
            this.showAlert('error', 'Please verify your Gmail address first.');
            this.setStep(1);
            return false;
        }
        return true;
    },

    backToEmail() {
        this.hideAlert();
        this.clearBoxes();
        if (this.expiryTimer) clearInterval(this.expiryTimer);
        this.setStep(1);
        this.el.email.focus();
        this.el.email.select();
    },

    /* ---------------- timers ---------------- */

    startExpiryTimer(seconds) {
        if (this.expiryTimer) clearInterval(this.expiryTimer);
        this.expiresAt = Date.now() + (seconds * 1000);
        const tick = () => {
            const left = Math.max(0, Math.round((this.expiresAt - Date.now()) / 1000));
            const m = Math.floor(left / 60);
            const s = left % 60;
            if (this.el.countdown) {
                this.el.countdown.textContent = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
            }
            if (left <= 0) {
                clearInterval(this.expiryTimer);
                this.expiryTimer = null;
                if (this.el.verify) this.el.verify.disabled = true;
                this.showAlert('error', 'Your code expired. Please resend a new code.');
            } else if (this.el.verify) {
                this.el.verify.disabled = this.busy;
            }
        };
        tick();
        this.expiryTimer = setInterval(tick, 1000);
    },

    startResendTimer() {
        if (this.resendTimer) clearInterval(this.resendTimer);
        const seconds = Math.max(0, Number(this.cfg.resendSeconds || 30));
        this.resendUntil = Date.now() + seconds * 1000;
        const tick = () => {
            if (!this.el.resend) return;
            const left = Math.max(0, Math.round((this.resendUntil - Date.now()) / 1000));
            if (left > 0) {
                this.el.resend.innerHTML = 'Resend code (<span id="otpResendCount">' + left + '</span>s)';
                this.el.resend.disabled = true;
                this.el.resendCount = document.getElementById('otpResendCount');
            } else {
                this.el.resend.textContent = 'Resend code';
                this.el.resend.disabled = false;
                if (this.resendTimer) clearInterval(this.resendTimer);
                this.resendTimer = null;
            }
        };
        tick();
        if (seconds > 0) this.resendTimer = setInterval(tick, 1000);
    },

    /* ---------------- Gmail preview ---------------- */

    renderGmail(data) {
        const p = data.preview || {};
        const set = (id, value) => {
            const node = document.getElementById(id);
            if (node) node.textContent = value == null ? '' : String(value);
        };
        set('gmailFromName', p.from_name + ' <' + p.from_email + '>');
        set('gmailSubject', p.subject);
        set('gmailTo', 'to: ' + p.to);
        set('gmailDate', p.date);
        set('gmailHeadline', p.headline);
        set('gmailText', p.text);
        set('gmailCode', p.code || '------');
        set('gmailExpiry', 'Valid for ' + (p.expires_minutes || 10) + ' minutes - do not share this code.');
        
        // Update delivery status
        const delivery = document.getElementById('gmailDelivery');
        if (delivery && data.delivery) {
            const status = data.delivery.status || '';
            const message = data.delivery.message || '';
            const badge = document.createElement('span');
            badge.className = 'badge ' + (status === 'sent' ? 'bg-success' : status === 'failed' ? 'bg-danger' : 'bg-warning text-dark');
            badge.textContent = message;
            delivery.innerHTML = '';
            delivery.appendChild(badge);
        }
    },

    openGmail() {
        if (!this.el.overlay) return;
        this.el.overlay.hidden = false;
        requestAnimationFrame(() => this.el.overlay.classList.add('open'));
        const delivery = document.getElementById('gmailDelivery');
        if (delivery) {
            delivery.innerHTML = '<span class="badge bg-secondary">Checking delivery...</span>';
        }
    },

    closeGmail() {
        if (!this.el.overlay || this.el.overlay.hidden) return;
        this.el.overlay.classList.remove('open');
        setTimeout(() => { this.el.overlay.hidden = true; }, 180);
        if (this.el.boxes[0]) this.el.boxes[0].focus();
    },

    /* ---------------- real-time delivery polling ---------------- */

    pollDeliveryStatus(email, purpose) {
        if (this.pollTimer) clearInterval(this.pollTimer);
        let attempts = 0;
        const maxAttempts = 30; // 30 seconds max
        
        this.pollTimer = setInterval(async () => {
            attempts++;
            try {
                const data = await this.post({ 
                    action: 'status', 
                    email: email, 
                    purpose: purpose 
                });
                
                if (data.delivery && data.delivery.status) {
                    this.renderGmail(data); // Updates delivery badge in modal
                    
                    if (data.delivery.status === 'sent') {
                        clearInterval(this.pollTimer);
                        this.showAlert('success', 'Code delivered to ' + email + '. Check your Gmail inbox.');
                    } else if (data.delivery.status === 'failed') {
                        clearInterval(this.pollTimer);
                        this.showAlert('error', 'Delivery failed: ' + data.delivery.message);
                    }
                }
                
                if (attempts >= maxAttempts) {
                    clearInterval(this.pollTimer);
                }
            } catch (e) {
                // Ignore polling errors
                if (attempts >= maxAttempts) {
                    clearInterval(this.pollTimer);
                }
            }
        }, 1000);
    },

    /* ---------------- transport ---------------- */

    async post(payload) {
        let resp;
        try {
            resp = await fetch(this.cfg.endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.cfg.csrf || window.CSRF_TOKEN || ''
                },
                body: JSON.stringify(payload)
            });
        } catch (e) {
            throw new Error('Network error - the server could not be reached.');
        }

        let data = null;
        try {
            data = await resp.json();
        } catch (e) {
            data = null;
        }

        if (!data) {
            throw new Error('Unexpected server response (' + resp.status + '). Please try again.');
        }
        if (!resp.ok && !data.error) {
            throw new Error('Request failed (' + resp.status + '). Please try again.');
        }
        return data;
    },

    /** Test email configuration - can be called from console */
    async testEmailConfig() {
        try {
            const data = await this.post({ action: 'test-config' });
            console.log('Email config test:', data);
            return data;
        } catch (e) {
            console.error('Email config test failed:', e);
            throw e;
        }
    }
};

document.addEventListener('DOMContentLoaded', () => OtpAuth.init());
