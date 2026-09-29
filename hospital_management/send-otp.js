const nodemailer = require('nodemailer');
const fs = require('fs');
const path = require('path');

// Load config
const configPath = path.join(__dirname, 'email-config.json');
let config = {};
if (fs.existsSync(configPath)) {
  try {
    config = JSON.parse(fs.readFileSync(configPath, 'utf8'));
  } catch (e) {
    console.error('ERROR: Failed to parse email-config.json: ' + e.message);
    process.exit(1);
  }
} else {
  console.error('ERROR: email-config.json not found at ' + configPath);
  process.exit(1);
}

// Priority: CLI args > env > config file
const args = process.argv.slice(2);
const to = args[0];
const subject = args[1];
const html = args[2];
const text = args[3];

if (!to || !subject || !html) {
  console.error('Usage: node send-otp.js <to> <subject> <html> [text]');
  process.exit(1);
}

// Build transporter from config
function createTransporter() {
  // Gmail with App Password
  if (config.gmail && config.gmail.user && config.gmail.pass && config.gmail.pass !== 'YOUR_16_CHAR_APP_PASSWORD_HERE') {
    return nodemailer.createTransport({
      service: 'gmail',
      auth: {
        user: config.gmail.user,
        pass: config.gmail.pass
      }
    });
  }
  // Generic SMTP
  if (config.smtp && config.smtp.user && config.smtp.pass) {
    return nodemailer.createTransport({
      host: config.smtp.host || 'smtp.gmail.com',
      port: config.smtp.port || 587,
      secure: config.smtp.secure || false,
      auth: {
        user: config.smtp.user,
        pass: config.smtp.pass
      }
    });
  }
  // SendGrid API
  if (config.sendgrid && config.sendgrid.apiKey) {
    return nodemailer.createTransport({
      host: 'smtp.sendgrid.net',
      port: 587,
      auth: {
        user: 'apikey',
        pass: config.sendgrid.apiKey
      }
    });
  }
  // Mailgun
  if (config.mailgun && config.mailgun.user && config.mailgun.pass) {
    return nodemailer.createTransport({
      host: 'smtp.mailgun.org',
      port: 587,
      auth: {
        user: config.mailgun.user,
        pass: config.mailgun.pass
      }
    });
  }
  throw new Error('No valid email transport configured. Edit email-config.json with valid credentials.');
}

async function send() {
  try {
    const transporter = createTransporter();
    const from = config.from || config.gmail?.user || config.smtp?.user || 'no-reply@localhost';
    const fromName = config.fromName || 'Lotus Women\'s Hospital';

    const info = await transporter.sendMail({
      from: `"${fromName}" <${from}>`,
      to: to,
      subject: subject,
      html: html,
      text: text || html.replace(/<[^>]+>/g, '')
    });

    console.log('SENT:' + info.messageId);
    process.exit(0);
  } catch (err) {
    console.error('ERROR:' + err.message);
    process.exit(1);
  }
}

send();