<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="theme-color" content="#f5f7ff">
<title>Student App — NAYI UDAAN LIBRARY</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<style>
*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;-webkit-font-smoothing:antialiased}

:root{
  --bg:#f5f7ff;
  --bg2:#ffffff;
  --bg3:#f0f2fc;
  --bg4:#e8ebf8;
  --ac:#4f46e5;
  --ac2:#4338ca;
  --ac-light:#ede9fe;
  --ac-glow:rgba(79,70,229,.18);
  --ok:#16a34a;
  --ok-bg:#f0fdf4;
  --ok-border:#bbf7d0;
  --warn:#d97706;
  --warn-bg:#fffbeb;
  --warn-border:#fde68a;
  --err:#dc2626;
  --err-bg:#fff1f2;
  --err-border:#fecdd3;
  --tx:#0f172a;
  --tx2:#475569;
  --tx3:#94a3b8;
  --br:#e2e8f0;
  --br2:#cbd5e1;
  --r:18px;
  --r2:14px;
  --r3:10px;
  --sh:0 1px 4px rgba(15,23,42,.06),0 4px 14px rgba(15,23,42,.05);
  --sh2:0 8px 32px rgba(15,23,42,.10);
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
    radial-gradient(ellipse 60% 40% at 10% 0%, rgba(79,70,229,.06), transparent 60%),
    radial-gradient(ellipse 40% 30% at 90% 100%, rgba(16,163,74,.04), transparent 60%);
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
  margin-bottom:32px;
}

.login-icon{
  width:80px;height:80px;
  background:linear-gradient(145deg,var(--ac),#7c3aed);
  border-radius:26px;
  display:flex;align-items:center;justify-content:center;
  font-size:40px;
  margin-bottom:16px;
  box-shadow:0 12px 40px var(--ac-glow);
}

.login-title{
  font-size:26px;font-weight:800;letter-spacing:-.5px;
  color:var(--tx);margin-bottom:4px;
}
.login-sub{font-size:13px;color:var(--tx3);font-weight:500;letter-spacing:.5px;text-transform:uppercase;}

.login-card{
  width:100%;max-width:380px;
  background:var(--bg2);
  border:1px solid var(--br);
  border-radius:24px;
  padding:24px 22px;
  box-shadow:var(--sh2);
}

.field{margin-bottom:16px;}
.field label{
  display:block;
  font-size:11px;font-weight:700;
  color:var(--tx3);letter-spacing:1px;text-transform:uppercase;
  margin-bottom:7px;
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
  padding:13px 15px 13px 42px;
  color:var(--tx);
  font-size:15px;font-weight:500;
  font-family:'Outfit',sans-serif;
  outline:none;
  transition:border-color .2s,box-shadow .2s;
}
.field input:focus{
  border-color:var(--ac);
  background:#fff;
  box-shadow:0 0 0 3px var(--ac-glow);
}
.field input::placeholder{color:var(--tx3);font-weight:400;}

.login-btn{
  width:100%;padding:14px;
  background:linear-gradient(135deg,var(--ac),#7c3aed);
  border:none;border-radius:var(--r3);
  color:#fff;
  font-family:'Outfit',sans-serif;
  font-size:15px;font-weight:700;
  cursor:pointer;
  transition:transform .15s,opacity .15s;
  box-shadow:0 6px 20px var(--ac-glow);
  margin-top:4px;
}
.login-btn:active{transform:scale(.97);opacity:.9;}

.login-err{
  background:var(--err-bg);
  border:1px solid var(--err-border);
  border-radius:var(--r3);
  padding:11px 14px;
  font-size:13px;color:var(--err);
  margin-top:12px;display:none;text-align:center;
  animation:shake .3s ease;
}
@keyframes shake{0%,100%{transform:translateX(0)}25%{transform:translateX(-6px)}75%{transform:translateX(6px)}}

.login-footer{margin-top:18px;text-align:center;font-size:12px;color:var(--tx3);}

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
  padding:calc(var(--safe-top) + 12px) 18px 12px;
  display:flex;align-items:center;justify-content:space-between;
  background:var(--bg2);
  border-bottom:1px solid var(--br);
  position:relative;z-index:10;
  flex-shrink:0;
  box-shadow:var(--sh);
}

.hdr-left{display:flex;align-items:center;gap:12px;}
.hdr-av{
  width:42px;height:42px;border-radius:13px;
  display:flex;align-items:center;justify-content:center;
  font-size:15px;font-weight:800;color:#fff;
  flex-shrink:0;
  box-shadow:0 3px 10px rgba(0,0,0,.15);
}
.hdr-name{font-size:15px;font-weight:700;letter-spacing:-.2px;line-height:1.2;color:var(--tx);}
.hdr-batch{font-size:11px;color:var(--tx3);font-weight:500;margin-top:1px;}

.hdr-right{display:flex;align-items:center;gap:8px;}
.logout-btn{
  background:var(--bg3);
  border:1px solid var(--br);
  border-radius:20px;
  color:var(--tx2);font-size:11px;font-weight:600;
  padding:6px 12px;cursor:pointer;
  font-family:'Outfit',sans-serif;
  transition:all .2s;
}
.logout-btn:active{background:var(--bg4);}

/* ── SCROLL AREA ── */
.scroll-area{
  flex:1;
  overflow-y:auto;
  overflow-x:hidden;
  -webkit-overflow-scrolling:touch;
  padding:12px 16px calc(var(--safe-bottom) + 80px);
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
  margin:18px 0 10px;
  display:flex;align-items:center;gap:8px;
}
.sec-label::after{
  content:'';flex:1;height:1px;
  background:var(--br);
}

/* ── BANNER / ANNOUNCEMENT ── */
.banner-card{
  background:linear-gradient(135deg,var(--ac),#7c3aed);
  border-radius:var(--r2);
  padding:16px 18px;
  margin-bottom:12px;
  color:#fff;
  position:relative;overflow:hidden;
}
.banner-card::before{
  content:'';position:absolute;
  top:-30px;right:-30px;
  width:120px;height:120px;
  background:rgba(255,255,255,.06);
  border-radius:50%;
}
.banner-tag{
  font-size:9px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;
  background:rgba(255,255,255,.2);
  border-radius:10px;padding:3px 8px;
  display:inline-block;margin-bottom:7px;
}
.banner-title{font-size:15px;font-weight:700;margin-bottom:4px;}
.banner-msg{font-size:12px;opacity:.85;line-height:1.5;}
.banner-date{font-size:10px;opacity:.6;margin-top:6px;}

.notice-card{
  background:var(--bg2);
  border:1px solid var(--br);
  border-left:3px solid var(--ac);
  border-radius:var(--r3);
  padding:13px 15px;
  margin-bottom:8px;
}
.notice-title{font-size:13px;font-weight:700;color:var(--tx);margin-bottom:3px;}
.notice-msg{font-size:12px;color:var(--tx2);line-height:1.5;}
.notice-date{font-size:10px;color:var(--tx3);margin-top:5px;}

/* ── STATS ROW ── */
.stats-row{
  display:grid;grid-template-columns:repeat(3,1fr);
  gap:10px;margin-bottom:6px;
}
.stat-box{
  background:var(--bg2);
  border:1px solid var(--br);
  border-radius:var(--r2);
  padding:14px 10px;
  text-align:center;
  position:relative;overflow:hidden;
  box-shadow:var(--sh);
}
.stat-box::before{
  content:'';position:absolute;top:0;left:0;right:0;height:3px;
  border-radius:var(--r2) var(--r2) 0 0;
}
.stat-box.s-ok::before{background:var(--ok);}
.stat-box.s-warn::before{background:var(--warn);}
.stat-box.s-ac::before{background:var(--ac);}

.stat-val{
  font-size:24px;font-weight:800;letter-spacing:-.5px;
  line-height:1;margin-bottom:4px;
}
.stat-val.ok{color:var(--ok);}
.stat-val.warn{color:var(--warn);}
.stat-val.ac{color:var(--ac);}
.stat-lbl{font-size:9px;color:var(--tx3);letter-spacing:.5px;text-transform:uppercase;font-weight:700;}

/* ── QR CARD ── */
.qr-card{
  background:var(--bg2);
  border:1px solid var(--br);
  border-radius:22px;
  padding:22px 20px;
  text-align:center;
  position:relative;overflow:hidden;
  margin-bottom:6px;
  box-shadow:var(--sh);
}

.qr-badge{
  display:inline-flex;align-items:center;gap:6px;
  background:var(--ac-light);
  border:1px solid rgba(79,70,229,.2);
  border-radius:20px;
  padding:5px 12px;
  font-size:11px;font-weight:700;color:var(--ac);letter-spacing:.5px;
  text-transform:uppercase;margin-bottom:14px;
}
.qr-badge::before{
  content:'';width:6px;height:6px;border-radius:50%;
  background:var(--ok);
  animation:pulse 2s ease infinite;
}
@keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.4;transform:scale(.8)}}

.qr-wrap{
  width:210px;height:210px;
  background:#fff;
  border-radius:20px;
  margin:0 auto 16px;
  padding:14px;
  display:flex;align-items:center;justify-content:center;
  box-shadow:0 4px 20px rgba(79,70,229,.15), 0 0 0 1px var(--br);
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
  background:var(--bg3);
  border:1px solid var(--br);
  border-radius:20px;
  color:var(--tx2);font-size:13px;font-weight:600;
  padding:9px 20px;cursor:pointer;
  font-family:'Outfit',sans-serif;
  transition:all .2s;
  margin-bottom:14px;
}
.refresh-btn:active{background:var(--bg4);transform:scale(.96);}

.scan-hint{
  font-size:12px;color:var(--tx2);line-height:1.6;
  background:var(--ac-light);
  border:1px solid rgba(79,70,229,.15);
  border-radius:var(--r3);
  padding:11px 13px;
  text-align:left;
}

/* ── TIMING CARD ── */
.timing-card{
  background:var(--bg2);
  border:1px solid var(--br);
  border-radius:var(--r2);
  padding:15px 16px;
  margin-bottom:8px;
  display:flex;align-items:center;gap:14px;
  box-shadow:var(--sh);
}
.timing-ic{
  width:40px;height:40px;border-radius:12px;
  background:var(--ac-light);
  display:flex;align-items:center;justify-content:center;
  font-size:18px;flex-shrink:0;
}
.timing-name{font-size:13px;font-weight:700;color:var(--tx);}
.timing-time{font-size:12px;color:var(--tx2);margin-top:2px;}
.timing-badge{
  margin-left:auto;font-size:10px;font-weight:700;
  padding:4px 9px;border-radius:20px;
  background:var(--ok-bg);color:var(--ok);
  border:1px solid var(--ok-border);
  flex-shrink:0;
}

/* ── ATTENDANCE LIST ── */
.att-list{display:flex;flex-direction:column;gap:8px;}
.att-row{
  background:var(--bg2);
  border:1px solid var(--br);
  border-radius:var(--r2);
  padding:13px 15px;
  display:flex;align-items:center;gap:13px;
  box-shadow:var(--sh);
}
.att-indicator{
  width:38px;height:38px;border-radius:12px;
  display:flex;align-items:center;justify-content:center;
  font-size:16px;flex-shrink:0;
}
.att-indicator.present{background:var(--ok-bg);}
.att-indicator.absent{background:var(--err-bg);}
.att-indicator.late{background:var(--warn-bg);}
.att-body{flex:1;min-width:0;}
.att-date{font-size:14px;font-weight:700;color:var(--tx);}
.att-times{font-size:11px;color:var(--tx2);margin-top:3px;}
.att-tag{
  font-size:10px;font-weight:800;
  padding:4px 9px;border-radius:20px;
  letter-spacing:.5px;text-transform:uppercase;flex-shrink:0;
}
.att-tag.present{background:var(--ok-bg);color:var(--ok);}
.att-tag.absent{background:var(--err-bg);color:var(--err);}
.att-tag.late{background:var(--warn-bg);color:var(--warn);}

/* ── FEE / INVOICE CARDS ── */
.fee-summary{
  background:linear-gradient(135deg,#0f172a,#1e1b4b);
  border-radius:var(--r2);
  padding:18px;color:#fff;
  margin-bottom:12px;
}
.fee-summary-top{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;}
.fee-amount{font-size:28px;font-weight:800;letter-spacing:-.5px;}
.fee-label{font-size:11px;opacity:.6;text-transform:uppercase;letter-spacing:1px;margin-bottom:2px;}
.fee-status-pill{
  font-size:11px;font-weight:700;padding:5px 12px;border-radius:20px;
}
.fee-status-pill.paid{background:rgba(22,163,74,.25);color:#4ade80;}
.fee-status-pill.partial{background:rgba(79,70,229,.3);color:#a5b4fc;}
.fee-status-pill.pending{background:rgba(217,119,6,.25);color:#fbbf24;}
.fee-status-pill.overdue{background:rgba(220,38,38,.25);color:#f87171;}
.fee-row{display:flex;justify-content:space-between;font-size:12px;opacity:.7;padding:3px 0;}
.fee-row span:last-child{font-weight:600;opacity:1;}

.inv-card{
  background:var(--bg2);
  border:1px solid var(--br);
  border-radius:var(--r2);
  padding:14px 16px;
  margin-bottom:8px;
  box-shadow:var(--sh);
}
.inv-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;}
.inv-id{font-size:11px;font-weight:700;color:var(--ac);font-family:monospace;}
.inv-tag{
  font-size:10px;font-weight:700;padding:3px 9px;border-radius:20px;
}
.inv-tag.paid{background:var(--ok-bg);color:var(--ok);}
.inv-tag.partial{background:var(--ac-light);color:var(--ac);}
.inv-type{font-size:13px;font-weight:700;color:var(--tx);margin-bottom:4px;}
.inv-meta{display:flex;gap:16px;font-size:11px;color:var(--tx3);}
.inv-meta span{display:flex;align-items:center;gap:3px;}
.inv-amounts{display:flex;justify-content:space-between;margin-top:10px;padding-top:10px;border-top:1px solid var(--br);}
.inv-paid{font-size:14px;font-weight:800;color:var(--ok);}
.inv-balance{font-size:12px;font-weight:700;color:var(--err);}

/* ── BOOKS ── */
.book-card{
  background:var(--bg2);
  border:1px solid var(--br);
  border-radius:var(--r2);
  padding:14px 16px;
  margin-bottom:8px;
  display:flex;align-items:center;gap:14px;
  box-shadow:var(--sh);
}
.book-ic{
  width:46px;height:46px;border-radius:12px;
  background:var(--bg3);
  display:flex;align-items:center;justify-content:center;
  font-size:22px;flex-shrink:0;
}
.book-title{font-size:13px;font-weight:700;color:var(--tx);}
.book-author{font-size:11px;color:var(--tx3);margin-top:2px;}
.book-due{font-size:11px;font-weight:600;margin-top:4px;}
.book-due.ok{color:var(--ok);}
.book-due.warn{color:var(--warn);}
.book-due.err{color:var(--err);}

/* ── HOLIDAY ── */
.holiday-card{
  background:var(--bg2);
  border:1px solid var(--br);
  border-radius:var(--r2);
  padding:13px 16px;
  margin-bottom:8px;
  display:flex;align-items:center;gap:14px;
  box-shadow:var(--sh);
}
.holiday-date-box{
  min-width:46px;height:46px;border-radius:12px;
  background:var(--ac-light);
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  flex-shrink:0;
}
.holiday-day{font-size:18px;font-weight:800;color:var(--ac);line-height:1;}
.holiday-mon{font-size:9px;font-weight:700;color:var(--ac);text-transform:uppercase;letter-spacing:.5px;}
.holiday-name{font-size:13px;font-weight:700;color:var(--tx);}
.holiday-type{font-size:11px;color:var(--tx3);margin-top:2px;}

/* ── PROFILE CARD ── */
.profile-hero{
  background:linear-gradient(135deg,var(--ac),#7c3aed);
  border-radius:22px;
  padding:20px;
  margin-bottom:10px;
  display:flex;align-items:center;gap:15px;
  color:#fff;
}
.profile-av{
  width:58px;height:58px;border-radius:18px;
  display:flex;align-items:center;justify-content:center;
  font-size:22px;font-weight:800;color:#fff;
  flex-shrink:0;
  background:rgba(255,255,255,.2);
  border:2px solid rgba(255,255,255,.3);
}
.profile-name{font-size:18px;font-weight:800;letter-spacing:-.3px;}
.profile-id{
  font-size:11px;font-weight:700;
  background:rgba(255,255,255,.2);
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
  box-shadow:var(--sh);
}
.info-row{
  display:flex;justify-content:space-between;align-items:center;
  padding:13px 16px;
  border-bottom:1px solid var(--br);
  gap:12px;
}
.info-row:last-child{border-bottom:none;}
.info-lbl{font-size:12px;color:var(--tx3);font-weight:500;flex-shrink:0;}
.info-val{font-size:13px;font-weight:700;color:var(--tx);text-align:right;word-break:break-word;}

.fee-tag{
  display:inline-flex;align-items:center;gap:4px;
  font-size:11px;font-weight:800;
  padding:4px 10px;border-radius:20px;
}
.fee-tag.paid{background:var(--ok-bg);color:var(--ok);}
.fee-tag.partial{background:var(--ac-light);color:var(--ac);}
.fee-tag.pending{background:var(--warn-bg);color:var(--warn);}
.fee-tag.overdue{background:var(--err-bg);color:var(--err);}

/* ── WHATSAPP BUTTON ── */
.wa-btn{
  display:flex;align-items:center;justify-content:center;gap:10px;
  background:#25d366;
  color:#fff;
  border:none;border-radius:var(--r2);
  padding:15px;width:100%;
  font-family:'Outfit',sans-serif;
  font-size:15px;font-weight:700;
  cursor:pointer;
  margin-top:8px;
  box-shadow:0 4px 16px rgba(37,211,102,.3);
  transition:transform .15s,opacity .15s;
}
.wa-btn:active{transform:scale(.97);opacity:.9;}

.contact-card{
  background:var(--bg2);
  border:1px solid var(--br);
  border-radius:var(--r2);
  padding:16px;
  margin-bottom:10px;
  box-shadow:var(--sh);
}
.contact-lib-name{font-size:16px;font-weight:800;color:var(--tx);margin-bottom:4px;}
.contact-detail{font-size:12px;color:var(--tx2);margin-bottom:3px;}

/* ── BOTTOM NAV ── */
.bottom-nav{
  position:fixed;bottom:0;left:0;right:0;
  background:rgba(255,255,255,.95);
  backdrop-filter:blur(20px);
  -webkit-backdrop-filter:blur(20px);
  border-top:1px solid var(--br);
  padding:6px 4px calc(var(--safe-bottom) + 6px);
  display:flex;justify-content:space-around;
  z-index:50;
  box-shadow:0 -4px 20px rgba(15,23,42,.06);
}

.bn-item{
  display:flex;flex-direction:column;align-items:center;gap:2px;
  padding:5px 10px;cursor:pointer;
  opacity:.4;
  transition:opacity .2s,transform .15s;
  border:none;background:none;color:var(--tx);
  font-family:'Outfit',sans-serif;
  border-radius:12px;
  position:relative;min-width:52px;
}
.bn-item.active{opacity:1;}
.bn-item:active{transform:scale(.92);}
.bn-icon{font-size:20px;line-height:1;transition:transform .2s;}
.bn-item.active .bn-icon{transform:scale(1.1);}
.bn-lbl{font-size:9px;font-weight:700;letter-spacing:.3px;}
.bn-item.active .bn-lbl{color:var(--ac);}
.bn-dot{
  position:absolute;top:3px;right:8px;
  width:6px;height:6px;border-radius:50%;
  background:var(--err);display:none;
}
.bn-dot.show{display:block;}

/* ── LOADING / EMPTY ── */
.loading{text-align:center;padding:40px 20px;color:var(--tx3);}
.spinner{
  width:28px;height:28px;
  border:2.5px solid var(--br);
  border-top:2.5px solid var(--ac);
  border-radius:50%;
  animation:spin .8s linear infinite;
  margin:0 auto 12px;
}
@keyframes spin{to{transform:rotate(360deg)}}
.empty{text-align:center;padding:40px 20px;color:var(--tx3);}
.empty-icon{font-size:48px;display:block;margin-bottom:10px;}
.empty-text{font-size:13px;font-weight:500;line-height:1.6;}

/* ── TOAST ── */
.toast{
  position:fixed;bottom:calc(var(--safe-bottom) + 88px);left:50%;
  transform:translateX(-50%) translateY(20px);
  background:#0f172a;
  border-radius:20px;
  padding:10px 18px;
  font-size:13px;font-weight:600;
  color:#fff;
  white-space:nowrap;
  z-index:200;opacity:0;
  transition:all .3s ease;
  pointer-events:none;
  box-shadow:0 8px 24px rgba(0,0,0,.2);
}
.toast.show{opacity:1;transform:translateX(-50%) translateY(0);}

/* ── OFFLINE BANNER ── */
.offline-banner{
  background:var(--warn-bg);
  border-bottom:1px solid var(--warn-border);
  padding:8px 16px;
  font-size:12px;font-weight:600;color:var(--warn);
  text-align:center;display:none;
  flex-shrink:0;
}
.offline-banner.show{display:block;}

@media(max-width:360px){
  .stat-val{font-size:20px;}
  .qr-wrap{width:190px;height:190px;}
  #qrcode{width:162px;height:162px;}
  #qrcode canvas,#qrcode img{width:162px!important;height:162px!important;}
  .bn-item{padding:5px 6px;min-width:44px;}
}
@media(min-width:480px){
  .scroll-area{max-width:480px;margin:0 auto;}
  .header{max-width:480px;margin:0 auto;}
  .bottom-nav{max-width:480px;left:50%;right:auto;transform:translateX(-50%);}
}

.bn-icon .material-icons-round{
  font-size:22px;
  color:inherit;
  line-height:1;
  display:block;
}
.bn-item.active .bn-icon .material-icons-round{
  color:var(--ac);
}

/* Pull to refresh */
.ptr-indicator{
  text-align:center;
  font-size:12px;color:var(--tx3);
  padding:10px;
  margin-top:-40px;
  transition:margin-top .3s;
  flex-shrink:0;
}
.ptr-indicator.show{margin-top:0;}
</style>
</head>
<body>

<!-- ══ LOGIN SCREEN ══ -->
<div id="loginScreen">
  <div class="login-brand">
    <div class="login-icon" id="loginIcon">📚</div>
    <div class="login-title">Student Portal</div>
    <div class="login-sub">Nayi Udaan Library</div>
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
  <div class="login-footer">📚 Your attendance, fees & library info in one place</div>
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

  <!-- Offline Banner -->
  <div class="offline-banner" id="offlineBanner">📡 You're offline — showing cached data</div>
  <!-- Pull to Refresh Indicator -->
  <div class="ptr-indicator" id="ptrIndicator">🔄 Release to refresh…</div> 

  <div class="scroll-area" id="scrollArea">

    <!-- ── HOME TAB ── -->
    <div class="tab-page active" id="tab-home">
      <!-- Banner / Announcement -->
      <div id="bannerArea"></div>

      <!-- Stats -->
      <div class="sec-label">This Month</div>
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

      <!-- QR Code -->
      <div class="sec-label">Your QR Code</div>
      <div class="qr-card">
        <div class="qr-badge">🟢 Live Attendance QR</div>
        <div class="qr-wrap">
          <div id="qrcode"></div>
        </div>
        <div class="qr-expiry">
          ⏱ Valid until: <span class="qr-expiry-val" id="qrExpiry">—</span>
        </div>
        <button class="refresh-btn" onclick="refreshQR()">🔄 Refresh QR</button>
        <div class="scan-hint">📌 Show this QR at the library entrance to mark attendance. Scan again when you leave.</div>
      </div>

      <!-- Library Timings -->
      <div class="sec-label">Library Timings</div>
      <div id="timingList"><div class="loading"><div class="spinner"></div></div></div>
    </div>

    <!-- ── FEES TAB ── -->
    <div class="tab-page" id="tab-fees">
      <div id="feeSummaryArea"></div>
      <div class="sec-label">Payment History</div>
      <div id="invoiceList"><div class="loading"><div class="spinner"></div></div></div>
    </div>

    <!-- ── HISTORY TAB ── -->
    <div class="tab-page" id="tab-history">
      <div class="sec-label">Issued Books</div>
      <div id="bookList"><div class="loading"><div class="spinner"></div></div></div>

      <div class="sec-label">Attendance Records</div>
      <div class="att-list" id="attList">
        <div class="loading"><div class="spinner"></div>Loading…</div>
      </div>
    </div>

    <!-- ── NOTICES TAB ── -->
    <div class="tab-page" id="tab-notices">
      <div class="sec-label">Notices & Announcements</div>
      <div id="noticeList"><div class="loading"><div class="spinner"></div></div></div>

      <div class="sec-label">Holiday Calendar</div>
      <div id="holidayList"><div class="loading"><div class="spinner"></div></div></div>
    </div>

    <!-- ── PROFILE TAB ── -->
    <div class="tab-page" id="tab-profile">
      <div id="profileHero"></div>
      <div class="sec-label">My Details</div>
      <div class="info-card" id="profileCard">
        <div class="loading"><div class="spinner"></div></div>
      </div>

      <div class="sec-label">Contact Library</div>
      <div id="contactArea"></div>
    </div>

  </div><!-- /scroll-area -->

  <!-- Bottom Nav — 5 tabs -->
  <nav class="bottom-nav">
    <button class="bn-item active" onclick="switchTab('home',this)" id="btn-home">
      <div class="bn-dot" id="dot-home"></div>
      <span class="bn-icon"><i class="material-icons-round">home</i></span>
      <span class="bn-lbl">Home</span>
    </button>
    <button class="bn-item" onclick="switchTab('fees',this)" id="btn-fees">
      <div class="bn-dot" id="dot-fees"></div>
      <span class="bn-icon"><i class="material-icons-round">payments</i></span>
      <span class="bn-lbl">Fees</span>
    </button>
    <button class="bn-item" onclick="switchTab('history',this)" id="btn-history">
      <div class="bn-dot"></div>
      <span class="bn-icon"><i class="material-icons-round">history</i></span>
      <span class="bn-lbl">History</span>
    </button>
    <button class="bn-item" onclick="switchTab('notices',this)" id="btn-notices">
      <div class="bn-dot" id="dot-notices"></div>
      <span class="bn-icon"><i class="material-icons-round">announcement</i></span>
      <span class="bn-lbl">Notices</span>
    </button>
    <button class="bn-item" onclick="switchTab('profile',this)" id="btn-profile">
      <div class="bn-dot"></div>
      <span class="bn-icon"><i class="material-icons-round">person</i></span>
      <span class="bn-lbl">Profile</span>
    </button>
  </nav>
</div>

<div class="toast" id="toast"></div>

<script>
  // Load library info on login screen
fetch('api/index.php?action=get_login_info')
  .then(r => r.json())
  .then(d => {
    if (d.logo_url) {
      document.getElementById('loginIcon').innerHTML =
        `<img src="${d.logo_url}" style="width:100%;height:100%;object-fit:contain;border-radius:20px;">`;
    }
    if (d.name) document.querySelector('.login-sub').textContent = d.name;
  }).catch(() => {});
  
const API = 'api/index.php';
let studentData = null;
let qrObj = null;

// ── OFFLINE DETECTION ──
function updateOnlineStatus() {
  const banner = document.getElementById('offlineBanner');
  if (!navigator.onLine) banner.classList.add('show');
  else banner.classList.remove('show');
}
window.addEventListener('online', updateOnlineStatus);
window.addEventListener('offline', updateOnlineStatus);
updateOnlineStatus();

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
      localStorage.removeItem('stu_cache');
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
  localStorage.removeItem('stu_cache');
  location.reload();
}

document.getElementById('loginPhone').addEventListener('keydown', e => { if(e.key==='Enter') doLogin(); });
document.getElementById('loginId').addEventListener('keydown',    e => { if(e.key==='Enter') doLogin(); });

function initApp(id, phone, data) {
  document.getElementById('loginScreen').style.display = 'none';
  document.getElementById('app').style.display = 'flex';

  if (data) {
    studentData = data;
    cacheData(data);
    renderApp(data);
    loadExtras(id, phone);
  } else {
    // Try network first, fall back to cache
    const cached = JSON.parse(localStorage.getItem('stu_cache') || 'null');
    if (cached) { studentData = cached; renderApp(cached); }

    fetch(`${API}?action=get_student_qr&student_id=${encodeURIComponent(id)}&phone=${encodeURIComponent(phone)}`)
      .then(r => r.json())
      .then(d => {
        if (d.error) { if (!cached) doLogout(); return; }
        studentData = d;
        cacheData(d);
        renderApp(d);
        loadExtras(id, phone);
      })
      .catch(() => {
        if (!cached) doLogout();
        else showToast('📡 Offline — showing cached data');
      });
  }
}

function cacheData(data) {
  try { localStorage.setItem('stu_cache', JSON.stringify(data)); } catch(e) {}
}

async function loadExtras(id, phone) {
  // Load notices, holidays, invoices, books in parallel
  try {
    const [noticesRes, holidaysRes, invoicesRes, booksRes] = await Promise.allSettled([
      fetch(`${API}?action=get_student_notices`).then(r=>r.json()),
      fetch(`${API}?action=get_student_holidays`).then(r=>r.json()),
      fetch(`${API}?action=get_student_invoices&student_id=${encodeURIComponent(id)}&phone=${encodeURIComponent(phone)}`).then(r=>r.json()),
      fetch(`${API}?action=get_student_books&student_id=${encodeURIComponent(id)}&phone=${encodeURIComponent(phone)}`).then(r=>r.json()),
    ]);

    if (noticesRes.status==='fulfilled' && !noticesRes.value.error) renderNotices(noticesRes.value.notices||[]);
    if (holidaysRes.status==='fulfilled' && !holidaysRes.value.error) renderHolidays(holidaysRes.value.holidays||[]);
    if (invoicesRes.status==='fulfilled' && !invoicesRes.value.error) renderFees({...invoicesRes.value, student: studentData?.student});
    if (booksRes.status==='fulfilled' && !booksRes.value.error) renderBooks(booksRes.value.books||[]);
  } catch(e) { console.warn('loadExtras failed', e); }
}

function renderApp(data) {
  const stu   = data.student;
  const batch = data.batch;
  const fname = stu.fname || '';
  const lname = stu.lname || '';
  const initials = (fname[0]||'') + (lname[0]||'');
  const color = stu.color || '#4f46e5';

  document.getElementById('appName').textContent  = fname + (lname ? ' ' + lname : '');
  document.getElementById('appBatch').textContent = batch ? '🏫 ' + batch.name : stu.id;
  const av = document.getElementById('appAv');
  av.textContent    = initials.toUpperCase();
  av.style.background = color;

  renderQR(data.token, data.expires_at, stu.id);
  renderStats(data.attendance || []);
  renderHistory(data.attendance || []);
  renderProfile(stu, batch, color);
  renderTimings(data.batch);
  renderBanner(stu);
}

// ── BANNER ──
function renderBanner(stu) {
  const fs = stu.fee_status || 'pending';
  let html = '';
  if (fs === 'overdue') {
    html = `<div class="banner-card" style="background:linear-gradient(135deg,#dc2626,#b91c1c)">
      <div class="banner-tag">⚠ Fee Alert</div>
      <div class="banner-title">Fee Overdue!</div>
      <div class="banner-msg">Your fee payment is overdue. Please contact the library immediately.</div>
    </div>`;
    document.getElementById('dot-fees').classList.add('show');
  } else if (fs === 'pending' || fs === 'partial') {
    const due = stu.due_date ? new Date(stu.due_date).toLocaleDateString('en-IN',{day:'numeric',month:'short',year:'numeric'}) : '';
    html = `<div class="banner-card" style="background:linear-gradient(135deg,#d97706,#b45309)">
      <div class="banner-tag">💳 Fee Reminder</div>
      <div class="banner-title">Fee Due ${due ? 'by ' + due : 'Soon'}</div>
      <div class="banner-msg">Please clear your fee to continue enjoying library services.</div>
    </div>`;
    document.getElementById('dot-fees').classList.add('show');
  } else {
    html = `<div class="banner-card">
      <div class="banner-tag">✨ Welcome</div>
      <div class="banner-title">Good ${greeting()}, ${stu.fname}!</div>
      <div class="banner-msg">Your account is up to date. Happy studying! 📚</div>
    </div>`;
  }
  document.getElementById('bannerArea').innerHTML = html;
}

function greeting() {
  const h = new Date().getHours();
  if (h < 12) return 'Morning';
  if (h < 17) return 'Afternoon';
  return 'Evening';
}

// ── TIMINGS ──
function renderTimings(batch) {
  const el = document.getElementById('timingList');
  if (!batch) {
    el.innerHTML = `<div class="empty"><span class="empty-icon">🕐</span><div class="empty-text">No batch timing found.</div></div>`;
    return;
  }
  const fmt = t => {
    if (!t) return '—';
    const [h,m] = t.split(':');
    const hr = +h;
    return (hr>12?hr-12:(hr||12))+':'+m+' '+(hr>=12?'PM':'AM');
  };
  el.innerHTML = `<div class="timing-card">
    <div class="timing-ic">🏫</div>
    <div>
      <div class="timing-name">${batch.name}</div>
      <div class="timing-time">⏰ ${fmt(batch.start_time)} — ${fmt(batch.end_time)}</div>
    </div>
    <div class="timing-badge">Your Batch</div>
  </div>`;
}

// ── QR ──
function renderQR(token, expiresAt, studentId) {
  const scanUrl = window.location.origin + window.location.pathname.replace('student_app.php','') + 'scan.php?token=' + token;
  document.getElementById('qrcode').innerHTML = '';
  if (qrObj) { try { qrObj.clear(); } catch(e){} }
  qrObj = new QRCode(document.getElementById('qrcode'), {
    text: scanUrl,
    width: 182, height: 182,
    colorDark: '#0f172a', colorLight: '#ffffff',
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

// ── STATS ──
function renderStats(attArr) {
  const present = attArr.filter(a => a.status === 'present').length;
  const total   = attArr.length;
  const absent  = total - present;
  const rate    = total ? Math.round(present / total * 100) : 0;
  document.getElementById('statPresent').textContent = present;
  document.getElementById('statAbsent').textContent  = absent;
  document.getElementById('statRate').textContent    = rate + '%';
}

// ── ATTENDANCE HISTORY ──
function renderHistory(attArr) {
  const el = document.getElementById('attList');
  if (!attArr.length) {
    el.innerHTML = `<div class="empty"><span class="empty-icon">📅</span><div class="empty-text">No attendance records yet.<br>Scan your QR to check in!</div></div>`;
    return;
  }
  el.innerHTML = attArr.map(a => {
    const d       = new Date(a.date);
    const dateStr = d.toLocaleDateString('en-IN',{weekday:'short',day:'numeric',month:'short'});
    const isLate  = +a.is_late;
    const cin     = a.check_in  ? formatTime(a.check_in)  : '—';
    const cout    = a.check_out ? formatTime(a.check_out) : '—';
    const tagClass = a.status === 'present' ? (isLate ? 'late' : 'present') : 'absent';
    const tagText  = a.status === 'present' ? (isLate ? 'Late' : 'Present') : 'Absent';
    const icon     = isLate ? '⚠️' : (a.status==='present' ? '✅' : '❌');
    return `<div class="att-row">
      <div class="att-indicator ${tagClass}">${icon}</div>
      <div class="att-body">
        <div class="att-date">${dateStr}</div>
        <div class="att-times">In: ${cin} · Out: ${cout}${isLate?' · '+a.late_minutes+'min late':''}</div>
      </div>
      <span class="att-tag ${tagClass}">${tagText}</span>
    </div>`;
  }).join('');
}

// ── FEES & INVOICES ──
function renderFees(data) {
  const stu = data.student || studentData?.student;
  const invoices = data.invoices || [];
  const fs = stu?.fee_status || 'pending';
  const feeIcons = {paid:'✅',partial:'◑',pending:'⏳',overdue:'🚨'};

  // Summary card
  const totalPaid = invoices.reduce((s,i) => s + (+i.amount||0), 0);
  const balance   = +(stu?.net_fee||0) - totalPaid;

  document.getElementById('feeSummaryArea').innerHTML = `
    <div class="fee-summary">
      <div class="fee-summary-top">
        <div>
          <div class="fee-label">Net Fee</div>
          <div class="fee-amount">₹ ${Number(stu?.net_fee||0).toLocaleString('en-IN')}</div>
        </div>
        <div class="fee-status-pill ${fs}">${feeIcons[fs]} ${fs.charAt(0).toUpperCase()+fs.slice(1)}</div>
      </div>
      <div class="fee-row"><span>Total Paid</span><span>₹ ${totalPaid.toLocaleString('en-IN')}</span></div>
      <div class="fee-row"><span>Balance</span><span>₹ ${Math.max(0,balance).toLocaleString('en-IN')}</span></div>
      <div class="fee-row"><span>Due Date</span><span>${stu?.due_date ? new Date(stu.due_date).toLocaleDateString('en-IN',{day:'numeric',month:'short',year:'numeric'}) : '—'}</span></div>
    </div>`;

  // Invoice list
  const el = document.getElementById('invoiceList');
  if (!invoices.length) {
    el.innerHTML = `<div class="empty"><span class="empty-icon">🧾</span><div class="empty-text">No payment records yet.</div></div>`;
    return;
  }
  el.innerHTML = invoices.map(inv => `
    <div class="inv-card">
      <div class="inv-top">
        <span class="inv-id">${inv.id}</span>
        <span class="inv-tag ${inv.status}">${inv.status==='paid'?'✅ Paid':'◑ Partial'}</span>
      </div>
      <div class="inv-type">${inv.type}</div>
      <div class="inv-meta">
        <span>📅 ${inv.invoice_date ? new Date(inv.invoice_date).toLocaleDateString('en-IN',{day:'numeric',month:'short',year:'numeric'}) : '—'}</span>
        <span>💳 ${inv.mode||'—'}</span>
        ${inv.month?`<span>📆 ${inv.month}</span>`:''}
      </div>
      <div class="inv-amounts">
        <div><div style="font-size:10px;color:var(--tx3);margin-bottom:2px">PAID</div><div class="inv-paid">₹ ${Number(inv.amount||0).toLocaleString('en-IN')}</div></div>
        ${+inv.balance>0?`<div style="text-align:right"><div style="font-size:10px;color:var(--tx3);margin-bottom:2px">BALANCE</div><div class="inv-balance">₹ ${Number(inv.balance).toLocaleString('en-IN')}</div></div>`:'<div style="color:var(--ok);font-size:12px;font-weight:700;align-self:flex-end">✓ Cleared</div>'}
      </div>
    </div>`).join('');
}

// ── BOOKS ──
function renderBooks(books) {
  const el = document.getElementById('bookList');
  if (!books.length) {
    el.innerHTML = `<div class="empty"><span class="empty-icon">📖</span><div class="empty-text">No books currently issued.</div></div>`;
    return;
  }
  el.innerHTML = books.map(b => {
    const due = b.due_date ? new Date(b.due_date) : null;
    const today = new Date();
    const diff = due ? Math.ceil((due - today) / 86400000) : null;
    const dueClass = diff === null ? '' : (diff < 0 ? 'err' : (diff <= 3 ? 'warn' : 'ok'));
    const dueText = diff === null ? '—' : (diff < 0 ? `Overdue by ${Math.abs(diff)} day(s)` : (diff === 0 ? 'Due today!' : `Due in ${diff} day(s)`));
    return `<div class="book-card">
      <div class="book-ic">${b.emoji||'📘'}</div>
      <div>
        <div class="book-title">${b.title}</div>
        <div class="book-author">${b.author||'—'}</div>
        <div class="book-due ${dueClass}">📅 ${dueText}</div>
      </div>
    </div>`;
  }).join('');
}

// ── NOTICES ──
function renderNotices(notices) {
  const el = document.getElementById('noticeList');
  if (!notices.length) {
    el.innerHTML = `<div class="empty"><span class="empty-icon">📢</span><div class="empty-text">No notices right now.</div></div>`;
    return;
  }
  // Show red dot on notices tab
  document.getElementById('dot-notices').classList.add('show');
  el.innerHTML = notices.map(n => `
    <div class="notice-card">
      <div class="notice-title">${n.title}</div>
      <div class="notice-msg">${n.message}</div>
      <div class="notice-date">${n.created_at ? new Date(n.created_at).toLocaleDateString('en-IN',{day:'numeric',month:'short',year:'numeric'}) : ''}</div>
    </div>`).join('');
}

// ── HOLIDAYS ──
function renderHolidays(holidays) {
  const el = document.getElementById('holidayList');
  if (!holidays.length) {
    el.innerHTML = `<div class="empty"><span class="empty-icon">🗓️</span><div class="empty-text">No upcoming holidays.</div></div>`;
    return;
  }
  el.innerHTML = holidays.map(h => {
    const d = new Date(h.date);
    return `<div class="holiday-card">
      <div class="holiday-date-box">
        <div class="holiday-day">${d.getDate()}</div>
        <div class="holiday-mon">${d.toLocaleString('en-IN',{month:'short'})}</div>
      </div>
      <div>
        <div class="holiday-name">${h.name}</div>
        <div class="holiday-type">${h.type||'Holiday'}</div>
      </div>
    </div>`;
  }).join('');
}

// ── PROFILE ──
function renderProfile(stu, batch, color) {
  const fname = stu.fname || '';
  const lname = stu.lname || '';
  const initials = (fname[0]||'') + (lname[0]||'');

  document.getElementById('profileHero').innerHTML = `
    <div class="profile-hero">
      <div class="profile-av">${initials.toUpperCase()}</div>
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
    <div class="info-row"><span class="info-lbl">Due Date</span><span class="info-val" style="color:${fs==='overdue'?'var(--err)':'var(--tx)'}">${dueDate}</span></div>
    <div class="info-row"><span class="info-lbl">Net Fee</span><span class="info-val">₹ ${Number(stu['net_fee']||0).toLocaleString('en-IN')}</span></div>
    <div class="info-row"><span class="info-lbl">Joined</span><span class="info-val">${stu.join_date ? new Date(stu.join_date).toLocaleDateString('en-IN',{day:'numeric',month:'short',year:'numeric'}) : '—'}</span></div>
  `;

  // Contact section
  renderContact(studentData?.settings);
}

function renderContact(settings) {
  // Try to get settings from studentData if available
  const waNumber = studentData?.settings?.wa_number || '';
  const libName  = studentData?.settings?.name || 'Nayi Udaan Library';
  const libPhone = studentData?.settings?.phone || '';
  const libAddr  = studentData?.settings?.addr || '';

  document.getElementById('contactArea').innerHTML = `
    <div class="contact-card">
      <div class="contact-lib-name">📚 ${libName}</div>
      ${libPhone ? `<div class="contact-detail">📞 ${libPhone}</div>` : ''}
      ${libAddr  ? `<div class="contact-detail">📍 ${libAddr}</div>` : ''}
      ${waNumber ? `
      <button class="wa-btn" onclick="openWA('${waNumber}')">
        <span>💬</span> Chat on WhatsApp
      </button>` : ''}
    </div>`;
}

function openWA(number) {
  let clean = number.replace(/\D/g,'');
  // Add India country code if not present
  if (clean.length === 10) clean = '91' + clean;
  const msg = encodeURIComponent(`Hello, I am a student at ${studentData?.settings?.name||'Nayi Udaan Library'}. My ID is ${studentData?.student?.id||''}. I need assistance.`);
  window.open(`https://wa.me/${clean}?text=${msg}`, '_blank');
}

// ── TAB SWITCHING ──
function switchTab(tab, el) {
  document.querySelectorAll('.tab-page').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.bn-item').forEach(b => b.classList.remove('active'));
  document.getElementById('tab-' + tab).classList.add('active');
  el.classList.add('active');
  document.getElementById('scrollArea').scrollTop = 0;
  if (tab === 'history' && studentData) renderHistory(studentData.attendance || []);
  // Clear notice dot when viewed
  if (tab === 'notices') document.getElementById('dot-notices').classList.remove('show');
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
  setTimeout(() => t.classList.remove('show'), 2500);
}

// Auto-refresh QR every 20 minutes
setInterval(refreshQR, 20 * 60 * 1000);

// Refresh when student comes back to the app
document.addEventListener('visibilitychange', () => {
  if (document.visibilityState === 'visible') {
    const auth = JSON.parse(localStorage.getItem('stu_auth') || 'null');
    if (!auth) return;
    const lastRefresh = +localStorage.getItem('stu_last_refresh') || 0;
    const diff = Date.now() - lastRefresh;
    // Only refresh if more than 3 minutes have passed
    if (diff > 3 * 60 * 1000) {
      localStorage.setItem('stu_last_refresh', Date.now());
      fetch(`${API}?action=get_student_qr&student_id=${encodeURIComponent(auth.id)}&phone=${encodeURIComponent(auth.phone)}`)
        .then(r => r.json())
        .then(data => {
          if (data.error) return;
          studentData = data;
          cacheData(data);
          renderApp(data);
          loadExtras(auth.id, auth.phone);
        }).catch(() => {});
    }
  }
});

// ── PULL TO REFRESH ──
let ptrStart = 0;
let ptrActive = false;
const scrollArea = document.getElementById('scrollArea');
const ptrEl = document.getElementById('ptrIndicator');

scrollArea.addEventListener('touchstart', e => {
  if (scrollArea.scrollTop === 0) {
    ptrStart = e.touches[0].clientY;
    ptrActive = true;
  }
}, { passive: true });

scrollArea.addEventListener('touchmove', e => {
  if (!ptrActive) return;
  const diff = e.touches[0].clientY - ptrStart;
  if (diff > 60) ptrEl.classList.add('show');
  else ptrEl.classList.remove('show');
}, { passive: true });

// On release, if indicator is shown, refresh data
scrollArea.addEventListener('touchend', () => {
  if (!ptrActive) return;
  ptrActive = false;
  if (ptrEl.classList.contains('show')) {
    ptrEl.textContent = '🔄 Refreshing…';
    const auth = JSON.parse(localStorage.getItem('stu_auth') || 'null');
    if (auth) {
      localStorage.removeItem('stu_cache');
      fetch(`${API}?action=get_student_qr&student_id=${encodeURIComponent(auth.id)}&phone=${encodeURIComponent(auth.phone)}`)
        .then(r => r.json())
        .then(data => {
          if (data.error) return;
          studentData = data;
          cacheData(data);
          renderApp(data);
          loadExtras(auth.id, auth.phone);
          showToast('✅ Data refreshed!');
        })
        .catch(() => showToast('📡 No internet'));
    }
  }
  ptrEl.classList.remove('show');
  ptrEl.textContent = '🔄 Release to refresh…';
});
</script>
</body>
</html>
