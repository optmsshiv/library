<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="theme-color" content="#0a0914">
<title>Student App — NAYI UDAAN LIBRARY</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<style>
*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;-webkit-font-smoothing:antialiased}

:root{
  --bg:#0a0914;
  --bg2:#110f20;
  --bg3:#1a1730;
  --bg4:#221f3a;
  --ac:#7c6fff;
  --ac2:#6358e8;
  --ac-glow:rgba(124,111,255,.35);
  --ok:#23d18b;
  --ok-bg:rgba(35,209,139,.1);
  --warn:#f5a623;
  --warn-bg:rgba(245,166,35,.1);
  --err:#ff5c5c;
  --err-bg:rgba(255,92,92,.1);
  --tx:#eeeaf8;
  --tx2:#9b92c4;
  --tx3:#5c5585;
  --br:rgba(255,255,255,.07);
  --br2:rgba(255,255,255,.12);
  --glass:rgba(255,255,255,.04);
  --glass2:rgba(255,255,255,.08);
  --r:18px;
  --r2:14px;
  --r3:10px;
  --safe-top: env(safe-area-inset-top, 0px);
  --safe-bottom: env(safe-area-inset-bottom, 0px);
}

html,body{
  height:100%;
  background:var(--bg);
  color:var(--tx);
  font-family:'Outfit',sans-serif;
  overflow:hidden;
  overscroll-behavior:none;
}

/* ── AMBIENT BACKGROUND ── */
body::before{
  content:'';
  position:fixed;inset:0;
  background:
    radial-gradient(ellipse 70% 50% at 15% -5%, rgba(124,111,255,.2), transparent 60%),
    radial-gradient(ellipse 50% 40% at 85% 100%, rgba(92,58,220,.15), transparent 60%),
    radial-gradient(ellipse 40% 30% at 50% 50%, rgba(35,209,139,.04), transparent 70%);
  pointer-events:none;z-index:0;
}

/* ══════════════════════════════════
   LOGIN SCREEN
══════════════════════════════════ */
#loginScreen{
  position:fixed;inset:0;z-index:100;
  display:flex;flex-direction:column;
  align-items:center;justify-content:center;
  padding:24px;
  background:var(--bg);
  overflow-y:auto;
}

.login-brand{
  display:flex;flex-direction:column;align-items:center;
  margin-bottom:36px;
}

.login-icon{
  width:80px;height:80px;
  background:linear-gradient(145deg,var(--ac),#5c3aed);
  border-radius:26px;
  display:flex;align-items:center;justify-content:center;
  font-size:40px;
  margin-bottom:18px;
  box-shadow:0 12px 40px var(--ac-glow), 0 0 0 1px rgba(124,111,255,.2);
  position:relative;
}
.login-icon::after{
  content:'';position:absolute;inset:-1px;
  border-radius:27px;
  background:linear-gradient(145deg,rgba(255,255,255,.15),transparent);
  pointer-events:none;
}

.login-title{
  font-size:26px;font-weight:800;letter-spacing:-.5px;
  background:linear-gradient(135deg,#fff 40%,var(--tx2));
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;
  background-clip:text;
  margin-bottom:5px;
}
.login-sub{font-size:14px;color:var(--tx3);font-weight:400;}

.login-card{
  width:100%;max-width:380px;
  background:var(--bg2);
  border:1px solid var(--br2);
  border-radius:24px;
  padding:24px 22px;
  box-shadow:0 24px 64px rgba(0,0,0,.5);
}

.field{margin-bottom:18px;}
.field label{
  display:block;
  font-size:11px;font-weight:700;
  color:var(--tx3);letter-spacing:1px;text-transform:uppercase;
  margin-bottom:8px;
}
.input-wrap{position:relative;}
.input-icon{
  position:absolute;left:14px;top:50%;transform:translateY(-50%);
  font-size:16px;pointer-events:none;
}
.field input{
  width:100%;
  background:var(--bg3);
  border:1.5px solid var(--br);
  border-radius:var(--r3);
  padding:14px 15px 14px 42px;
  color:var(--tx);
  font-size:15px;font-weight:500;
  font-family:'Outfit',sans-serif;
  outline:none;
  transition:border-color .2s, box-shadow .2s, background .2s;
}
.field input:focus{
  border-color:var(--ac);
  background:var(--bg4);
  box-shadow:0 0 0 3px var(--ac-glow);
}
.field input::placeholder{color:var(--tx3);font-weight:400;}

.login-btn{
  width:100%;padding:15px;
  background:linear-gradient(135deg,var(--ac),#5c3aed);
  border:none;border-radius:var(--r3);
  color:#fff;
  font-family:'Outfit',sans-serif;
  font-size:15px;font-weight:700;letter-spacing:.3px;
  cursor:pointer;
  transition:transform .15s, box-shadow .15s, opacity .15s;
  box-shadow:0 8px 24px var(--ac-glow);
  margin-top:6px;
  position:relative;overflow:hidden;
}
.login-btn::after{
  content:'';position:absolute;inset:0;
  background:linear-gradient(180deg,rgba(255,255,255,.12),transparent);
  pointer-events:none;
}
.login-btn:active{transform:scale(.97);opacity:.9;}

.login-err{
  background:var(--err-bg);
  border:1px solid rgba(255,92,92,.2);
  border-radius:var(--r3);
  padding:12px 14px;
  font-size:13px;color:#ff9898;
  margin-top:14px;display:none;text-align:center;
  animation:shake .3s ease;
}
@keyframes shake{0%,100%{transform:translateX(0)}25%{transform:translateX(-6px)}75%{transform:translateX(6px)}}

.login-footer{margin-top:20px;text-align:center;font-size:12px;color:var(--tx3);}

/* ══════════════════════════════════
   MAIN APP SHELL
══════════════════════════════════ */
#app{
  display:none;
  flex-direction:column;
  height:100%;
  position:relative;z-index:1;
}

/* ── HEADER ── */
.header{
  padding:calc(var(--safe-top) + 14px) 18px 14px;
  display:flex;align-items:center;justify-content:space-between;
  background:linear-gradient(180deg,var(--bg) 60%,transparent);
  position:relative;z-index:10;
  flex-shrink:0;
}

.hdr-left{display:flex;align-items:center;gap:12px;}
.hdr-av{
  width:44px;height:44px;border-radius:14px;
  display:flex;align-items:center;justify-content:center;
  font-size:16px;font-weight:800;color:#fff;
  flex-shrink:0;
  box-shadow:0 4px 12px rgba(0,0,0,.3);
  position:relative;
}
.hdr-av::after{
  content:'';position:absolute;inset:0;border-radius:14px;
  background:linear-gradient(145deg,rgba(255,255,255,.2),transparent);
  pointer-events:none;
}
.hdr-name{font-size:16px;font-weight:700;letter-spacing:-.3px;line-height:1.2;}
.hdr-batch{font-size:12px;color:var(--tx2);font-weight:400;margin-top:1px;}

.hdr-right{display:flex;align-items:center;gap:8px;}
.logout-btn{
  background:var(--glass2);
  border:1px solid var(--br);
  border-radius:20px;
  color:var(--tx2);font-size:12px;font-weight:600;
  padding:7px 13px;cursor:pointer;
  font-family:'Outfit',sans-serif;
  transition:all .2s;
}
.logout-btn:active{background:var(--bg3);}

/* ── SCROLL AREA ── */
.scroll-area{
  flex:1;
  overflow-y:auto;
  overflow-x:hidden;
  -webkit-overflow-scrolling:touch;
  padding:6px 16px calc(var(--safe-bottom) + 80px);
  scroll-behavior:smooth;
}
.scroll-area::-webkit-scrollbar{display:none;}

/* ── TAB PAGES ── */
.tab-page{display:none;}
.tab-page.active{display:block;animation:pageIn .25s ease both;}
@keyframes pageIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}

/* ── SECTION LABEL ── */
.sec-label{
  font-size:10px;font-weight:700;
  letter-spacing:1.5px;text-transform:uppercase;
  color:var(--tx3);
  margin:20px 0 10px;
  display:flex;align-items:center;gap:8px;
}
.sec-label::after{
  content:'';flex:1;height:1px;
  background:linear-gradient(90deg,var(--br),transparent);
}

/* ── STATS ROW ── */
.stats-row{
  display:grid;grid-template-columns:repeat(3,1fr);
  gap:10px;margin-bottom:6px;
}
.stat-box{
  background:var(--bg2);
  border:1px solid var(--br);
  border-radius:var(--r2);
  padding:16px 10px;
  text-align:center;
  position:relative;overflow:hidden;
  transition:transform .15s;
}
.stat-box:active{transform:scale(.97);}
.stat-box::before{
  content:'';position:absolute;top:0;left:0;right:0;height:2px;
  border-radius:var(--r2) var(--r2) 0 0;
}
.stat-box.s-ok::before{background:var(--ok);}
.stat-box.s-warn::before{background:var(--warn);}
.stat-box.s-ac::before{background:var(--ac);}

.stat-val{
  font-size:26px;font-weight:800;letter-spacing:-.5px;
  line-height:1;margin-bottom:4px;
}
.stat-val.ok{color:var(--ok);}
.stat-val.warn{color:var(--warn);}
.stat-val.ac{color:var(--ac);}
.stat-lbl{font-size:10px;color:var(--tx3);letter-spacing:.5px;text-transform:uppercase;font-weight:600;}

/* ── QR CARD ── */
.qr-card{
  background:var(--bg2);
  border:1px solid var(--br2);
  border-radius:22px;
  padding:22px 20px;
  text-align:center;
  position:relative;overflow:hidden;
  margin-bottom:6px;
}
.qr-card::before{
  content:'';position:absolute;inset:0;
  background:radial-gradient(ellipse 90% 50% at 50% -20%,rgba(124,111,255,.12),transparent);
  pointer-events:none;
}

.qr-badge{
  display:inline-flex;align-items:center;gap:6px;
  background:rgba(124,111,255,.12);
  border:1px solid rgba(124,111,255,.2);
  border-radius:20px;
  padding:5px 12px;
  font-size:11px;font-weight:700;color:var(--ac);letter-spacing:.5px;
  text-transform:uppercase;margin-bottom:14px;
}
.qr-badge::before{
  content:'';width:6px;height:6px;border-radius:50%;
  background:var(--ac);
  animation:pulse 2s ease infinite;
}
@keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.5;transform:scale(.8)}}

.qr-wrap{
  width:210px;height:210px;
  background:#fff;
  border-radius:20px;
  margin:0 auto 16px;
  padding:14px;
  display:flex;align-items:center;justify-content:center;
  box-shadow:0 0 0 1px rgba(0,0,0,.05), 0 16px 48px rgba(0,0,0,.5), 0 0 40px var(--ac-glow);
  position:relative;
}
#qrcode{width:182px;height:182px;}
#qrcode canvas,#qrcode img{width:182px!important;height:182px!important;border-radius:6px;}

.qr-expiry{
  font-size:12px;color:var(--tx3);
  margin-bottom:14px;
  display:flex;align-items:center;justify-content:center;gap:5px;
}
.qr-expiry-val{color:var(--warn);font-weight:700;}

.refresh-btn{
  display:inline-flex;align-items:center;gap:7px;
  background:var(--glass2);
  border:1px solid var(--br2);
  border-radius:20px;
  color:var(--tx);font-size:13px;font-weight:600;
  padding:10px 20px;cursor:pointer;
  font-family:'Outfit',sans-serif;
  transition:all .2s;
  margin-bottom:14px;
}
.refresh-btn:active{background:var(--bg4);transform:scale(.96);}

.scan-hint{
  font-size:12px;color:var(--tx2);line-height:1.6;
  background:rgba(124,111,255,.07);
  border:1px solid rgba(124,111,255,.15);
  border-radius:var(--r3);
  padding:11px 13px;
  text-align:left;
}

/* ── ATTENDANCE LIST ── */
.att-list{display:flex;flex-direction:column;gap:8px;}

.att-row{
  background:var(--bg2);
  border:1px solid var(--br);
  border-radius:var(--r2);
  padding:14px 15px;
  display:flex;align-items:center;gap:13px;
  transition:transform .1s;
}
.att-row:active{transform:scale(.99);}

.att-indicator{
  width:38px;height:38px;border-radius:12px;
  display:flex;align-items:center;justify-content:center;
  font-size:16px;flex-shrink:0;
}
.att-indicator.present{background:var(--ok-bg);}
.att-indicator.absent{background:var(--err-bg);}
.att-indicator.late{background:var(--warn-bg);}

.att-body{flex:1;min-width:0;}
.att-date{font-size:14px;font-weight:700;letter-spacing:-.2px;line-height:1.2;}
.att-times{font-size:11px;color:var(--tx2);margin-top:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}

.att-tag{
  font-size:10px;font-weight:800;
  padding:4px 9px;border-radius:20px;
  letter-spacing:.5px;text-transform:uppercase;
  flex-shrink:0;
}
.att-tag.present{background:var(--ok-bg);color:var(--ok);}
.att-tag.absent{background:var(--err-bg);color:var(--err);}
.att-tag.late{background:var(--warn-bg);color:var(--warn);}

/* ── PROFILE CARD ── */
.profile-hero{
  background:var(--bg2);
  border:1px solid var(--br);
  border-radius:22px;
  padding:20px;
  margin-bottom:10px;
  display:flex;align-items:center;gap:15px;
}
.profile-av{
  width:58px;height:58px;border-radius:18px;
  display:flex;align-items:center;justify-content:center;
  font-size:22px;font-weight:800;color:#fff;
  flex-shrink:0;
  box-shadow:0 6px 20px rgba(0,0,0,.3);
}
.profile-name{font-size:18px;font-weight:800;letter-spacing:-.3px;}
.profile-id{
  font-size:11px;font-weight:700;
  color:var(--ac);
  background:rgba(124,111,255,.1);
  border:1px solid rgba(124,111,255,.15);
  border-radius:6px;
  padding:2px 8px;
  display:inline-block;margin-top:5px;
  font-family:monospace;letter-spacing:1px;
}

.info-card{
  background:var(--bg2);
  border:1px solid var(--br);
  border-radius:var(--r);
  overflow:hidden;
}
.info-row{
  display:flex;justify-content:space-between;align-items:center;
  padding:13px 16px;
  border-bottom:1px solid rgba(255,255,255,.04);
  gap:12px;
}
.info-row:last-child{border-bottom:none;}
.info-lbl{
  font-size:12px;color:var(--tx3);font-weight:500;
  flex-shrink:0;
}
.info-val{
  font-size:13px;font-weight:700;color:var(--tx);
  text-align:right;word-break:break-word;
}

.fee-tag{
  display:inline-flex;align-items:center;gap:4px;
  font-size:11px;font-weight:800;
  padding:4px 10px;border-radius:20px;
}
.fee-tag.paid{background:var(--ok-bg);color:var(--ok);}
.fee-tag.partial{background:rgba(124,111,255,.1);color:#b0a6ff;}
.fee-tag.pending{background:var(--warn-bg);color:var(--warn);}
.fee-tag.overdue{background:var(--err-bg);color:var(--err);}

/* ── BOTTOM NAV ── */
.bottom-nav{
  position:fixed;bottom:0;left:0;right:0;
  background:rgba(10,9,20,.88);
  backdrop-filter:blur(20px) saturate(1.5);
  -webkit-backdrop-filter:blur(20px) saturate(1.5);
  border-top:1px solid var(--br);
  padding:8px 10px calc(var(--safe-bottom) + 8px);
  display:flex;justify-content:space-around;
  z-index:50;
}

.bn-item{
  display:flex;flex-direction:column;align-items:center;gap:3px;
  padding:6px 24px;cursor:pointer;
  opacity:.4;
  transition:opacity .2s, transform .15s;
  border:none;background:none;color:var(--tx);
  font-family:'Outfit',sans-serif;
  border-radius:16px;
  position:relative;
}
.bn-item.active{opacity:1;}
.bn-item:active{transform:scale(.92);}

.bn-icon{
  font-size:22px;line-height:1;
  transition:transform .2s;
}
.bn-item.active .bn-icon{transform:scale(1.1);}

.bn-lbl{font-size:10px;font-weight:700;letter-spacing:.3px;}
.bn-item.active .bn-lbl{color:var(--ac);}

.bn-dot{
  position:absolute;top:4px;right:18px;
  width:6px;height:6px;border-radius:50%;
  background:var(--ac);
  display:none;
}
.bn-item.active .bn-dot{display:block;}

/* ── LOADING ── */
.loading{
  text-align:center;padding:50px 20px;color:var(--tx2);
}
.spinner{
  width:32px;height:32px;
  border:2.5px solid rgba(255,255,255,.08);
  border-top:2.5px solid var(--ac);
  border-radius:50%;
  animation:spin .8s linear infinite;
  margin:0 auto 14px;
}
@keyframes spin{to{transform:rotate(360deg)}}

/* ── EMPTY STATE ── */
.empty{
  text-align:center;padding:50px 20px;color:var(--tx3);
}
.empty-icon{font-size:52px;display:block;margin-bottom:12px;filter:grayscale(.3);}
.empty-text{font-size:14px;font-weight:500;line-height:1.6;}

/* ── TOAST ── */
.toast{
  position:fixed;bottom:calc(var(--safe-bottom) + 85px);left:50%;
  transform:translateX(-50%) translateY(20px);
  background:var(--bg3);
  border:1px solid var(--br2);
  border-radius:20px;
  padding:10px 18px;
  font-size:13px;font-weight:600;
  color:var(--tx);
  white-space:nowrap;
  z-index:200;
  opacity:0;
  transition:all .3s ease;
  pointer-events:none;
  box-shadow:0 8px 24px rgba(0,0,0,.4);
}
.toast.show{opacity:1;transform:translateX(-50%) translateY(0);}

/* ── RESPONSIVE TWEAKS ── */
@media(max-width:360px){
  .stat-val{font-size:22px;}
  .qr-wrap{width:190px;height:190px;}
  #qrcode{width:162px;height:162px;}
  #qrcode canvas,#qrcode img{width:162px!important;height:162px!important;}
  .bn-item{padding:6px 18px;}
}
@media(min-width:480px){
  .scroll-area{max-width:480px;margin:0 auto;}
  .header{max-width:480px;margin:0 auto;}
  .bottom-nav{max-width:480px;left:50%;right:auto;transform:translateX(-50%);}
}
</style>
</head>
<body>

<!-- ══ LOGIN SCREEN ══ -->
<div id="loginScreen">
  <div class="login-brand">
    <div class="login-icon">📚</div>
    <div class="login-title">Student Portal</div>
    <div class="login-sub">NAYI UDAAN LIBRARY</div>
  </div>

  <div class="login-card">
    <div class="field">
      <label>Student ID</label>
      <div class="input-wrap">
        <span class="input-icon">🎓</span>
        <input type="text" id="loginId" placeholder="e.g. STU-001" autocapitalize="characters" autocomplete="off">
      </div>
    </div>
    <div class="field">
      <label>Phone Number</label>
      <div class="input-wrap">
        <span class="input-icon">📞</span>
        <input type="tel" id="loginPhone" placeholder="Registered phone number" inputmode="numeric">
      </div>
    </div>
    <button class="login-btn" id="loginBtn" onclick="doLogin()">Sign In →</button>
    <div class="login-err" id="loginErr"></div>
  </div>

  <div class="login-footer">Your attendance & fee details in one place</div>
</div>

<!-- ══ MAIN APP ══ -->
<div id="app">
  <div class="header">
    <div class="hdr-left">
      <div class="hdr-av" id="appAv">S</div>
      <div>
        <div class="hdr-name" id="appName">Student</div>
        <div class="hdr-batch" id="appBatch">Loading…</div>
      </div>
    </div>
    <div class="hdr-right">
      <button class="logout-btn" onclick="doLogout()">Sign Out</button>
    </div>
  </div>

  <div class="scroll-area" id="scrollArea">

    <!-- QR TAB -->
    <div class="tab-page active" id="tab-qr">
      <div class="sec-label">This Month's Stats</div>
      <div class="stats-row">
        <div class="stat-box s-ok">
          <div class="stat-val ok" id="statPresent">—</div>
          <div class="stat-lbl">Present</div>
        </div>
        <div class="stat-box s-warn">
          <div class="stat-val warn" id="statAbsent">—</div>
          <div class="stat-lbl">Absent</div>
        </div>
        <div class="stat-box s-ac">
          <div class="stat-val ac" id="statRate">—</div>
          <div class="stat-lbl">Rate</div>
        </div>
      </div>

      <div class="sec-label">Your QR Code</div>
      <div class="qr-card">
        <div class="qr-badge">🟢 Live Attendance QR</div>
        <div class="qr-wrap">
          <div id="qrcode"></div>
        </div>
        <div class="qr-expiry">
          ⏱ Valid until: <span class="qr-expiry-val" id="qrExpiry">—</span>
        </div>
        <button class="refresh-btn" onclick="refreshQR()">🔄 Refresh QR Code</button>
        <div class="scan-hint">📌 Show this QR at the library entrance to mark check-in. Scan again when you leave to check-out.</div>
      </div>
    </div>

    <!-- HISTORY TAB -->
    <div class="tab-page" id="tab-history">
      <div class="sec-label">Recent Attendance</div>
      <div class="att-list" id="attList">
        <div class="loading"><div class="spinner"></div>Loading records…</div>
      </div>
    </div>

    <!-- PROFILE TAB -->
    <div class="tab-page" id="tab-profile">
      <div id="profileHero"></div>
      <div class="sec-label">My Details</div>
      <div class="info-card" id="profileCard">
        <div class="loading"><div class="spinner"></div></div>
      </div>
    </div>

  </div><!-- /scroll-area -->

  <!-- Bottom Nav -->
  <nav class="bottom-nav">
    <button class="bn-item active" onclick="switchTab('qr',this)" id="btn-qr">
      <div class="bn-dot"></div>
      <span class="bn-icon">📱</span>
      <span class="bn-lbl">My QR</span>
    </button>
    <button class="bn-item" onclick="switchTab('history',this)" id="btn-history">
      <div class="bn-dot"></div>
      <span class="bn-icon">📅</span>
      <span class="bn-lbl">History</span>
    </button>
    <button class="bn-item" onclick="switchTab('profile',this)" id="btn-profile">
      <div class="bn-dot"></div>
      <span class="bn-icon">👤</span>
      <span class="bn-lbl">Profile</span>
    </button>
  </nav>
</div>

<div class="toast" id="toast"></div>

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
  const btn   = document.getElementById('loginBtn');
  document.getElementById('loginErr').style.display = 'none';
  if (!id || !phone) { showLoginErr('Please enter both Student ID and Phone.'); return; }
  btn.textContent = 'Signing in…';
  btn.style.opacity = '.7';
  fetch(`${API}?action=get_student_qr&student_id=${encodeURIComponent(id)}&phone=${encodeURIComponent(phone)}`)
    .then(r => r.json())
    .then(data => {
      btn.textContent = 'Sign In →';
      btn.style.opacity = '1';
      if (data.error) { showLoginErr(data.error); return; }
      localStorage.setItem('stu_auth', JSON.stringify({ id, phone }));
      initApp(id, phone, data);
    })
    .catch(() => {
      btn.textContent = 'Sign In →';
      btn.style.opacity = '1';
      showLoginErr('Network error. Please try again.');
    });
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

document.getElementById('loginPhone').addEventListener('keydown', e => { if (e.key==='Enter') doLogin(); });
document.getElementById('loginId').addEventListener('keydown',    e => { if (e.key==='Enter') doLogin(); });

function initApp(id, phone, data) {
  document.getElementById('loginScreen').style.display = 'none';
  document.getElementById('app').style.display = 'flex';
  if (data) {
    studentData = data;
    renderApp(data);
  } else {
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
  const color = stu.color || '#7c6fff';

  document.getElementById('appName').textContent  = fname + (lname ? ' ' + lname : '');
  document.getElementById('appBatch').textContent = batch ? '🏫 ' + batch.name : stu.id;
  const av = document.getElementById('appAv');
  av.textContent    = initials.toUpperCase();
  av.style.background = color;

  renderQR(data.token, data.expires_at, stu.id);
  renderStats(data.attendance || []);
  renderHistory(data.attendance || []);
  renderProfile(stu, batch, color);
}

function renderQR(token, expiresAt, studentId) {
  const scanUrl = window.location.origin + window.location.pathname.replace('student_app.php','') + 'scan.php?token=' + token;
  document.getElementById('qrcode').innerHTML = '';
  if (qrObj) { try { qrObj.clear(); } catch(e){} }
  qrObj = new QRCode(document.getElementById('qrcode'), {
    text: scanUrl,
    width: 182, height: 182,
    colorDark: '#1a1730', colorLight: '#ffffff',
    correctLevel: QRCode.CorrectLevel.M
  });
  if (expiresAt) {
    const exp = new Date(expiresAt);
    document.getElementById('qrExpiry').textContent =
      exp.toLocaleString('en-IN',{hour:'2-digit',minute:'2-digit',day:'numeric',month:'short'});
  }
}

function refreshQR() {
  const auth = JSON.parse(localStorage.getItem('stu_auth') || 'null');
  if (!auth) return;
  showToast('🔄 Refreshing QR…');
  fetch(`${API}?action=get_student_qr&student_id=${encodeURIComponent(auth.id)}&phone=${encodeURIComponent(auth.phone)}`)
    .then(r => r.json())
    .then(data => {
      if (data.error) return;
      studentData = data;
      renderQR(data.token, data.expires_at, auth.id);
      showToast('✅ QR refreshed!');
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
    el.innerHTML = `<div class="empty">
      <span class="empty-icon">📅</span>
      <div class="empty-text">No attendance records yet.<br>Scan your QR to check in!</div>
    </div>`;
    return;
  }
  const statusIcon = { present:'✅', absent:'❌', late:'⚠️' };
  el.innerHTML = attArr.map(a => {
    const d       = new Date(a.date);
    const dateStr = d.toLocaleDateString('en-IN',{weekday:'short',day:'numeric',month:'short'});
    const isLate  = +a.is_late;
    const cin     = a.check_in  ? formatTime(a.check_in)  : '—';
    const cout    = a.check_out ? formatTime(a.check_out) : '—';
    const tagClass = a.status === 'present' ? (isLate ? 'late' : 'present') : 'absent';
    const tagText  = a.status === 'present' ? (isLate ? 'Late' : 'Present') : 'Absent';
    const icon     = isLate ? statusIcon.late : (statusIcon[a.status] || '❓');
    return `<div class="att-row">
      <div class="att-indicator ${tagClass}">${icon}</div>
      <div class="att-body">
        <div class="att-date">${dateStr}</div>
        <div class="att-times">In: ${cin} &nbsp;·&nbsp; Out: ${cout}${isLate ? ' · ' + a.late_minutes + 'min late' : ''}</div>
      </div>
      <span class="att-tag ${tagClass}">${tagText}</span>
    </div>`;
  }).join('');
}

function renderProfile(stu, batch, color) {
  const fname = stu.fname || '';
  const lname = stu.lname || '';
  const initials = (fname[0]||'') + (lname[0]||'');
  // Hero
  document.getElementById('profileHero').innerHTML = `
    <div class="profile-hero">
      <div class="profile-av" style="background:${color}">${initials.toUpperCase()}</div>
      <div>
        <div class="profile-name">${fname} ${lname}</div>
        <div class="profile-id">${stu.id}</div>
      </div>
    </div>`;

  const feeIcons = {paid:'✅',partial:'◑',pending:'⏳',overdue:'🚨'};
  const fs = stu.fee_status || 'pending';
  const dueDate = stu.due_date
    ? new Date(stu.due_date).toLocaleDateString('en-IN',{day:'numeric',month:'short',year:'numeric'})
    : '—';

  document.getElementById('profileCard').innerHTML = `
    <div class="info-row"><span class="info-lbl">Phone</span><span class="info-val">${stu.phone||'—'}</span></div>
    <div class="info-row"><span class="info-lbl">Batch</span><span class="info-val">${batch ? batch.name : '—'}</span></div>
    <div class="info-row"><span class="info-lbl">Seat</span><span class="info-val">${stu.seat||'—'} · ${(stu.seat_type||'').toUpperCase()}</span></div>
    <div class="info-row"><span class="info-lbl">Course</span><span class="info-val">${stu.course||'—'}</span></div>
    <div class="info-row"><span class="info-lbl">Fee Status</span><span class="info-val"><span class="fee-tag ${fs}">${feeIcons[fs]} ${fs.charAt(0).toUpperCase()+fs.slice(1)}</span></span></div>
    <div class="info-row"><span class="info-lbl">Due Date</span><span class="info-val" style="color:${fs==='overdue'?'var(--err)':'inherit'}">${dueDate}</span></div>
    <div class="info-row"><span class="info-lbl">Net Fee</span><span class="info-val">₹${Number(stu.net_fee||0).toLocaleString('en-IN')}</span></div>
    <div class="info-row"><span class="info-lbl">Joined</span><span class="info-val">${stu.join_date ? new Date(stu.join_date).toLocaleDateString('en-IN',{day:'numeric',month:'short',year:'numeric'}) : '—'}</span></div>
  `;
}

function switchTab(tab, el) {
  document.querySelectorAll('.tab-page').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.bn-item').forEach(b => b.classList.remove('active'));
  document.getElementById('tab-' + tab).classList.add('active');
  el.classList.add('active');
  document.getElementById('scrollArea').scrollTop = 0;
  if (tab === 'history' && studentData) renderHistory(studentData.attendance || []);
}

function formatTime(t) {
  if (!t) return '—';
  const [h, m] = t.split(':');
  const hr = +h;
  return (hr > 12 ? hr-12 : (hr||12)) + ':' + m + ' ' + (hr >= 12 ? 'PM' : 'AM');
}

function showToast(msg) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 2200);
}

// Auto-refresh QR every 20 minutes
setInterval(refreshQR, 20 * 60 * 1000);
</script>
</body>
</html>
