<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="theme-color" content="#030712">
<title>QR Scanner — NAYI UDAAN LIBRARY</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<!-- jsQR for QR decoding -->
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<style>
*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;-webkit-font-smoothing:antialiased}

:root{
  --bg:#030712;
  --ok:#00e58a;
  --ok-dim:rgba(0,229,138,.15);
  --warn:#fbbf24;
  --err:#ff4d6d;
  --ac:#6ee7f7;
  --ac-dim:rgba(110,231,247,.1);
  --tx:#f0f9ff;
  --tx2:#94a3b8;
  --safe-top:env(safe-area-inset-top,0px);
  --safe-bottom:env(safe-area-inset-bottom,0px);
}

html,body{
  width:100%;height:100%;
  background:var(--bg);
  overflow:hidden;
  font-family:'Outfit',sans-serif;
  color:var(--tx);
}

/* ── FULL-SCREEN VIDEO ── */
#video{
  position:fixed;inset:0;
  width:100%;height:100%;
  object-fit:cover;
  transform:scaleX(-1); /* mirror for front cam; removed for back cam */
  z-index:0;
}
#canvas{display:none;}

/* ── DARK OVERLAY ── */
.overlay{
  position:fixed;inset:0;z-index:1;
  background:rgba(0,0,0,.45);
}

/* ── SCAN WINDOW CUTOUT ── */
.scan-area{
  position:fixed;
  top:50%;left:50%;
  transform:translate(-50%,-58%);
  z-index:2;
  display:flex;flex-direction:column;align-items:center;
}

.scan-frame{
  width:260px;height:260px;
  position:relative;
}

/* Corner brackets */
.corner{
  position:absolute;
  width:36px;height:36px;
  border-color:var(--ac);
  border-style:solid;
}
.corner.tl{top:0;left:0;border-width:3px 0 0 3px;border-radius:6px 0 0 0;}
.corner.tr{top:0;right:0;border-width:3px 3px 0 0;border-radius:0 6px 0 0;}
.corner.bl{bottom:0;left:0;border-width:0 0 3px 3px;border-radius:0 0 0 6px;}
.corner.br{bottom:0;right:0;border-width:0 3px 3px 0;border-radius:0 0 6px 0;}

/* Laser scan line */
.scan-line{
  position:absolute;
  left:6px;right:6px;
  height:2px;
  background:linear-gradient(90deg,transparent,var(--ac),transparent);
  box-shadow:0 0 8px var(--ac),0 0 20px rgba(110,231,247,.4);
  animation:scanMove 2.2s ease-in-out infinite;
  top:0;
}
@keyframes scanMove{
  0%{top:8px;opacity:0;}
  10%{opacity:1;}
  90%{opacity:1;}
  100%{top:calc(100% - 8px);opacity:0;}
}

/* Corner pulse glow */
.scan-frame::before{
  content:'';
  position:absolute;inset:-2px;
  border-radius:8px;
  background:transparent;
  box-shadow:0 0 0 1px rgba(110,231,247,.15);
  animation:framePulse 2.5s ease-in-out infinite;
}
@keyframes framePulse{
  0%,100%{box-shadow:0 0 0 1px rgba(110,231,247,.1);}
  50%{box-shadow:0 0 0 1px rgba(110,231,247,.35),0 0 24px rgba(110,231,247,.08);}
}

/* ── TOP BAR ── */
.topbar{
  position:fixed;
  top:0;left:0;right:0;
  padding:calc(var(--safe-top) + 14px) 18px 14px;
  z-index:10;
  display:flex;align-items:center;justify-content:space-between;
  background:linear-gradient(180deg,rgba(3,7,18,.9) 0%,transparent 100%);
}

.brand{display:flex;align-items:center;gap:10px;}
.brand-icon{
  width:38px;height:38px;border-radius:11px;
  background:linear-gradient(135deg,var(--ac-dim),rgba(110,231,247,.2));
  border:1px solid rgba(110,231,247,.25);
  display:flex;align-items:center;justify-content:center;
  font-size:18px;
}
.brand-name{
  font-size:14px;font-weight:700;letter-spacing:-.2px;
  line-height:1.1;
}
.brand-sub{font-size:10px;color:var(--tx2);font-weight:500;letter-spacing:.5px;text-transform:uppercase;}

.cam-toggle{
  background:rgba(255,255,255,.08);
  border:1px solid rgba(255,255,255,.12);
  border-radius:20px;
  color:var(--tx);font-size:12px;font-weight:600;
  padding:7px 14px;cursor:pointer;
  font-family:'Outfit',sans-serif;
  transition:all .2s;
  display:flex;align-items:center;gap:5px;
}
.cam-toggle:active{background:rgba(255,255,255,.15);}

/* ── HINT TEXT ── */
.hint-wrap{
  margin-top:16px;
  text-align:center;
}
.hint{
  display:inline-flex;align-items:center;gap:6px;
  background:rgba(0,0,0,.5);
  border:1px solid rgba(255,255,255,.1);
  backdrop-filter:blur(10px);
  border-radius:20px;
  padding:8px 16px;
  font-size:12px;font-weight:500;color:var(--tx2);
}
.hint-dot{
  width:6px;height:6px;border-radius:50%;
  background:var(--ok);
  animation:blink 1.4s ease infinite;
}
@keyframes blink{0%,100%{opacity:1;}50%{opacity:.3;}}

/* ── STATUS PILL ── */
.status-pill{
  position:fixed;
  bottom:calc(var(--safe-bottom) + 30px);
  left:50%;transform:translateX(-50%);
  z-index:10;
  min-width:220px;max-width:320px;
  background:rgba(3,7,18,.85);
  border:1px solid rgba(255,255,255,.1);
  backdrop-filter:blur(16px);
  border-radius:18px;
  padding:14px 20px;
  text-align:center;
  transition:all .3s ease;
}
.status-pill.ok{border-color:rgba(0,229,138,.35);background:rgba(0,20,12,.85);}
.status-pill.err{border-color:rgba(255,77,109,.35);background:rgba(20,5,10,.85);}
.status-pill.scanning{border-color:rgba(110,231,247,.2);}

.pill-icon{font-size:28px;margin-bottom:6px;display:block;}
.pill-title{font-size:14px;font-weight:700;margin-bottom:3px;}
.pill-sub{font-size:11px;color:var(--tx2);font-weight:400;}
.pill-sub.ok{color:var(--ok);}
.pill-sub.err{color:var(--err);}

/* ── SUCCESS FLASH ── */
.flash{
  position:fixed;inset:0;z-index:20;
  background:rgba(0,229,138,.15);
  pointer-events:none;
  opacity:0;
  transition:opacity .1s;
}
.flash.show{opacity:1;}

/* ── LOADING SCREEN ── */
#loadingScreen{
  position:fixed;inset:0;z-index:50;
  background:var(--bg);
  display:flex;flex-direction:column;
  align-items:center;justify-content:center;
  gap:16px;
}
.loader-icon{font-size:48px;animation:bounce .8s ease infinite alternate;}
@keyframes bounce{from{transform:translateY(0);}to{transform:translateY(-10px);}}
.loader-text{font-size:15px;font-weight:600;color:var(--tx2);}
.loader-sub{font-size:12px;color:var(--tx2);opacity:.6;margin-top:-8px;}

/* ── PERMISSION ERROR ── */
#permError{
  position:fixed;inset:0;z-index:50;
  background:var(--bg);
  display:none;flex-direction:column;
  align-items:center;justify-content:center;
  padding:32px;text-align:center;gap:14px;
}
.perm-icon{font-size:56px;}
.perm-title{font-size:20px;font-weight:800;}
.perm-msg{font-size:14px;color:var(--tx2);line-height:1.6;max-width:300px;}
.perm-btn{
  background:var(--ac);color:#000;
  border:none;border-radius:14px;
  padding:13px 28px;
  font-family:'Outfit',sans-serif;
  font-size:14px;font-weight:700;
  cursor:pointer;margin-top:6px;
}

/* ── COUNT BADGE ── */
.count-badge{
  position:fixed;
  top:calc(var(--safe-top) + 14px);
  right:18px;
  z-index:15;
  display:none;
}
.count-inner{
  background:var(--ok);
  color:#000;
  border-radius:12px;
  padding:5px 12px;
  font-size:12px;font-weight:800;
  display:flex;align-items:center;gap:5px;
}

@media(min-width:500px){
  .scan-frame{width:300px;height:300px;}
}
</style>
</head>
<body>

<video id="video" autoplay playsinline muted></video>
<canvas id="canvas"></canvas>
<div class="overlay"></div>
<div class="flash" id="flash"></div>

<!-- Loading -->
<div id="loadingScreen">
  <div class="loader-icon">📷</div>
  <div class="loader-text">Starting Camera…</div>
  <div class="loader-sub">Allow camera access when prompted</div>
</div>

<!-- Permission Error -->
<div id="permError">
  <div class="perm-icon">🚫</div>
  <div class="perm-title">Camera Access Denied</div>
  <div class="perm-msg">Please allow camera permission in your browser settings, then reload this page.</div>
  <button class="perm-btn" onclick="location.reload()">🔄 Try Again</button>
</div>

<!-- Top Bar -->
<div class="topbar">
  <div class="brand">
    <div class="brand-icon">📚</div>
    <div>
      <div class="brand-name">QR Scanner</div>
      <div class="brand-sub">NAYI UDAAN LIBRARY</div>
    </div>
  </div>
  <button class="cam-toggle" onclick="toggleCamera()">🔄 Flip</button>
</div>

<!-- Scan Area -->
<div class="scan-area">
  <div class="scan-frame">
    <div class="corner tl"></div>
    <div class="corner tr"></div>
    <div class="corner bl"></div>
    <div class="corner br"></div>
    <div class="scan-line"></div>
  </div>
  <div class="hint-wrap">
    <div class="hint"><div class="hint-dot"></div> Point camera at student's QR code</div>
  </div>
</div>

<!-- Status Pill -->
<div class="status-pill scanning" id="statusPill">
  <span class="pill-icon">🔍</span>
  <div class="pill-title">Ready to Scan</div>
  <div class="pill-sub">Waiting for QR code…</div>
</div>

<!-- Scan count -->
<div class="count-badge" id="countBadge">
  <div class="count-inner">✅ <span id="countNum">0</span> scanned today</div>
</div>

<script>
const video    = document.getElementById('video');
const canvas   = document.getElementById('canvas');
const ctx      = canvas.getContext('2d');
const pill     = document.getElementById('statusPill');
const flash    = document.getElementById('flash');
const loading  = document.getElementById('loadingScreen');
const permErr  = document.getElementById('permError');
const countBadge = document.getElementById('countBadge');
const countNum   = document.getElementById('countNum');

let scanning    = true;
let cooldown    = false;
let facingMode  = 'environment'; // back camera
let scanCount   = parseInt(sessionStorage.getItem('scanCount') || '0');
let stream      = null;
let animFrame   = null;

// Show scan count
function updateCount() {
  if (scanCount > 0) {
    countNum.textContent = scanCount;
    countBadge.style.display = 'block';
  }
}
updateCount();

// ── START CAMERA ──
async function startCamera() {
  loading.style.display = 'flex';
  if (stream) { stream.getTracks().forEach(t => t.stop()); }
  if (animFrame) cancelAnimationFrame(animFrame);

  try {
    stream = await navigator.mediaDevices.getUserMedia({
      video: {
        facingMode: facingMode,
        width:  { ideal: 1280 },
        height: { ideal: 720 },
      },
      audio: false
    });
    video.srcObject = stream;
    video.setAttribute('playsinline', true);
    await video.play();

    // Mirror only for front camera
    video.style.transform = facingMode === 'user' ? 'scaleX(-1)' : 'scaleX(1)';

    loading.style.display = 'none';
    scanning = true;
    tick();
  } catch (err) {
    loading.style.display = 'none';
    if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
      permErr.style.display = 'flex';
    } else {
      // Try front camera as fallback
      if (facingMode === 'environment') {
        facingMode = 'user';
        startCamera();
      } else {
        permErr.style.display = 'flex';
      }
    }
  }
}

// ── SCAN LOOP ──
function tick() {
  if (!scanning) return;
  animFrame = requestAnimationFrame(tick);

  if (video.readyState !== video.HAVE_ENOUGH_DATA) return;

  canvas.width  = video.videoWidth;
  canvas.height = video.videoHeight;
  ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

  const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
  const code = jsQR(imageData.data, imageData.width, imageData.height, {
    inversionAttempts: 'dontInvert',
  });

  if (code && code.data && !cooldown) {
    onQRFound(code.data);
  }
}

// ── QR FOUND ──
function onQRFound(url) {
  // Only handle URLs from our own domain
  if (!url.includes('scan.php') && !url.includes('token=')) {
    showPill('warn', '⚠️', 'Invalid QR', 'Not a valid student QR code');
    return;
  }

  cooldown = true;
  beep();
  flashScreen();

  // Extract token
  let token = '';
  try {
    const u = new URL(url);
    token = u.searchParams.get('token') || '';
  } catch(e) {
    const m = url.match(/token=([a-f0-9]+)/i);
    token = m ? m[1] : '';
  }

  if (!token) {
    showPill('err', '❌', 'Invalid QR', 'Could not read token');
    setTimeout(() => { cooldown = false; showPill('scanning','🔍','Ready to Scan','Waiting for QR code…'); }, 3000);
    return;
  }

  showPill('ok', '✅', 'QR Detected!', 'Processing attendance…');

  // Call API directly and show result inline
  fetch(`scan.php?token=${encodeURIComponent(token)}`, { method: 'GET' })
    .then(r => r.text())
    .then(html => {
      // Parse result from PHP response
      const parser = new DOMParser();
      const doc = parser.parseFromString(html, 'text/html');

      const actionLabel = doc.querySelector('.action-label')?.textContent?.trim() || '';
      const stuName     = doc.querySelector('.stu-name')?.textContent?.trim() || '';
      const headClass   = doc.querySelector('.card-head')?.className || '';

      if (actionLabel.includes('CHECKED IN')) {
        showPill('ok', '✅', stuName || 'Checked In!', 'Check-in recorded successfully');
        scanCount++;
        sessionStorage.setItem('scanCount', scanCount);
        updateCount();
      } else if (actionLabel.includes('CHECKED OUT')) {
        showPill('ok', '👋', stuName || 'Checked Out!', 'Check-out recorded successfully');
        scanCount++;
        sessionStorage.setItem('scanCount', scanCount);
        updateCount();
      } else if (actionLabel.includes('ALREADY')) {
        showPill('warn', '☑️', stuName || 'Already Done', 'Attendance complete for today');
      } else if (doc.querySelector('.card-head.err')) {
        const errMsg = doc.querySelector('.err-msg')?.textContent?.trim() || 'Error processing QR';
        showPill('err', '❌', 'Error', errMsg.substring(0,60));
      } else {
        showPill('ok', '✅', stuName || 'Done!', 'Attendance recorded');
        scanCount++;
        sessionStorage.setItem('scanCount', scanCount);
        updateCount();
      }

      setTimeout(() => {
        cooldown = false;
        showPill('scanning', '🔍', 'Ready to Scan', 'Waiting for QR code…');
      }, 3500);
    })
    .catch(() => {
      showPill('err', '❌', 'Network Error', 'Could not reach server');
      setTimeout(() => {
        cooldown = false;
        showPill('scanning', '🔍', 'Ready to Scan', 'Waiting for QR code…');
      }, 3000);
    });
}

function showPill(type, icon, title, sub) {
  pill.className = 'status-pill ' + (type === 'scanning' ? 'scanning' : type === 'ok' ? 'ok' : 'err');
  pill.innerHTML = `
    <span class="pill-icon">${icon}</span>
    <div class="pill-title">${title}</div>
    <div class="pill-sub ${type === 'ok' ? 'ok' : type === 'err' ? 'err' : ''}">${sub}</div>
  `;
}

function flashScreen() {
  flash.classList.add('show');
  setTimeout(() => flash.classList.remove('show'), 200);
}

function beep() {
  try {
    const ctx2  = new (window.AudioContext || window.webkitAudioContext)();
    const osc   = ctx2.createOscillator();
    const gain  = ctx2.createGain();
    osc.connect(gain);
    gain.connect(ctx2.destination);
    osc.frequency.value = 1046; // high C
    osc.type = 'sine';
    gain.gain.setValueAtTime(0, ctx2.currentTime);
    gain.gain.linearRampToValueAtTime(0.3, ctx2.currentTime + 0.01);
    gain.gain.exponentialRampToValueAtTime(0.001, ctx2.currentTime + 0.25);
    osc.start(ctx2.currentTime);
    osc.stop(ctx2.currentTime + 0.3);
  } catch(e){}
}

function toggleCamera() {
  facingMode = facingMode === 'environment' ? 'user' : 'environment';
  startCamera();
}

// ── START ──
startCamera();

// Keep screen awake (where supported)
if ('wakeLock' in navigator) {
  navigator.wakeLock.request('screen').catch(() => {});
}

// Re-acquire wake lock on visibility change
document.addEventListener('visibilitychange', () => {
  if (document.visibilityState === 'visible') {
    if ('wakeLock' in navigator) navigator.wakeLock.request('screen').catch(() => {});
  }
});
</script>
</body>
</html>
