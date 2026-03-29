# 📱 OPTMS WhatsApp Server — Setup Guide

Sends WhatsApp messages **directly from your own number** — no API keys, no third-party services.

---

## How It Works

```
Your WhatsApp Phone
      ↕  (stays linked)
Node.js Server  ←→  PHP ERP Backend
  (localhost:3001)      (your server)
```

1. Run the Node server on your computer
2. Scan QR once — your WhatsApp links (like WhatsApp Web)
3. The ERP sends messages through this server
4. Messages appear **sent from your WhatsApp number** ✅

---

## Quick Start

### Step 1 — Install Node.js
Download from **https://nodejs.org** (choose LTS version)

### Step 2 — Start the server

**Windows:**
```
Double-click  START-WINDOWS.bat
```

**Mac / Linux:**
```bash
chmod +x start.sh
./start.sh
```

### Step 3 — Scan QR
Open **http://localhost:3001** in your browser → scan QR with WhatsApp

### Step 4 — Done!
Go to your ERP → WhatsApp page → messages now send directly ✅

---

## Manual Commands

```bash
# Install dependencies (first time only)
npm install

# Start server
node server.js

# Start with auto-restart on crash (recommended for production)
npm install -g pm2
pm2 start server.js --name optms-wa
pm2 save
pm2 startup
```

---

## API Endpoints

The server runs at `http://localhost:3001`

| Method | Path | Description |
|--------|------|-------------|
| GET | `/` | Status page (open in browser) |
| GET | `/status` | JSON connection status + QR |
| GET | `/qr` | QR code as PNG image |
| POST | `/send` | Send single message |
| POST | `/send-bulk` | Send bulk messages |
| POST | `/logout` | Disconnect WhatsApp |
| POST | `/reconnect` | Force reconnect |

### Send a message
```bash
curl -X POST http://localhost:3001/send \
  -H "Content-Type: application/json" \
  -d '{"to": "919876543210", "message": "Hello!"}'
```

---

## Running on a Server (VPS/Linux)

If your PHP server is not on the same machine, run this on the **same machine as PHP**:

```bash
# Install Node + npm
sudo apt install nodejs npm

# Go to whatsapp-server folder
cd /path/to/whatsapp-server
npm install

# Run with PM2 (auto-restart)
npm install -g pm2
pm2 start server.js --name optms-wa
pm2 save && pm2 startup
```

Then update `WA_NODE` in `api/whatsapp.php` if needed:
```php
define('WA_NODE', 'http://127.0.0.1:3001');  // same machine
// or:
define('WA_NODE', 'http://192.168.1.x:3001'); // local network
```

---

## Troubleshooting

**"Server Offline" in ERP**
→ Node server isn't running. Double-click `START-WINDOWS.bat`

**QR keeps refreshing / not scanning**
→ Make sure your phone has internet. Try `npm install` again.

**"Session disconnected"**
→ WhatsApp unlinked after inactivity. Rescan QR in browser.

**Messages not sending**
→ Check the phone number includes country code (e.g., `919876543210` not `9876543210`)

**Port 3001 in use**
→ Edit `server.js`, change `const PORT = 3001` to any free port, and update `api/whatsapp.php` too.

---

## Session Persistence

The session is saved in `.wwebjs_auth/` folder. You only need to scan QR **once** — it auto-reconnects on restart.

To reset: delete the `.wwebjs_auth/` folder and rescan.

---

## Requirements

- Node.js 16+ 
- ~200MB disk (Chromium is bundled)
- WhatsApp account on your phone
- The computer must stay on while sending messages
