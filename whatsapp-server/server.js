/**
 * ═══════════════════════════════════════════════════════
 *  OPTMS Tech ERP — WhatsApp Local Server
 *  Uses whatsapp-web.js to send from YOUR number
 *  Run: node server.js
 *  Then scan QR in browser: http://localhost:3001
 * ═══════════════════════════════════════════════════════
 */

const { Client, LocalAuth } = require('whatsapp-web.js');
const express  = require('express');
const cors     = require('cors');
const qrcode   = require('qrcode');
const fs       = require('fs');
const path     = require('path');

const app  = express();
const PORT = 3001;

app.use(cors({ origin: '*', methods: ['GET','POST','OPTIONS'] }));
app.use(express.json());
// Explicit CORS for direct browser fetch calls
app.use((req, res, next) => {
  res.header('Access-Control-Allow-Origin', '*');
  res.header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
  res.header('Access-Control-Allow-Headers', 'Content-Type');
  if (req.method === 'OPTIONS') return res.sendStatus(204);
  next();
});

// ── State ──────────────────────────────────────────────
let currentQR     = null;
let isReady       = false;
let isInitializing= false;
let clientInfo    = null;
let msgSentCount  = 0;
let msgLog        = [];
let client        = null;

// ── Init WhatsApp Client ───────────────────────────────
function initClient() {
  if (isInitializing) return;
  isInitializing = true;
  currentQR = null;
  isReady   = false;

  client = new Client({
    authStrategy: new LocalAuth({ dataPath: path.join(__dirname, '.wwebjs_auth') }),
    puppeteer: {
      headless: true,
      args: [
        '--no-sandbox',
        '--disable-setuid-sandbox',
        '--disable-dev-shm-usage',
        '--disable-accelerated-2d-canvas',
        '--no-first-run',
        '--no-zygote',
        '--disable-gpu',
      ],
    },
  });

  client.on('qr', async (qr) => {
    console.log('📱 QR received — scan in browser at http://localhost:' + PORT);
    currentQR = await qrcode.toDataURL(qr);
    isReady   = false;
  });

  client.on('authenticated', () => {
    console.log('✅ WhatsApp authenticated!');
    currentQR = null;
  });

  client.on('ready', async () => {
    isReady       = true;
    isInitializing= false;
    currentQR     = null;
    const info    = client.info;
    clientInfo    = {
      name  : info.pushname,
      number: info.wid.user,
      platform: info.platform,
    };
    console.log(`✅ WhatsApp Ready! Connected as: ${clientInfo.name} (+${clientInfo.number})`);
  });

  client.on('disconnected', (reason) => {
    console.log('❌ WhatsApp disconnected:', reason);
    isReady       = false;
    isInitializing= false;
    clientInfo    = null;
    currentQR     = null;
    // Auto-reconnect after 5s
    setTimeout(initClient, 5000);
  });

  client.on('auth_failure', (msg) => {
    console.error('❌ Auth failure:', msg);
    isInitializing = false;
  });

  client.initialize();
}

// ── Start client on launch ─────────────────────────────
initClient();

// ═══════════════════════════════════════════════════════
// API ROUTES
// ═══════════════════════════════════════════════════════

// GET /status — connection status + QR
app.get('/status', async (req, res) => {
  res.json({
    connected  : isReady,
    initializing: isInitializing && !currentQR,
    qr         : currentQR,         // base64 PNG data URL
    client     : clientInfo,
    sent_count : msgSentCount,
  });
});

// GET /qr — just the QR image as PNG
app.get('/qr', async (req, res) => {
  if (!currentQR) {
    return res.status(404).json({ error: isReady ? 'Already connected — no QR needed' : 'QR not ready yet, please wait…' });
  }
  const base64 = currentQR.replace(/^data:image\/png;base64,/, '');
  res.set('Content-Type', 'image/png');
  res.send(Buffer.from(base64, 'base64'));
});

// POST /send — send a single message
app.post('/send', async (req, res) => {
  const { to, message } = req.body;
  if (!to || !message) return res.status(400).json({ success: false, error: 'to and message are required' });
  if (!isReady)         return res.status(503).json({ success: false, error: 'WhatsApp not connected. Scan QR first.' });

  try {
    // Normalize phone: strip non-digits, add @c.us suffix
    const phone   = to.replace(/\D/g, '');
    const chatId  = phone.includes('@') ? phone : phone + '@c.us';
    const sent    = await client.sendMessage(chatId, message);
    msgSentCount++;
    msgLog.unshift({ time: new Date().toISOString(), to: phone, preview: message.slice(0, 60), id: sent.id._serialized });
    if (msgLog.length > 100) msgLog = msgLog.slice(0, 100);
    console.log(`✅ Sent to ${phone}: ${message.slice(0, 40)}…`);
    res.json({ success: true, message_id: sent.id._serialized });
  } catch (err) {
    console.error('Send error:', err.message);
    res.status(500).json({ success: false, error: err.message });
  }
});

// POST /send-bulk — send to multiple recipients
app.post('/send-bulk', async (req, res) => {
  const { messages } = req.body; // [{to, message, name}]
  if (!Array.isArray(messages) || !messages.length) return res.status(400).json({ success:false, error:'messages array required' });
  if (!isReady) return res.status(503).json({ success:false, error:'WhatsApp not connected. Scan QR first.' });

  const results = [];
  for (const m of messages) {
    const phone  = (m.to || '').replace(/\D/g, '');
    const chatId = phone + '@c.us';
    try {
      const sent = await client.sendMessage(chatId, m.message);
      msgSentCount++;
      results.push({ to: phone, name: m.name, success: true, message_id: sent.id._serialized });
      msgLog.unshift({ time: new Date().toISOString(), to: phone, preview: m.message.slice(0,60) });
      console.log(`✅ Bulk sent to ${m.name} (${phone})`);
    } catch (err) {
      console.error(`❌ Failed to ${phone}:`, err.message);
      results.push({ to: phone, name: m.name, success: false, error: err.message });
    }
    // 1.5s delay between messages to avoid WhatsApp spam detection
    await new Promise(r => setTimeout(r, 1500));
  }

  const sent   = results.filter(r => r.success).length;
  const failed = results.length - sent;
  res.json({ success: true, sent, failed, results });
});

// GET /log — recent send log
app.get('/log', (req, res) => res.json(msgLog.slice(0, 50)));

// POST /logout — disconnect WhatsApp
app.post('/logout', async (req, res) => {
  try {
    await client.logout();
    isReady    = false;
    clientInfo = null;
    res.json({ success: true });
  } catch (e) {
    res.json({ success: false, error: e.message });
  }
});

// POST /reconnect — force restart client
app.post('/reconnect', (req, res) => {
  try { client.destroy(); } catch(e) {}
  isReady       = false;
  isInitializing= false;
  clientInfo    = null;
  currentQR     = null;
  setTimeout(initClient, 1000);
  res.json({ success: true, message: 'Reconnecting…' });
});

// ── Simple status page ─────────────────────────────────
app.get('/', (req, res) => {
  res.send(`<!DOCTYPE html>
<html>
<head>
  <title>OPTMS WhatsApp Server</title>
  <meta charset="UTF-8">
  <meta http-equiv="refresh" content="30">
  <style>
    *{margin:0;padding:0;box-sizing:border-box}
    body{font-family:'Segoe UI',sans-serif;background:#0a0f1e;color:#e0e6f0;min-height:100vh;display:flex;align-items:center;justify-content:center}
    .card{background:#131929;border:1px solid #1e2d4a;border-radius:16px;padding:36px;max-width:440px;width:90%;text-align:center;box-shadow:0 8px 40px rgba(0,0,0,.5)}
    .logo{font-size:40px;margin-bottom:10px}
    h1{font-size:20px;color:#7dd3b0;margin-bottom:4px}
    .sub{font-size:12px;color:#5a7a9a;margin-bottom:24px}
    .status{display:inline-flex;align-items:center;gap:8px;padding:8px 18px;border-radius:20px;font-size:13px;font-weight:600;margin-bottom:24px}
    .ok{background:rgba(37,211,102,.15);color:#25d366;border:1px solid rgba(37,211,102,.3)}
    .no{background:rgba(220,50,50,.12);color:#f87171;border:1px solid rgba(220,50,50,.25)}
    .wait{background:rgba(250,200,50,.1);color:#fbbf24;border:1px solid rgba(250,200,50,.25)}
    #qr-wrap img{border-radius:12px;border:3px solid #25d366;max-width:220px}
    .info{background:#0d1526;border-radius:10px;padding:14px;margin-top:18px;font-size:12px;color:#5a7a9a;text-align:left;line-height:1.8}
    .info strong{color:#7dd3b0}
    .stat{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #1e2d4a;font-size:12px}
    .stat:last-child{border-bottom:none}
    .pill{background:#1e3a2f;color:#25d366;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:700}
  </style>
</head>
<body>
<div class="card">
  <div class="logo">📱</div>
  <h1>OPTMS WhatsApp Server</h1>
  <div class="sub">Running on port ${PORT} · Auto-refreshing every 3s</div>
  <div id="status-badge" class="status wait">⏳ Checking…</div>
  <div id="qr-wrap"></div>
  <div class="info" id="info-box">Loading…</div>
</div>
<script>
async function refresh() {
  const d = await fetch('/status').then(r=>r.json()).catch(()=>null);
  if (!d) return;
  const badge = document.getElementById('status-badge');
  const qrWrap = document.getElementById('qr-wrap');
  const info = document.getElementById('info-box');
  if (d.connected) {
    badge.className = 'status ok';
    badge.textContent = '✅ Connected as ' + d.client?.name + ' (+' + d.client?.number + ')';
    qrWrap.innerHTML = '';
    info.innerHTML = '<div class="stat"><span>Status</span><span class="pill">READY</span></div>' +
      '<div class="stat"><span>Platform</span><span>' + (d.client?.platform||'—') + '</span></div>' +
      '<div class="stat"><span>Messages Sent</span><span>' + d.sent_count + '</span></div>' +
      '<div class="stat"><span>API Endpoint</span><span>http://localhost:${PORT}/send</span></div>';
  } else if (d.qr) {
    badge.className = 'status no';
    badge.textContent = '📱 Scan QR to connect';
    qrWrap.innerHTML = '<p style="font-size:12px;color:#5a7a9a;margin-bottom:10px">Open WhatsApp → Linked Devices → Link a Device</p><img src="/qr?t='+Date.now()+'" alt="QR"><br><br>';
    info.innerHTML = '<div class="stat"><span>Status</span><span style="color:#fbbf24">Waiting for scan…</span></div>';
  } else {
    badge.className = 'status wait';
    badge.textContent = '⏳ Initializing…';
    qrWrap.innerHTML = '';
    info.innerHTML = '<div class="stat"><span>Status</span><span style="color:#fbbf24">Starting up, please wait…</span></div>';
  }
}
refresh();
</script>
</body>
</html>`);
});

// ── Start server ───────────────────────────────────────
app.listen(PORT, () => {
  console.log(`\n🚀 OPTMS WhatsApp Server running at http://localhost:${PORT}`);
  console.log(`   Open that URL in your browser to scan QR code\n`);
});
