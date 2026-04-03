<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="theme-color" content="#1e1b4b">
<title>Student App — NAYI UDAAN LIBRARY</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<style>
*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent}
:root{
  --bg:#0f0e17;
  --bg2:#1a1830;
  --bg3:#24224a;
  --ac:#6c63ff;--ac2:#5a52e0;
  --ok:#22c55e;--warn:#f59e0b;--err:#ef4444;
  --tx:#f0effe;--tx2:#a89ec9;--tx3:#6b63a3;
  --sf:rgba(255,255,255,.06);--sf2:rgba(255,255,255,.1);
  --br:rgba(255,255,255,.08);
  --r:16px;--r2:12px;
}
html,body{height:100%;background:var(--bg);color:var(--tx);font-family:'DM Sans',sans-serif;overflow-x:hidden}

/* ── MESH BACKGROUND ── */
body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse 80% 60% at 20% -10%,rgba(108,99,255,.25),transparent),radial-gradient(ellipse 60% 50% at 80% 100%,rgba(124,58,237,.18),transparent);pointer-events:none;z-index:0}

/* ── LOGIN SCREEN ── */
#loginScreen{position:fixed;inset:0;z-index:100;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:24px;background:var(--bg)}
.login-logo{width:72px;height:72px;background:linear-gradient(135deg,var(--ac),#7c3aed);border-radius:20px;display:flex;align-items:center;justify-content:center;font-size:36px;margin:0 auto 16px;box-shadow:0 8px 32px rgba(108,99,255,.4)}
.login-title{font-family:'Syne',sans-serif;font-size:24px;font-weight:800;text-align:center;margin-bottom:4px}
.login-sub{font-size:13px;color:var(--tx2);text-align:center;margin-bottom:32px}
.login-card{background:var(--bg2);border:1px solid var(--br);border-radius:20px;padding:24px;width:100%;max-width:360px}
.field{margin-bottom:16px}
.field label{display:block;font-size:12px;font-weight:600;color:var(--tx2);letter-spacing:.8px;text-transform:uppercase;margin-bottom:7px}
.field input{width:100%;background:var(--sf);border:1.5px solid var(--br);border-radius:10px;padding:13px 15px;color:var(--tx);font-size:15px;font-family:'DM Sans',sans-serif;outline:none;transition:border-color .2s}
.field input:focus{border-color:var(--ac)}
.field input::placeholder{color:var(--tx3)}
.login-btn{width:100%;padding:14px;background:linear-gradient(135deg,var(--ac),#7c3aed);border:none;border-radius:12px;color:#fff;font-family:'Syne',sans-serif;font-size:16px;font-weight:700;cursor:pointer;transition:opacity .2s;margin-top:4px}
.login-btn:hover{opacity:.9}
.login-err{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.25);border-radius:10px;padding:11px 14px;font-size:13px;color:#fca5a5;margin-top:12px;display:none;text-align:center}

/* ── MAIN APP ── */
#app{display:none;flex-direction:column;min-height:100vh;position:relative;z-index:1}

/* Top bar */
.topbar{padding:52px 20px 16px;display:flex;align-items:center;justify-content:space-between}
.tb-name{font-family:'Syne',sans-serif;font-size:18px;font-weight:700}
.tb-sub{font-size:12px;color:var(--tx2);margin-top:1px}
.tb-av{width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-size:14px;font-weight:700;color:#fff;flex-shrink:0;border:2px solid rgba(255,255,255,.2)}
.logout-btn{background:none;border:1px solid var(--br);border-radius:8px;color:var(--tx2);font-size:11px;padding:5px 9px;cursor:pointer;font-family:'DM Sans',sans-serif}

/* Scroll content */
.content{padding:0 16px 100px;flex:1}

/* Section title */
.sec-t{font-family:'Syne',sans-serif;font-size:12px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--tx3);margin:20px 0 10px}

/* Stats row */
.stats-row{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:4px}
.stat-box{background:var(--bg2);border:1px solid var(--br);border-radius:14px;padding:14px;text-align:center}
.stat-val{font-family:'Syne',sans-serif;font-size:22px;font-weight:800;margin-bottom:2px}
.stat-val.ok{color:var(--ok)}
.stat-val.warn{color:var(--warn)}
.stat-val.ac{color:var(--ac)}
.stat-lbl{font-size:10px;color:var(--tx3);letter-spacing:.5px;text-transform:uppercase;font-weight:500}

/* QR card */
.qr-card{background:var(--bg2);border:1px solid var(--br);border-radius:20px;padding:20px;margin-bottom:4px;text-align:center;position:relative;overflow:hidden}
.qr-card::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 80% 60% at 50% -10%,rgba(108,99,255,.15),transparent);pointer-events:none}
.qr-label{font-size:12px;color:var(--tx2);margin-bottom:4px}
.qr-title{font-family:'Syne',sans-serif;font-size:17px;font-weight:700;margin-bottom:16px}
.qr-wrap{width:200px;height:200px;background:#fff;border-radius:16px;margin:0 auto 16px;padding:12px;display:flex;align-items:center;justify-content:center;box-shadow:0 8px 32px rgba(0,0,0,.4)}
#qrcode{width:176px;height:176px}
#qrcode canvas,#qrcode img{width:176px!important;height:176px!important;border-radius:6px}
.qr-expiry{font-size:11px;color:var(--tx3);margin-bottom:14px}
.qr-expiry span{color:var(--warn);font-weight:600}
.refresh-btn{display:inline-flex;align-items:center;gap:6px;background:var(--sf2);border:1px solid var(--br);border-radius:10px;color:var(--tx);font-size:13px;font-weight:600;padding:10px 18px;cursor:pointer;font-family:'DM Sans',sans-serif;transition:all .2s}
.refresh-btn:hover{background:rgba(255,255,255,.15)}
.scan-hint{margin-top:12px;font-size:12px;color:var(--tx3);line-height:1.5;background:rgba(108,99,255,.1);border:1px solid rgba(108,99,255,.2);border-radius:10px;padding:10px}

/* Attendance list */
.att-list{display:flex;flex-direction:column;gap:8px}
.att-row{background:var(--bg2);border:1px solid var(--br);border-radius:14px;padding:13px 16px;display:flex;align-items:center;gap:12px}
.att-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0}
.att-dot.present{background:var(--ok);box-shadow:0 0 8px rgba(34,197,94,.5)}
.att-dot.absent{background:var(--err);box-shadow:0 0 8px rgba(239,68,68,.5)}
.att-date{font-family:'Syne',sans-serif;font-size:13px;font-weight:600;flex:1}
.att-times{font-size:11px;color:var(--tx2);margin-top:2px}
.att-tag{font-size:10px;font-weight:700;padding:3px 8px;border-radius:6px;letter-spacing:.5px;text-transform:uppercase}
.att-tag.present{background:rgba(34,197,94,.12);color:var(--ok)}
.att-tag.absent{background:rgba(239,68,68,.12);color:var(--err)}
.att-tag.late{background:rgba(245,158,11,.12);color:var(--warn)}

/* Info card */
.info-card{background:var(--bg2);border:1px solid var(--br);border-radius:16px;padding:16px}
.info-row{display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid rgba(255,255,255,.05)}
.info-row:last-child{border-bottom:none}
.info-lbl{font-size:12px;color:var(--tx3)}
.info-val{font-size:13px;font-weight:600;color:var(--tx);text-align:right;max-width:60%}

/* Fee tag */
.fee-tag{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:700;padding:3px 9px;border-radius:8px}
.fee-tag.paid{background:rgba(34,197,94,.12);color:var(--ok)}
.fee-tag.partial{background:rgba(108,99,255,.12);color:#a78bfa}
.fee-tag.pending{background:rgba(245,158,11,.12);color:var(--warn)}
.fee-tag.overdue{background:rgba(239,68,68,.12);color:var(--err)}

/* Bottom nav */
.bottom-nav{position:fixed;bottom:0;left:0;right:0;background:rgba(15,14,23,.92);backdrop-filter:blur(16px);border-top:1px solid var(--br);padding:8px 0 env(safe-area-inset-bottom);display:flex;justify-content:space-around;z-index:50}
.bn-item{display:flex;flex-direction:column;align-items:center;gap:3px;padding:8px 20px;cursor:pointer;opacity:.5;transition:opacity .2s;border:none;background:none;color:var(--tx);font-family:'DM Sans',sans-serif}
.bn-item.active{opacity:1}
.bn-icon{font-size:22px;line-height:1}
.bn-lbl{font-size:10px;font-weight:600;letter-spacing:.3px}

/* Tab pages */
.tab-page{display:none}.tab-page.active{display:block}

/* Loading */
.loading{text-align:center;padding:40px 20px;color:var(--tx2)}
.spinner{width:36px;height:36px;border:3px solid rgba(255,255,255,.1);border-top:3px solid var(--ac);border-radius:50%;animation:spin 1s linear infinite;margin:0 auto 12px}
@keyframes spin{to{transform:rotate(360deg)}}
@keyframes fadeUp{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
.tab-page.active>*{animation:fadeUp .3s ease both}

/* Empty state */
.empty{text-align:center;padding:36px 20px;color:var(--tx2)}
.empty-icon{font-size:48px;display:block;margin-bottom:10px}
</style>
</head>
<body>

<!-- LOGIN SCREEN -->
<div id="loginScreen">
  <div class="login-logo">📚</div>
  <div class="login-title">Student App</div>
  <div class="login-sub">Sign in to view your QR & attendance</div>
  <div class="login-card">
    <div class="field">
      <label>Student ID</label>
      <input type="text" id="loginId" placeholder="e.g. STU-001" autocapitalize="characters">
    </div>
    <div class="field">
      <label>Phone Number</label>
      <input type="tel" id="loginPhone" placeholder="Your registered phone" inputmode="numeric">
    </div>
    <button class="login-btn" onclick="doLogin()">Sign In →</button>
    <div class="login-err" id="loginErr"></div>
  </div>
</div>

<!-- MAIN APP -->
<div id="app">
  <!-- Top bar -->
  <div class="topbar">
    <div>
      <div class="tb-name" id="appName">Student</div>
      <div class="tb-sub" id="appBatch">Loading…</div>
    </div>
    <div style="display:flex;align-items:center;gap:10px">
      <button class="logout-btn" onclick="doLogout()">Sign Out</button>
      <div class="tb-av" id="appAv" style="background:#6c63ff">S</div>
    </div>
  </div>

  <div class="content">
    <!-- QR TAB -->
    <div class="tab-page active" id="tab-qr">
      <div class="sec-t">Attendance Stats (Last 30 Days)</div>
      <div class="stats-row">
        <div class="stat-box"><div class="stat-val ok" id="statPresent">—</div><div class="stat-lbl">Present</div></div>
        <div class="stat-box"><div class="stat-val warn" id="statAbsent">—</div><div class="stat-lbl">Absent</div></div>
        <div class="stat-box"><div class="stat-val ac" id="statRate">—</div><div class="stat-lbl">Rate</div></div>
      </div>

      <div class="sec-t">Your QR Code</div>
      <div class="qr-card">
        <div class="qr-label">Scan to mark attendance</div>
        <div class="qr-title">📱 Show this to the scanner</div>
        <div class="qr-wrap">
          <div id="qrcode"></div>
        </div>
        <div class="qr-expiry">Valid until: <span id="qrExpiry">—</span></div>
        <button class="refresh-btn" onclick="refreshQR()">🔄 Refresh QR</button>
        <div class="scan-hint">📌 Scan this QR at the library entrance to mark your check-in. Scan again to check-out.</div>
      </div>
    </div>

    <!-- HISTORY TAB -->
    <div class="tab-page" id="tab-history">
      <div class="sec-t">Recent Attendance</div>
      <div class="att-list" id="attList">
        <div class="loading"><div class="spinner"></div>Loading…</div>
      </div>
    </div>

    <!-- PROFILE TAB -->
    <div class="tab-page" id="tab-profile">
      <div class="sec-t">My Details</div>
      <div class="info-card" id="profileCard">
        <div class="loading"><div class="spinner"></div></div>
      </div>
    </div>
  </div>

  <!-- Bottom Nav -->
  <nav class="bottom-nav">
    <button class="bn-item active" onclick="switchTab('qr',this)" id="btn-qr">
      <span class="bn-icon">📱</span><span class="bn-lbl">My QR</span>
    </button>
    <button class="bn-item" onclick="switchTab('history',this)" id="btn-history">
      <span class="bn-icon">📅</span><span class="bn-lbl">History</span>
    </button>
    <button class="bn-item" onclick="switchTab('profile',this)" id="btn-profile">
      <span class="bn-icon">👤</span><span class="bn-lbl">Profile</span>
    </button>
  </nav>
</div>

<script>
const API = 'api/index.php';
let studentData = null;
let qrObj = null;

// ── PERSIST LOGIN ──
const saved = JSON.parse(localStorage.getItem('stu_auth') || 'null');
if (saved) initApp(saved.id, saved.phone);

function doLogin() {
  const id    = document.getElementById('loginId').value.trim().toUpperCase();
  const phone = document.getElementById('loginPhone').value.trim();
  const errEl = document.getElementById('loginErr');
  errEl.style.display = 'none';
  if (!id || !phone) { showLoginErr('Please enter both Student ID and Phone.'); return; }
  fetch(`${API}?action=get_student_qr&student_id=${encodeURIComponent(id)}&phone=${encodeURIComponent(phone)}`)
    .then(r => r.json())
    .then(data => {
      if (data.error) { showLoginErr(data.error); return; }
      localStorage.setItem('stu_auth', JSON.stringify({ id, phone }));
      initApp(id, phone, data);
    })
    .catch(() => showLoginErr('Network error. Please try again.'));
}

function showLoginErr(msg) {
  const el = document.getElementById('loginErr');
  el.textContent = msg;
  el.style.display = 'block';
}

function doLogout() {
  localStorage.removeItem('stu_auth');
  location.reload();
}

// Allow Enter key on login
document.getElementById('loginPhone').addEventListener('keydown', e => { if (e.key==='Enter') doLogin(); });
document.getElementById('loginId').addEventListener('keydown', e => { if (e.key==='Enter') doLogin(); });

function initApp(id, phone, data) {
  document.getElementById('loginScreen').style.display = 'none';
  document.getElementById('app').style.display = 'flex';
  if (data) {
    studentData = data;
    renderApp(data);
  } else {
    // Fetch fresh data
    fetch(`${API}?action=get_student_qr&student_id=${encodeURIComponent(id)}&phone=${encodeURIComponent(phone)}`)
      .then(r => r.json())
      .then(data => {
        if (data.error) { doLogout(); return; }
        studentData = data;
        renderApp(data);
      }).catch(doLogout);
  }
}

function renderApp(data) {
  const stu   = data.student;
  const batch = data.batch;
  const fname = stu.fname || '';
  const lname = stu.lname || '';
  const initials = (fname[0]||'') + (lname[0]||'');

  // Top bar
  document.getElementById('appName').textContent = fname + ' ' + lname;
  document.getElementById('appBatch').textContent = batch ? '🏫 ' + batch.name : stu.id;
  document.getElementById('appAv').textContent = initials.toUpperCase();
  document.getElementById('appAv').style.background = stu.color || '#6c63ff';

  // QR
  renderQR(data.token, data.expires_at, stu.id);

  // Attendance stats
  renderStats(data.attendance || []);

  // History
  renderHistory(data.attendance || []);

  // Profile
  renderProfile(stu, batch);
}

function renderQR(token, expiresAt, studentId) {
  // Build scan URL
  const scanUrl = window.location.origin + window.location.pathname.replace('student_app.php','') + 'scan.php?token=' + token;

  document.getElementById('qrcode').innerHTML = '';
  if (qrObj) { try { qrObj.clear(); } catch(e){} }
  qrObj = new QRCode(document.getElementById('qrcode'), {
    text: scanUrl,
    width: 176, height: 176,
    colorDark: '#1e1b4b', colorLight: '#ffffff',
    correctLevel: QRCode.CorrectLevel.M
  });

  // Expiry display
  if (expiresAt) {
    const exp = new Date(expiresAt);
    document.getElementById('qrExpiry').textContent = exp.toLocaleString('en-IN',{hour:'2-digit',minute:'2-digit',day:'numeric',month:'short'});
  }
}

function refreshQR() {
  const auth = JSON.parse(localStorage.getItem('stu_auth') || 'null');
  if (!auth) return;
  fetch(`${API}?action=get_student_qr&student_id=${encodeURIComponent(auth.id)}&phone=${encodeURIComponent(auth.phone)}`)
    .then(r => r.json())
    .then(data => {
      if (data.error) return;
      studentData = data;
      renderQR(data.token, data.expires_at, auth.id);
    });
}

function renderStats(attArr) {
  const present = attArr.filter(a => a.status === 'present').length;
  const total   = attArr.length;
  const absent  = total - present;
  const rate    = total ? Math.round(present / total * 100) : 0;
  document.getElementById('statPresent').textContent = present;
  document.getElementById('statAbsent').textContent  = absent;
  document.getElementById('statRate').textContent    = rate + '%';
}

function renderHistory(attArr) {
  const el = document.getElementById('attList');
  if (!attArr.length) {
    el.innerHTML = '<div class="empty"><span class="empty-icon">📅</span><div>No attendance records yet.<br>Scan your QR to check in!</div></div>';
    return;
  }
  el.innerHTML = attArr.map(a => {
    const d       = new Date(a.date);
    const dateStr = d.toLocaleDateString('en-IN',{weekday:'short',day:'numeric',month:'short'});
    const isLate  = +a.is_late;
    const cin     = a.check_in  ? formatTime(a.check_in)  : '—';
    const cout    = a.check_out ? formatTime(a.check_out) : '—';
    const tag     = a.status === 'present'
      ? (isLate ? '<span class="att-tag late">Late</span>' : '<span class="att-tag present">Present</span>')
      : '<span class="att-tag absent">Absent</span>';
    return `<div class="att-row">
      <div class="att-dot ${a.status}"></div>
      <div style="flex:1">
        <div class="att-date">${dateStr}</div>
        <div class="att-times">In: ${cin} · Out: ${cout}${isLate?' · ⚠ '+a.late_minutes+'min late':''}</div>
      </div>
      ${tag}
    </div>`;
  }).join('');
}

function renderProfile(stu, batch) {
  const feeColors = {paid:'paid',partial:'partial',pending:'pending',overdue:'overdue'};
  const feeIcons  = {paid:'✅',partial:'◑',pending:'⏳',overdue:'🚨'};
  const fs = stu.fee_status || 'pending';
  const dueDate = stu.due_date ? new Date(stu.due_date).toLocaleDateString('en-IN',{day:'numeric',month:'short',year:'numeric'}) : '—';
  document.getElementById('profileCard').innerHTML = `
    <div class="info-row"><span class="info-lbl">Student ID</span><span class="info-val" style="font-family:monospace">${stu.id}</span></div>
    <div class="info-row"><span class="info-lbl">Name</span><span class="info-val">${stu.fname} ${stu.lname||''}</span></div>
    <div class="info-row"><span class="info-lbl">Phone</span><span class="info-val">${stu.phone||'—'}</span></div>
    <div class="info-row"><span class="info-lbl">Batch</span><span class="info-val">${batch ? batch.name : '—'}</span></div>
    <div class="info-row"><span class="info-lbl">Seat</span><span class="info-val">${stu.seat||'—'} (${(stu.seat_type||'').toUpperCase()})</span></div>
    <div class="info-row"><span class="info-lbl">Course</span><span class="info-val">${stu.course||'—'}</span></div>
    <div class="info-row"><span class="info-lbl">Fee Status</span><span class="info-val"><span class="fee-tag ${feeColors[fs]}">${feeIcons[fs]} ${fs.charAt(0).toUpperCase()+fs.slice(1)}</span></span></div>
    <div class="info-row"><span class="info-lbl">Due Date</span><span class="info-val" style="color:${fs==='overdue'?'var(--err)':'inherit'}">${dueDate}</span></div>
    <div class="info-row"><span class="info-lbl">Net Fee</span><span class="info-val">₹${stu.net_fee||0}</span></div>
    <div class="info-row"><span class="info-lbl">Joined</span><span class="info-val">${stu.join_date ? new Date(stu.join_date).toLocaleDateString('en-IN',{day:'numeric',month:'short',year:'numeric'}) : '—'}</span></div>
  `;
}

function switchTab(tab, el) {
  document.querySelectorAll('.tab-page').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.bn-item').forEach(b => b.classList.remove('active'));
  document.getElementById('tab-' + tab).classList.add('active');
  el.classList.add('active');
  if (tab === 'history' && studentData) renderHistory(studentData.attendance || []);
}

function formatTime(t) {
  if (!t) return '—';
  const [h, m] = t.split(':');
  const hr = +h;
  return (hr > 12 ? hr-12 : (hr||12)) + ':' + m + ' ' + (hr >= 12 ? 'PM' : 'AM');
}

// Auto-refresh QR every 20 minutes
setInterval(refreshQR, 20 * 60 * 1000);
</script>
</body>
</html>
