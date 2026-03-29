<?php
session_start();
if (empty($_SESSION['staff_id'])) {
    header('Location: login.php');
    exit;
}
$staffName = htmlspecialchars($_SESSION['staff_name'] ?? 'Admin');
$staffRole = htmlspecialchars(ucfirst($_SESSION['staff_role'] ?? 'staff'));
$staffInitials = strtoupper(implode('', array_map(fn($p) => $p[0], array_slice(explode(' ', $_SESSION['staff_name'] ?? 'A'), 0, 2))));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>OPTMS Tech ERP v6</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=DM+Sans:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
:root{--bg:#f0ede8;--sf:#faf8f5;--sf2:#ede9e3;--sf3:#e4dfd8;--br:#d8d3cc;--br2:#c8c2ba;--ac:#4a7c6f;--ac2:#5a9186;--gd:#c47d2b;--gd2:#d4902f;--em:#3a7d5e;--ro:#c0444f;--vi:#7c5cbf;--sk:#3a7ab0;--wa:#25d366;--wa2:#128c7e;--or:#e67e22;--tx:#2c2825;--tx2:#5a534c;--tx3:#8a8078;--fd:'DM Serif Display',serif;--fb:'DM Sans',sans-serif;--fm:'JetBrains Mono',monospace;--r:12px;--r2:8px;--sh:0 2px 16px rgba(60,50,40,.10);--sh2:0 8px 32px rgba(60,50,40,.18)}
*{margin:0;padding:0;box-sizing:border-box}
body{
font-family:var(--fb);
font-size:14px;
background:var(--bg);
color:var(--tx);
min-height:100vh;
overflow-x:hidden;
line-height:1.6
}
/* SIDEBAR */
.sb{position:fixed;left:0;top:0;bottom:0;width:232px;background:var(--sf);border-right:1px solid var(--br);display:flex;flex-direction:column;z-index:200}
.sb-logo{padding:18px 16px 14px;border-bottom:1px solid var(--br)}
.logo-row{display:flex;align-items:center;gap:9px}
.logo-ic{width:33px;height:33px;background:linear-gradient(135deg,var(--ac),var(--vi));border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:15px}
.logo-tx{font-family:var(--fd);font-size:18px}.logo-sb{font-size:9px;color:var(--tx3);font-family:var(--fm);letter-spacing:1.5px;text-transform:uppercase}
.sb-nav{flex:1;padding:10px 9px;overflow-y:auto}
.nl{font-size:9px;color:var(--tx3);letter-spacing:1.5px;text-transform:uppercase;padding:0 7px;margin-bottom:3px;font-family:var(--fm)}
.ni{display:flex;align-items:center;gap:8px;padding:7px 9px;border-radius:var(--r2);cursor:pointer;transition:all .2s;color:var(--tx2);font-size:12.5px;font-weight:500;position:relative;white-space:nowrap}
.ni:hover{background:var(--sf2);color:var(--tx)}
.ni.active{background:rgba(74,124,111,.12);color:var(--ac)}
.ni.active::before{content:'';position:absolute;left:0;top:5px;bottom:5px;width:3px;background:var(--ac);border-radius:0 3px 3px 0}
.ni-ic{font-size:14px;width:17px;text-align:center}
.nbadge{margin-left:auto;background:var(--ro);color:#fff;font-size:9px;font-weight:700;padding:1px 5px;border-radius:20px;min-width:16px;text-align:center}
.nbadge.g{background:var(--em)}.nbadge.y{background:var(--gd);color:#000}.nbadge.wa{background:var(--wa)}.nbadge.or{background:var(--or)}
.ns{margin-bottom:16px}
.sb-foot{padding:12px;border-top:1px solid var(--br)}
.u-card{display:flex;align-items:center;gap:8px;padding:8px 9px;background:var(--sf2);border-radius:var(--r2)}
.u-av{width:28px;height:28px;background:linear-gradient(135deg,var(--gd),var(--ro));border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#fff}
.u-nm{font-size:11.5px;font-weight:600}.u-rl{font-size:10px;color:var(--tx3)}
/* MAIN */
.main{margin-left:232px;min-height:100vh;display:flex;flex-direction:column}
.topbar{position:sticky;top:0;z-index:100;background:rgba(240,237,232,.97);backdrop-filter:blur(12px);border-bottom:1px solid var(--br);padding:0 22px;height:56px;display:flex;align-items:center;gap:11px}
.pg-title{font-family:var(--fd);font-size:18px;flex:1}
.srch{display:flex;align-items:center;gap:6px;background:var(--sf);border:1px solid var(--br2);border-radius:var(--r2);padding:6px 10px;width:190px}
.srch input{background:none;border:none;outline:none;color:var(--tx);font-size:12px;width:100%;font-family:var(--fb)}
.srch input::placeholder{color:var(--tx3)}
.btn{display:inline-flex;align-items:center;gap:4px;padding:6px 12px;border-radius:var(--r2);font-size:12px;font-weight:600;cursor:pointer;border:none;transition:all .2s;font-family:var(--fb)}
.bp{background:var(--ac);color:#fff}.bp:hover{background:var(--ac2);transform:translateY(-1px)}
.bg{background:transparent;color:var(--tx2);border:1px solid var(--br2)}.bg:hover{background:var(--sf2);color:var(--tx)}
.bd{background:var(--ro);color:#fff}.bd:hover{background:#a83840}
.bwa{background:var(--wa);color:#fff}.bwa:hover{background:var(--wa2)}
.bor{background:var(--or);color:#fff}.bor:hover{background:#d35400}
/* CONTENT */
.content{padding:18px 22px;flex:1}
.page{display:none}.page.active{display:block}
/* STATS */
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:18px}
.sc{
background:linear-gradient(135deg,#ffffff,#f8f6f2);
border:1px solid var(--br);
border-radius:var(--r);
padding:16px;
position:relative;
overflow:hidden;
transition:all .3s
}
.sc:hover{border-color:var(--br2);transform:translateY(-2px);box-shadow:var(--sh)}
.sc::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:var(--ca,var(--ac))}
.s-ic{width:36px;height:36px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:16px;margin-bottom:10px}
.s-lb{font-size:9.5px;color:var(--tx3);text-transform:uppercase;letter-spacing:.8px;font-family:var(--fm);margin-bottom:3px}
.s-vl{font-size:24px;font-weight:700;color:var(--tx);line-height:1;margin-bottom:5px;font-family:var(--fd)}
.s-mt{font-size:11px;color:var(--tx3)}
.bup{background:rgba(58,125,94,.12);color:var(--em);font-size:10px;font-weight:600;padding:2px 5px;border-radius:4px}
.bdn{background:rgba(192,68,79,.12);color:var(--ro);font-size:10px;font-weight:600;padding:2px 5px;border-radius:4px}
/* PANEL */
.panel{background:var(--sf);border:1px solid var(--br);border-radius:var(--r);overflow:hidden;margin-bottom:14px}
.ph{padding:13px 17px;border-bottom:1px solid var(--br);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px}
.pt{font-family:var(--fd);font-size:14.5px}.pb{padding:16px}
/* TABLE */
.tw{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:12px}
thead th{text-align:left;padding:8px 12px;background:var(--sf2);color:var(--tx3);font-size:9px;font-weight:600;text-transform:uppercase;letter-spacing:.9px;font-family:var(--fm);border-bottom:1px solid var(--br)}
tbody tr{border-bottom:1px solid var(--br);transition:background .15s}
tbody tr:hover{background:var(--sf2)}
tbody tr:last-child{border-bottom:none}
tbody td{padding:9px 12px;color:var(--tx2);vertical-align:middle}
/* TAGS */
.tag{display:inline-flex;align-items:center;gap:3px;padding:2px 7px;border-radius:4px;font-size:10px;font-weight:600;font-family:var(--fm)}
.tpd{background:rgba(58,125,94,.12);color:var(--em)}.tpn{background:rgba(196,125,43,.12);color:var(--gd)}
.tod{background:rgba(192,68,79,.12);color:var(--ro)}.tac{background:rgba(74,124,111,.12);color:var(--ac2)}
.tis{background:rgba(124,92,191,.1);color:var(--vi)}.trt{background:rgba(58,125,94,.1);color:var(--em)}
.tav{background:rgba(74,124,111,.1);color:var(--ac)}.twa{background:rgba(37,211,102,.12);color:var(--wa2)}
.tpart{background:rgba(58,122,176,.12);color:var(--sk)}.tor{background:rgba(230,126,34,.12);color:var(--or)}
/* MODAL */
.mo{display:none;position:fixed;inset:0;background:rgba(44,40,37,.52);z-index:500;align-items:center;justify-content:center;padding:16px}
.mo.open{display:flex}
.md{background:var(--sf);border-radius:var(--r);width:100%;max-width:540px;max-height:94vh;overflow-y:auto;box-shadow:var(--sh2);animation:mIn .22s ease}
.md.wide{max-width:680px}.md.lg{max-width:800px}
@keyframes mIn{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:translateY(0)}}
.mh{padding:16px 19px;border-bottom:1px solid var(--br);display:flex;align-items:center;justify-content:space-between}
.mt{font-family:var(--fd);font-size:16px}
.mc{width:28px;height:28px;border-radius:6px;background:var(--sf2);border:none;cursor:pointer;font-size:15px;display:flex;align-items:center;justify-content:center;color:var(--tx2);transition:all .2s}
.mc:hover{background:var(--sf3)}.mb{padding:18px}.mf{padding:12px 19px;border-top:1px solid var(--br);display:flex;justify-content:flex-end;gap:9px}
/* FORM */
.fg{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.fgi{display:flex;flex-direction:column;gap:4px}.fgi.full{grid-column:1/-1}
label{font-size:11px;font-weight:600;color:var(--tx2);letter-spacing:.3px}
input,select,textarea{padding:7px 10px;border:1px solid var(--br2);border-radius:var(--r2);background:var(--sf2);color:var(--tx);font-size:12.5px;font-family:var(--fb);outline:none;transition:border-color .2s;width:100%}
input:focus,select:focus,textarea:focus{border-color:var(--ac)}
textarea{resize:vertical;min-height:70px}select option{background:var(--sf)}
/* SECTION */
.sec-hd{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:8px}
.sec-t{font-family:var(--fd);font-size:15px}.sec-s{font-size:11px;color:var(--tx3);margin-top:2px}
/* BATCH */
.sbar{height:5px;background:var(--sf2);border-radius:3px;overflow:hidden;margin-bottom:7px}
.sfill{height:100%;border-radius:3px;transition:width 1s ease}
.sf-g{background:linear-gradient(90deg,var(--em),#4ead82)}.sf-y{background:linear-gradient(90deg,var(--gd),var(--gd2))}.sf-r{background:linear-gradient(90deg,var(--ro),#e05565)}
.bst{font-size:9px;font-weight:700;padding:3px 8px;border-radius:20px;font-family:var(--fm)}
.bst-o{background:rgba(58,125,94,.12);color:var(--em)}.bst-f{background:rgba(192,68,79,.12);color:var(--ro)}.bst-n{background:rgba(196,125,43,.12);color:var(--gd)}
/* SEAT VISUAL */
.seat-visual{display:flex;flex-wrap:wrap;gap:3px;margin-top:10px}
.seat-cell{width:34px;height:23px;border-radius:4px;border:1px solid;display:flex;align-items:center;justify-content:center;font-size:7.5px;font-family:var(--fm);cursor:pointer;transition:all .15s;font-weight:600;position:relative}
.seat-cell:hover{transform:scale(1.1);z-index:5}
.seat-occ{background:rgba(192,68,79,.14);border-color:rgba(192,68,79,.4);color:var(--ro)}
.seat-vac{background:rgba(58,125,94,.1);border-color:rgba(58,125,94,.3);color:var(--em)}
.seat-due{background:rgba(230,126,34,.2);border-color:rgba(230,126,34,.5);color:var(--or);animation:pulseDue 2s infinite}
.seat-overdue{background:rgba(192,68,79,.25);border-color:rgba(192,68,79,.6);color:var(--ro);animation:pulseDue 1s infinite}
.seat-tooltip{display:none;position:absolute;bottom:calc(100%+4px);left:50%;transform:translateX(-50%);background:var(--tx);color:var(--sf);font-size:9px;padding:3px 7px;border-radius:4px;white-space:nowrap;z-index:20;pointer-events:none}
.seat-cell:hover .seat-tooltip{display:block}
@keyframes pulseDue{0%,100%{opacity:1}50%{opacity:.6}}
/* DONUT */
.dn-wrap{display:flex;align-items:center;gap:16px;padding:14px 17px}
.dn-leg{flex:1;display:flex;flex-direction:column;gap:6px}
.dli{display:flex;align-items:center;gap:6px;font-size:12px}
.dld{width:7px;height:7px;border-radius:2px}.dll{flex:1;color:var(--tx2)}.dlv{font-weight:700;font-family:var(--fm);font-size:12px}
/* ACTIVITY */
.act-it{display:flex;gap:9px;padding:9px 0;border-bottom:1px solid var(--br)}
.act-it:last-child{border-bottom:none}
.act-d{width:26px;height:26px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0}
.act-tx{font-size:12px;color:var(--tx2);line-height:1.5}.act-tx strong{color:var(--tx)}.act-tm{font-size:10px;color:var(--tx3);font-family:var(--fm);margin-top:2px}
/* CHART */
.cbar{flex:1;border-radius:3px 3px 0 0;cursor:pointer;position:relative}
.cbar:hover{opacity:.75}
.cbar .tt{display:none;position:absolute;bottom:calc(100%+4px);left:50%;transform:translateX(-50%);background:var(--tx);color:var(--sf);font-size:9px;padding:2px 6px;border-radius:4px;white-space:nowrap;font-family:var(--fm);z-index:10}
.cbar:hover .tt{display:block}
/* CALENDAR */
.mcal{display:grid;grid-template-columns:repeat(7,1fr);gap:3px;font-family:var(--fm)}
.cal-dl{text-align:center;color:var(--tx3);padding:2px 0;font-size:9px}
.cal-d{text-align:center;padding:5px 2px;border-radius:4px;cursor:pointer;color:var(--tx2);transition:all .15s;font-size:11px}
.cal-d:hover{background:var(--sf3)}.cal-d.today{background:var(--ac);color:#fff;font-weight:700}
.cal-d.event{color:var(--gd2);font-weight:600}.cal-d.empty{color:transparent;pointer-events:none}
/* TOAST */
.toast-wrap{position:fixed;bottom:18px;right:18px;z-index:9999;display:flex;flex-direction:column;gap:7px}
.toast{padding:11px 15px;border-radius:var(--r2);background:var(--tx);color:var(--sf);font-size:12.5px;font-weight:500;box-shadow:var(--sh2);display:flex;align-items:center;gap:7px;animation:tIn .28s ease;min-width:220px}
.toast.ok{background:var(--em)}.toast.er{background:var(--ro)}.toast.wn{background:var(--gd);color:#fff}.toast.wa{background:var(--wa)}
@keyframes tIn{from{opacity:0;transform:translateX(28px)}to{opacity:1;transform:translateX(0)}}
@keyframes tOut{from{opacity:1}to{opacity:0;transform:translateX(28px)}}
/* TABS */
.tabs{display:flex;gap:2px;background:var(--sf2);padding:3px;border-radius:var(--r2)}
.tab{flex:1;padding:5px 10px;text-align:center;font-size:11.5px;font-weight:500;color:var(--tx3);border-radius:6px;cursor:pointer;transition:all .2s;white-space:nowrap}
.tab.active{background:var(--sf);color:var(--tx)}
/* PAG */
.pag{display:flex;align-items:center;justify-content:space-between;padding:9px 14px;border-top:1px solid var(--br)}
.pag-i{font-size:11px;color:var(--tx3)}.pag-b{display:flex;gap:3px}
.pb2{padding:3px 8px;border-radius:5px;font-size:11px;cursor:pointer;border:1px solid var(--br2);background:var(--sf);color:var(--tx2);transition:all .2s}
.pb2:hover,.pb2.active{background:var(--ac);color:#fff;border-color:var(--ac)}
/* PROGRESS */
.prg{height:6px;background:var(--sf2);border-radius:3px;overflow:hidden}
.prf{height:100%;border-radius:3px}
/* GRIDS */
.g2{display:grid;grid-template-columns:1fr 1fr;gap:14px}.g3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px}
.g4{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}.gm{display:grid;grid-template-columns:1fr 310px;gap:14px}
/* EMPTY */
.empty{text-align:center;padding:36px 16px;color:var(--tx3)}
.empty .ei{font-size:36px;margin-bottom:8px}.empty .et{font-size:12.5px}
/* QUICK ACTIONS */
.qa-gr{display:grid;grid-template-columns:repeat(8,1fr);gap:9px;margin-bottom:18px}
.qa-b{display:flex;flex-direction:column;align-items:center;gap:6px;padding:12px 7px;background:var(--sf);border:1px solid var(--br);border-radius:var(--r);cursor:pointer;transition:all .2s;text-align:center}
.qa-b:hover{border-color:var(--ac);background:rgba(74,124,111,.05);transform:translateY(-2px)}
.qa-ic{width:36px;height:36px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:17px}
.qa-lb{font-size:10px;font-weight:500;color:var(--tx2);line-height:1.3}
/* ALERTS */
.al-row{display:grid;grid-template-columns:1fr 1fr 1fr;gap:11px;margin-bottom:18px}
.al-card{padding:11px 14px;border-radius:var(--r);border:1px solid;display:flex;align-items:flex-start;gap:9px}
.al-w{background:rgba(196,125,43,.07);border-color:rgba(196,125,43,.25)}.al-d{background:rgba(192,68,79,.07);border-color:rgba(192,68,79,.25)}.al-i{background:rgba(74,124,111,.07);border-color:rgba(74,124,111,.25)}
.al-t{font-size:12px;font-weight:600;margin-bottom:2px}.al-b{font-size:11px;color:var(--tx2);line-height:1.4}
/* FEE ITEMS */
.fi{display:flex;align-items:center;gap:9px;padding:9px 12px;background:var(--sf2);border-radius:var(--r2);margin-bottom:6px}
.fd2{width:7px;height:7px;border-radius:50%;flex-shrink:0}.fn2{flex:1;font-size:12.5px;font-weight:500}.fsb{font-size:10px;color:var(--tx3)}.fa{font-size:13px;font-weight:700;font-family:var(--fm)}
/* EXP ITEMS */
.ei2{display:flex;align-items:center;gap:10px;padding:10px 12px;background:var(--sf2);border:1px solid var(--br);border-radius:var(--r2)}
.eic{width:31px;height:31px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0}
.en2{font-size:12.5px;font-weight:500}.ed{font-size:10px;color:var(--tx3);font-family:var(--fm)}.ea{font-size:13px;font-weight:700;font-family:var(--fm)}.ea-d{color:var(--ro)}.ea-c{color:var(--em)}
/* STU */
.si{display:flex;align-items:center;gap:8px}
.sav{width:27px;height:27px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#fff;flex-shrink:0}
/* CHIP */
.chip{display:inline-flex;align-items:center;gap:3px;padding:3px 8px;border-radius:20px;font-size:10px;font-weight:600;border:1px solid}
.chip-tl{border-color:rgba(74,124,111,.3);color:var(--ac);background:rgba(74,124,111,.08)}
/* NOTIF BTN */
.nb-btn{width:32px;height:32px;background:var(--sf);border:1px solid var(--br2);border-radius:var(--r2);display:flex;align-items:center;justify-content:center;cursor:pointer;position:relative;color:var(--tx2);font-size:13px;transition:all .2s}
.nb-btn:hover{background:var(--sf2)}
.nd{position:absolute;top:4px;right:4px;width:7px;height:7px;background:var(--ro);border-radius:50%;border:2px solid var(--bg)}
/* DIVIDER */
.sdiv{font-size:11px;font-weight:600;color:var(--tx3);text-transform:uppercase;letter-spacing:1px;font-family:var(--fm);margin:14px 0 8px;padding-bottom:4px;border-bottom:1px solid var(--br)}
/* TOGGLE */
.toggle-wrap{position:relative;display:inline-block;width:36px;height:20px}
.toggle-inp{display:none}
.toggle-sl{position:absolute;inset:0;background:var(--br2);border-radius:20px;cursor:pointer;transition:.3s}
.toggle-sl::before{content:'';position:absolute;width:14px;height:14px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s}
.toggle-inp:checked+.toggle-sl{background:var(--ac)}
.toggle-inp:checked+.toggle-sl::before{transform:translateX(16px)}
.perm-row{display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--br)}
.perm-row:last-child{border-bottom:none}
/* WHATSAPP */
.wa-panel{background:linear-gradient(135deg,rgba(37,211,102,.07),rgba(18,140,126,.05));border:1px solid rgba(37,211,102,.22);border-radius:var(--r)}
.wa-tpl{background:var(--sf);border:1px solid rgba(37,211,102,.25);border-radius:var(--r2);padding:11px 13px;cursor:pointer;transition:all .2s}
.wa-tpl:hover{border-color:var(--wa);background:rgba(37,211,102,.05);transform:translateY(-1px)}
.wa-tpl .wt-ic{font-size:20px;margin-bottom:5px}.wa-tpl .wt-lb{font-size:12px;font-weight:600;color:var(--tx);margin-bottom:2px}.wa-tpl .wt-ds{font-size:10px;color:var(--tx3)}
.wa-preview{background:#e3f7d5;border-radius:12px 12px 0 12px;padding:12px 14px;font-size:12px;line-height:1.6;color:#1a1a1a;white-space:pre-line;border:1px solid rgba(37,211,102,.2)}
.wa-status-dot{width:8px;height:8px;border-radius:50%;background:var(--wa);display:inline-block;animation:waPulse 1.5s infinite}
@keyframes waPulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.6;transform:scale(.8)}}
/* DISCOUNT */
.disc-badge{background:rgba(230,126,34,.12);border:1px solid rgba(230,126,34,.3);border-radius:var(--r2);padding:6px 10px;display:flex;align-items:center;gap:8px}
.fee-due-row{background:rgba(192,68,79,.04) !important;border-left:3px solid var(--ro)}
.fee-partial-row{background:rgba(230,126,34,.04) !important;border-left:3px solid var(--or)}
.seat-legend{display:flex;gap:12px;flex-wrap:wrap;font-size:10px;color:var(--tx3);margin-top:8px}
.sl-item{display:flex;align-items:center;gap:5px}
.sl-dot{width:12px;height:10px;border-radius:2px}
/* FEE PARTIAL BAR */
.fee-partial-wrap{margin-top:5px}
.fee-partial-bar{height:6px;background:var(--sf2);border-radius:3px;overflow:hidden;margin:3px 0}
.fee-partial-fill{height:100%;border-radius:3px;background:linear-gradient(90deg,var(--em),#4ead82)}
.fee-bal-badge{display:inline-flex;align-items:center;gap:3px;padding:2px 6px;border-radius:4px;background:rgba(192,68,79,.1);color:var(--ro);font-size:10px;font-weight:700;font-family:var(--fm)}

.sc.green{--ca:#3bb273}
.sc.blue{--ca:#4a8cff}
.sc.orange{--ca:#ff9f43}
.sc.red{--ca:#ff5c5c}
.sc.purple{--ca:#8e6cff}
.panel{background:rgba(255,255,255,0.85);backdrop-filter:blur(6px)}
.panel:hover{transform:translateY(-2px);box-shadow:0 10px 25px rgba(0,0,0,0.06)}

/* SCROLLBAR */
::-webkit-scrollbar{width:4px;height:4px}::-webkit-scrollbar-track{background:transparent}::-webkit-scrollbar-thumb{background:var(--br2);border-radius:3px}

/* STUDENT PROFILE */
.sp-header{background:linear-gradient(135deg,var(--ac),var(--vi));border-radius:var(--r) var(--r) 0 0;padding:22px 22px 54px;position:relative;overflow:hidden}
.sp-header::before{content:'';position:absolute;top:-30px;right:-30px;width:150px;height:150px;border-radius:50%;background:rgba(255,255,255,.07)}
.sp-av-wrap{position:absolute;bottom:-34px;left:22px;z-index:2}
.sp-av{width:68px;height:68px;border-radius:18px;border:3px solid var(--sf);display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:700;color:#fff;box-shadow:var(--sh)}
.sp-name{color:#fff;font-family:var(--fd);font-size:20px;margin-bottom:3px}
.sp-id{color:rgba(255,255,255,.7);font-size:11px;font-family:var(--fm)}
.sp-body{padding:46px 22px 14px}
.sp-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px}
.sp-field{display:flex;flex-direction:column;gap:3px}
.sp-field.full{grid-column:1/-1}
.sp-label{font-size:10px;font-weight:600;color:var(--tx3);text-transform:uppercase;letter-spacing:.6px;font-family:var(--fm)}
.sp-val{font-size:13px;font-weight:500;color:var(--tx);padding:6px 10px;background:var(--sf2);border-radius:var(--r2);border:1px solid transparent;transition:all .2s}
.sp-val.editable{cursor:text;border-color:var(--br);background:var(--sf)}
.sp-val.editable:hover{border-color:var(--br2)}
.sp-val.editable:focus{border-color:var(--ac);outline:none;background:#fff}
.sp-section{font-size:10px;font-weight:700;color:var(--tx3);text-transform:uppercase;letter-spacing:1px;font-family:var(--fm);margin:14px 0 8px;padding-bottom:5px;border-bottom:1px solid var(--br)}
.sp-fee-bar{height:8px;background:var(--sf2);border-radius:4px;overflow:hidden;margin:5px 0}
.sp-fee-fill{height:100%;border-radius:4px;background:linear-gradient(90deg,var(--em),#4ead82)}
.sp-stat{display:flex;align-items:center;gap:7px;padding:9px 12px;background:var(--sf2);border-radius:var(--r2);font-size:12px}
.sp-stat-ic{font-size:16px}
.sp-edit-toggle{display:flex;align-items:center;gap:6px;padding:4px 10px;border-radius:20px;border:1px solid var(--br2);background:var(--sf2);color:var(--tx2);font-size:11px;font-weight:600;cursor:pointer;transition:all .2s}
.sp-edit-toggle:hover,.sp-edit-toggle.on{background:var(--ac);color:#fff;border-color:var(--ac)}
.sp-seat-chip{display:inline-flex;align-items:center;gap:5px;padding:5px 11px;background:rgba(74,124,111,.1);border:1px solid rgba(74,124,111,.3);border-radius:20px;font-size:12px;font-weight:700;color:var(--ac);font-family:var(--fm);cursor:pointer;transition:all .2s}
.sp-seat-chip:hover{background:rgba(74,124,111,.2);transform:scale(1.05)}

/* WHATSAPP QR */
.wa-qr-box{display:flex;flex-direction:column;align-items:center;gap:12px;padding:18px;background:rgba(37,211,102,.05);border:1px solid rgba(37,211,102,.2);border-radius:var(--r);cursor:pointer;transition:all .2s}
.wa-qr-box:hover{background:rgba(37,211,102,.1)}
.wa-qr-img{width:160px;height:160px;border:3px solid var(--wa);border-radius:10px;display:flex;align-items:center;justify-content:center;background:#fff;overflow:hidden}
.wa-conn-badge{display:flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;font-size:10px;font-weight:700;font-family:var(--fm)}
.wa-conn-ok{background:rgba(37,211,102,.15);color:var(--wa2);border:1px solid rgba(37,211,102,.3)}
.wa-conn-no{background:rgba(192,68,79,.1);color:var(--ro);border:1px solid rgba(192,68,79,.25)}
.wa-steps{display:flex;flex-direction:column;gap:8px}
.wa-step{display:flex;align-items:flex-start;gap:10px;font-size:12px;color:var(--tx2)}
.wa-step-n{width:22px;height:22px;border-radius:50%;background:var(--wa);color:#fff;font-size:10px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0}
@keyframes fuUp{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
.page.active>*{animation:fuUp .28s ease both}
@media(max-width:1100px){
.stats-grid,.qa-gr{grid-template-columns:repeat(2,1fr)}
.gm,.g2,.g3,.g4,.al-row{grid-template-columns:1fr}
#dashBatchCards{grid-template-columns:1fr !important}
}
</style>
</head>
<body>
<!-- SIDEBAR -->
<div class="sb">
  <div class="sb-logo"><div class="logo-row"><div class="logo-ic">📚</div><div><div class="logo-tx">OPTMS Tech</div><div class="logo-sb">ERP v6.0</div></div></div></div>
  <nav class="sb-nav">
    <div class="ns"><div class="nl">Overview</div>
      <div class="ni active" data-page="dashboard"><span class="ni-ic">⊞</span> Dashboard</div>
      <div class="ni" data-page="analytics"><span class="ni-ic">📊</span> Analytics</div>
    </div>
    <div class="ns"><div class="nl">Students</div>
      <div class="ni" data-page="students"><span class="ni-ic">👨‍🎓</span> All Students</div>
      <div class="ni" data-page="enroll" id="ni-enroll"><span class="ni-ic">➕</span> Enroll Student</div>
      <div class="ni" data-page="seats"><span class="ni-ic">🪑</span> Seat Allocation</div>
      <div class="ni" data-page="attendance"><span class="ni-ic">📋</span> Attendance <span class="nbadge" id="b-absent">0</span></div>
    </div>
    <div class="ns"><div class="nl">Books</div>
      <div class="ni" data-page="books"><span class="ni-ic">📖</span> Books Catalog</div>
      <div class="ni" data-page="transactions"><span class="ni-ic">🔄</span> Issue / Returns <span class="nbadge" id="b-overdue">0</span></div>
    </div>
    <div class="ns"><div class="nl">Finance</div>
      <div class="ni" data-page="fees"><span class="ni-ic">💰</span> Fee Management <span class="nbadge" id="b-fee">0</span></div>
      <div class="ni" data-page="invoices"><span class="ni-ic">🧾</span> Invoices</div>
      <div class="ni" data-page="expenses"><span class="ni-ic">💸</span> Expenses</div>
      <div class="ni" data-page="reports"><span class="ni-ic">📈</span> Reports</div>
    </div>
    <div class="ns"><div class="nl">Communication</div>
      <div class="ni" data-page="whatsapp"><span class="ni-ic">💬</span> WhatsApp <span class="nbadge wa">New</span></div>
    </div>
    <div class="ns"><div class="nl">Admin</div>
      <div class="ni" data-page="staff"><span class="ni-ic">👥</span> Staff & Users</div>
      <div class="ni" data-page="notifications"><span class="ni-ic">🔔</span> Notifications <span class="nbadge g" id="b-notif">0</span></div>
      <div class="ni" data-page="settings"><span class="ni-ic">⚙️</span> Settings</div>
    </div>
  </nav>
  <div class="sb-foot">
    <div class="u-card">
      <div class="u-av"><?= $staffInitials ?></div>
      <div style="flex:1"><div class="u-nm"><?= $staffName ?></div><div class="u-rl"><?= $staffRole ?></div></div>
      <span style="color:var(--tx3);cursor:pointer;font-size:13px" title="Change Password" onclick="openM('mChangePw')">⚙</span>
      <a href="logout.php" title="Logout" onclick="return confirm('Logout from the system?')" style="color:var(--ro);text-decoration:none;font-size:13px;cursor:pointer;margin-left:4px">⏻</a>
    </div>
  </div>
</div>

<!-- MAIN -->
<div class="main">
  <div class="topbar">
    <div class="pg-title" id="topTitle">Dashboard</div>
    <div class="srch"><span style="color:var(--tx3);font-size:12px">🔍</span><input id="gSearch" placeholder="Search students, books…" oninput="globalSearch(this.value)"></div>
    <div style="display:flex;align-items:center;gap:9px">
      <div class="chip chip-tl">📅 <span id="todayChip"></span></div>
      <button class="btn bwa" onclick="navTo('whatsapp')" style="gap:5px;font-size:11px">💬 WhatsApp</button>
      <button class="btn bg" onclick="openM('mWaQR')" style="font-size:11px;padding:6px 9px" title="Connect WhatsApp QR">📱</button>
      <div class="nb-btn" onclick="navTo('notifications')">🔔<div class="nd" id="notifDot" style="display:none"></div></div>
      <button class="btn bp" onclick="openM('mEnroll')">+ Enroll</button>
    </div>
  </div>
  <div class="content">
<!-- DASHBOARD -->
<div class="page active" id="page-dashboard">
  <div class="al-row" id="dashAlerts"></div>
  <div class="stats-grid" id="dashStats"></div>
  <div class="qa-gr">
    <div class="qa-b" onclick="openM('mEnroll')"><div class="qa-ic" style="background:rgba(74,124,111,.12)">➕</div><div class="qa-lb">New<br>Enroll</div></div>
    <div class="qa-b" onclick="openM('mCollectFee')"><div class="qa-ic" style="background:rgba(58,125,94,.12)">💳</div><div class="qa-lb">Collect<br>Fee</div></div>
    <div class="qa-b" onclick="openM('mIssueBook')"><div class="qa-ic" style="background:rgba(196,125,43,.12)">📤</div><div class="qa-lb">Issue<br>Book</div></div>
    <div class="qa-b" onclick="openM('mReturnBook')"><div class="qa-ic" style="background:rgba(124,92,191,.12)">📩</div><div class="qa-lb">Return<br>Book</div></div>
    <div class="qa-b" onclick="navTo('seats')"><div class="qa-ic" style="background:rgba(192,68,79,.12)">🪑</div><div class="qa-lb">Seat<br>Booking</div></div>
    <div class="qa-b" onclick="navTo('attendance')"><div class="qa-ic" style="background:rgba(58,122,176,.12)">📋</div><div class="qa-lb">Mark<br>Attend.</div></div>
    <div class="qa-b" onclick="openM('mExpense')"><div class="qa-ic" style="background:rgba(212,144,47,.12)">💸</div><div class="qa-lb">Add<br>Expense</div></div>
    <div class="qa-b" onclick="navTo('whatsapp')"><div class="qa-ic" style="background:rgba(37,211,102,.12)">💬</div><div class="qa-lb">WhatsApp</div></div>
  </div>
  <!-- Row 1: Batch Seats (left) + Expense Tracker (right) -->
  <div class="g2" style="margin-bottom:14px">
    <div>
      <div class="sec-hd"><div><div class="sec-t">Batch-wise Seat Availability</div><div class="sec-s">Live occupancy · 🟠 Fee Pending · 🔴 Fee Overdue</div></div><button class="btn bg" onclick="navTo('seats')" style="font-size:11px">Manage →</button></div>
      <div id="dashBatchCards" style="display:grid;grid-template-columns:1fr 1fr;gap:12px"></div>
    </div>
    <div>
      <div class="sec-hd"><div><div class="sec-t">Expense Tracker</div><div class="sec-s">Monthly outflows by category</div></div><button class="btn bg" onclick="openM('mExpense')" style="font-size:11px">+ Add</button></div>
      <div class="panel" id="dashExpTracker"></div>
    </div>
  </div>
  <!-- Row 2: Recent Students + Fee Overview + Revenue + Calendar -->
  <div class="g2">
    <div>
      <div class="sec-hd"><div><div class="sec-t">Recent Students & Fee Status</div></div><button class="btn bg" onclick="navTo('students')" style="font-size:11px">All →</button></div>
      <div class="panel">
        <div class="tw"><table>
          <thead><tr><th>Student</th><th>Batch</th><th>Seat</th><th>Fee</th><th>Paid</th><th>Balance</th><th>Status</th><th>Msg</th></tr></thead>
          <tbody id="dashStuTable"></tbody>
        </table></div>
      </div>
    </div>
    <div style="display:flex;flex-direction:column;gap:14px">
      <!-- Fee Overview -->
      <div>
        <div class="sec-hd"><div><div class="sec-t">Fee Overview</div></div><button class="btn bg" onclick="navTo('fees')" style="font-size:11px">Details →</button></div>
        <div id="dashFeeOv"></div>
      </div>
      <!-- Revenue split donut -->
      <div class="panel" style="margin-bottom:0">
        <div class="ph"><div class="pt">Revenue Split</div><span style="font-size:10px;color:var(--tx3);font-family:var(--fm)">This Month</span></div>
        <div class="dn-wrap">
          <svg width="84" height="84" viewBox="0 0 84 84">
            <circle cx="42" cy="42" r="32" fill="none" stroke="var(--sf2)" stroke-width="10"/>
            <circle cx="42" cy="42" r="32" fill="none" stroke="var(--ac)" stroke-width="10" stroke-dasharray="121 80" stroke-dashoffset="0" stroke-linecap="round" style="transform-origin:center;transform:rotate(-90deg)"/>
            <circle cx="42" cy="42" r="32" fill="none" stroke="var(--gd)" stroke-width="10" stroke-dasharray="50 151" stroke-dashoffset="-121" stroke-linecap="round" style="transform-origin:center;transform:rotate(-90deg)"/>
            <circle cx="42" cy="42" r="32" fill="none" stroke="var(--em)" stroke-width="10" stroke-dasharray="30 171" stroke-dashoffset="-171" stroke-linecap="round" style="transform-origin:center;transform:rotate(-90deg)"/>
            <text x="42" y="45" text-anchor="middle" fill="var(--tx)" font-size="8.5" font-weight="700" font-family="DM Serif Display" id="donutC">₹0</text>
          </svg>
          <div class="dn-leg">
            <div class="dli"><div class="dld" style="background:var(--ac)"></div><span class="dll">Seat Fees</span><span class="dlv">60%</span></div>
            <div class="dli"><div class="dld" style="background:var(--gd)"></div><span class="dll">Late Fines</span><span class="dlv">25%</span></div>
            <div class="dli"><div class="dld" style="background:var(--em)"></div><span class="dll">Others</span><span class="dlv">15%</span></div>
          </div>
        </div>
        <div style="padding:0 17px 14px">
          <div style="font-size:9.5px;color:var(--tx3);font-family:var(--fm);margin-bottom:6px">WEEKLY COLLECTION</div>
          <div style="display:flex;align-items:flex-end;gap:4px;height:44px" id="weekChart"></div>
          <div style="display:flex;justify-content:space-between;font-size:9px;color:var(--tx3);font-family:var(--fm);margin-top:4px"><span>W1</span><span>W2</span><span>W3</span><span>W4</span></div>
        </div>
      </div>
      <!-- Mini Calendar -->
      <div class="panel" style="margin-bottom:0">
        <div class="ph"><div class="pt">📅 Calendar</div><div style="display:flex;gap:5px"><button class="btn bg" style="font-size:11px;padding:3px 7px" id="calPrev">‹</button><button class="btn bg" style="font-size:11px;padding:3px 7px" id="calNext">›</button></div></div>
        <div class="pb" style="padding-top:10px">
          <div style="text-align:center;font-size:11.5px;font-weight:600;margin-bottom:8px;font-family:var(--fm)" id="calTitle"></div>
          <div class="mcal" id="miniCal"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- STUDENTS -->
<div class="page" id="page-students">
  <div class="sec-hd">
    <div><div class="sec-t">All Students</div><div class="sec-s" id="stuCount2"></div></div>
    <div style="display:flex;gap:7px;align-items:center;flex-wrap:wrap">
      <input placeholder="Search…" style="width:130px;font-size:11.5px" oninput="stuSrch(this.value)" id="stuSrchInp">
      <div class="tabs" id="stuTabs"><div class="tab active" onclick="stuFilt('all',this)">All</div><div class="tab" onclick="stuFilt('paid',this)">Paid</div><div class="tab" onclick="stuFilt('partial',this)">Partial</div><div class="tab" onclick="stuFilt('pending',this)">Pending</div><div class="tab" onclick="stuFilt('overdue',this)">Overdue</div></div>
      <button class="btn bp" onclick="openM('mEnroll')">+ Enroll</button>
      <button class="btn bwa" onclick="navTo('whatsapp')" style="font-size:11px">💬 Bulk Msg</button>
    </div>
  </div>
  <div class="panel"><div class="tw"><table>
    <thead><tr><th>Student</th><th>Batch</th><th>Seat</th><th>Type</th><th>Full Fee</th><th>Discount</th><th>Net Fee</th><th>Paid</th><th>Balance</th><th>Status</th><th>Due</th><th>Action</th></tr></thead>
    <tbody id="stuTable"></tbody>
  </table></div>
  <div class="pag"><span class="pag-i" id="stuPagI"></span><div class="pag-b" id="stuPagB"></div></div></div>
</div>

<!-- SEATS -->
<div class="page" id="page-seats">
  <div class="sec-hd">
    <div><div class="sec-t">Seat Allocation</div><div class="sec-s">Batch seat map with fee status highlight</div></div>
    <div style="display:flex;gap:7px"><button class="btn bp" onclick="openM('mAddBatch')">+ Add Batch</button><button class="btn bg" onclick="openM('mAllocSeat')">Allocate Seat</button></div>
  </div>
  <div class="stats-grid" style="grid-template-columns:repeat(3,1fr)">
    <div class="sc" style="--ca:var(--ac)"><div class="s-ic" style="background:rgba(74,124,111,.1)">🏠</div><div class="s-lb">Total Seats</div><div class="s-vl" id="st-total">0</div></div>
    <div class="sc" style="--ca:var(--em)"><div class="s-ic" style="background:rgba(58,125,94,.1)">✅</div><div class="s-lb">Vacant</div><div class="s-vl" id="st-vacant">0</div></div>
    <div class="sc" style="--ca:var(--ro)"><div class="s-ic" style="background:rgba(192,68,79,.1)">🔴</div><div class="s-lb">Occupied</div><div class="s-vl" id="st-occupied">0</div></div>
  </div>
  <div style="margin-bottom:10px">
    <div class="seat-legend">
      <div class="sl-item"><div class="sl-dot" style="background:rgba(58,125,94,.15);border:1px solid rgba(58,125,94,.4)"></div>Vacant</div>
      <div class="sl-item"><div class="sl-dot" style="background:rgba(192,68,79,.15);border:1px solid rgba(192,68,79,.4)"></div>Occupied (Fee Paid)</div>
      <div class="sl-item"><div class="sl-dot" style="background:rgba(230,126,34,.25);border:1px solid rgba(230,126,34,.5)"></div>Fee Pending/Partial ⚠</div>
      <div class="sl-item"><div class="sl-dot" style="background:rgba(192,68,79,.3);border:1px solid rgba(192,68,79,.7)"></div>Fee Overdue 🚨</div>
    </div>
  </div>
  <div class="g2" id="batchGrid"></div>
</div>

<!-- ATTENDANCE -->
<div class="page" id="page-attendance">
  <div class="sec-hd">
    <div><div class="sec-t">Attendance</div><div class="sec-s" id="attLbl"></div></div>
    <div style="display:flex;gap:7px;align-items:center;flex-wrap:wrap">
      <select id="attBatchF" onchange="renderAtt()" style="font-size:12px;padding:6px 9px"><option value="all">All Batches</option></select>
      <button class="btn bp" onclick="saveAtt()">💾 Save</button>
      <button class="btn bg" onclick="markAll(true)">✓ All Present</button>
      <button class="btn bd" onclick="markAll(false)" style="font-size:11px">✗ All Absent</button>
    </div>
  </div>
  <div class="stats-grid" style="grid-template-columns:repeat(4,1fr)">
    <div class="sc" style="--ca:var(--em)"><div class="s-lb">Present</div><div class="s-vl" id="at-p">0</div></div>
    <div class="sc" style="--ca:var(--ro)"><div class="s-lb">Absent</div><div class="s-vl" id="at-a">0</div></div>
    <div class="sc" style="--ca:var(--gd)"><div class="s-lb">Rate</div><div class="s-vl" id="at-r">0%</div></div>
    <div class="sc" style="--ca:var(--ac)"><div class="s-lb">Total</div><div class="s-vl" id="at-t">0</div></div>
  </div>
  <div class="panel"><div class="tw"><table>
    <thead><tr><th>Student</th><th>Batch</th><th>Seat</th><th>Fee Status</th><th>Attend.</th><th>Toggle</th></tr></thead>
    <tbody id="attTable"></tbody>
  </table></div></div>
</div>

<!-- BOOKS -->
<div class="page" id="page-books">
  <div class="sec-hd">
    <div><div class="sec-t">Books Catalog</div><div class="sec-s" id="bkCount"></div></div>
    <div style="display:flex;gap:7px;align-items:center">
      <input placeholder="Search…" style="width:130px;font-size:11.5px" oninput="bkSrch(this.value)">
      <select id="bkCatF" onchange="renderBooks()" style="font-size:12px;padding:6px 9px"><option value="all">All</option><option>Academic</option><option>Self-Help</option><option>Fiction</option><option>Science</option></select>
      <button class="btn bp" onclick="openM('mAddBook')">+ Add Book</button>
    </div>
  </div>
  <div class="panel"><div class="tw"><table>
    <thead><tr><th>Book</th><th>Author</th><th>Category</th><th>Copies</th><th>Available</th><th>Shelf</th><th>Status</th><th>Action</th></tr></thead>
    <tbody id="bkTable"></tbody>
  </table></div>
  <div class="pag"><span class="pag-i" id="bkPagI"></span><div class="pag-b" id="bkPagB"></div></div></div>
</div>

<!-- TRANSACTIONS -->
<div class="page" id="page-transactions">
  <div class="sec-hd">
    <div><div class="sec-t">Issue & Returns</div><div class="sec-s" id="txCount"></div></div>
    <div style="display:flex;gap:7px"><button class="btn bp" onclick="openM('mIssueBook')">📤 Issue</button><button class="btn bg" onclick="openM('mReturnBook')">📩 Return</button></div>
  </div>
  <div class="stats-grid" style="grid-template-columns:repeat(4,1fr)">
    <div class="sc" style="--ca:var(--vi)"><div class="s-lb">Issued</div><div class="s-vl" id="tx-is">0</div></div>
    <div class="sc" style="--ca:var(--ro)"><div class="s-lb">Overdue</div><div class="s-vl" id="tx-od">0</div></div>
    <div class="sc" style="--ca:var(--em)"><div class="s-lb">Returned</div><div class="s-vl" id="tx-rt">0</div></div>
    <div class="sc" style="--ca:var(--gd)"><div class="s-lb">Fine Collected</div><div class="s-vl" id="tx-fn">₹0</div></div>
  </div>
  <div class="panel"><div class="tw"><table>
    <thead><tr><th>Student</th><th>Book</th><th>Issue Date</th><th>Due Date</th><th>Return Date</th><th>Fine</th><th>Status</th><th>Action</th></tr></thead>
    <tbody id="txTable"></tbody>
  </table></div></div>
</div>

<!-- FEES -->
<div class="page" id="page-fees">
  <div class="sec-hd">
    <div><div class="sec-t">Fee Management</div></div>
    <div style="display:flex;gap:7px;flex-wrap:wrap">
      <button class="btn bp" onclick="openM('mCollectFee')">💳 Collect Fee</button>
      <button class="btn bwa" onclick="waBulkFee()" style="font-size:11px">💬 WA Reminders</button>
      <button class="btn bg" onclick="sendReminders()" style="font-size:11px">📣 Send Reminders</button>
    </div>
  </div>
  <div class="stats-grid">
    <div class="sc" style="--ca:var(--em)"><div class="s-ic" style="background:rgba(58,125,94,.1)">✅</div><div class="s-lb">Collected</div><div class="s-vl" id="fc-c">₹0</div><div class="s-mt" id="fc-cm"></div></div>
    <div class="sc" style="--ca:var(--sk)"><div class="s-ic" style="background:rgba(58,122,176,.1)">◑</div><div class="s-lb">Partial Payments</div><div class="s-vl" id="fc-pp">0</div><div class="s-mt" id="fc-ppm"></div></div>
    <div class="sc" style="--ca:var(--gd)"><div class="s-ic" style="background:rgba(196,125,43,.1)">⏳</div><div class="s-lb">Pending</div><div class="s-vl" id="fc-p">₹0</div><div class="s-mt" id="fc-pm"></div></div>
    <div class="sc" style="--ca:var(--ro)"><div class="s-ic" style="background:rgba(192,68,79,.1)">🚨</div><div class="s-lb">Overdue</div><div class="s-vl" id="fc-o">₹0</div><div class="s-mt" id="fc-om"></div></div>
  </div>
  <div class="panel">
    <div class="ph"><div class="pt">Fee Records</div>
      <div style="display:flex;gap:7px;align-items:center;flex-wrap:wrap">
        <input placeholder="Search…" style="width:120px;font-size:11.5px" oninput="feeSrch(this.value)">
        <div class="tabs" id="feeTabs"><div class="tab active" onclick="feeFilt('all',this)">All</div><div class="tab" onclick="feeFilt('paid',this)">Paid</div><div class="tab" onclick="feeFilt('partial',this)">Partial</div><div class="tab" onclick="feeFilt('pending',this)">Pending</div><div class="tab" onclick="feeFilt('overdue',this)">Overdue</div></div>
      </div>
    </div>
    <div class="tw"><table>
      <thead><tr><th>Student</th><th>Batch</th><th>Full Fee</th><th>Discount</th><th>Net Fee</th><th>Paid Amt</th><th>Balance Due</th><th>Paid On</th><th>Status</th><th>Due Date</th><th>Action</th></tr></thead>
      <tbody id="feeTable"></tbody>
    </table></div>
    <div class="pag"><span class="pag-i" id="feePagI"></span></div>
  </div>
</div>

<!-- INVOICES -->
<div class="page" id="page-invoices">
  <div class="sec-hd"><div><div class="sec-t">Invoices</div><div class="sec-s" id="invCount"></div></div><button class="btn bp" onclick="openM('mGenInv')">+ Generate</button></div>
  <div class="panel"><div class="tw"><table>
    <thead><tr><th>Invoice #</th><th>Student</th><th>Type</th><th>Total Fee</th><th>Discount</th><th>Paid</th><th>Balance</th><th>Date</th><th>Mode</th><th>Status</th><th>Action</th></tr></thead>
    <tbody id="invTable"></tbody>
  </table></div></div>
</div>

<!-- EXPENSES -->
<div class="page" id="page-expenses">
  <div class="sec-hd"><div><div class="sec-t">Expenses</div></div><button class="btn bp" onclick="openM('mExpense')">+ Add</button></div>
  <div class="stats-grid" style="grid-template-columns:repeat(3,1fr)">
    <div class="sc" style="--ca:var(--ro)"><div class="s-lb">Total Expenses</div><div class="s-vl" id="ex-t">₹0</div></div>
    <div class="sc" style="--ca:var(--em)"><div class="s-lb">Net Profit</div><div class="s-vl" id="ex-p">₹0</div></div>
    <div class="sc" style="--ca:var(--ac)"><div class="s-lb">Revenue</div><div class="s-vl" id="ex-r">₹0</div></div>
  </div>
  <div class="panel">
    <div class="ph"><div class="pt">Records</div><select id="exCatF" onchange="renderExp()" style="font-size:12px;padding:6px 9px"><option value="all">All</option><option>Utilities</option><option>Staff</option><option>Maintenance</option><option>Supplies</option><option>Books</option></select></div>
    <div class="pb" style="display:flex;flex-direction:column;gap:7px" id="expList"></div>
  </div>
</div>

<!-- ANALYTICS -->
<div class="page" id="page-analytics">
  <div class="sec-hd"><div><div class="sec-t">Analytics</div></div></div>
  <div class="g3" id="analCards"></div>
  <div class="g2">
    <div class="panel"><div class="ph"><div class="pt">Monthly Revenue</div></div><div class="pb"><div style="display:flex;align-items:flex-end;gap:7px;height:110px" id="revChart"></div><div style="display:flex;justify-content:space-around;font-size:9px;color:var(--tx3);font-family:var(--fm);margin-top:5px"><span>Jan</span><span>Feb</span><span>Mar</span><span>Apr*</span></div></div></div>
    <div class="panel"><div class="ph"><div class="pt">Batch Occupancy</div></div><div class="pb" id="batchAnal"></div></div>
  </div>
</div>

<!-- REPORTS -->
<div class="page" id="page-reports">
  <div class="sec-hd"><div><div class="sec-t">Reports</div></div></div>
  <div class="g3">
    <div class="panel" style="cursor:pointer" onclick="genReport('monthly')"><div class="pb" style="text-align:center;padding:22px"><div style="font-size:34px;margin-bottom:8px">📄</div><div style="font-weight:600;margin-bottom:3px">Monthly Summary</div><button class="btn bp" style="margin-top:10px">Generate</button></div></div>
    <div class="panel" style="cursor:pointer" onclick="genReport('fee')"><div class="pb" style="text-align:center;padding:22px"><div style="font-size:34px;margin-bottom:8px">💰</div><div style="font-weight:600;margin-bottom:3px">Fee Report</div><button class="btn bp" style="margin-top:10px">Generate</button></div></div>
    <div class="panel" style="cursor:pointer" onclick="genReport('books')"><div class="pb" style="text-align:center;padding:22px"><div style="font-size:34px;margin-bottom:8px">📚</div><div style="font-weight:600;margin-bottom:3px">Book Inventory</div><button class="btn bp" style="margin-top:10px">Generate</button></div></div>
    <div class="panel" style="cursor:pointer" onclick="genReport('attendance')"><div class="pb" style="text-align:center;padding:22px"><div style="font-size:34px;margin-bottom:8px">📋</div><div style="font-weight:600;margin-bottom:3px">Attendance</div><button class="btn bp" style="margin-top:10px">Generate</button></div></div>
    <div class="panel" style="cursor:pointer" onclick="genReport('expense')"><div class="pb" style="text-align:center;padding:22px"><div style="font-size:34px;margin-bottom:8px">💸</div><div style="font-weight:600;margin-bottom:3px">Expense Report</div><button class="btn bp" style="margin-top:10px">Generate</button></div></div>
    <div class="panel" style="cursor:pointer" onclick="genReport('student')"><div class="pb" style="text-align:center;padding:22px"><div style="font-size:34px;margin-bottom:8px">👥</div><div style="font-weight:600;margin-bottom:3px">Student Directory</div><button class="btn bp" style="margin-top:10px">Generate</button></div></div>
  </div>
  <div class="panel" id="rptOut" style="display:none"><div class="ph"><div class="pt" id="rptTitle">Report</div><button class="btn bg" onclick="window.print()">🖨 Print</button></div><div class="pb" id="rptBody"></div></div>
</div>

<!-- WHATSAPP -->
<div class="page" id="page-whatsapp">
  <div class="sec-hd"><div><div class="sec-t">💬 WhatsApp Messaging</div><div class="sec-s">Auto-send fee, enrollment & reminder messages</div></div><div style="display:flex;align-items:center;gap:8px"><button class="btn bwa" style="font-size:11px;gap:6px" onclick="openM('mWaQR')"><div class="wa-status-dot" style="width:7px;height:7px;border-radius:50%;background:#fff;display:inline-block;animation:waPulse 1.5s infinite"></div> Connect QR</button></div></div>
  <div class="panel wa-panel" style="margin-bottom:14px">
    <div class="ph" style="border-color:rgba(37,211,102,.2)"><div class="pt" style="color:var(--wa2)">📋 Message Templates</div><span style="font-size:11px;color:var(--tx3)">Click any template to compose & send</span></div>
    <div class="pb"><div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px" id="waTemplateGrid"></div></div>
  </div>
  <div class="g2" style="margin-bottom:14px">
    <div class="panel">
      <div class="ph"><div class="pt">✍ Compose</div></div>
      <div class="pb">
        <div class="fgi" style="margin-bottom:10px"><label>Select Student(s)</label><select id="wa-stu" onchange="waUpdatePreview()"><option value="">-- Select Student --</option><option value="all">📢 All Students</option><option value="pending_all">⏳ All Pending + Partial</option><option value="overdue">🚨 All Overdue</option></select></div>
        <div class="fgi" style="margin-bottom:10px"><label>Template</label><select id="wa-tpl" onchange="waLoadTemplate()"><option value="">-- Select Template --</option><option value="welcome">🎉 Welcome / Enrollment</option><option value="fee_due">💰 Fee Due Reminder</option><option value="fee_overdue">🚨 Fee Overdue Alert</option><option value="partial_payment">💳 Partial Payment Received</option><option value="fee_receipt">✅ Fee Payment Receipt</option><option value="discount_applied">🎁 Discount Applied</option><option value="book_due">📚 Book Return Reminder</option><option value="book_overdue">⚠ Book Overdue Fine</option><option value="seat_allotted">🪑 Seat Allotment</option><option value="holiday">📅 Holiday Notice</option><option value="custom">✏ Custom Message</option></select></div>
        <div class="fgi" style="margin-bottom:10px"><label>Message</label><textarea id="wa-msg" rows="8" placeholder="Select a template…" oninput="waUpdatePreview()" style="font-size:12px;line-height:1.6;min-height:140px"></textarea></div>
        <div style="display:flex;gap:8px;flex-wrap:wrap"><button class="btn bwa" onclick="waSend()" style="flex:1">💬 Send Direct</button><button class="btn bg" onclick="waCopy()" style="font-size:11px">📋 Copy</button><button class="btn bg" onclick="waSchedule()" style="font-size:11px">⏰ Schedule</button></div>
        <div id="wa-send-info" style="margin-top:8px;font-size:11px;color:var(--tx3)"></div>
      </div>
    </div>
    <div class="panel">
      <div class="ph"><div class="pt">👁 Preview</div><span style="font-size:10px;color:var(--tx3)">WhatsApp appearance</span></div>
      <div class="pb">
        <div style="background:#e5ddd5;border-radius:12px;padding:16px;min-height:200px">
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid rgba(0,0,0,.1)">
            <div style="width:32px;height:32px;border-radius:50%;background:var(--wa);display:flex;align-items:center;justify-content:center;font-size:16px">📚</div>
            <div><div style="font-size:13px;font-weight:700;color:#1a1a1a">OPTMS Tech Library</div><div style="font-size:10px;color:#666">Official Account</div></div>
          </div>
          <div class="wa-preview" id="waPreview">Select a template to preview…</div>
        </div>
        <div style="margin-top:12px"><div style="font-size:10px;color:var(--tx3);font-family:var(--fm);margin-bottom:6px">RECENT SENDS</div><div id="waSendLog" style="display:flex;flex-direction:column;gap:5px;max-height:140px;overflow-y:auto"></div></div>
      </div>
    </div>
  </div>
  <div class="panel">
    <div class="ph"><div class="pt">📢 Bulk Send</div></div>
    <div class="pb"><div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px">
      <div style="background:var(--sf2);border-radius:var(--r2);padding:14px;text-align:center;border:1px solid var(--br)"><div style="font-size:22px;margin-bottom:6px">🎉</div><div style="font-weight:600;font-size:12px;margin-bottom:3px">Welcome New</div><div style="font-size:10px;color:var(--tx3);margin-bottom:10px">This month's enrollments</div><button class="btn bwa" style="width:100%;font-size:11px" onclick="bulkSend('welcome')">Send (<span id="bk-welcome">0</span>)</button></div>
      <div style="background:var(--sf2);border-radius:var(--r2);padding:14px;text-align:center;border:1px solid var(--br)"><div style="font-size:22px;margin-bottom:6px">⏳</div><div style="font-weight:600;font-size:12px;margin-bottom:3px">Fee Pending</div><div style="font-size:10px;color:var(--tx3);margin-bottom:10px">Pending + partial</div><button class="btn bwa" style="width:100%;font-size:11px;background:#e67e22" onclick="bulkSend('pending')">Send (<span id="bk-pending">0</span>)</button></div>
      <div style="background:var(--sf2);border-radius:var(--r2);padding:14px;text-align:center;border:1px solid var(--br)"><div style="font-size:22px;margin-bottom:6px">🚨</div><div style="font-weight:600;font-size:12px;margin-bottom:3px">Fee Overdue</div><div style="font-size:10px;color:var(--tx3);margin-bottom:10px">Critical overdue</div><button class="btn bd" style="width:100%;font-size:11px" onclick="bulkSend('overdue')">Send (<span id="bk-overdue2">0</span>)</button></div>
      <div style="background:var(--sf2);border-radius:var(--r2);padding:14px;text-align:center;border:1px solid var(--br)"><div style="font-size:22px;margin-bottom:6px">📚</div><div style="font-weight:600;font-size:12px;margin-bottom:3px">Book Overdue</div><div style="font-size:10px;color:var(--tx3);margin-bottom:10px">Return reminders</div><button class="btn" style="width:100%;font-size:11px;background:var(--vi);color:#fff" onclick="bulkSend('bookoverdue')">Send (<span id="bk-bookod">0</span>)</button></div>
    </div></div>
  </div>
</div>

<!-- STAFF -->
<div class="page" id="page-staff">
  <div class="sec-hd"><div><div class="sec-t">Staff & Users</div><div class="sec-s" id="staffCount"></div></div><button class="btn bp" onclick="openM('mAddStaff')">+ Add Staff</button></div>
  <div class="panel"><div class="tw"><table>
    <thead><tr><th>Staff</th><th>Role</th><th>Email</th><th>Phone</th><th>Permissions</th><th>Status</th><th>Action</th></tr></thead>
    <tbody id="staffTable"></tbody>
  </table></div></div>
</div>

<!-- NOTIFICATIONS -->
<div class="page" id="page-notifications">
  <div class="sec-hd"><div><div class="sec-t">Notifications</div><div class="sec-s" id="notifCount"></div></div><button class="btn bg" onclick="clearNotifs()" style="font-size:11px">Clear All</button></div>
  <div class="panel"><div class="pb" id="notifList" style="display:flex;flex-direction:column;gap:7px"></div></div>
</div>

<!-- SETTINGS -->
<div class="page" id="page-settings">
  <div class="sec-hd"><div><div class="sec-t">Settings</div></div></div>
  <div class="g2">
    <div class="panel"><div class="ph"><div class="pt">OPTMS Tech Info</div></div><div class="pb">
      <div class="fg">
        <div class="fgi full"><label>Library Name</label><input id="s-name" value="OPTMS Tech Study Library"></div>
        <div class="fgi"><label>Phone / WhatsApp</label><input id="s-phone" value="+91 72820 71620"></div>
        <div class="fgi"><label>Email</label><input id="s-email" value="admin@optms.co.in"></div>
        <div class="fgi full"><label>Address</label><input id="s-addr" value="Madhepura, Bihar - 852113"></div>
        <div class="fgi"><label>Fine Per Day (₹)</label><input id="s-fine" value="5" type="number"></div>
        <div class="fgi"><label>Max Issue Days</label><input id="s-days" value="14" type="number"></div>
        <div class="fgi"><label>AC Seat Extra (₹)</label><input id="s-acfee" value="200" type="number"></div>
        <div class="fgi"><label>WhatsApp Number</label><input id="s-wa" value="917282071620"></div>
      </div>
      <div style="margin-top:14px;display:flex;gap:8px">
        <button class="btn bp" onclick="saveSettings()">💾 Save</button>
        <button class="btn bd" onclick="if(confirm('Reset?')){initData();toast('Reset!','wn')}">🔄 Reset</button>
      </div>
    </div></div>
    <div class="panel"><div class="ph"><div class="pt">System Stats</div></div><div class="pb" id="setStats"></div></div>
  </div>
</div>

  </div><!-- /content -->
</div><!-- /main -->
<!-- MODALS -->
<div class="mo" id="mEnroll"><div class="md wide">
  <div class="mh"><div class="mt">Enroll New Student</div><button class="mc" onclick="closeM('mEnroll')">✕</button></div>
  <div class="mb">
    <div class="sdiv">Personal Info</div>
    <div class="fg">
      <div class="fgi"><label>First Name *</label><input id="en-fn" placeholder="Rahul"></div>
      <div class="fgi"><label>Last Name *</label><input id="en-ln" placeholder="Kumar"></div>
      <div class="fgi"><label>Phone *</label><input id="en-ph" placeholder="+91 98765 43210"></div>
      <div class="fgi"><label>Email</label><input id="en-em" placeholder="email@example.com"></div>
      <div class="fgi full"><label>Address</label><input id="en-ad" placeholder="Full address…"></div>
      <div class="fgi"><label>Course / Exam</label><input id="en-co" placeholder="UPSC / JEE"></div>
      <div class="fgi"><label>Join Date</label><input id="en-dt" type="date"></div>
    </div>
    <div class="sdiv" style="margin-top:14px">Batch & Seat</div>
    <div class="fg">
      <div class="fgi"><label>Batch *</label><select id="en-bt" onchange="calcEnrollFee()"><option value="">-- Select --</option></select></div>
      <div class="fgi"><label>Seat Type *</label><select id="en-ac" onchange="calcEnrollFee()"><option value="non-ac">Non-AC (Standard)</option><option value="ac">AC (Premium)</option></select></div>
      <div class="fgi"><label>Seat Number</label><input id="en-st" placeholder="A-12 (optional)"></div>
      <div class="fgi"><label>Base Fee (₹/month)</label><input id="en-fe" type="number" readonly style="background:var(--sf3);font-weight:700"></div>
    </div>
    <div class="sdiv" style="margin-top:14px">🎁 Discount (Optional)</div>
    <div class="fg">
      <div class="fgi"><label>Discount Type</label><select id="en-disc-type" onchange="calcEnrollFee()"><option value="none">No Discount</option><option value="flat">Flat Amount (₹)</option><option value="percent">Percentage (%)</option></select></div>
      <div class="fgi"><label>Discount Value</label><input id="en-disc-val" type="number" placeholder="0" oninput="calcEnrollFee()"></div>
      <div class="fgi"><label>Discount Reason</label><input id="en-disc-reason" placeholder="e.g. Early bird, Sibling"></div>
      <div class="fgi"><label>Net Fee (₹/month)</label><input id="en-net-fe" type="number" readonly style="background:var(--sf3);font-weight:700;color:var(--em)"></div>
    </div>
    <div id="en-fee-note" style="display:none;margin-top:8px;font-size:11.5px;padding:8px 12px;background:rgba(74,124,111,.08);border-radius:var(--r2);border:1px solid rgba(74,124,111,.2)"></div>
    <div style="margin-top:12px;padding:10px 13px;background:rgba(37,211,102,.06);border:1px solid rgba(37,211,102,.2);border-radius:var(--r2)">
      <label style="display:flex;align-items:center;gap:8px;cursor:pointer"><input type="checkbox" id="en-wa" checked style="width:auto"> <span style="font-size:12px;color:var(--tx2)">💬 Send WhatsApp welcome message after enrollment</span></label>
    </div>
  </div>
  <div class="mf"><button class="btn bg" onclick="closeM('mEnroll')">Cancel</button><button class="btn bp" onclick="enrollStudent()">Enroll Student</button></div>
</div></div>

<div class="mo" id="mCollectFee"><div class="md wide">
  <div class="mh"><div class="mt">Collect Fee</div><button class="mc" onclick="closeM('mCollectFee')">✕</button></div>
  <div class="mb">
    <div class="fg">
      <div class="fgi full"><label>Student *</label><select id="cf-stu" onchange="cfLoadStudent()"><option value="">-- Select --</option></select></div>
      <div class="fgi"><label>Month</label><input id="cf-mo" value="March 2026"></div>
      <div class="fgi"><label>Net Fee (₹)</label><input id="cf-tot" type="number" readonly style="background:var(--sf3);font-weight:700"></div>
    </div>
    <div id="cf-status-info" style="margin:10px 0;display:none"></div>
    <div class="sdiv" style="margin-top:14px">Payment</div>
    <div class="fg">
      <div class="fgi full"><label>Payment Mode</label>
        <select id="cf-mode" onchange="toggleSplit()">
          <option value="cash">💵 Cash</option><option value="upi">📱 UPI</option><option value="neft">🏦 NEFT</option><option value="cheque">📄 Cheque</option><option value="split">✂ Split (UPI + Cash)</option><option value="split2">✂ Split (2 Custom)</option>
        </select>
      </div>
    </div>
    <div id="payNormal" class="fg" style="margin-top:10px">
      <div class="fgi"><label>Amount Paying (₹)</label><input id="cf-amt" type="number" placeholder="0" oninput="cfCalcBalance()"></div>
      <div class="fgi"><label>Transaction Ref</label><input id="cf-ref" placeholder="Auto-generated"></div>
    </div>
    <div id="paySplit" style="display:none;margin-top:10px">
      <div class="fg">
        <div class="fgi"><label>Mode 1</label><select id="cf-m1"><option>Cash</option><option>UPI</option><option>NEFT</option></select></div>
        <div class="fgi"><label>Amount 1 (₹)</label><input id="cf-a1" type="number" placeholder="0" oninput="calcSplitRem()"></div>
        <div class="fgi"><label>Mode 2</label><select id="cf-m2"><option>UPI</option><option>Cash</option><option>NEFT</option></select></div>
        <div class="fgi"><label>Amount 2 (₹)</label><input id="cf-a2" type="number" placeholder="0" readonly style="background:var(--sf3)"></div>
      </div>
      <div id="splitNote" style="margin-top:6px;font-size:11px;color:var(--tx3);padding:6px 10px;background:var(--sf2);border-radius:var(--r2)"></div>
    </div>
    <div id="cf-balance-note" style="display:none;margin-top:10px;padding:10px 13px;border-radius:var(--r2);border:1px solid rgba(196,125,43,.3);background:rgba(196,125,43,.07)"></div>
    <div class="fg" style="margin-top:10px"><div class="fgi full"><label>Remarks</label><input id="cf-rem" placeholder="Optional…"></div></div>
    <div style="margin-top:12px;padding:10px 13px;background:rgba(37,211,102,.06);border:1px solid rgba(37,211,102,.2);border-radius:var(--r2)">
      <label style="display:flex;align-items:center;gap:8px;cursor:pointer"><input type="checkbox" id="cf-wa" checked style="width:auto"> <span style="font-size:12px;color:var(--tx2)">💬 Send payment receipt via WhatsApp</span></label>
    </div>
  </div>
  <div class="mf"><button class="btn bg" onclick="closeM('mCollectFee')">Cancel</button><button class="btn bp" onclick="collectFee()">💳 Collect</button></div>
</div></div>

<div class="mo" id="mAddBatch"><div class="md">
  <div class="mh"><div class="mt" id="mAddBatchTitle">Add New Batch</div><button class="mc" onclick="closeM('mAddBatch')">✕</button></div>
  <div class="mb"><div class="fg">
    <div class="fgi full"><label>Batch Name *</label><input id="ab-nm" placeholder="Morning Batch"></div>
    <div class="fgi"><label>Start Time *</label><input id="ab-st" type="time" value="08:00"></div>
    <div class="fgi"><label>End Time *</label><input id="ab-et" type="time" value="12:00"></div>
    <div class="fgi"><label>Total Seats *</label><input id="ab-ts" type="number" placeholder="80" min="1"></div>
    <div class="fgi"><label>Base Fee (₹/month) *</label><input id="ab-fe" type="number" placeholder="1200"></div>
    <div class="fgi"><label>AC Extra Fee (₹)</label><input id="ab-ac" type="number" placeholder="200" value="200"></div>
  </div></div>
  <div class="mf"><button class="btn bg" onclick="closeM('mAddBatch')">Cancel</button><button class="btn bp" id="batchSaveBtn" onclick="saveBatch()">Add Batch</button></div>
</div></div>

<div class="mo" id="mIssueBook"><div class="md">
  <div class="mh"><div class="mt">Issue Book</div><button class="mc" onclick="closeM('mIssueBook')">✕</button></div>
  <div class="mb"><div class="fg">
    <div class="fgi full"><label>Student *</label><select id="ib-stu"><option value="">-- Select --</option></select></div>
    <div class="fgi full"><label>Book *</label><select id="ib-bk"><option value="">-- Select --</option></select></div>
    <div class="fgi"><label>Issue Date</label><input id="ib-id" type="date"></div>
    <div class="fgi"><label>Due Date</label><input id="ib-dd" type="date" readonly></div>
  </div></div>
  <div class="mf"><button class="btn bg" onclick="closeM('mIssueBook')">Cancel</button><button class="btn bp" onclick="issueBook()">Issue</button></div>
</div></div>

<div class="mo" id="mReturnBook"><div class="md">
  <div class="mh"><div class="mt">Return Book</div><button class="mc" onclick="closeM('mReturnBook')">✕</button></div>
  <div class="mb"><div class="fg">
    <div class="fgi full"><label>Transaction *</label><select id="rb-tx" onchange="calcFine()"><option value="">-- Select --</option></select></div>
    <div class="fgi"><label>Return Date</label><input id="rb-dt" type="date"></div>
    <div class="fgi"><label>Fine (₹)</label><input id="rb-fn" type="number" value="0" readonly style="background:var(--sf3)"></div>
    <div class="fgi full"><label>Condition</label><select id="rb-cd"><option>Good</option><option>Damaged</option><option>Lost</option></select></div>
  </div>
  <div id="rb-note" style="display:none;margin-top:8px;padding:8px 10px;background:rgba(192,68,79,.07);border:1px solid rgba(192,68,79,.2);border-radius:var(--r2);font-size:11px;color:var(--ro)"></div>
  </div>
  <div class="mf"><button class="btn bg" onclick="closeM('mReturnBook')">Cancel</button><button class="btn bp" onclick="returnBook()">Return</button></div>
</div></div>

<div class="mo" id="mAddBook"><div class="md">
  <div class="mh"><div class="mt">Add Book</div><button class="mc" onclick="closeM('mAddBook')">✕</button></div>
  <div class="mb"><div class="fg">
    <div class="fgi full"><label>Title *</label><input id="bk-tl" placeholder="Atomic Habits"></div>
    <div class="fgi"><label>Author *</label><input id="bk-au"></div><div class="fgi"><label>ISBN</label><input id="bk-is"></div>
    <div class="fgi"><label>Category</label><select id="bk-ca"><option>Self-Help</option><option>Academic</option><option>Fiction</option><option>Science</option></select></div>
    <div class="fgi"><label>Copies *</label><input id="bk-cp" type="number"></div><div class="fgi"><label>Shelf</label><input id="bk-sh"></div>
  </div></div>
  <div class="mf"><button class="btn bg" onclick="closeM('mAddBook')">Cancel</button><button class="btn bp" onclick="addBook()">Add</button></div>
</div></div>

<div class="mo" id="mExpense"><div class="md">
  <div class="mh"><div class="mt">Add Expense</div><button class="mc" onclick="closeM('mExpense')">✕</button></div>
  <div class="mb"><div class="fg">
    <div class="fgi full"><label>Name *</label><input id="ex-nm" placeholder="Electricity Bill"></div>
    <div class="fgi"><label>Amount (₹) *</label><input id="ex-am" type="number"></div>
    <div class="fgi"><label>Category</label><select id="ex-ca"><option>Utilities</option><option>Staff</option><option>Maintenance</option><option>Supplies</option><option>Books</option><option>Other</option></select></div>
    <div class="fgi"><label>Date</label><input id="ex-dt" type="date"></div>
    <div class="fgi full"><label>Notes</label><input id="ex-nt" placeholder="Optional…"></div>
  </div></div>
  <div class="mf"><button class="btn bg" onclick="closeM('mExpense')">Cancel</button><button class="btn bp" onclick="addExp()">Add</button></div>
</div></div>

<div class="mo" id="mAllocSeat"><div class="md">
  <div class="mh"><div class="mt">Allocate Seat</div><button class="mc" onclick="closeM('mAllocSeat')">✕</button></div>
  <div class="mb"><div class="fg">
    <div class="fgi full"><label>Student *</label><select id="as-stu"><option value="">-- Select --</option></select></div>
    <div class="fgi"><label>Batch *</label><select id="as-bt"><option value="">-- Select --</option></select></div>
    <div class="fgi"><label>Seat Number *</label><input id="as-st" placeholder="A-12"></div>
  </div></div>
  <div class="mf"><button class="btn bg" onclick="closeM('mAllocSeat')">Cancel</button><button class="btn bp" onclick="allocSeat()">Allocate</button></div>
</div></div>

<div class="mo" id="mAddStaff"><div class="md wide">
  <div class="mh"><div class="mt" id="staffModalTitle">Add Staff</div><button class="mc" onclick="closeM('mAddStaff')">✕</button></div>
  <div class="mb">
    <div class="fg">
      <div class="fgi"><label>Full Name *</label><input id="sf-nm"></div>
      <div class="fgi"><label>Role *</label><select id="sf-rl" onchange="setDefaultPerms()"><option value="librarian">Librarian</option><option value="accountant">Accountant</option><option value="receptionist">Receptionist</option><option value="admin">Admin</option></select></div>
      <div class="fgi"><label>Email *</label><input id="sf-em"></div>
      <div class="fgi"><label>Phone</label><input id="sf-ph"></div>
      <div class="fgi"><label>Username</label><input id="sf-un"></div>
      <div class="fgi"><label>Password</label><input id="sf-pw" type="password"></div>
    </div>
    <div class="sdiv" style="margin-top:14px">Permissions</div>
    <div id="permList"></div>
  </div>
  <div class="mf"><button class="btn bg" onclick="closeM('mAddStaff')">Cancel</button><button class="btn bp" id="staffSaveBtn" onclick="saveStaff()">Add Staff</button></div>
</div></div>

<div class="mo" id="mGenInv"><div class="md">
  <div class="mh"><div class="mt">Generate Invoice</div><button class="mc" onclick="closeM('mGenInv')">✕</button></div>
  <div class="mb"><div class="fg">
    <div class="fgi full"><label>Student *</label><select id="gi-stu" onchange="autoFillInv()"><option value="">-- Select --</option></select></div>
    <div class="fgi"><label>Type</label><select id="gi-tp"><option value="fee">Monthly Fee</option><option value="fine">Book Fine</option><option value="other">Other</option></select></div>
    <div class="fgi"><label>Amount (₹)</label><input id="gi-am" type="number"></div>
    <div class="fgi full"><label>Period</label><input id="gi-mo" value="March 2026"></div>
  </div></div>
  <div class="mf"><button class="btn bg" onclick="closeM('mGenInv')">Cancel</button><button class="btn bp" onclick="genInvoice()">Generate</button></div>
</div></div>

<!-- WhatsApp Quick Send Modal -->
<!-- STUDENT PROFILE MODAL -->
<div class="mo" id="mStudentProfile"><div class="md lg">
  <div class="mh" style="border-bottom:none;padding-bottom:0">
    <div></div>
    <div style="display:flex;align-items:center;gap:7px">
      <button class="sp-edit-toggle" id="spEditToggle" onclick="toggleProfileEdit()">✏ Edit</button>
      <button class="mc" onclick="closeM('mStudentProfile')">✕</button>
    </div>
  </div>
  <div class="sp-header" id="spHeader">
    <div class="sp-name" id="spHeaderName">Student Name</div>
    <div class="sp-id" id="spHeaderId">#STU-001</div>
    <div class="sp-av-wrap"><div class="sp-av" id="spAv" style="background:#4a7c6f">AB</div></div>
  </div>
  <div class="sp-body">
    <!-- Fee status bar -->
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;padding:10px 14px;background:var(--sf2);border-radius:var(--r2)">
      <span id="spFeeTag" class="tag tpn">⏳ Pending</span>
      <div style="flex:1">
        <div style="display:flex;justify-content:space-between;font-size:10px;color:var(--tx3);font-family:var(--fm);margin-bottom:3px"><span id="spPaidLbl">Paid ₹0</span><span id="spDueLbl">Due ₹0</span></div>
        <div class="sp-fee-bar"><div class="sp-fee-fill" id="spFeeFill" style="width:0%"></div></div>
      </div>
      <div style="text-align:right">
        <div style="font-size:15px;font-weight:700;font-family:var(--fm);color:var(--em)" id="spNetFee">₹0</div>
        <div style="font-size:9px;color:var(--tx3)">Net Fee</div>
      </div>
    </div>

    <!-- Seat & Batch -->
    <div class="sp-section">📍 Placement</div>
    <div class="sp-grid" style="margin-bottom:14px">
      <div class="sp-field">
        <div class="sp-label">Batch</div>
        <div id="spBatchDisp"></div>
      </div>
      <div class="sp-field">
        <div class="sp-label">Seat Number</div>
        <div id="spSeatDisp">
          <span class="sp-seat-chip" id="spSeatChip" onclick="openAllocFromProfile()">🪑 <span id="spSeatNum">—</span></span>
        </div>
      </div>
      <div class="sp-field">
        <div class="sp-label">Seat Type</div>
        <div id="spSeatTypeDisp"></div>
      </div>
      <div class="sp-field">
        <div class="sp-label">Join Date</div>
        <div id="spJoinDate" class="sp-val">—</div>
      </div>
    </div>

    <!-- Personal Info -->
    <div class="sp-section">👤 Personal Details</div>
    <div class="sp-grid" style="margin-bottom:14px">
      <div class="sp-field">
        <div class="sp-label">First Name</div>
        <div id="spFname" class="sp-val" contenteditable="false">—</div>
      </div>
      <div class="sp-field">
        <div class="sp-label">Last Name</div>
        <div id="spLname" class="sp-val" contenteditable="false">—</div>
      </div>
      <div class="sp-field">
        <div class="sp-label">Phone / WhatsApp</div>
        <div id="spPhone" class="sp-val" contenteditable="false">—</div>
      </div>
      <div class="sp-field">
        <div class="sp-label">Email</div>
        <div id="spEmail" class="sp-val" contenteditable="false">—</div>
      </div>
      <div class="sp-field full">
        <div class="sp-label">Course / Subject</div>
        <div id="spCourse" class="sp-val" contenteditable="false">—</div>
      </div>
      <div class="sp-field full">
        <div class="sp-label">Address</div>
        <div id="spAddr" class="sp-val" contenteditable="false">—</div>
      </div>
    </div>

    <!-- Fee Details -->
    <div class="sp-section">💰 Fee Details</div>
    <div class="sp-grid" style="margin-bottom:14px">
      <div class="sp-field">
        <div class="sp-label">Base Fee</div>
        <div id="spBaseFee" class="sp-val">—</div>
      </div>
      <div class="sp-field">
        <div class="sp-label">Discount</div>
        <div id="spDiscount" class="sp-val">—</div>
      </div>
      <div class="sp-field">
        <div class="sp-label">Paid Amount</div>
        <div id="spPaidAmt" class="sp-val">—</div>
      </div>
      <div class="sp-field">
        <div class="sp-label">Due Date</div>
        <div id="spDueDate" class="sp-val">—</div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="sp-section">⚡ Quick Actions</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <button class="btn bp" style="font-size:11px" id="spCollectBtn" onclick="closeM('mStudentProfile')">💰 Collect Fee</button>
      <button class="btn bwa" style="font-size:11px" id="spWaBtn">💬 Send WhatsApp</button>
      <button class="btn bg" style="font-size:11px" onclick="openAllocFromProfile()">🪑 Change Seat</button>
      <button class="btn bd" style="font-size:11px" id="spDelBtn">🗑 Remove</button>
    </div>
  </div>
  <div class="mf" id="spSaveFooter" style="display:none">
    <button class="btn bg" onclick="cancelProfileEdit()">Cancel</button>
    <button class="btn bp" onclick="saveProfileEdit()">💾 Save Changes</button>
  </div>
</div></div>

<!-- WHATSAPP QR CONNECT MODAL -->
<div class="mo" id="mWaQR"><div class="md">
  <div class="mh"><div class="mt">📱 Connect WhatsApp</div><button class="mc" onclick="closeM('mWaQR')">✕</button></div>
  <div class="mb">
    <div style="display:flex;gap:16px;align-items:flex-start">
      <div style="flex-shrink:0">
        <div class="wa-qr-box" onclick="refreshWaQR()" id="waQRBox">
          <div class="wa-qr-img" id="waQRImg">
            <canvas id="waQRCanvas" width="150" height="150"></canvas>
          </div>
          <div style="font-size:10px;color:var(--tx3);font-family:var(--fm)" id="waQRStatus">Tap to refresh</div>
        </div>
        <div style="display:flex;justify-content:center;margin-top:8px">
          <div class="wa-conn-badge wa-conn-no" id="waConnBadge">● Disconnected</div>
        </div>
      </div>
      <div style="flex:1">
        <div style="font-weight:600;font-size:13px;margin-bottom:8px;color:var(--tx)">Scan to Connect</div>
        <div style="font-size:11px;color:var(--tx3);margin-bottom:14px">Link your WhatsApp to send messages directly from the ERP without switching apps.</div>
        <div class="wa-steps">
          <div class="wa-step"><div class="wa-step-n">1</div><span>Open <strong>WhatsApp</strong> on your phone</span></div>
          <div class="wa-step"><div class="wa-step-n">2</div><span>Tap <strong>Menu (⋮)</strong> → <strong>Linked Devices</strong></span></div>
          <div class="wa-step"><div class="wa-step-n">3</div><span>Tap <strong>Link a Device</strong></span></div>
          <div class="wa-step"><div class="wa-step-n">4</div><span>Point your camera at the <strong>QR code</strong></span></div>
        </div>
        <div style="margin-top:14px;padding:10px 12px;background:rgba(37,211,102,.07);border:1px solid rgba(37,211,102,.2);border-radius:var(--r2)">
          <div style="font-size:11px;font-weight:600;color:var(--wa2);margin-bottom:4px">📌 Note</div>
          <div style="font-size:11px;color:var(--tx2)">WhatsApp Web QR requires a backend service. In standalone mode, messages open in WhatsApp Web automatically.</div>
        </div>
        <div style="margin-top:12px">
          <div style="font-size:10px;color:var(--tx3);margin-bottom:6px;font-family:var(--fm)">WHATSAPP NUMBER</div>
          <div style="display:flex;gap:7px">
            <input id="waConnNum" placeholder="+91 XXXXXXXXXX" style="flex:1;font-size:12px" value="">
            <button class="btn bwa" style="font-size:11px" onclick="saveWaNumber()">Save</button>
          </div>
        </div>
      </div>
    </div>
    <div style="margin-top:14px;border-top:1px solid var(--br);padding-top:12px">
      <div style="font-size:10px;color:var(--tx3);font-family:var(--fm);margin-bottom:8px">CONNECTION STATUS</div>
      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <div class="sp-stat" style="flex:1"><span class="sp-stat-ic">📱</span><div><div style="font-size:11px;font-weight:600">WhatsApp Web</div><div style="font-size:10px;color:var(--wa2)">Opens automatically</div></div></div>
        <div class="sp-stat" style="flex:1"><span class="sp-stat-ic">✅</span><div><div style="font-size:11px;font-weight:600">Messages Sent</div><div style="font-size:10px;color:var(--tx3)" id="waConnMsgCount">0 messages this session</div></div></div>
      </div>
    </div>
  </div>
  <div class="mf">
    <button class="btn bg" onclick="closeM('mWaQR')">Close</button>
    <button class="btn bg" style="font-size:11px;color:var(--ro)" onclick="waLogoutDevice()">🔌 Disconnect</button>
    <button class="btn bwa" onclick="testWaConnection()">💬 Test Message</button>
  </div>
</div></div>

<div class="mo" id="mWaSend"><div class="md">
  <div class="mh"><div class="mt">💬 Send WhatsApp</div><button class="mc" onclick="closeM('mWaSend')">✕</button></div>
  <div class="mb">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;padding:10px 13px;background:rgba(37,211,102,.08);border-radius:var(--r2);border:1px solid rgba(37,211,102,.2)">
      <div style="font-size:28px">💬</div>
      <div><div style="font-weight:600;font-size:13px" id="waSendTo">Student Name</div><div style="font-size:11px;color:var(--tx3)" id="waSendPhone">+91 XXXXXXXXXX</div></div>
    </div>
    <div class="fgi" style="margin-bottom:12px"><label>Message</label><textarea id="waSendMsg" rows="7" style="font-size:12px;line-height:1.6"></textarea></div>
    <div class="wa-preview" id="waSendPreview" style="font-size:11.5px"></div>
  </div>
  <div class="mf">
    <button class="btn bg" onclick="closeM('mWaSend')">Cancel</button>
    <button class="btn bg" onclick="waCopyModal()" style="font-size:11px">📋 Copy</button>
    <button class="btn bwa" id="waOpenBtn">💬 Send Direct</button>
  </div>
</div></div>

<!-- CHANGE PASSWORD MODAL -->
<div class="mo" id="mChangePw"><div class="md">
  <div class="mh"><div class="mt">🔑 Change Password</div><button class="mc" onclick="closeM('mChangePw')">✕</button></div>
  <div class="mb">
    <div class="fgi" style="margin-bottom:12px"><label>Current Password</label><input type="password" id="cp-cur" placeholder="Enter current password"></div>
    <div class="fgi" style="margin-bottom:12px"><label>New Password</label><input type="password" id="cp-new" placeholder="Min 6 characters"></div>
    <div class="fgi"><label>Confirm New Password</label><input type="password" id="cp-cf" placeholder="Repeat new password"></div>
  </div>
  <div class="mf"><button class="btn bg" onclick="closeM('mChangePw')">Cancel</button><button class="btn bp" onclick="doChangePassword()">Update Password</button></div>
</div></div>

<div class="toast-wrap" id="toastWrap"></div>
<script>
// ═══ API CONFIG ═══
const API = 'api/index.php';

async function apiGet(action, params = {}) {
  const qs = new URLSearchParams({ action, ...params }).toString();
  const r = await fetch(`${API}?${qs}`);
  return r.json();
}

async function apiPost(action, data = {}) {
  const r = await fetch(`${API}?action=${action}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
  });
  return r.json();
}

// ═══ DB STATE ═══
let DB = {
  students: [], batches: [], books: [], transactions: [],
  expenses: [], invoices: [], activities: [], notifications: [],
  settings: {name:'OPTMS Tech Study Library',phone:'+91 72820 71620',email:'admin@optms.co.in',
             addr:'Madhepura, Bihar - 852113',fine_per_day:5,loan_days:14,wa_number:'917282071620'},
  attendance: {}, waSendLog: [], staff: []
};
let editBatchIdx = -1, editStaffIdx = -1;

// ═══ INIT — loads all data from MySQL ═══
async function initData() {
  try {
    const data = await apiGet('get_dashboard');

    // Map DB columns to JS camelCase used throughout the app
    DB.batches = (data.batches || []).map(b => ({
      id: b.id, name: b.name,
      startTime: b.start_time ? b.start_time.slice(0,5) : '08:00',
      endTime:   b.end_time   ? b.end_time.slice(0,5)   : '12:00',
      total: +b.total_seats, occupied: +b.occupied_seats,
      baseFee: +b.base_fee, acExtra: +b.ac_extra
    }));

    DB.students = (data.students || []).map(s => ({
      id: s.id, fname: s.fname, lname: s.lname, phone: s.phone,
      batchId: s.batch_id, seatType: s.seat_type, seat: s.seat,
      baseFee: +s.base_fee,
      discount: { type: s.discount_type, value: +s.discount_value, reason: s.discount_reason },
      netFee: +s.net_fee, paidAmt: +s.paid_amt,
      feeStatus: s.fee_status, paidOn: s.paid_on,
      dueDate: s.due_date, course: s.course,
      color: s.color || '#4a7c6f', joinDate: s.join_date
    }));

    DB.books = (data.books || []).map(b => ({
      id: b.id, title: b.title, author: b.author, isbn: b.isbn,
      category: b.category, copies: +b.copies, available: +b.available,
      shelf: b.shelf, emoji: b.emoji || '📘'
    }));

    DB.transactions = (data.transactions || []).map(t => ({
      id: t.id, studentId: t.student_id, bookId: t.book_id,
      issueDate: t.issue_date, dueDate: t.due_date,
      returnDate: t.return_date, fine: +t.fine, status: t.status
    }));

    DB.expenses = (data.expenses || []).map(e => ({
      id: e.id, name: e.name, amount: +e.amount,
      category: e.category, date: e.expense_date,
      notes: e.notes, emoji: e.emoji || '💸'
    }));

    DB.invoices = (data.invoices || []).map(i => ({
      id: i.id, studentId: i.student_id, type: i.type,
      amount: +i.amount, baseFee: +i.base_fee, discount: +i.discount,
      netFee: +i.net_fee, paidAmt: +i.paid_amt, balance: +i.balance,
      date: i.invoice_date, month: i.month, mode: i.mode, status: i.status
    }));

    DB.activities = (data.activities || []).map(a => ({
      icon: a.icon, bg: a.bg, text: a.text,
      time: timeSince(a.created_at)
    }));

    DB.notifications = (data.notifications || []).map(n => ({
      id: +n.id, type: n.type, title: n.title, msg: n.msg,
      time: timeSince(n.created_at), read: !!+n.is_read
    }));

    DB.staff = (data.staff || []).map(sf => ({
      id: sf.id, name: sf.name, role: sf.role,
      email: sf.email, phone: sf.phone, username: sf.username,
      perms: {
        students: !!+sf.perm_students, fees: !!+sf.perm_fees,
        books: !!+sf.perm_books, expenses: !!+sf.perm_expenses,
        reports: !!+sf.perm_reports, staff: !!+sf.perm_staff,
        settings: !!+sf.perm_settings
      },
      status: sf.status
    }));

    const s = data.settings || {};
    DB.settings = {
      name: s.name || 'OPTMS Tech Study Library',
      phone: s.phone || '', email: s.email || '',
      addr: s.addr || '',
      fine: +(s.fine_per_day || 5),
      days: +(s.loan_days || 14),
      acFee: +(s.ac_extra || 200),
      waNum: s.wa_number || ''
    };

    // Build attendance map from today's data
    const attData = await apiGet('get_attendance', { date: new Date().toISOString().split('T')[0] });
    DB.attendance = attData.attendance || {};
    // Default present for any student not in attendance
    DB.students.forEach(st => { if (!DB.attendance[st.id]) DB.attendance[st.id] = 'present'; });

    // WA log
    const waLog = await apiGet('get_wa_log');
    DB.waSendLog = (waLog || []).map(l => ({
      time: l.created_at ? l.created_at.slice(11,16) : '',
      to: l.sent_to, preview: l.preview, type: l.type
    }));

  } catch(e) {
    console.error('Init failed:', e);
    toast('Failed to load data from server', 'er');
  }
  refreshAll();
}

function timeSince(ts) {
  if (!ts) return 'Just now';
  const diff = Math.floor((Date.now() - new Date(ts)) / 1000);
  if (diff < 60) return 'Just now';
  if (diff < 3600) return Math.floor(diff/60) + ' min ago';
  if (diff < 86400) return Math.floor(diff/3600) + ' hr ago';
  return Math.floor(diff/86400) + ' day(s) ago';
}

// ═══ NAVIGATION ═══
const PAGE_TITLES={dashboard:'Dashboard',students:'All Students',seats:'Seat Allocation',attendance:'Attendance',books:'Books Catalog',transactions:'Issue & Returns',fees:'Fee Management',invoices:'Invoices',expenses:'Expenses',reports:'Reports',analytics:'Analytics',whatsapp:'WhatsApp Messaging',staff:'Staff & Users',notifications:'Notifications',settings:'Settings'};
function navTo(page){
  document.querySelectorAll('.ni').forEach(n=>n.classList.remove('active'));
  const ni=document.querySelector(`.ni[data-page="${page}"]`);if(ni)ni.classList.add('active');
  document.querySelectorAll('.page').forEach(p=>p.classList.remove('active'));
  const pg=document.getElementById('page-'+page);if(pg)pg.classList.add('active');
  document.getElementById('topTitle').textContent=PAGE_TITLES[page]||page;
  renderPage(page);
}
document.querySelectorAll('.ni[data-page]').forEach(el=>{
  el.addEventListener('click',()=>{if(el.dataset.page==='enroll'){openM('mEnroll');return;}navTo(el.dataset.page);});
});
function renderPage(p){
  const map={dashboard:renderDash,students:renderStudents,seats:renderSeats,attendance:renderAtt,books:renderBooks,transactions:renderTx,fees:renderFees,invoices:renderInv,expenses:renderExp,analytics:renderAnal,whatsapp:renderWA,staff:renderStaff,notifications:renderNotifs,settings:renderSettings};
  if(map[p])map[p]();
}

// ═══ DASHBOARD ═══
let calDate=new Date(2026,2,1);
function renderDash(){
  const s=DB.students;
  const paid=s.filter(x=>x.feeStatus==='paid');
  const partial=s.filter(x=>x.feeStatus==='partial');
  const pending=s.filter(x=>x.feeStatus==='pending');
  const overdue=s.filter(x=>x.feeStatus==='overdue');
  const totalRev=paid.reduce((a,x)=>a+x.netFee,0)+partial.reduce((a,x)=>a+x.paidAmt,0);
  const totalExp=DB.expenses.reduce((a,e)=>a+e.amount,0);
  const issTx=DB.transactions.filter(t=>t.status!=='returned');
  const odTx=DB.transactions.filter(t=>t.status==='overdue');
  const totalSeats=DB.batches.reduce((a,b)=>a+b.total,0);
  const occSeats=DB.batches.reduce((a,b)=>a+b.occupied,0);
  const prsnt=Object.values(DB.attendance).filter(v=>v==='present').length;
  const totalDiscount=s.reduce((a,x)=>a+(x.baseFee-x.netFee),0);
  const allDue=[...pending,...overdue,...partial].reduce((a,x)=>a+(x.netFee-x.paidAmt),0);

  document.getElementById('dashAlerts').innerHTML=`
    <div class="al-card al-w"><span style="font-size:17px">⚠️</span><div><div class="al-t">Pending & Partial Payments</div><div class="al-b">${[...pending,...partial].length} students — ₹${[...pending,...partial].reduce((a,x)=>a+(x.netFee-x.paidAmt),0).toLocaleString()} outstanding</div></div></div>
    <div class="al-card al-d"><span style="font-size:17px">🚨</span><div><div class="al-t">Fee Overdue Alert</div><div class="al-b">${overdue.length} students overdue — seats highlighted in red 🔴</div></div></div>
    <div class="al-card al-i"><span style="font-size:17px">🎁</span><div><div class="al-t">Discounts Applied</div><div class="al-b">${s.filter(x=>x.baseFee>x.netFee).length} students with discounts — ₹${totalDiscount.toLocaleString()} waived</div></div></div>`;

  document.getElementById('dashStats').innerHTML=`
    <div class="sc" style="--ca:var(--ac)"><div class="s-ic" style="background:rgba(74,124,111,.1)">👨‍🎓</div><div class="s-lb">Total Students</div><div class="s-vl">${s.length}</div><div class="s-mt"><span class="bup">↑ 12%</span></div></div>
    <div class="sc" style="--ca:var(--em)"><div class="s-ic" style="background:rgba(58,125,94,.1)">🪑</div><div class="s-lb">Seats Available</div><div class="s-vl">${totalSeats-occSeats}</div><div class="s-mt">${occSeats}/${totalSeats} occupied</div></div>
    <div class="sc" style="--ca:var(--gd)"><div class="s-ic" style="background:rgba(196,125,43,.1)">💰</div><div class="s-lb">Revenue Collected</div><div class="s-vl">${fmt(totalRev)}</div><div class="s-mt"><span class="bup">incl. partial</span></div></div>
    <div class="sc" style="--ca:var(--ro)"><div class="s-ic" style="background:rgba(192,68,79,.1)">⏳</div><div class="s-lb">Total Due</div><div class="s-vl">${fmt(allDue)}</div><div class="s-mt" style="color:var(--ro)">${[...pending,...overdue,...partial].length} students</div></div>
    <div class="sc" style="--ca:var(--or)"><div class="s-ic" style="background:rgba(230,126,34,.1)">🎁</div><div class="s-lb">Discounts Given</div><div class="s-vl">${fmt(totalDiscount)}</div><div class="s-mt">${s.filter(x=>x.baseFee>x.netFee).length} students</div></div>
    <div class="sc" style="--ca:var(--vi)"><div class="s-ic" style="background:rgba(124,92,191,.1)">📚</div><div class="s-lb">Books Issued</div><div class="s-vl">${issTx.length}</div><div class="s-mt" style="color:var(--ro)">${odTx.length} overdue</div></div>
    <div class="sc" style="--ca:var(--ac)"><div class="s-ic" style="background:rgba(74,124,111,.1)">✅</div><div class="s-lb">Attendance Today</div><div class="s-vl">${prsnt}</div><div class="s-mt" style="color:var(--em)">${s.length?Math.round(prsnt/s.length*100):0}%</div></div>
    <div class="sc" style="--ca:var(--gd)"><div class="s-ic" style="background:rgba(196,125,43,.1)">💸</div><div class="s-lb">Monthly Expenses</div><div class="s-vl">${fmt(totalExp)}</div><div class="s-mt"><span class="bdn">↑ 3.1%</span></div></div>`;

  // ── BATCH SEAT AVAILABILITY WITH FEE STATUS ──
  // Build seat→student map
  const seatMap={};
  DB.students.forEach(st=>{ if(st.seat&&st.batchId)seatMap[st.batchId+'_'+st.seat]=st; });
  let batchHTML='';
  DB.batches.forEach(b=>{
    const pct=Math.round(b.occupied/b.total*100);
    const fc=pct>=100?'sf-r':pct>=70?'sf-y':'sf-g';
    const sc=pct>=100?'bst-f':pct>=70?'bst-n':'bst-o';
    const vacCount=b.total-b.occupied;
    const bStudents=DB.students.filter(x=>x.batchId===b.id);
    const bDue=bStudents.filter(x=>x.feeStatus==='pending'||x.feeStatus==='partial').length;
    const bOD=bStudents.filter(x=>x.feeStatus==='overdue').length;
    batchHTML+=`<div class="panel" style="margin-bottom:10px">
      <div class="ph" style="padding:10px 14px">
        <div><div style="font-weight:600;font-size:13px">${batchEmoji(b.name)} ${b.name}</div>
          <div style="font-size:10px;color:var(--tx3);font-family:var(--fm)">${fmtT(b.startTime)}–${fmtT(b.endTime)} · ₹${b.baseFee}+AC₹${b.acExtra}</div></div>
        <div style="display:flex;align-items:center;gap:8px">
          <div class="bst ${sc}">${pct>=100?'Full':pct>=70?'Filling':'Open'}</div>
          ${bDue>0?`<span style="font-size:9px;background:rgba(230,126,34,.15);color:var(--or);padding:2px 6px;border-radius:3px;font-weight:700;font-family:var(--fm)">⏳${bDue} pending</span>`:''}
          ${bOD>0?`<span style="font-size:9px;background:rgba(192,68,79,.15);color:var(--ro);padding:2px 6px;border-radius:3px;font-weight:700;font-family:var(--fm);animation:pulseDue 1s infinite">🚨${bOD} overdue</span>`:''}
        </div>
      </div>
      <div style="padding:8px 14px 12px">
        <div class="sbar"><div class="sfill ${fc}" style="width:${pct}%"></div></div>
        <div style="display:flex;justify-content:space-between;font-size:10px;font-family:var(--fm);color:var(--tx3)"><span>Total: <b>${b.total}</b></span><span style="color:var(--ro)">Occupied: <b>${b.occupied}</b></span><span style="color:var(--em)">Vacant: <b>${vacCount}</b></span></div>
      </div>
    </div>`;
  });
  document.getElementById('dashBatchCards').innerHTML=batchHTML;

  // ── EXPENSE TRACKER ──
  const catTotals={};DB.expenses.forEach(e=>{catTotals[e.category]=(catTotals[e.category]||0)+e.amount;});
  const catColors={Staff:'var(--ro)',Utilities:'var(--gd)',Maintenance:'var(--vi)',Books:'var(--sk)',Supplies:'var(--ac)',Other:'var(--tx3)'};
  document.getElementById('dashExpTracker').innerHTML=`<div class="pb">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
      <div><div style="font-size:9.5px;color:var(--tx3);font-family:var(--fm);text-transform:uppercase">TOTAL THIS MONTH</div><div style="font-size:22px;font-weight:700;color:var(--ro);font-family:var(--fd)">${fmt(totalExp)}</div></div>
      <div style="text-align:right"><div style="font-size:9.5px;color:var(--tx3);font-family:var(--fm);text-transform:uppercase">NET PROFIT</div><div style="font-size:22px;font-weight:700;color:var(--em);font-family:var(--fd)">${fmt(totalRev-totalExp)}</div></div>
    </div>
    ${Object.entries(catTotals).map(([cat,amt])=>{const pct=Math.round(amt/totalExp*100);return`<div style="margin-bottom:8px"><div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:3px"><span style="color:var(--tx2)">${cat}</span><span style="font-family:var(--fm);font-weight:700">₹${amt.toLocaleString()} (${pct}%)</span></div><div class="prg"><div class="prf" style="width:${pct}%;background:${catColors[cat]||'var(--ac)'}"></div></div></div>`;}).join('')}
    <div style="margin-top:10px;display:flex;flex-direction:column;gap:6px">
      ${DB.expenses.slice(0,4).map(e=>`<div class="ei2"><div class="eic" style="background:rgba(74,124,111,.1)">${e.emoji}</div><div style="flex:1"><div class="en2">${e.name}</div><div class="ed">${fmtDate(e.date)}</div></div><div class="ea ea-d">-₹${e.amount.toLocaleString()}</div></div>`).join('')}
    </div>
  </div>`;

  // ── FEE OVERVIEW ──
  document.getElementById('dashFeeOv').innerHTML=`<div class="panel" style="margin-bottom:8px"><div class="pb" style="padding-bottom:10px">
    <div class="fi"><div class="fd2" style="background:var(--em)"></div><div><div class="fn2">Fully Paid</div><div class="fsb">${paid.length} students</div></div><div class="fa" style="color:var(--em)">${fmt(paid.reduce((a,x)=>a+x.netFee,0))}</div></div>
    <div class="fi"><div class="fd2" style="background:var(--sk)"></div><div><div class="fn2">Partial Payment</div><div class="fsb">${partial.length} students · ₹${partial.reduce((a,x)=>a+(x.netFee-x.paidAmt),0).toLocaleString()} still due</div></div><div class="fa" style="color:var(--sk)">${fmt(partial.reduce((a,x)=>a+x.paidAmt,0))}</div></div>
    <div class="fi"><div class="fd2" style="background:var(--gd)"></div><div><div class="fn2">Pending</div><div class="fsb">${pending.length} students</div></div><div class="fa" style="color:var(--gd)">${fmt(pending.reduce((a,x)=>a+x.netFee,0))}</div></div>
    <div class="fi"><div class="fd2" style="background:var(--ro)"></div><div><div class="fn2">Overdue</div><div class="fsb">${overdue.length} students &gt;7 days</div></div><div class="fa" style="color:var(--ro)">${fmt(overdue.reduce((a,x)=>a+x.netFee,0))}</div></div>
    <div class="fi"><div class="fd2" style="background:var(--or)"></div><div><div class="fn2">Discounts</div><div class="fsb">${s.filter(x=>x.baseFee>x.netFee).length} students</div></div><div class="fa" style="color:var(--or)">${fmt(totalDiscount)}</div></div>
    <div style="margin-top:6px"><div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:4px;font-family:var(--fm)"><span style="color:var(--tx3)">Collection Rate</span><span style="color:var(--em);font-weight:700">${s.length?Math.round((paid.length+partial.length)/s.length*100):0}%</span></div>
    <div class="prg"><div class="prf" style="width:${s.length?Math.round((paid.length+partial.length)/s.length*100):0}%;background:linear-gradient(90deg,var(--em),#4ead82)"></div></div></div>
  </div></div>`;

  // ── RECENT STUDENTS TABLE ──
  const recent=[...s].sort((a,b)=>b.id.localeCompare(a.id)).slice(0,7);
  document.getElementById('dashStuTable').innerHTML=recent.map(x=>{
    const bal=x.netFee-x.paidAmt;
    const rowClass=x.feeStatus==='overdue'?'fee-due-row':x.feeStatus==='partial'||x.feeStatus==='pending'?'fee-partial-row':'';
    return `<tr class="${rowClass}">
      <td><div class="si"><div class="sav" style="background:${x.color}">${x.fname[0]+x.lname[0]}</div><div><div style="font-weight:600;font-size:12.5px;cursor:pointer;color:var(--ac)" onclick="openStudentProfile('${x.id}')">${x.fname} ${x.lname}</div><div style="font-size:10px;color:var(--tx3);font-family:var(--fm)">#${x.id}</div></div></div></td>
      <td>${bTag(x.batchId)}</td>
      <td>${x.seat?`<span style="font-family:var(--fm);font-size:11px;font-weight:600">${x.seat}</span>`:'<span style="color:var(--tx3)">—</span>'}${x.feeStatus==='overdue'?'<span style="font-size:9px;margin-left:3px">🔴</span>':x.feeStatus==='pending'||x.feeStatus==='partial'?'<span style="font-size:9px;margin-left:3px">🟠</span>':''}</td>
      <td><span style="font-family:var(--fm);font-weight:700">₹${x.netFee}</span>${x.baseFee>x.netFee?`<div style="font-size:9px;color:var(--or)">🎁-₹${x.baseFee-x.netFee}</div>`:''}</td>
      <td><span style="font-family:var(--fm);font-weight:700;color:var(--em)">₹${x.paidAmt}</span></td>
      <td>${bal>0?`<span class="fee-bal-badge">₹${bal} DUE</span>`:`<span style="color:var(--em);font-size:11px">✓</span>`}</td>
      <td><span class="tag ${x.feeStatus==='paid'?'tpd':x.feeStatus==='partial'?'tpart':x.feeStatus==='pending'?'tpn':'tod'}">${x.feeStatus==='paid'?'✓ Paid':x.feeStatus==='partial'?'◑ Partial':x.feeStatus==='pending'?'⏳ Pending':'🚨 Overdue'}</span></td>
      <td><button class="btn bwa" style="font-size:10px;padding:3px 7px" onclick="waQuick('${x.id}','${x.feeStatus==='paid'?'fee_receipt':x.feeStatus==='partial'?'partial_payment':x.feeStatus==='overdue'?'fee_overdue':'fee_due'}')">💬</button></td>
    </tr>`;
  }).join('');

  // Donut + weekly chart
  document.getElementById('donutC').textContent=fmt(totalRev);
  const wData=[38000,52000,34000,totalRev>0?totalRev:60000];
  const wMax=Math.max(...wData,1);
  document.getElementById('weekChart').innerHTML=wData.map((v,i)=>`<div class="cbar" style="flex:1;height:${Math.round(v/wMax*100)}%;background:var(--ac);opacity:${i===3?1:.55};border-radius:3px 3px 0 0"><div class="tt">₹${v.toLocaleString()}</div></div>`).join('');
  renderCal();
}

function renderCal(){
  const d=calDate;
  document.getElementById('calTitle').textContent=d.toLocaleDateString('en-IN',{month:'long',year:'numeric'});
  const days=['Su','Mo','Tu','We','Th','Fr','Sa'];
  let h=days.map(d=>`<div class="cal-dl">${d}</div>`).join('');
  const first=new Date(d.getFullYear(),d.getMonth(),1).getDay();
  for(let i=0;i<first;i++) h+=`<div class="cal-d empty">-</div>`;
  const dim=new Date(d.getFullYear(),d.getMonth()+1,0).getDate();
  const events=[1,5,11,15,20,24,31];
  for(let i=1;i<=dim;i++){const cls=i===20&&d.getMonth()===2?'today':events.includes(i)?'event':'';h+=`<div class="cal-d ${cls}">${i}</div>`;}
  document.getElementById('miniCal').innerHTML=h;
  document.getElementById('calPrev').onclick=()=>{calDate=new Date(calDate.getFullYear(),calDate.getMonth()-1,1);renderCal();};
  document.getElementById('calNext').onclick=()=>{calDate=new Date(calDate.getFullYear(),calDate.getMonth()+1,1);renderCal();};
}

// ═══ STUDENTS ═══
let stuPage=1,stuFilterVal='all',stuSearchVal='';
function renderStudents(){
  let list=DB.students.filter(x=>{
    const mF=stuFilterVal==='all'||x.feeStatus===stuFilterVal;
    const mS=!stuSearchVal||`${x.fname} ${x.lname} ${x.id} ${x.phone}`.toLowerCase().includes(stuSearchVal.toLowerCase());
    return mF&&mS;
  });
  document.getElementById('stuCount2').textContent=`${list.length} student(s)`;
  const pp=7,total=list.length,pages=Math.ceil(total/pp)||1;
  stuPage=Math.min(stuPage,pages);
  const sl=list.slice((stuPage-1)*pp,stuPage*pp);
  document.getElementById('stuTable').innerHTML=sl.map(x=>{
    const bal=x.netFee-x.paidAmt;
    const discTxt=x.baseFee>x.netFee?`<span class="tag tor" style="font-size:9px">🎁-₹${x.baseFee-x.netFee}</span>`:'<span style="color:var(--tx3);font-size:10px">—</span>';
    const rowClass=x.feeStatus==='overdue'?'fee-due-row':x.feeStatus==='partial'||x.feeStatus==='pending'?'fee-partial-row':'';
    return `<tr class="${rowClass}">
      <td><div class="si"><div class="sav" style="background:${x.color}">${x.fname[0]+x.lname[0]}</div><div><div style="font-weight:600;font-size:12.5px;cursor:pointer;color:var(--ac)" onclick="openStudentProfile('${x.id}')">${x.fname} ${x.lname}</div><div style="font-size:10px;color:var(--tx3);font-family:var(--fm)">#${x.id}</div></div></div></td>
      <td>${bTag(x.batchId)}</td>
      <td><span style="font-family:var(--fm);font-size:11px">${x.seat||'—'}</span>${x.feeStatus==='overdue'?'🔴':x.feeStatus!=='paid'?'🟠':''}</td>
      <td>${x.seatType==='ac'?'<span class="tag tac" style="font-size:9px">❄ AC</span>':'<span style="font-size:10px;color:var(--tx3)">Non-AC</span>'}</td>
      <td><span style="font-family:var(--fm);font-weight:700">₹${x.baseFee}</span></td>
      <td>${discTxt}${x.discount?.reason?`<div style="font-size:9px;color:var(--tx3)">${x.discount.reason}</div>`:''}</td>
      <td><span style="font-family:var(--fm);font-weight:700;color:var(--em)">₹${x.netFee}</span></td>
      <td><span style="font-family:var(--fm);font-weight:700;color:var(--em)">₹${x.paidAmt}</span></td>
      <td>${bal>0?`<span class="fee-bal-badge">₹${bal}</span>`:''}</td>
      <td><span class="tag ${x.feeStatus==='paid'?'tpd':x.feeStatus==='partial'?'tpart':x.feeStatus==='pending'?'tpn':'tod'}">${x.feeStatus==='paid'?'✓ Paid':x.feeStatus==='partial'?'◑ Partial':x.feeStatus==='pending'?'⏳ Pending':'🚨 Overdue'}</span></td>
      <td><span style="font-size:10.5px;font-family:var(--fm);color:${x.feeStatus==='overdue'?'var(--ro)':x.feeStatus==='pending'?'var(--gd)':'var(--tx3)'}">${fmtDate(x.dueDate)}</span></td>
      <td><div style="display:flex;gap:4px">
        ${x.feeStatus!=='paid'?`<button class="btn bp" style="font-size:10px;padding:3px 7px" onclick="qCollect('${x.id}')">Collect</button>`:''}
        <button class="btn bg" style="font-size:10px;padding:3px 7px" onclick="openStudentProfile('${x.id}')">👤</button>
        <button class="btn bwa" style="font-size:10px;padding:3px 7px" onclick="waQuick('${x.id}','${x.feeStatus==='paid'?'fee_receipt':x.feeStatus==='partial'?'partial_payment':x.feeStatus==='overdue'?'fee_overdue':'fee_due'}')">💬</button>
        <button class="btn bd" style="font-size:10px;padding:3px 6px" onclick="delStu('${x.id}')">✕</button>
      </div></td>
    </tr>`;
  }).join('')||'<tr><td colspan="12"><div class="empty"><div class="ei">👨‍🎓</div><div class="et">No students</div></div></td></tr>';
  document.getElementById('stuPagI').textContent=`${sl.length} of ${total}`;
  let pb='';for(let i=1;i<=pages;i++) pb+=`<div class="pb2 ${i===stuPage?'active':''}" onclick="stuPage=${i};renderStudents()">${i}</div>`;
  document.getElementById('stuPagB').innerHTML=pb;
}
function stuSrch(v){stuSearchVal=v;stuPage=1;renderStudents();}
function stuFilt(f,el){stuFilterVal=f;stuPage=1;document.querySelectorAll('#stuTabs .tab').forEach(t=>t.classList.remove('active'));el.classList.add('active');renderStudents();}
function qCollect(id){populateFeeModal(id);openM('mCollectFee');}
function delStu(id){if(!confirm('Remove?'))return;DB.students=DB.students.filter(x=>x.id!==id);toast('Removed','wn');renderStudents();updateBadges();}
// ═══ STUDENT PROFILE ═══
let profileStudentId = null;
let profileEditMode = false;

function openStudentProfile(id) {
  const s = DB.students.find(x => x.id === id);
  if (!s) return;
  profileStudentId = id;
  profileEditMode = false;

  // Header
  document.getElementById('spHeaderName').textContent = s.fname + ' ' + s.lname;
  document.getElementById('spHeaderId').textContent = '#' + s.id;
  document.getElementById('spAv').textContent = (s.fname[0] + s.lname[0]).toUpperCase();
  document.getElementById('spAv').style.background = s.color || '#4a7c6f';

  // Fee bar
  const pct = s.netFee > 0 ? Math.round(s.paidAmt / s.netFee * 100) : 0;
  document.getElementById('spFeeFill').style.width = pct + '%';
  document.getElementById('spPaidLbl').textContent = 'Paid ₹' + s.paidAmt.toLocaleString();
  document.getElementById('spDueLbl').textContent = 'Due ₹' + Math.max(0, s.netFee - s.paidAmt).toLocaleString();
  document.getElementById('spNetFee').textContent = '₹' + s.netFee.toLocaleString();
  const feeTagEl = document.getElementById('spFeeTag');
  const feeMap = { paid: ['tpd', '✓ Paid'], partial: ['tpart', '◑ Partial'], pending: ['tpn', '⏳ Pending'], overdue: ['tod', '🚨 Overdue'] };
  feeTagEl.className = 'tag ' + (feeMap[s.feeStatus]?.[0] || 'tpn');
  feeTagEl.textContent = feeMap[s.feeStatus]?.[1] || s.feeStatus;

  // Batch & seat
  const b = DB.batches.find(x => x.id === s.batchId);
  document.getElementById('spBatchDisp').innerHTML = b ? `<span class="tag ${b.name.includes('Morning')||b.name.includes('Early')?'tpn':b.name.includes('Evening')?'tis':b.name.includes('Night')?'tac':'tav'}">${b.name}</span>` : '<span style="color:var(--tx3)">—</span>';
  document.getElementById('spSeatNum').textContent = s.seat || '—';
  document.getElementById('spSeatChip').title = s.seat ? 'Click to change seat' : 'Click to allocate seat';
  document.getElementById('spSeatTypeDisp').innerHTML = s.seatType === 'ac' ? '<span class="tag tac" style="font-size:10px">❄ AC</span>' : '<span style="font-size:11px;color:var(--tx2)">Non-AC</span>';
  document.getElementById('spJoinDate').textContent = s.joinDate || '—';

  // Personal
  document.getElementById('spFname').textContent = s.fname;
  document.getElementById('spLname').textContent = s.lname;
  document.getElementById('spPhone').textContent = s.phone || '—';
  document.getElementById('spEmail').textContent = s.email || '—';
  document.getElementById('spCourse').textContent = s.course || '—';
  document.getElementById('spAddr').textContent = s.addr || '—';

  // Fee details
  document.getElementById('spBaseFee').textContent = '₹' + (s.baseFee || 0).toLocaleString();
  document.getElementById('spDiscount').textContent = s.baseFee > s.netFee ? '₹' + (s.baseFee - s.netFee).toLocaleString() + (s.discount?.reason ? ' — ' + s.discount.reason : '') : '—';
  document.getElementById('spPaidAmt').textContent = '₹' + (s.paidAmt || 0).toLocaleString();
  document.getElementById('spDueDate').textContent = fmtDate(s.dueDate);

  // Quick action buttons
  document.getElementById('spCollectBtn').onclick = () => { closeM('mStudentProfile'); qCollect(id); };
  document.getElementById('spWaBtn').onclick = () => { closeM('mStudentProfile'); setTimeout(() => waQuick(id, s.feeStatus === 'paid' ? 'fee_receipt' : s.feeStatus === 'overdue' ? 'fee_overdue' : 'fee_due'), 200); };
  document.getElementById('spDelBtn').onclick = () => { closeM('mStudentProfile'); delStu(id); };

  // Edit toggle reset
  document.getElementById('spEditToggle').classList.remove('on');
  document.getElementById('spEditToggle').textContent = '✏ Edit';
  document.getElementById('spSaveFooter').style.display = 'none';
  ['spFname','spLname','spPhone','spEmail','spCourse','spAddr'].forEach(id2 => {
    const el = document.getElementById(id2);
    el.contentEditable = 'false';
    el.classList.remove('editable');
  });

  openM('mStudentProfile');
}

function toggleProfileEdit() {
  profileEditMode = !profileEditMode;
  const toggle = document.getElementById('spEditToggle');
  const footer = document.getElementById('spSaveFooter');
  const fields = ['spFname','spLname','spPhone','spEmail','spCourse','spAddr'];
  if (profileEditMode) {
    toggle.classList.add('on');
    toggle.textContent = '✏ Editing…';
    footer.style.display = 'flex';
    fields.forEach(id2 => {
      const el = document.getElementById(id2);
      el.contentEditable = 'true';
      el.classList.add('editable');
    });
    document.getElementById('spFname').focus();
  } else {
    cancelProfileEdit();
  }
}

function cancelProfileEdit() {
  profileEditMode = false;
  const toggle = document.getElementById('spEditToggle');
  toggle.classList.remove('on');
  toggle.textContent = '✏ Edit';
  document.getElementById('spSaveFooter').style.display = 'none';
  ['spFname','spLname','spPhone','spEmail','spCourse','spAddr'].forEach(id2 => {
    const el = document.getElementById(id2);
    el.contentEditable = 'false';
    el.classList.remove('editable');
  });
  // Re-render original values
  openStudentProfile(profileStudentId);
}

function saveProfileEdit() {
  const s = DB.students.find(x => x.id === profileStudentId);
  if (!s) return;
  const get = id2 => document.getElementById(id2).textContent.trim();
  s.fname = get('spFname') || s.fname;
  s.lname = get('spLname') || s.lname;
  s.phone = get('spPhone');
  s.email = get('spEmail');
  s.course = get('spCourse');
  s.addr = get('spAddr');
  addActivity('✏', 'rgba(74,124,111,.14)', `Profile updated → <strong>${s.fname} ${s.lname}</strong>`);
  toast('Profile saved!', 'ok');
  renderStudents();
  // Re-open in view mode
  profileEditMode = false;
  openStudentProfile(profileStudentId);
}

function openAllocFromProfile() {
  const s = DB.students.find(x => x.id === profileStudentId);
  closeM('mStudentProfile');
  setTimeout(() => {
    openM('mAllocSeat');
    if (s) {
      document.getElementById('as-stu').value = s.id;
      if (s.batchId) document.getElementById('as-bt').value = s.batchId;
      if (s.seat) document.getElementById('as-st').value = s.seat;
    }
  }, 200);
}

// ═══ SEAT CLICK — OPEN ALLOC WITH PREFILL ═══
function openAllocSeatPrefilled(batchId, seatNum) {
  openM('mAllocSeat');
  setTimeout(() => {
    document.getElementById('as-bt').value = batchId;
    document.getElementById('as-st').value = seatNum;
  }, 50);
}

// ═══ WHATSAPP QR CONNECT ═══
let waSessionMsgCount = 0;
const WA_PROXY = 'api/whatsapp.php';

// ── Poll WA status every 4s when modal open ────────────
let waStatusPoller = null;

async function waFetch(action, data = null) {
  const url = WA_PROXY + '?action=' + action;
  const opts = data
    ? { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) }
    : { method: 'GET' };
  try {
    const r = await fetch(url, opts);
    return await r.json();
  } catch (e) {
    return { success: false, error: 'Cannot reach server: ' + e.message };
  }
}

function openM_waQR() { openM('mWaQR'); }

async function initWaQR() {
  const num = DB.settings?.waNum || DB.settings?.waNumber || '';
  document.getElementById('waConnNum').value = num ? (num.startsWith('+') ? num : '+' + num) : '';
  document.getElementById('waConnMsgCount').textContent = waSessionMsgCount + ' messages this session';
  await pollWaStatus();
  if (waStatusPoller) clearInterval(waStatusPoller);
  waStatusPoller = setInterval(pollWaStatus, 4000);
}

async function pollWaStatus() {
  setWaQRLoading();
  const r = await waFetch('status');

  if (!r || r.error?.includes('offline') || r.error?.includes('Cannot reach')) {
    setWaServerOffline(r?.error || 'WhatsApp server not running');
    return;
  }

  if (r.connected) {
    setWaConnected(r.client);
  } else if (r.qr) {
    setWaQR(r.qr);
  } else if (r.initializing) {
    setWaInitializing();
  } else {
    setWaInitializing();
  }
}

function setWaServerOffline(msg) {
  document.getElementById('waQRImg').innerHTML =
    '<div style="text-align:center;padding:16px"><div style="font-size:34px;margin-bottom:8px">🖥️</div>' +
    '<div style="font-size:11px;color:var(--ro);font-weight:600;margin-bottom:6px">Server Offline</div>' +
    '<div style="font-size:10px;color:var(--tx3);line-height:1.5">Run:<br><code style="font-size:9px;background:var(--sf2);padding:2px 4px;border-radius:3px">whatsapp-server/<br>START-WINDOWS.bat</code></div></div>';
  document.getElementById('waQRStatus').textContent = 'Start Node server first';
  document.getElementById('waConnBadge').className = 'wa-conn-badge wa-conn-no';
  document.getElementById('waConnBadge').textContent = '● Server Offline';
}

function setWaInitializing() {
  document.getElementById('waQRImg').innerHTML =
    '<div style="text-align:center;padding:20px"><div style="font-size:28px;margin-bottom:8px;animation:waPulse 1s infinite">⏳</div>' +
    '<div style="font-size:11px;color:var(--gd)">Initializing…</div></div>';
  document.getElementById('waQRStatus').textContent = 'Starting WhatsApp, please wait…';
  document.getElementById('waConnBadge').className = 'wa-conn-badge wa-conn-no';
  document.getElementById('waConnBadge').textContent = '⏳ Connecting…';
}

function setWaQRLoading() {
  // Don't clear QR if already shown
}

function setWaQR(qrDataUrl) {
  document.getElementById('waQRImg').innerHTML =
    `<img src="${qrDataUrl}" style="width:150px;height:150px;border-radius:6px" alt="WhatsApp QR">`;
  document.getElementById('waQRStatus').textContent = 'Scan this QR with WhatsApp · ' + new Date().toLocaleTimeString();
  document.getElementById('waConnBadge').className = 'wa-conn-badge wa-conn-no';
  document.getElementById('waConnBadge').textContent = '📱 Waiting for scan…';
}

function setWaConnected(client) {
  document.getElementById('waQRImg').innerHTML =
    '<div style="text-align:center;padding:16px"><div style="font-size:40px;margin-bottom:8px">✅</div>' +
    `<div style="font-size:12px;font-weight:700;color:var(--wa2)">${client?.name || 'Connected'}</div>` +
    `<div style="font-size:10px;color:var(--tx3)">+${client?.number || ''}</div></div>`;
  document.getElementById('waQRStatus').textContent = 'Connected · Messages send directly ✅';
  document.getElementById('waConnBadge').className = 'wa-conn-badge wa-conn-ok';
  document.getElementById('waConnBadge').textContent = '● Connected — ' + (client?.name || 'WhatsApp');
}

function refreshWaQR() {
  document.getElementById('waQRStatus').textContent = 'Refreshing…';
  pollWaStatus();
}

async function saveWaNumber() {
  const val = document.getElementById('waConnNum').value.replace(/\D/g, '');
  if (!val) return toast('Enter a phone number', 'er');
  DB.settings.waNum = val;
  DB.settings.waNumber = val;
  document.getElementById('s-wa').value = val;
  // Also persist to server settings
  try { await apiPost('save_settings', { wa_number: val }); } catch(e) {}
  toast('WhatsApp number saved!', 'ok');
}

async function testWaConnection() {
  toast('Sending test message…', 'wn');
  const r = await waFetch('test');
  if (r.success) {
    waSessionMsgCount++;
    document.getElementById('waConnMsgCount').textContent = waSessionMsgCount + ' messages this session';
    toast('✅ Test message sent to your WhatsApp!', 'wa');
  } else {
    toast('❌ ' + (r.error || 'Send failed'), 'er');
  }
}

async function waLogoutDevice() {
  if (!confirm('Disconnect WhatsApp? You will need to scan QR again.')) return;
  const r = await waFetch('logout');
  toast(r.success ? 'Disconnected' : 'Error: ' + r.error, r.success ? 'wn' : 'er');
  pollWaStatus();
}

// Stop polling when modal closes
document.addEventListener('click', (e) => {
  if (e.target?.classList?.contains('mo')) {
    if (waStatusPoller) { clearInterval(waStatusPoller); waStatusPoller = null; }
  }
});

// ── Real WA send (replaces open-in-browser) ───────────
async function waSendDirect(phone, message, studentName) {
  const r = await waFetch('send', { to: phone, message, student_name: studentName });
  if (r.success) {
    waSessionMsgCount++;
    toast('✅ WhatsApp sent to ' + (studentName || phone) + '!', 'wa');
    addActivity('💬', 'rgba(37,211,102,.14)', `WhatsApp → <strong>${studentName || phone}</strong>`);
    return true;
  } else {
    // Fallback to WA web link if server not ready
    if (r.error?.includes('offline') || r.error?.includes('not connected')) {
      const enc = encodeURIComponent(message);
      const num = phone.replace(/\D/g, '');
      window.open(`https://wa.me/${num}?text=${enc}`, '_blank');
      toast('📱 Opened WhatsApp Web (server offline)', 'wn');
    } else {
      toast('❌ ' + r.error, 'er');
    }
    return false;
  }
}


// ═══ SEATS (with fee status highlight) ═══
function renderSeats(){
  const total=DB.batches.reduce((a,b)=>a+b.total,0);
  const occ=DB.batches.reduce((a,b)=>a+b.occupied,0);
  document.getElementById('st-total').textContent=total;
  document.getElementById('st-vacant').textContent=total-occ;
  document.getElementById('st-occupied').textContent=occ;
  document.getElementById('batchGrid').innerHTML=DB.batches.map((b,i)=>{
    const pct=Math.round(b.occupied/b.total*100);
    const fc=pct>=100?'sf-r':pct>=70?'sf-y':'sf-g';
    const sc=pct>=100?'bst-f':pct>=70?'bst-n':'bst-o';
    const bStudents=DB.students.filter(x=>x.batchId===b.id);
    const seatStudentMap={};bStudents.forEach(st=>{if(st.seat)seatStudentMap[st.seat]=st;});
    let cells='';
    for(let s=1;s<=b.total;s++){
      const sn=seatLbl(b.name,s);const stu=seatStudentMap[sn];
      let cls='seat-vac',ttText='Vacant: '+sn;
      if(stu){
        if(stu.feeStatus==='overdue'){cls='seat-overdue';ttText=`🚨 ${stu.fname} — OVERDUE ₹${stu.netFee-stu.paidAmt}`;}
        else if(stu.feeStatus==='pending'){cls='seat-due';ttText=`⏳ ${stu.fname} — Pending ₹${stu.netFee}`;}
        else if(stu.feeStatus==='partial'){cls='seat-due';ttText=`🟠 ${stu.fname} — Partial, Due ₹${stu.netFee-stu.paidAmt}`;}
        else{cls='seat-occ';ttText=`✓ ${stu.fname} — Paid · Click to view`;}
      }
      const clickFn=stu?`openStudentProfile('${stu.id}')`:`openAllocSeatPrefilled('${b.id}','${sn}')`;
      cells+=`<div class="seat-cell ${cls}" onclick="${clickFn}"><div class="seat-tooltip">${ttText}</div>${sn}</div>`;
    }
    const bDue=bStudents.filter(x=>x.feeStatus!=='paid').length;
    const bOD=bStudents.filter(x=>x.feeStatus==='overdue').length;
    return `<div class="panel"><div class="ph">
      <div><div style="font-weight:600;font-size:13px">${batchEmoji(b.name)} ${b.name}</div>
      <div style="font-size:10px;color:var(--tx3);font-family:var(--fm)">${fmtT(b.startTime)}–${fmtT(b.endTime)} · Base ₹${b.baseFee} · AC +₹${b.acExtra}</div></div>
      <div style="display:flex;gap:7px;align-items:center">
        <div class="bst ${sc}">${pct>=100?'Full':pct>=70?'Filling':'Open'}</div>
        ${bOD>0?`<span style="font-size:9px;background:rgba(192,68,79,.15);color:var(--ro);padding:2px 6px;border-radius:3px;font-weight:700;animation:pulseDue 1s infinite">🚨${bOD} overdue</span>`:''}
        ${bDue>bOD?`<span style="font-size:9px;background:rgba(230,126,34,.15);color:var(--or);padding:2px 6px;border-radius:3px;font-weight:700">🟠${bDue-bOD} due</span>`:''}
        <button class="btn bg" style="font-size:10px;padding:3px 7px" onclick="editBatch(${i})">✏ Edit</button>
        <button class="btn bd" style="font-size:10px;padding:3px 6px" onclick="delBatch(${i})">✕</button>
      </div>
    </div>
    <div class="pb">
      <div class="sbar"><div class="sfill ${fc}" style="width:${pct}%"></div></div>
      <div style="display:flex;justify-content:space-between;font-size:10px;font-family:var(--fm);color:var(--tx3);margin-bottom:12px">
        <span>Total:<b>${b.total}</b></span><span style="color:var(--ro)">Occupied:<b>${b.occupied}</b></span><span style="color:var(--em)">Vacant:<b>${b.total-b.occupied}</b></span>
      </div>
      <div class="seat-visual">${cells}</div>
    </div></div>`;
  }).join('');
}

function seatLbl(n,i){const p={'Early Morning':'E','Morning':'A','Afternoon':'B','Evening':'C','Night':'D','Late Night':'F'};return`${p[n]||'X'}-${String(i).padStart(2,'0')}`;}

function editBatch(idx){
  editBatchIdx=idx;const b=DB.batches[idx];
  document.getElementById('mAddBatchTitle').textContent='✏ Edit Batch';
  document.getElementById('batchSaveBtn').textContent='Save Changes';
  document.getElementById('ab-nm').value=b.name;document.getElementById('ab-st').value=b.startTime;
  document.getElementById('ab-et').value=b.endTime;document.getElementById('ab-ts').value=b.total;
  document.getElementById('ab-fe').value=b.baseFee;document.getElementById('ab-ac').value=b.acExtra;
  openM('mAddBatch');
}
function saveBatch(){
  const nm=gv('ab-nm'),st=gv('ab-st'),et=gv('ab-et'),ts=+gv('ab-ts'),fe=+gv('ab-fe'),ac=+gv('ab-ac');
  if(!nm||!st||!et||!ts||!fe)return toast('Fill required fields','er');
  if(editBatchIdx>=0){
    const b=DB.batches[editBatchIdx];
    if(ts<b.occupied)return toast('Cannot reduce below occupied','er');
    Object.assign(b,{name:nm,startTime:st,endTime:et,total:ts,baseFee:fe,acExtra:ac});
    toast(`"${nm}" updated!`,'ok');editBatchIdx=-1;
  } else {
    DB.batches.push({id:'BT-'+Date.now(),name:nm,startTime:st,endTime:et,total:ts,occupied:0,baseFee:fe,acExtra:ac});
    addActivity('🆕','rgba(74,124,111,.14)',`Batch "<strong>${nm}</strong>" added (${ts} seats)`);
    toast(`Batch "${nm}" created!`,'ok');
  }
  closeM('mAddBatch');document.getElementById('mAddBatchTitle').textContent='Add New Batch';document.getElementById('batchSaveBtn').textContent='Add Batch';editBatchIdx=-1;
  renderSeats();populateBatchSelects();
}
function delBatch(idx){if(!confirm('Delete batch?'))return;DB.batches.splice(idx,1);toast('Deleted','wn');renderSeats();populateBatchSelects();}
function populateBatchSelects(){
  const opts=DB.batches.map(b=>`<option value="${b.id}">${b.name} (${fmtT(b.startTime)}–${fmtT(b.endTime)})</option>`).join('');
  ['en-bt','as-bt','attBatchF'].forEach(id=>{const el=document.getElementById(id);if(!el)return;if(id==='attBatchF')el.innerHTML=`<option value="all">All Batches</option>`+opts;else el.innerHTML=`<option value="">-- Select --</option>`+opts;});
}
function fmtT(t){const[h,m]=t.split(':');const hr=+h;return`${hr===0?12:hr>12?hr-12:hr}:${m} ${hr<12?'AM':'PM'}`;}

// ═══ ENROLL ═══
function calcEnrollFee(){
  const bId=gv('en-bt'),acType=gv('en-ac');const b=DB.batches.find(x=>x.id===bId);
  if(!b){document.getElementById('en-fe').value='';document.getElementById('en-net-fe').value='';document.getElementById('en-fee-note').style.display='none';return;}
  const base=b.baseFee+(acType==='ac'?b.acExtra:0);
  document.getElementById('en-fe').value=base;
  applyEnrollDiscount(base);
}
function applyEnrollDiscount(base){
  if(!base)base=+gv('en-fe');
  const dtype=gv('en-disc-type'),dval=+gv('en-disc-val')||0;
  let disc=0;
  if(dtype==='flat')disc=Math.min(dval,base);
  else if(dtype==='percent')disc=Math.round(base*dval/100);
  const net=base-disc;
  document.getElementById('en-net-fe').value=net;
  const note=document.getElementById('en-fee-note');
  note.style.display='block';
  note.innerHTML=`💡 Base: ₹${base}${disc>0?` <span style="color:var(--or)">− Discount: ₹${disc}</span> = <strong style="color:var(--em)">Net: ₹${net}/month</strong>`:`= ₹${net}/month`}`;
}
document.getElementById('en-bt').addEventListener('change',calcEnrollFee);
document.getElementById('en-ac').addEventListener('change',calcEnrollFee);
document.getElementById('en-disc-type').addEventListener('change',()=>applyEnrollDiscount(+gv('en-fe')));
document.getElementById('en-disc-val').addEventListener('input',()=>applyEnrollDiscount(+gv('en-fe')));

function enrollStudent(){
  const fn=gv('en-fn'),ln=gv('en-ln'),ph=gv('en-ph'),bt=gv('en-bt'),fe=+gv('en-fe'),net=+gv('en-net-fe'),ac=gv('en-ac');
  if(!fn||!ln||!ph||!bt||!fe)return toast('Fill required fields','er');
  const batch=DB.batches.find(b=>b.id===bt);
  if(batch&&batch.occupied>=batch.total)return toast(`${batch.name} is full!`,'er');
  const id='STU-'+String(DB.students.length+1).padStart(3,'0');
  const cols=['#4a7c6f','#c47d2b','#3a7ab0','#7c5cbf','#c0444f','#3a7d5e'];
  const due=new Date();due.setDate(due.getDate()+11);
  const discType=gv('en-disc-type'),discVal=+gv('en-disc-val')||0,discReason=gv('en-disc-reason');
  DB.students.push({id,fname:fn,lname:ln,phone:ph,email:gv('en-em'),batchId:bt,seatType:ac,seat:gv('en-st'),baseFee:fe,discount:{type:discType,value:discVal,reason:discReason},netFee:net||fe,paidAmt:0,feeStatus:'pending',paidOn:'-',dueDate:due.toLocaleDateString('en-IN',{day:'numeric',month:'short',year:'numeric'}),course:gv('en-co'),addr:gv('en-ad'),color:cols[DB.students.length%cols.length],joinDate:new Date().toLocaleDateString('en-IN',{day:'numeric',month:'short',year:'numeric'})});
  if(batch)batch.occupied++;DB.attendance[id]='present';
  addActivity('👨‍🎓','rgba(58,125,94,.14)',`<strong>${fn} ${ln}</strong> enrolled in ${batch?.name||bt}${fe>net?` (₹${fe-net} discount)`:''}`);
  addNotif('success','New Enrollment',`${fn} ${ln} enrolled`);
  if(document.getElementById('en-wa').checked)setTimeout(()=>waQuick(id,'welcome'),600);
  closeM('mEnroll');toast(`${fn} ${ln} enrolled${fe>net?` with ₹${fe-net} discount`:''}!`,'ok');
  ['en-fn','en-ln','en-ph','en-em','en-bt','en-fe','en-net-fe','en-st','en-co','en-ad','en-disc-val','en-disc-reason'].forEach(i=>{const el=document.getElementById(i);if(el)el.value='';});
  document.getElementById('en-fee-note').style.display='none';
  refreshAll();updateBadges();
}

// ═══ ATTENDANCE ═══
function renderAtt(){
  const today=new Date().toLocaleDateString('en-IN',{weekday:'long',year:'numeric',month:'long',day:'numeric'});
  document.getElementById('attLbl').textContent=`Date: ${today}`;
  const bf=gv('attBatchF');
  const list=DB.students.filter(s=>bf==='all'||s.batchId===bf);
  const prs=list.filter(s=>DB.attendance[s.id]==='present').length;
  document.getElementById('at-p').textContent=prs;document.getElementById('at-a').textContent=list.length-prs;
  document.getElementById('at-r').textContent=list.length?Math.round(prs/list.length*100)+'%':'0%';
  document.getElementById('at-t').textContent=list.length;
  document.getElementById('attTable').innerHTML=list.map(s=>{
    const st=DB.attendance[s.id]||'absent';
    return `<tr><td><div class="si"><div class="sav" style="background:${s.color}">${s.fname[0]+s.lname[0]}</div><div><div style="font-weight:600;font-size:12.5px">${s.fname} ${s.lname}</div><div style="font-size:10px;color:var(--tx3);font-family:var(--fm)">${s.id}</div></div></div></td>
    <td>${bTag(s.batchId)}</td><td><span style="font-family:var(--fm);font-size:11px">${s.seat||'—'}</span></td>
    <td><span class="tag ${s.feeStatus==='paid'?'tpd':s.feeStatus==='partial'?'tpart':s.feeStatus==='pending'?'tpn':'tod'}">${s.feeStatus==='paid'?'✓ Paid':s.feeStatus==='partial'?'◑ Partial':s.feeStatus==='pending'?'⏳ Pending':'🚨 Overdue'}</span></td>
    <td><span class="tag ${st==='present'?'tpd':'tod'}">${st==='present'?'✓ Present':'✗ Absent'}</span></td>
    <td><button class="btn ${st==='present'?'bd':'bp'}" style="font-size:10.5px;padding:4px 10px" onclick="togAtt('${s.id}')">${st==='present'?'Absent':'Present'}</button></td></tr>`;
  }).join('')||'<tr><td colspan="6"><div class="empty"><div class="ei">📋</div><div class="et">No students</div></div></td></tr>';
}
function togAtt(id){DB.attendance[id]=DB.attendance[id]==='present'?'absent':'present';renderAtt();}
function markAll(p){DB.students.forEach(s=>{DB.attendance[s.id]=p?'present':'absent';});renderAtt();toast(p?'All present':'All absent',p?'ok':'wn');}
function saveAtt(){const p=Object.values(DB.attendance).filter(v=>v==='present').length;addActivity('📋','rgba(58,122,176,.14)',`Attendance: <strong>${p}/${DB.students.length}</strong> present`);toast(`Saved! ${p} present`,'ok');updateBadges();}

// ═══ BOOKS ═══
let bkPage=1,bkSearch='';
function renderBooks(){
  const cf=document.getElementById('bkCatF')?.value||'all';
  let list=DB.books.filter(b=>(cf==='all'||b.category===cf)&&(!bkSearch||`${b.title} ${b.author}`.toLowerCase().includes(bkSearch.toLowerCase())));
  document.getElementById('bkCount').textContent=`${list.length} books`;
  const pp=7,total=list.length,pages=Math.ceil(total/pp)||1;
  bkPage=Math.min(bkPage,pages);const sl=list.slice((bkPage-1)*pp,bkPage*pp);
  document.getElementById('bkTable').innerHTML=sl.map(b=>{const av=b.available>0;
    return `<tr><td><div style="display:flex;align-items:center;gap:7px"><span style="font-size:17px">${b.emoji}</span><div><div style="font-weight:600;font-size:12.5px">${b.title}</div><div style="font-size:10px;color:var(--tx3);font-family:var(--fm)">${b.id}</div></div></div></td>
    <td>${b.author}</td><td><span class="tag tac" style="font-size:9px">${b.category}</span></td>
    <td style="font-family:var(--fm);font-weight:700">${b.copies}</td>
    <td><span style="font-family:var(--fm);font-weight:700;color:${av?'var(--em)':'var(--ro)'}">${b.available}</span></td>
    <td><span style="font-family:var(--fm);font-size:11px">${b.shelf||'—'}</span></td>
    <td><span class="tag ${av?'tav':'tis'}">${av?'Available':'Issued'}</span></td>
    <td><div style="display:flex;gap:4px"><button class="btn bp" style="font-size:10px;padding:3px 7px" onclick="openIssueFor('${b.id}')">Issue</button><button class="btn bd" style="font-size:10px;padding:3px 6px" onclick="delBook('${b.id}')">✕</button></div></td></tr>`;
  }).join('')||'<tr><td colspan="8"><div class="empty"><div class="ei">📚</div><div class="et">No books</div></div></td></tr>';
  document.getElementById('bkPagI').textContent=`${sl.length} of ${total}`;
  let pb='';for(let i=1;i<=pages;i++) pb+=`<div class="pb2 ${i===bkPage?'active':''}" onclick="bkPage=${i};renderBooks()">${i}</div>`;
  document.getElementById('bkPagB').innerHTML=pb;
}
function bkSrch(v){bkSearch=v;bkPage=1;renderBooks();}
function openIssueFor(bkId){populateIssueModal(bkId);openM('mIssueBook');}
function delBook(id){if(!confirm('Remove?'))return;DB.books=DB.books.filter(b=>b.id!==id);toast('Removed','wn');renderBooks();}
function addBook(){
  const tl=gv('bk-tl'),au=gv('bk-au'),cp=+gv('bk-cp');if(!tl||!au||!cp)return toast('Fill required','er');
  const emjs=['📘','📙','📗','📕','📔','📒'];
  DB.books.push({id:'BK-'+String(DB.books.length+1).padStart(3,'0'),title:tl,author:au,isbn:gv('bk-is')||'N/A',category:gv('bk-ca'),copies:cp,available:cp,shelf:gv('bk-sh')||'TBD',emoji:emjs[DB.books.length%emjs.length]});
  addActivity('📚','rgba(58,122,176,.14)',`Book "<strong>${tl}</strong>" added`);
  closeM('mAddBook');toast(`Added!`,'ok');renderBooks();
}

// ═══ TRANSACTIONS ═══
function renderTx(){
  const iss=DB.transactions.filter(t=>t.status!=='returned');const od=DB.transactions.filter(t=>t.status==='overdue');const rt=DB.transactions.filter(t=>t.status==='returned');const fine=DB.transactions.reduce((a,t)=>a+(t.fine||0),0);
  document.getElementById('tx-is').textContent=iss.length;document.getElementById('tx-od').textContent=od.length;document.getElementById('tx-rt').textContent=rt.length;document.getElementById('tx-fn').textContent=fmt(fine);
  document.getElementById('b-overdue').textContent=od.length;document.getElementById('txCount').textContent=`${DB.transactions.length} transactions`;
  document.getElementById('txTable').innerHTML=DB.transactions.map(t=>{
    const s=DB.students.find(x=>x.id===t.studentId);const b=DB.books.find(x=>x.id===t.bookId);if(!s||!b)return'';
    return `<tr><td><div class="si"><div class="sav" style="background:${s.color}">${s.fname[0]+s.lname[0]}</div><span style="font-size:12.5px;font-weight:600">${s.fname} ${s.lname}</span></div></td>
    <td>${b.emoji} ${b.title}</td>
    <td><span style="font-family:var(--fm);font-size:10.5px">${fmtDate(t.issueDate)}</span></td>
    <td><span style="font-family:var(--fm);font-size:10.5px;color:${t.status==='overdue'?'var(--ro)':'inherit'}">${fmtDate(t.dueDate)}</span></td>
    <td><span style="font-family:var(--fm);font-size:10.5px">${t.returnDate||'—'}</span></td>
    <td><span style="font-family:var(--fm);font-weight:700;color:${t.fine>0?'var(--ro)':'inherit'}">₹${t.fine}</span></td>
    <td><span class="tag ${t.status==='returned'?'trt':t.status==='overdue'?'tod':'tis'}">${t.status==='returned'?'✓ Returned':t.status==='overdue'?'⚠ Overdue':'📤 Issued'}</span></td>
    <td>${t.status!=='returned'?`<div style="display:flex;gap:4px"><button class="btn bg" style="font-size:10px;padding:3px 7px" onclick="qReturn('${t.id}')">Return</button><button class="btn bwa" style="font-size:10px;padding:3px 7px" onclick="waQuick('${t.studentId}','book_overdue')">💬</button></div>`:''}</td></tr>`;
  }).join('')||'<tr><td colspan="8"><div class="empty"><div class="ei">🔄</div><div class="et">No transactions</div></div></td></tr>';
}
function qReturn(txId){populateReturnModal();setTimeout(()=>{document.getElementById('rb-tx').value=txId;calcFine();},50);openM('mReturnBook');}
function populateIssueModal(bkId){
  document.getElementById('ib-stu').innerHTML='<option value="">-- Select --</option>'+DB.students.map(s=>`<option value="${s.id}">${s.fname} ${s.lname}</option>`).join('');
  document.getElementById('ib-bk').innerHTML='<option value="">-- Select --</option>'+DB.books.filter(b=>b.available>0).map(b=>`<option value="${b.id}" ${b.id===bkId?'selected':''}>${b.emoji} ${b.title} (${b.available})</option>`).join('');
  const today=new Date().toISOString().split('T')[0];document.getElementById('ib-id').value=today;
  const due=new Date();due.setDate(due.getDate()+DB.settings.days);document.getElementById('ib-dd').value=due.toISOString().split('T')[0];
}
function issueBook(){
  const stuId=gv('ib-stu'),bkId=gv('ib-bk');if(!stuId||!bkId)return toast('Select student and book','er');
  const bk=DB.books.find(b=>b.id===bkId);const stu=DB.students.find(s=>s.id===stuId);
  if(bk.available<=0)return toast('No copies available!','er');bk.available--;
  const iDate=new Date().toLocaleDateString('en-IN',{day:'numeric',month:'short',year:'numeric'});
  const due=new Date();due.setDate(due.getDate()+DB.settings.days);
  DB.transactions.push({id:'TX-'+Date.now(),studentId:stuId,bookId:bkId,issueDate:iDate,dueDate:due.toLocaleDateString('en-IN',{day:'numeric',month:'short',year:'numeric'}),returnDate:null,fine:0,status:'issued'});
  addActivity('📤','rgba(124,92,191,.14)',`<strong>${stu?.fname}</strong> issued "${bk.title}"`);
  closeM('mIssueBook');toast(`"${bk.title}" issued!`,'ok');refreshAll();updateBadges();
}
function populateReturnModal(){
  const active=DB.transactions.filter(t=>t.status!=='returned');
  document.getElementById('rb-tx').innerHTML='<option value="">-- Select --</option>'+active.map(t=>{const s=DB.students.find(x=>x.id===t.studentId);const b=DB.books.find(x=>x.id===t.bookId);return`<option value="${t.id}">${b?.emoji} ${b?.title} → ${s?.fname} (Due:${fmtDate(t.dueDate)})</option>`;}).join('');
  document.getElementById('rb-dt').value=new Date().toISOString().split('T')[0];
}
function calcFine(){
  const txId=document.getElementById('rb-tx').value;const tx=DB.transactions.find(t=>t.id===txId);if(!tx)return;
  const due=new Date(tx.dueDate);const today=new Date();
  const diff=Math.max(0,Math.floor((today-due)/(1000*60*60*24)));const fine=diff*DB.settings.fine;
  document.getElementById('rb-fn').value=fine;
  const note=document.getElementById('rb-note');
  if(fine>0){note.style.display='block';note.textContent=`⚠ ${diff} days overdue. Fine: ₹${fine} (₹${DB.settings.fine}/day)`;}else note.style.display='none';
}
function returnBook(){
  const txId=gv('rb-tx'),cond=gv('rb-cd');if(!txId)return toast('Select transaction','er');
  const tx=DB.transactions.find(t=>t.id===txId);if(!tx)return;
  const bk=DB.books.find(b=>b.id===tx.bookId);const stu=DB.students.find(s=>s.id===tx.studentId);
  if(cond!=='Lost')bk.available++;
  tx.fine=+gv('rb-fn')||0;tx.status='returned';tx.returnDate=new Date().toLocaleDateString('en-IN',{day:'numeric',month:'short',year:'numeric'});
  addActivity('📩','rgba(58,125,94,.14)',`<strong>${stu?.fname}</strong> returned "${bk?.title}"${tx.fine>0?` Fine ₹${tx.fine}`:''}`);
  closeM('mReturnBook');toast(`Returned!${tx.fine>0?' Fine: ₹'+tx.fine:''}`,'ok');refreshAll();updateBadges();
}

// ═══ FEES ═══
let feeFiltVal='all',feeSrchVal='';
function renderFees(){
  const s=DB.students;
  const paid=s.filter(x=>x.feeStatus==='paid');const partial=s.filter(x=>x.feeStatus==='partial');
  const pend=s.filter(x=>x.feeStatus==='pending');const od=s.filter(x=>x.feeStatus==='overdue');
  document.getElementById('fc-c').textContent=fmt(paid.reduce((a,x)=>a+x.netFee,0));document.getElementById('fc-cm').textContent=`${paid.length} fully paid`;
  document.getElementById('fc-pp').textContent=partial.length;document.getElementById('fc-ppm').textContent=`₹${partial.reduce((a,x)=>a+(x.netFee-x.paidAmt),0).toLocaleString()} balance due`;
  document.getElementById('fc-p').textContent=fmt(pend.reduce((a,x)=>a+x.netFee,0));document.getElementById('fc-pm').textContent=`${pend.length} students`;
  document.getElementById('fc-o').textContent=fmt(od.reduce((a,x)=>a+x.netFee,0));document.getElementById('fc-om').textContent=`${od.length} students (>7 days)`;
  document.getElementById('b-fee').textContent=pend.length+od.length+partial.length;
  let list=s.filter(x=>(feeFiltVal==='all'||x.feeStatus===feeFiltVal)&&(!feeSrchVal||`${x.fname} ${x.lname} ${x.id}`.toLowerCase().includes(feeSrchVal.toLowerCase())));
  document.getElementById('feeTable').innerHTML=list.map(x=>{
    const bal=x.netFee-x.paidAmt;const pctPaid=Math.round(x.paidAmt/x.netFee*100);
    const discTxt=x.baseFee>x.netFee?`<div><span class="tag tor" style="font-size:9px">🎁 ₹${x.baseFee-x.netFee}</span><div style="font-size:9px;color:var(--tx3)">${x.discount?.reason||''}</div></div>`:'<span style="color:var(--tx3)">—</span>';
    const partialBar=x.feeStatus==='partial'?`<div class="fee-partial-wrap"><div class="fee-partial-bar"><div class="fee-partial-fill" style="width:${pctPaid}%"></div></div><div style="font-size:9px;color:var(--tx3);font-family:var(--fm)">${pctPaid}% paid</div></div>`:'';
    const rowClass=x.feeStatus==='overdue'?'fee-due-row':x.feeStatus==='partial'||x.feeStatus==='pending'?'fee-partial-row':'';
    return `<tr class="${rowClass}">
      <td><div class="si"><div class="sav" style="background:${x.color}">${x.fname[0]+x.lname[0]}</div><div><div style="font-weight:600;font-size:12.5px;cursor:pointer;color:var(--ac)" onclick="openStudentProfile('${x.id}')">${x.fname} ${x.lname}</div><div style="font-size:10px;color:var(--tx3);font-family:var(--fm)">${x.id}</div></div></div></td>
      <td>${bTag(x.batchId)}</td>
      <td><span style="font-family:var(--fm)">₹${x.baseFee}</span></td>
      <td>${discTxt}</td>
      <td><span style="font-family:var(--fm);font-weight:700;color:var(--em)">₹${x.netFee}</span></td>
      <td><div><span style="font-family:var(--fm);font-weight:700;color:var(--em)">₹${x.paidAmt}</span>${partialBar}</div></td>
      <td>${bal>0?`<div style="display:flex;align-items:center;gap:4px"><span class="fee-bal-badge">₹${bal} DUE</span></div>`:`<span style="color:var(--em);font-size:12px">✓ Clear</span>`}</td>
      <td><span style="font-family:var(--fm);font-size:10.5px">${fmtDate(x.paidOn)}</span></td>
      <td><span class="tag ${x.feeStatus==='paid'?'tpd':x.feeStatus==='partial'?'tpart':x.feeStatus==='pending'?'tpn':'tod'}">${x.feeStatus==='paid'?'✓ Paid':x.feeStatus==='partial'?'◑ Partial':x.feeStatus==='pending'?'⏳ Pending':'🚨 Overdue'}</span></td>
      <td><span style="font-size:10.5px;font-family:var(--fm);color:${x.feeStatus==='overdue'?'var(--ro)':x.feeStatus==='pending'?'var(--gd)':'var(--tx3)'}">${fmtDate(x.dueDate)}</span></td>
      <td><div style="display:flex;gap:4px">
        ${x.feeStatus!=='paid'?`<button class="btn bp" style="font-size:10px;padding:3px 7px" onclick="qCollect('${x.id}')">Collect</button>`:'<span style="color:var(--em);font-size:11px">✓</span>'}
        <button class="btn bwa" style="font-size:10px;padding:3px 7px" onclick="waQuick('${x.id}','${x.feeStatus==='paid'?'fee_receipt':x.feeStatus==='partial'?'partial_payment':x.feeStatus==='overdue'?'fee_overdue':'fee_due'}')">💬</button>
      </div></td>
    </tr>`;
  }).join('')||'<tr><td colspan="11"><div class="empty"><div class="ei">💰</div><div class="et">No records</div></div></td></tr>';
  document.getElementById('feePagI').textContent=`${list.length} records`;
}
function feeFilt(f,el){feeFiltVal=f;document.querySelectorAll('#feeTabs .tab').forEach(t=>t.classList.remove('active'));el.classList.add('active');renderFees();}
function feeSrch(v){feeSrchVal=v;renderFees();}
function sendReminders(){const od=DB.students.filter(x=>x.feeStatus!=='paid');od.forEach(s=>addActivity('📣','rgba(196,125,43,.14)',`Reminder → <strong>${s.fname}</strong>`));toast(`Reminders sent to ${od.length}`,'ok');}
function waBulkFee(){bulkSend('pending');navTo('whatsapp');}

// FEE COLLECT
function populateFeeModal(stuId){
  populateModal_cf();
  setTimeout(()=>{const s=DB.students.find(x=>x.id===stuId);if(s){document.getElementById('cf-stu').value=stuId;cfLoadStudent();}},50);
}
function populateModal_cf(){
  document.getElementById('cf-stu').innerHTML='<option value="">-- Select --</option>'+DB.students.filter(s=>s.feeStatus!=='paid').map(s=>`<option value="${s.id}">${s.fname} ${s.lname} — Net ₹${s.netFee} (${s.feeStatus})</option>`).join('');
  document.getElementById('cf-stu').onchange=cfLoadStudent;
}
function cfLoadStudent(){
  const s=DB.students.find(x=>x.id===gv('cf-stu'));if(!s)return;
  document.getElementById('cf-tot').value=s.netFee;
  document.getElementById('cf-amt').value='';
  const bal=s.netFee-s.paidAmt;
  const info=document.getElementById('cf-status-info');
  if(s.feeStatus==='partial'){
    info.style.display='block';
    info.innerHTML=`<div style="padding:10px 13px;border-radius:var(--r2);border:1px solid rgba(58,122,176,.3);background:rgba(58,122,176,.06)">
      <div style="font-size:12px;font-weight:600;margin-bottom:4px;color:var(--sk)">◑ Partial Payment on Record</div>
      <div style="font-size:11.5px;color:var(--tx2)">Net Fee: <strong>₹${s.netFee}</strong> | Paid: <strong style="color:var(--em)">₹${s.paidAmt}</strong> | <strong style="color:var(--ro)">Balance: ₹${bal}</strong></div>
      <div style="height:6px;background:var(--sf2);border-radius:3px;overflow:hidden;margin:6px 0"><div style="width:${Math.round(s.paidAmt/s.netFee*100)}%;height:100%;background:linear-gradient(90deg,var(--em),#4ead82);border-radius:3px"></div></div>
      <div style="font-size:10px;color:var(--tx3);font-family:var(--fm)">${Math.round(s.paidAmt/s.netFee*100)}% paid so far</div>
    </div>`;
    document.getElementById('cf-amt').value=bal;
  } else if(s.feeStatus==='overdue'){
    info.style.display='block';
    info.innerHTML=`<div style="padding:8px 12px;border-radius:var(--r2);border:1px solid rgba(192,68,79,.3);background:rgba(192,68,79,.06)"><div style="font-size:12px;font-weight:600;color:var(--ro)">🚨 Fee Overdue since ${fmtDate(s.dueDate)}</div><div style="font-size:11px;color:var(--tx2)">Amount due: ₹${s.netFee}</div></div>`;
    document.getElementById('cf-amt').value=s.netFee;
  } else if(s.baseFee>s.netFee){
    info.style.display='block';
    info.innerHTML=`<div style="padding:8px 12px;border-radius:var(--r2);border:1px solid rgba(230,126,34,.3);background:rgba(230,126,34,.06)"><div style="font-size:12px;font-weight:600;color:var(--or)">🎁 Discount Applied: ₹${s.baseFee-s.netFee}</div><div style="font-size:11px;color:var(--tx2)">Full fee: ₹${s.baseFee} → Net: ₹${s.netFee} (${s.discount?.reason||''})</div></div>`;
    document.getElementById('cf-amt').value=s.netFee;
  } else {
    info.style.display='none';
    document.getElementById('cf-amt').value=s.netFee;
  }
  document.getElementById('cf-balance-note').style.display='none';
}
function cfCalcBalance(){
  const s=DB.students.find(x=>x.id===gv('cf-stu'));if(!s)return;
  const tot=s.netFee;const now=+gv('cf-amt')||0;const totalPaid=s.paidAmt+now;const bal=tot-totalPaid;
  const note=document.getElementById('cf-balance-note');
  if(now>0&&bal>0){note.style.display='block';note.innerHTML=`<div style="font-size:12px;font-weight:600;color:var(--or);margin-bottom:4px">⚡ Partial Payment</div><div style="font-size:11.5px">Net Fee: ₹${tot} | Paying now: ₹${now} | <strong style="color:var(--ro)">Balance: ₹${bal}</strong></div><div style="font-size:10.5px;color:var(--tx3);margin-top:3px">Status: <strong>Partial</strong></div>`;}
  else note.style.display='none';
}
function toggleSplit(){
  const m=gv('cf-mode');const isSplit=m==='split'||m==='split2';
  document.getElementById('payNormal').style.display=isSplit?'none':'grid';
  document.getElementById('paySplit').style.display=isSplit?'block':'none';
  if(isSplit)calcSplitRem();
}
function calcSplitRem(){
  const tot=+gv('cf-tot')||0;const a1=+gv('cf-a1')||0;const rem=Math.max(0,tot-a1);
  document.getElementById('cf-a2').value=rem;
  document.getElementById('splitNote').textContent=`Total: ₹${tot} | Mode 1: ₹${a1} | Mode 2: ₹${rem}`;
}
function collectFee(){
  const stuId=gv('cf-stu');if(!stuId)return toast('Select student','er');
  const s=DB.students.find(x=>x.id===stuId);if(!s)return;
  const mode=gv('cf-mode');const isSplit=mode==='split'||mode==='split2';
  let amt,modeStr;
  if(isSplit){const a1=+gv('cf-a1')||0;const a2=+gv('cf-a2')||0;amt=a1+a2;modeStr=`${gv('cf-m1')} ₹${a1} + ${gv('cf-m2')} ₹${a2}`;}
  else{amt=+gv('cf-amt')||0;modeStr=mode;}
  if(!amt)return toast('Enter amount','er');
  const prevPaid=s.paidAmt;s.paidAmt=Math.min(s.netFee,prevPaid+amt);
  if(s.paidAmt>=s.netFee){s.feeStatus='paid';s.paidOn=new Date().toISOString().slice(0,10);}
  else{s.feeStatus='partial';s.paidOn=new Date().toISOString().slice(0,10);}
  const bal=s.netFee-s.paidAmt;
  const invId='INV-'+String(DB.invoices.length+1).padStart(4,'0');
  DB.invoices.push({id:invId,studentId:stuId,type:'Monthly Fee',amount:amt,baseFee:s.baseFee,discount:s.baseFee-s.netFee,netFee:s.netFee,paidAmt:s.paidAmt,balance:bal,date:s.paidOn,month:gv('cf-mo'),mode:modeStr,status:s.feeStatus==='paid'?'paid':'partial'});
  addActivity('💳','rgba(58,125,94,.14)',`<strong>${s.fname}</strong> paid ₹${amt} via ${modeStr}${s.feeStatus==='partial'?` (₹${bal} pending)`:' (full)'}`);
  addNotif('success','Fee Collected',`${s.fname} paid ₹${amt}${s.feeStatus==='partial'?` partial, ₹${bal} due`:''}`);
  if(document.getElementById('cf-wa').checked){setTimeout(()=>waQuick(stuId,s.feeStatus==='paid'?'fee_receipt':'partial_payment'),600);}
  closeM('mCollectFee');toast(`₹${amt} collected${bal>0?` — ₹${bal} still pending`:''}!`,'ok');refreshAll();updateBadges();
}

// ═══ INVOICES ═══
function renderInv(){
  document.getElementById('invCount').textContent=`${DB.invoices.length} invoice(s)`;
  document.getElementById('gi-stu').innerHTML='<option value="">-- Select --</option>'+DB.students.map(s=>`<option value="${s.id}">${s.fname} ${s.lname}</option>`).join('');
  document.getElementById('invTable').innerHTML=DB.invoices.length?DB.invoices.map(inv=>{
    const s=DB.students.find(x=>x.id===inv.studentId);
    return `<tr><td><span style="font-family:var(--fm);font-weight:700;color:var(--ac)">${inv.id}</span></td>
    <td>${s?`<div class="si"><div class="sav" style="background:${s.color}">${s.fname[0]+s.lname[0]}</div><span>${s.fname} ${s.lname}</span></div>`:'—'}</td>
    <td><span class="tag tac" style="font-size:9px">${inv.type}</span></td>
    <td><span style="font-family:var(--fm)">₹${inv.baseFee||inv.amount}</span></td>
    <td>${inv.discount>0?`<span class="tag tor" style="font-size:9px">🎁 -₹${inv.discount}</span>`:'<span style="color:var(--tx3)">—</span>'}</td>
    <td><span style="font-family:var(--fm);font-weight:700">₹${inv.amount}</span></td>
    <td>${inv.balance>0?`<span class="fee-bal-badge">₹${inv.balance}</span>`:`<span style="color:var(--em);font-size:11px">✓</span>`}</td>
    <td><span style="font-family:var(--fm);font-size:10.5px">${fmtDate(inv.date)}</span></td>
    <td><span style="font-size:11px">${inv.mode}</span></td>
    <td><span class="tag ${inv.status==='paid'?'tpd':'tpart'}">${inv.status==='paid'?'● Paid':'◑ Partial'}</span></td>
    <td><button class="btn bg" style="font-size:10px;padding:3px 7px" onclick="printInv('${inv.id}')">🖨 Print</button></td></tr>`;
  }).join(''):'<tr><td colspan="11"><div class="empty"><div class="ei">🧾</div><div class="et">No invoices yet</div></div></td></tr>';
}
function genInvoice(){
  const stuId=gv('gi-stu'),amt=+gv('gi-am');if(!stuId||!amt)return toast('Fill required','er');
  const s=DB.students.find(x=>x.id===stuId);
  DB.invoices.push({id:'INV-'+String(DB.invoices.length+1).padStart(4,'0'),studentId:stuId,type:gv('gi-tp')==='fee'?'Monthly Fee':gv('gi-tp')==='fine'?'Book Fine':'Other',amount:amt,baseFee:s?.baseFee||amt,discount:s?(s.baseFee-s.netFee):0,netFee:s?.netFee||amt,paidAmt:amt,balance:0,date:new Date().toISOString().slice(0,10),month:gv('gi-mo'),mode:'Manual',status:'paid'});
  closeM('mGenInv');toast('Invoice generated!','ok');renderInv();
}
function autoFillInv(){const s=DB.students.find(x=>x.id===gv('gi-stu'));if(s)document.getElementById('gi-am').value=s.netFee;}
function printInv(invId){
  const inv=DB.invoices.find(x=>x.id===invId);const s=DB.students.find(x=>x.id===inv.studentId);const b=s?DB.batches.find(bt=>bt.id===s.batchId):null;
  const w=window.open('','_blank');
  w.document.write(`<html><head><title>Invoice ${inv.id}</title><style>body{font-family:sans-serif;padding:40px;color:#2c2825;max-width:600px;margin:auto}.hd{border-bottom:2px solid #4a7c6f;padding-bottom:16px;margin-bottom:20px}.logo{font-size:22px;font-weight:700;color:#4a7c6f}.row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #eee}.tot{font-weight:700;font-size:18px;color:#4a7c6f}.ok{background:rgba(58,125,94,.1);color:#3a7d5e;display:inline-block;padding:3px 10px;border-radius:20px;font-size:12px}.partial{background:rgba(58,122,176,.1);color:#3a7ab0;display:inline-block;padding:3px 10px;border-radius:20px;font-size:12px}.disc{background:rgba(230,126,34,.08);border:1px solid rgba(230,126,34,.2);border-radius:8px;padding:10px;margin:10px 0}</style></head><body>
  <div class="hd"><div class="logo">📚 ${DB.settings.name}</div><div style="font-size:12px;color:#888;margin-top:4px">${DB.settings.addr} · ${DB.settings.phone}</div></div>
  <div style="display:flex;justify-content:space-between;margin-bottom:20px"><div><div style="font-size:20px;font-weight:700">INVOICE</div><div style="font-size:13px;color:#888">${inv.id}</div></div><div style="text-align:right"><div style="font-size:13px">${fmtDate(inv.date)}</div><div class="${inv.status==='paid'?'ok':'partial'}">${inv.status==='paid'?'✓ Paid':'◑ Partial'}</div></div></div>
  <div style="background:#f9f7f4;padding:14px;border-radius:8px;margin-bottom:16px"><div style="font-weight:600;margin-bottom:6px">Student</div><div style="font-size:13px">${s?.fname} ${s?.lname} · ${s?.id}</div><div style="font-size:12px;color:#888">${b?.name||''} · ${s?.seatType?.toUpperCase()} Seat ${s?.seat||''}</div></div>
  <div class="row"><span>Description</span><span>Amount</span></div>
  <div class="row"><span>${inv.type} – ${inv.month}</span><span>₹${inv.baseFee||inv.amount}</span></div>
  ${inv.discount>0?`<div class="disc"><strong>🎁 Discount Applied: -₹${inv.discount}</strong><br><span style="font-size:12px;color:#888">${s?.discount?.reason||''}</span></div>`:''}
  ${inv.discount>0?`<div class="row"><span>Net Fee (after discount)</span><span style="color:#3a7d5e;font-weight:700">₹${inv.netFee||inv.amount}</span></div>`:''}
  <div class="row"><span>Amount Paid Now</span><span>₹${inv.amount}</span></div>
  <div class="row tot"><span>Total Paid</span><span>₹${inv.paidAmt||inv.amount}</span></div>
  ${inv.balance>0?`<div style="background:rgba(192,68,79,.08);border:1px solid rgba(192,68,79,.2);border-radius:8px;padding:12px;margin-top:12px"><strong>⚠ Balance Due: ₹${inv.balance}</strong><br><span style="font-size:12px">Please clear by due date</span></div>`:''}
  <div style="margin-top:16px;font-size:12px;color:#888">Mode: ${inv.mode}</div>
  <div style="margin-top:40px;text-align:center;font-size:11px;color:#aaa">Thank you · OPTMS Tech ERP</div>
  <script>window.print();<\/script></body></html>`);
}

// ═══ EXPENSES ═══
function renderExp(){
  const cf=document.getElementById('exCatF')?.value||'all';
  const list=DB.expenses.filter(e=>cf==='all'||e.category===cf);
  const rev=DB.students.filter(x=>x.feeStatus==='paid').reduce((a,x)=>a+x.netFee,0)+DB.students.filter(x=>x.feeStatus==='partial').reduce((a,x)=>a+x.paidAmt,0);
  const allExp=DB.expenses.reduce((a,e)=>a+e.amount,0);
  document.getElementById('ex-t').textContent=fmt(list.reduce((a,e)=>a+e.amount,0));
  document.getElementById('ex-r').textContent=fmt(rev);
  const p=rev-allExp;document.getElementById('ex-p').textContent=fmt(Math.abs(p));document.getElementById('ex-p').style.color=p>=0?'var(--em)':'var(--ro)';
  document.getElementById('expList').innerHTML=list.map(e=>`<div class="ei2"><div class="eic" style="background:rgba(74,124,111,.1)">${e.emoji}</div><div style="flex:1"><div class="en2">${e.name}</div><div class="ed">${fmtDate(e.date)} · ${e.category}</div></div><div class="ea ea-d">-₹${e.amount.toLocaleString()}</div><button class="btn bd" style="font-size:10px;padding:3px 6px;margin-left:7px" onclick="delExp('${e.id}')">✕</button></div>`).join('')||'<div class="empty"><div class="ei">💸</div><div class="et">No expenses</div></div>';
}
function delExp(id){DB.expenses=DB.expenses.filter(e=>e.id!==id);toast('Removed','wn');renderExp();}
function addExp(){
  const nm=gv('ex-nm'),am=+gv('ex-am');if(!nm||!am)return toast('Fill required','er');
  const emjs={Utilities:'⚡',Staff:'👨‍💼',Maintenance:'🔧',Supplies:'📦',Books:'📚',Other:'💰'};
  DB.expenses.push({id:'EX-'+Date.now(),name:nm,amount:am,category:gv('ex-ca'),date:new Date().toISOString().slice(0,10),notes:gv('ex-nt'),emoji:emjs[gv('ex-ca')]||'💰'});
  addActivity('💸','rgba(196,125,43,.14)',`Expense: <strong>${nm}</strong> ₹${am.toLocaleString()}`);
  closeM('mExpense');toast('Expense added!','ok');renderExp();
}

// ═══ ANALYTICS ═══
function renderAnal(){
  const s=DB.students;const paid=s.filter(x=>x.feeStatus==='paid');
  const rev=paid.reduce((a,x)=>a+x.netFee,0)+s.filter(x=>x.feeStatus==='partial').reduce((a,x)=>a+x.paidAmt,0);
  const iss=DB.transactions.filter(t=>t.status!=='returned');const od=DB.transactions.filter(t=>t.status==='overdue');
  const bks=DB.books.reduce((a,b)=>a+b.copies,0);const prs=Object.values(DB.attendance).filter(v=>v==='present').length;
  document.getElementById('analCards').innerHTML=`
    <div class="panel"><div class="pb"><div class="s-lb">Revenue YTD</div><div class="s-vl" style="color:var(--em)">${fmt(rev*3)}</div></div></div>
    <div class="panel"><div class="pb"><div class="s-lb">Fee Collection Rate</div><div class="s-vl">${s.length?Math.round(paid.length/s.length*100):0}%</div></div></div>
    <div class="panel"><div class="pb"><div class="s-lb">Avg Attendance</div><div class="s-vl">${s.length?Math.round(prs/s.length*100):0}%</div></div></div>
    <div class="panel"><div class="pb"><div class="s-lb">Book Utilization</div><div class="s-vl">${bks?Math.round(iss.length/bks*100):0}%</div></div></div>
    <div class="panel"><div class="pb"><div class="s-lb">Overdue Rate</div><div class="s-vl" style="color:var(--ro)">${iss.length?Math.round(od.length/iss.length*100):0}%</div></div></div>
    <div class="panel"><div class="pb"><div class="s-lb">Net Profit</div><div class="s-vl" style="color:var(--ac)">${fmt(rev-DB.expenses.reduce((a,e)=>a+e.amount,0))}</div></div></div>`;
  const rData=[155000,162000,rev,rev*0.5];const rMax=Math.max(...rData,1);
  document.getElementById('revChart').innerHTML=rData.map((v,i)=>`<div class="cbar" style="flex:1;height:${Math.round(v/rMax*100)}%;background:var(--ac);opacity:${i===3?.4:.8};border-radius:4px 4px 0 0"><div class="tt">₹${v.toLocaleString()}</div></div>`).join('');
  document.getElementById('batchAnal').innerHTML=DB.batches.map(b=>{const p=Math.round(b.occupied/b.total*100);const fc=p>=80?'sf-r':p>=50?'sf-y':'sf-g';return`<div style="margin-bottom:10px"><div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:3px"><span>${b.name}</span><span style="font-family:var(--fm)">${b.occupied}/${b.total} (${p}%)</span></div><div class="sbar"><div class="sfill ${fc}" style="width:${p}%"></div></div></div>`;}).join('');
}

// ═══ REPORTS ═══
function genReport(type){
  const titles={monthly:'Monthly Summary',fee:'Fee Report',books:'Book Inventory',attendance:'Attendance',expense:'Expense Report',student:'Student Directory'};
  document.getElementById('rptTitle').textContent=titles[type];
  const s=DB.students;const paid=s.filter(x=>x.feeStatus==='paid');
  let html='';
  if(type==='monthly'){const rev=paid.reduce((a,x)=>a+x.netFee,0);const exp=DB.expenses.reduce((a,e)=>a+e.amount,0);html=`<div class="g3" style="margin-bottom:16px"><div class="sc" style="--ca:var(--em)"><div class="s-lb">Revenue</div><div class="s-vl" style="color:var(--em)">${fmt(rev)}</div></div><div class="sc" style="--ca:var(--ro)"><div class="s-lb">Expenses</div><div class="s-vl" style="color:var(--ro)">${fmt(exp)}</div></div><div class="sc" style="--ca:var(--ac)"><div class="s-lb">Profit</div><div class="s-vl">${fmt(rev-exp)}</div></div></div>`;}
  else{
    const map={fee:['Student','Batch','Base Fee','Discount','Net Fee','Paid','Balance','Status'],student:['ID','Name','Batch','Seat','Type','Course'],attendance:['Student','Batch','Status'],expense:['Name','Category','Amount','Date'],books:['Book','Author','Category','Available']};
    const rows={fee:s.map(x=>[`${x.fname} ${x.lname}`,batchName(x.batchId),`₹${x.baseFee}`,x.baseFee>x.netFee?`₹${x.baseFee-x.netFee} (${x.discount?.reason||''})`:'-',`₹${x.netFee}`,`₹${x.paidAmt}`,`₹${x.netFee-x.paidAmt}`,x.feeStatus]),student:s.map(x=>[x.id,`${x.fname} ${x.lname}`,batchName(x.batchId),x.seat||'—',x.seatType.toUpperCase(),x.course]),attendance:s.map(x=>[`${x.fname} ${x.lname}`,batchName(x.batchId),DB.attendance[x.id]||'absent']),expense:DB.expenses.map(e=>[e.name,e.category,`₹${e.amount.toLocaleString()}`,e.date]),books:DB.books.map(b=>[b.title,b.author,b.category,`${b.available}/${b.copies}`])};
    html=`<table style="width:100%;border-collapse:collapse;font-size:12px"><thead><tr>${map[type].map(c=>`<th style="padding:7px 11px;text-align:left;background:var(--sf2);color:var(--tx3);font-size:9px;text-transform:uppercase;font-family:var(--fm);border-bottom:1px solid var(--br)">${c}</th>`).join('')}</tr></thead><tbody>${rows[type].map(r=>`<tr style="border-bottom:1px solid var(--br)">${r.map(c=>`<td style="padding:7px 11px">${c}</td>`).join('')}</tr>`).join('')}</tbody></table>`;
  }
  document.getElementById('rptBody').innerHTML=html;document.getElementById('rptOut').style.display='block';document.getElementById('rptOut').scrollIntoView({behavior:'smooth'});toast('Report generated!','ok');
}

// ═══ WHATSAPP ═══
const WA_TEMPLATES={
  welcome:(s,b)=>`🎉 *Welcome to ${DB.settings.name}!*\n\nDear *${s.fname} ${s.lname}*,\n\nWe're delighted to have you! 🎓\n\n📋 *Your Details:*\n• Student ID: ${s.id}\n• Batch: ${b?.name||'—'} (${b?fmtT(b.startTime)+' – '+fmtT(b.endTime):''})\n• Seat: ${s.seat||'To be assigned'} (${s.seatType.toUpperCase()})\n• Net Monthly Fee: ₹${s.netFee}${s.baseFee>s.netFee?`\n• 🎁 Discount Applied: ₹${s.baseFee-s.netFee} (${s.discount?.reason||''})`:''}\n\n🏫 ${DB.settings.name}\n📍 ${DB.settings.addr}\n📞 ${DB.settings.phone}\n\nBest wishes for your studies! 📚`,
  fee_due:(s,b)=>`⏰ *Fee Payment Reminder*\n\nDear *${s.fname} ${s.lname}*,\n\nYour monthly fee is due.\n\n💰 *Fee Details:*\n• Net Fee: ₹${s.netFee}${s.baseFee>s.netFee?`\n• 🎁 Discount: ₹${s.baseFee-s.netFee} (${s.discount?.reason||''})`:''}\n• Amount Due: ₹${s.netFee-s.paidAmt}\n• Due Date: ${fmtDate(s.dueDate)}\n• Batch: ${b?.name||'—'}\n\nPlease pay to avoid late charges.\n\n📞 ${DB.settings.phone}`,
  fee_overdue:(s,b)=>`🚨 *URGENT: Fee Overdue*\n\nDear *${s.fname} ${s.lname}*,\n\nYour fee is *OVERDUE* since ${fmtDate(s.dueDate)}. Your seat may be de-allocated.\n\n⚠️ *Details:*\n• Net Fee: ₹${s.netFee}\n• Overdue Amount: ₹${s.netFee-s.paidAmt}\n• Late Fine: ₹${DB.settings.fine}/day (accumulating)\n• Batch: ${b?.name||'—'} · Seat: ${s.seat||'—'}\n\n❗ Clear *immediately* to retain your seat.\n\n📞 ${DB.settings.phone}\n🏫 ${DB.settings.name}`,
  partial_payment:(s,b)=>`💳 *Partial Payment Received*\n\nDear *${s.fname} ${s.lname}*,\n\nThank you for your partial payment!\n\n📊 *Payment Summary:*\n• Net Fee: ₹${s.netFee}\n• Amount Paid: ₹${s.paidAmt}\n• *Balance Due: ₹${s.netFee-s.paidAmt}*\n• Due Date: ${fmtDate(s.dueDate)}\n\nPlease pay ₹${s.netFee-s.paidAmt} at the earliest.\n\n📞 ${DB.settings.phone}`,
  fee_receipt:(s,b,inv)=>`✅ *Payment Receipt*\n\nDear *${s.fname} ${s.lname}*,\n\nFee payment confirmed! 🙏\n\n🧾 *Receipt:*\n• Receipt No: ${inv?.id||'—'}\n• Amount Paid: ₹${s.paidAmt}\n• Net Fee: ₹${s.netFee}${s.baseFee>s.netFee?`\n• 🎁 Discount: ₹${s.baseFee-s.netFee}`:''}\n• Date: ${fmtDate(s.paidOn)}\n• Batch: ${b?.name||'—'}\n\n✅ *Fee FULLY PAID*\n\n📞 ${DB.settings.phone}`,
  discount_applied:(s,b)=>`🎁 *Discount Applied to Your Fee*\n\nDear *${s.fname} ${s.lname}*,\n\nA discount has been applied to your fee account.\n\n💰 *Updated Fee Structure:*\n• Original Fee: ₹${s.baseFee}\n• Discount: -₹${s.baseFee-s.netFee} (${s.discount?.reason||'Special Discount'})\n• *Net Fee: ₹${s.netFee}/month*\n• Batch: ${b?.name||'—'}\n\nThank you for being a valued student! 🌟\n\n📞 ${DB.settings.phone}`,
  book_due:(s,b,tx)=>`📚 *Book Return Reminder*\n\nDear *${s.fname} ${s.lname}*,\n\n• Book: ${tx?.bookTitle||'Borrowed book'}\n• Due: ${tx?.dueDate||'—'}\n• Fine: ₹${DB.settings.fine}/day if late\n\nPlease return on time!\n\n📞 ${DB.settings.phone}`,
  book_overdue:(s,b,tx)=>`⚠️ *Book Overdue – Fine Accruing*\n\nDear *${s.fname} ${s.lname}*,\n\n• Book: ${tx?.bookTitle||'—'}\n• Due: ${tx?.dueDate||'—'}\n• Fine: ₹${tx?.fine||0} (₹${DB.settings.fine}/day)\n\nReturn *immediately* to stop fines.\n\n📞 ${DB.settings.phone}`,
  seat_allotted:(s,b)=>`🪑 *Seat Allotment Confirmed*\n\nDear *${s.fname} ${s.lname}*,\n\n• Seat: ${s.seat||'—'}\n• Batch: ${b?.name||'—'} (${b?fmtT(b.startTime)+' – '+fmtT(b.endTime):''})\n• Type: ${s.seatType.toUpperCase()}\n\nCarry your ID card while visiting.\n\n📞 ${DB.settings.phone}`,
  holiday:(s)=>`📅 *Holiday Notice*\n\nDear *${s.fname} ${s.lname}*,\n\nThe library will be *CLOSED* on the upcoming holiday. Normal operations resume next day.\n\n📞 ${DB.settings.phone}\n🏫 ${DB.settings.name}`,
  custom:(s)=>`Dear *${s.fname} ${s.lname}*,\n\n[Your message here]\n\n🏫 ${DB.settings.name}\n📞 ${DB.settings.phone}`
};

function renderWA(){
  const templates=[
    {key:'welcome',ic:'🎉',lb:'Welcome',ds:'Enrollment confirmation'},
    {key:'fee_due',ic:'💰',lb:'Fee Due',ds:'Gentle reminder'},
    {key:'fee_overdue',ic:'🚨',lb:'Overdue Alert',ds:'Urgent overdue'},
    {key:'partial_payment',ic:'💳',lb:'Partial Payment',ds:'Balance due notice'},
    {key:'fee_receipt',ic:'✅',lb:'Fee Receipt',ds:'Payment confirmed'},
    {key:'discount_applied',ic:'🎁',lb:'Discount Applied',ds:'Discount notification'},
    {key:'book_due',ic:'📚',lb:'Book Return Due',ds:'Return reminder'},
    {key:'book_overdue',ic:'⚠️',lb:'Book Overdue',ds:'Fine accruing alert'},
    {key:'seat_allotted',ic:'🪑',lb:'Seat Allotment',ds:'Seat confirmation'},
    {key:'holiday',ic:'📅',lb:'Holiday Notice',ds:'Library closure'},
    {key:'custom',ic:'✏️',lb:'Custom',ds:'Write your own'},
  ];
  document.getElementById('waTemplateGrid').innerHTML=templates.map(t=>`<div class="wa-tpl" onclick="waSelectTpl('${t.key}')"><div class="wt-ic">${t.ic}</div><div class="wt-lb">${t.lb}</div><div class="wt-ds">${t.ds}</div></div>`).join('');
  const pending=DB.students.filter(x=>x.feeStatus!=='paid').length;
  const overdue=DB.students.filter(x=>x.feeStatus==='overdue').length;
  const newStudents=DB.students.filter(s=>s.joinDate&&s.joinDate.includes('Mar')).length;
  const bookOd=DB.transactions.filter(t=>t.status==='overdue').length;
  document.getElementById('bk-welcome').textContent=newStudents;
  document.getElementById('bk-pending').textContent=pending;
  document.getElementById('bk-overdue2').textContent=overdue;
  document.getElementById('bk-bookod').textContent=bookOd;
  // populate student select
  document.getElementById('wa-stu').innerHTML='<option value="">-- Select Student --</option><option value="all">📢 All Students</option><option value="pending_all">⏳ All Pending + Partial</option><option value="overdue">🚨 All Overdue</option>'+DB.students.map(s=>`<option value="${s.id}">${s.fname} ${s.lname} (${s.feeStatus})</option>`).join('');
  renderWASendLog();
}
function waSelectTpl(key){document.getElementById('wa-tpl').value=key;waLoadTemplate();}
function waLoadTemplate(){
  const tplKey=gv('wa-tpl');const stuId=gv('wa-stu');
  let s=DB.students[0];let b=DB.batches.find(x=>x.id===s?.batchId);
  if(stuId&&!['all','pending_all','overdue'].includes(stuId)){s=DB.students.find(x=>x.id===stuId)||s;b=DB.batches.find(x=>x.id===s.batchId);}
  if(!tplKey||tplKey==='custom'){document.getElementById('wa-msg').value='';document.getElementById('waPreview').textContent='Type your message…';return;}
  const lastInv=DB.invoices.filter(x=>x.studentId===s?.id).pop();
  const lastTx=DB.transactions.filter(t=>t.studentId===s?.id&&t.status!=='returned').pop();
  const txData=lastTx?{...lastTx,bookTitle:DB.books.find(bk=>bk.id===lastTx.bookId)?.title||'—'}:null;
  const msg=WA_TEMPLATES[tplKey]?WA_TEMPLATES[tplKey](s,b,lastInv||txData):'';
  document.getElementById('wa-msg').value=msg;waUpdatePreview();
}
function waUpdatePreview(){document.getElementById('waPreview').textContent=gv('wa-msg')||'Select a template…';}
async function waSend(){
  const stuId=gv('wa-stu');const msg=gv('wa-msg');
  if(!msg)return toast('Write a message first','er');if(!stuId)return toast('Select a recipient','er');
  if(['all','pending_all','overdue'].includes(stuId)){
    const list=stuId==='all'?DB.students:stuId==='pending_all'?DB.students.filter(x=>x.feeStatus!=='paid'):DB.students.filter(x=>x.feeStatus==='overdue');
    if(!list.length)return toast('No students in this group','wn');
    toast('Sending to '+list.length+' students...','wn');
    const messages=list.map(s=>({to:s.phone,message:msg,name:s.fname+' '+s.lname}));
    const r=await waFetch('send_bulk',{messages});
    if(r.success){waSessionMsgCount+=r.sent||0;toast('Sent to '+r.sent+' students!','wa');}
    else{toast('Error: '+r.error,'er');}
    DB.waSendLog.push({time:new Date().toLocaleTimeString(),to:list.length+' students',preview:msg.slice(0,40)+'...',type:'bulk'});
  } else {
    const s=DB.students.find(x=>x.id===stuId);
    if(!s)return;
    await waSendDirect(s.phone,msg,s.fname+' '+s.lname);
    DB.waSendLog.push({time:new Date().toLocaleTimeString(),to:s.fname+' '+s.lname,preview:msg.slice(0,40)+'...',type:'single'});
  }
  addActivity('💬','rgba(37,211,102,.14)','WhatsApp sent');renderWASendLog();
}
function waCopy(){const msg=gv('wa-msg');if(!msg)return;navigator.clipboard?.writeText(msg).then(()=>toast('Copied!','ok')).catch(()=>toast('Select & copy manually','wn'));}
function waSchedule(){toast('Message scheduled for 9 AM tomorrow!','ok');}
async function openWALink(phone,msg,name){
  await waSendDirect(phone,msg,name||phone);
}
function openWALinkFallback(phone,msg){const p=phone.replace(/[^0-9]/g,'');const full=p.length===10?'91'+p:p;window.open('https://wa.me/'+full+'?text='+encodeURIComponent(msg),'_blank');}
function waQuick(stuId,tplKey){
  const s=DB.students.find(x=>x.id===stuId);if(!s)return;
  const b=DB.batches.find(x=>x.id===s.batchId);
  const lastInv=DB.invoices.filter(x=>x.studentId===s.id).pop();
  const lastTx=DB.transactions.filter(t=>t.studentId===s.id&&t.status!=='returned').pop();
  const txData=lastTx?{...lastTx,bookTitle:DB.books.find(bk=>bk.id===lastTx.bookId)?.title||'—'}:null;
  const msg=WA_TEMPLATES[tplKey]?WA_TEMPLATES[tplKey](s,b,lastInv||txData):'Hello '+s.fname;
  document.getElementById('waSendTo').textContent=`${s.fname} ${s.lname}`;
  document.getElementById('waSendPhone').textContent=`+91 ${s.phone}`;
  document.getElementById('waSendMsg').value=msg;
  document.getElementById('waSendPreview').textContent=msg;
  document.getElementById('waOpenBtn').onclick=()=>{waSendDirect(s.phone,msg,s.fname+' '+s.lname).then(()=>{DB.waSendLog.push({time:new Date().toLocaleTimeString(),to:s.fname+' '+s.lname,preview:msg.slice(0,40)+'...',type:'single'});if(document.getElementById('page-whatsapp').classList.contains('active'))renderWASendLog();});closeM('mWaSend');};
  openM('mWaSend');
}
function bulkSend(type){
  let list=[],tpl='';
  if(type==='welcome'){list=DB.students.filter(s=>s.joinDate&&s.joinDate.includes('Mar'));tpl='welcome';}
  else if(type==='pending'){list=DB.students.filter(x=>x.feeStatus!=='paid');tpl='fee_due';}
  else if(type==='overdue'){list=DB.students.filter(x=>x.feeStatus==='overdue');tpl='fee_overdue';}
  else if(type==='bookoverdue'){const txIds=DB.transactions.filter(t=>t.status==='overdue').map(t=>t.studentId);list=DB.students.filter(s=>txIds.includes(s.id));tpl='book_overdue';}
  if(!list.length){toast('No students in this category','wn');return;}
  const bulkMsgs=list.map(s=>{const b=DB.batches.find(x=>x.id===s.batchId);const lastTx=DB.transactions.filter(t=>t.studentId===s.id&&t.status!=='returned').pop();const txData=lastTx?{...lastTx,bookTitle:DB.books.find(bk=>bk.id===lastTx.bookId)?.title||'—'}:null;const msg=WA_TEMPLATES[tpl]?WA_TEMPLATES[tpl](s,b,txData):'';return{to:s.phone,message:msg,name:s.fname+' '+s.lname};});
  toast('Sending to '+list.length+' students...','wn');
  waFetch('send_bulk',{messages:bulkMsgs}).then(r=>{
    if(r.success){waSessionMsgCount+=r.sent||0;toast('Sent to '+r.sent+' students!','wa');}
    else toast('Error: '+r.error,'er');
  });
  DB.waSendLog.push({time:new Date().toLocaleTimeString(),to:`${list.length} students (${type})`,preview:`Bulk: ${tpl}`,type:'bulk'});
  addActivity('💬','rgba(37,211,102,.14)',`Bulk WA sent to <strong>${list.length}</strong> (${type})`);
  toast(`WhatsApp opened for ${list.length} students!`,'wa');
  if(document.getElementById('page-whatsapp').classList.contains('active'))renderWASendLog();
}
function waCopyModal(){const msg=gv('waSendMsg');navigator.clipboard?.writeText(msg).then(()=>toast('Copied!','ok'));}
function renderWASendLog(){const el=document.getElementById('waSendLog');if(!el)return;el.innerHTML=DB.waSendLog.slice(-8).reverse().map(l=>`<div style="display:flex;align-items:center;gap:7px;padding:5px 8px;background:var(--sf2);border-radius:var(--r2);font-size:11px"><span style="color:var(--wa2);font-weight:600;flex-shrink:0">${l.time}</span><span style="color:var(--tx2);flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${l.to}</span><span class="tag twa" style="font-size:9px">${l.type}</span></div>`).join('')||'<div style="font-size:11px;color:var(--tx3);text-align:center;padding:10px">No messages sent yet</div>';}

// ═══ STAFF ═══
const PERMS=[{key:'students',label:'Manage Students'},{key:'fees',label:'Fee Management'},{key:'books',label:'Books & Transactions'},{key:'expenses',label:'Expenses'},{key:'reports',label:'Reports'},{key:'staff',label:'Staff Mgmt'},{key:'settings',label:'Settings'}];
const ROLE_PERMS={admin:{students:true,fees:true,books:true,expenses:true,reports:true,staff:true,settings:true},librarian:{students:true,fees:false,books:true,expenses:false,reports:true,staff:false,settings:false},accountant:{students:false,fees:true,books:false,expenses:true,reports:true,staff:false,settings:false},receptionist:{students:true,fees:false,books:false,expenses:false,reports:false,staff:false,settings:false}};
function renderStaff(){
  document.getElementById('staffCount').textContent=`${DB.staff.length} staff`;
  document.getElementById('staffTable').innerHTML=DB.staff.map((sf,i)=>{const pc=Object.values(sf.perms).filter(Boolean).length;
    return `<tr><td><div class="si"><div class="sav" style="background:linear-gradient(135deg,var(--ac),var(--vi))">${sf.name.split(' ').map(n=>n[0]).join('').slice(0,2)}</div><div><div style="font-weight:600;font-size:12.5px">${sf.name}</div><div style="font-size:10px;color:var(--tx3);font-family:var(--fm)">${sf.id}</div></div></div></td>
    <td><span class="tag tac" style="text-transform:capitalize">${sf.role}</span></td><td>${sf.email}</td><td>${sf.phone}</td>
    <td><span style="font-family:var(--fm);font-size:11px">${pc}/7 perms</span></td><td><span class="tag tpd">Active</span></td>
    <td><div style="display:flex;gap:4px"><button class="btn bg" style="font-size:10px;padding:3px 7px" onclick="editStaff(${i})">✏</button>${i>0?`<button class="btn bd" style="font-size:10px;padding:3px 6px" onclick="delStaff(${i})">✕</button>`:''}</div></td></tr>`;
  }).join('')||'<tr><td colspan="7"><div class="empty"><div class="ei">👥</div><div class="et">No staff</div></div></td></tr>';
}
function buildPermList(){const role=gv('sf-rl')||'librarian';const d=ROLE_PERMS[role];document.getElementById('permList').innerHTML=PERMS.map(p=>`<div class="perm-row"><div><div style="font-size:13px;font-weight:500">${p.label}</div></div><label class="toggle-wrap"><input type="checkbox" id="perm-${p.key}" class="toggle-inp" ${d[p.key]?'checked':''}><span class="toggle-sl"></span></label></div>`).join('');}
function setDefaultPerms(){buildPermList();}
function editStaff(idx){editStaffIdx=idx;const sf=DB.staff[idx];document.getElementById('staffModalTitle').textContent='✏ Edit Staff';document.getElementById('staffSaveBtn').textContent='Save';document.getElementById('sf-nm').value=sf.name;document.getElementById('sf-rl').value=sf.role;document.getElementById('sf-em').value=sf.email;document.getElementById('sf-ph').value=sf.phone;document.getElementById('sf-un').value=sf.username;buildPermList();PERMS.forEach(p=>{const el=document.getElementById('perm-'+p.key);if(el)el.checked=sf.perms[p.key];});openM('mAddStaff');}
function delStaff(idx){if(!confirm('Remove?'))return;DB.staff.splice(idx,1);toast('Removed','wn');renderStaff();}
function saveStaff(){const nm=gv('sf-nm'),rl=gv('sf-rl'),em=gv('sf-em');if(!nm||!rl||!em)return toast('Fill required','er');const perms={};PERMS.forEach(p=>{const el=document.getElementById('perm-'+p.key);perms[p.key]=el?el.checked:false;});if(editStaffIdx>=0){Object.assign(DB.staff[editStaffIdx],{name:nm,role:rl,email:em,phone:gv('sf-ph'),username:gv('sf-un'),perms});toast(`${nm} updated!`,'ok');editStaffIdx=-1;}else{DB.staff.push({id:'SF-'+Date.now(),name:nm,role:rl,email:em,phone:gv('sf-ph'),username:gv('sf-un')||nm.split(' ')[0].toLowerCase(),perms,status:'active'});toast(`${nm} added!`,'ok');}closeM('mAddStaff');document.getElementById('staffModalTitle').textContent='Add Staff';document.getElementById('staffSaveBtn').textContent='Add Staff';editStaffIdx=-1;renderStaff();}

// ═══ NOTIFICATIONS ═══
function renderNotifs(){
  document.getElementById('notifCount').textContent=`${DB.notifications.length} notifications`;
  const ic={warning:'⚠️',info:'ℹ️',success:'✅',error:'🚨'};const bg={warning:'rgba(196,125,43,.1)',info:'rgba(74,124,111,.1)',success:'rgba(58,125,94,.1)',error:'rgba(192,68,79,.1)'};
  document.getElementById('notifList').innerHTML=DB.notifications.map(n=>`<div style="display:flex;gap:11px;padding:12px;background:${n.read?'transparent':'rgba(74,124,111,.04)'};border:1px solid ${n.read?'var(--br)':'rgba(74,124,111,.2)'};border-radius:var(--r2)">
    <div style="width:32px;height:32px;border-radius:9px;background:${bg[n.type]};display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0">${ic[n.type]}</div>
    <div style="flex:1"><div style="font-size:12.5px;font-weight:600;margin-bottom:2px">${n.title}</div><div style="font-size:11.5px;color:var(--tx2)">${n.msg}</div><div style="font-size:10px;color:var(--tx3);font-family:var(--fm);margin-top:3px">${n.time}</div></div>
    <div style="display:flex;gap:5px;align-items:flex-start">${!n.read?`<button class="btn bg" style="font-size:10px;padding:2px 7px" onclick="markRead(${n.id})">Read</button>`:''}<button class="btn bg" style="font-size:10px;padding:2px 6px" onclick="delNotif(${n.id})">✕</button></div>
  </div>`).join('')||'<div class="empty"><div class="ei">🔔</div><div class="et">No notifications</div></div>';
}
function markRead(id){const n=DB.notifications.find(x=>x.id===id);if(n)n.read=true;renderNotifs();updateBadges();}
function delNotif(id){DB.notifications=DB.notifications.filter(x=>x.id!==id);renderNotifs();updateBadges();}
function clearNotifs(){DB.notifications=[];renderNotifs();updateBadges();toast('Cleared','ok');}

// ═══ SETTINGS ═══
function renderSettings(){
  const s=DB.students;
  const data=[{l:'Total Students',v:s.length},{l:'Discounts Given',v:`${s.filter(x=>x.baseFee>x.netFee).length} students (₹${s.reduce((a,x)=>a+(x.baseFee-x.netFee),0).toLocaleString()})`},{l:'Total Books',v:DB.books.reduce((a,b)=>a+b.copies,0)},{l:'Active Transactions',v:DB.transactions.filter(t=>t.status!=='returned').length},{l:'Total Batches',v:DB.batches.length},{l:'Staff Members',v:DB.staff.length},{l:'Net Profit',v:fmt(s.filter(x=>x.feeStatus==='paid').reduce((a,x)=>a+x.netFee,0)-DB.expenses.reduce((a,e)=>a+e.amount,0))}];
  document.getElementById('setStats').innerHTML=data.map(d=>`<div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--br)"><span style="font-size:12px;color:var(--tx2)">${d.l}</span><span style="font-weight:700;font-family:var(--fm)">${d.v}</span></div>`).join('');
}
function saveSettings(){DB.settings.fine=+gv('s-fine');DB.settings.days=+gv('s-days');DB.settings.waNum=gv('s-wa');DB.settings.name=gv('s-name');toast('Settings saved!','ok');}

// ═══ MODALS ═══
function openM(id){
  const pre={
    mCollectFee:populateModal_cf,
    mIssueBook:()=>populateIssueModal(null),
    mReturnBook:populateReturnModal,
    mAddStaff:buildPermList,
    mGenInv:()=>{document.getElementById('gi-stu').innerHTML='<option value="">-- Select --</option>'+DB.students.map(s=>`<option value="${s.id}">${s.fname} ${s.lname}</option>`).join('');},
    mAllocSeat:()=>{document.getElementById('as-stu').innerHTML='<option value="">-- Select --</option>'+DB.students.map(s=>`<option value="${s.id}">${s.fname} ${s.lname}</option>`).join('');document.getElementById('as-bt').innerHTML='<option value="">-- Select --</option>'+DB.batches.map(b=>`<option value="${b.id}">${b.name}</option>`).join('');},
    mWaQR:()=>{setTimeout(initWaQR,100);},
    mEnroll:()=>{document.getElementById('en-dt').value=new Date().toISOString().split('T')[0];populateBatchSelects();}
  };
  if(pre[id])pre[id]();
  document.getElementById(id)?.classList.add('open');
}
function closeM(id){document.getElementById(id)?.classList.remove('open');}
document.querySelectorAll('.mo').forEach(el=>el.addEventListener('click',e=>{if(e.target===el)el.classList.remove('open');}));

// ═══ UTILITIES ═══
function gv(id){const el=document.getElementById(id);return el?el.value.trim():'';}
function fmt(n){if(n>=100000)return'₹'+(n/100000).toFixed(1)+'L';if(n>=1000)return'₹'+n.toLocaleString();return'₹'+n;}
// Format a date string (YYYY-MM-DD or any parseable) to "28 Mar 2026"; returns '—' for null/empty/0000
function fmtDate(v){if(!v||v==='-'||v.startsWith('0000'))return'—';const d=new Date(v);if(isNaN(d))return v;return d.toLocaleDateString('en-IN',{day:'numeric',month:'short',year:'numeric'});}
function batchName(id){const b=DB.batches.find(x=>x.id===id);return b?b.name:'Unknown';}
function bTag(bId){const b=DB.batches.find(x=>x.id===bId);if(!b)return'<span class="tag">—</span>';const cls=b.name.includes('Morning')||b.name.includes('Early')?'tpn':b.name.includes('Evening')?'tis':b.name.includes('Night')?'tac':'tav';return`<span class="tag ${cls}">${batchEmoji(b.name)} ${b.name}</span>`;}
function batchEmoji(n){const m={'Early Morning':'🌅','Morning':'☀️','Afternoon':'🌤','Evening':'🌆','Night':'🌙','Late Night':'🌃'};return m[n]||'📚';}
function addActivity(icon,bg,text){DB.activities.unshift({icon,bg,text,time:'Just now'});if(DB.activities.length>20)DB.activities.pop();}
function addNotif(type,title,msg){DB.notifications.unshift({id:Date.now(),type,title,msg,time:'Just now',read:false});}
function updateBadges(){
  const pf=DB.students.filter(x=>x.feeStatus!=='paid').length;
  const od=DB.transactions.filter(t=>t.status==='overdue').length;
  const un=DB.notifications.filter(n=>!n.read).length;
  const ab=Object.values(DB.attendance).filter(v=>v==='absent').length;
  document.getElementById('b-fee').textContent=pf;document.getElementById('b-overdue').textContent=od;
  document.getElementById('b-notif').textContent=un;document.getElementById('b-absent').textContent=ab;
  document.getElementById('notifDot').style.display=un>0?'block':'none';
}
function refreshAll(){updateBadges();const active=document.querySelector('.page.active');if(active){const id=active.id.replace('page-','');renderPage(id);}else renderDash();}
function globalSearch(v){if(!v.trim())return;const s=DB.students.find(x=>`${x.fname} ${x.lname} ${x.id}`.toLowerCase().includes(v.toLowerCase()));const bk=DB.books.find(x=>`${x.title} ${x.author}`.toLowerCase().includes(v.toLowerCase()));if(s){navTo('students');document.getElementById('stuSrchInp').value=v;stuSrch(v);}else if(bk){navTo('books');bkSrch(v);}}
function allocSeat(){const stuId=gv('as-stu'),bId=gv('as-bt'),seat=gv('as-st');if(!stuId||!bId||!seat)return toast('Fill all','er');const s=DB.students.find(x=>x.id===stuId);if(!s)return;s.seat=seat;s.batchId=bId;const b=DB.batches.find(x=>x.id===bId);if(b&&b.occupied<b.total)b.occupied++;addActivity('🪑','rgba(196,125,43,.14)',`Seat <strong>${seat}</strong> → <strong>${s.fname}</strong>`);closeM('mAllocSeat');toast(`Seat ${seat} allocated!`,'ok');renderSeats();renderDash();}
function toast(msg,type='ok'){const c=document.getElementById('toastWrap');const t=document.createElement('div');t.className=`toast ${type}`;const ic={ok:'✅',er:'❌',wn:'⚠️',wa:'💬'};t.innerHTML=`${ic[type]||''} ${msg}`;c.appendChild(t);setTimeout(()=>{t.style.animation='tOut .3s ease forwards';setTimeout(()=>t.remove(),300);},3500);}

// ═══ INIT ═══

// ═══════════════════════════════════════════════════════════
// API-BACKED OVERRIDES — replace in-memory-only functions
// ═══════════════════════════════════════════════════════════

// ── ENROLL STUDENT ──
async function enrollStudent() {
  const fn=gv('en-fn'), ln=gv('en-ln'), bt=gv('en-bt');
  if (!fn || !bt) return toast('First name and batch required', 'er');
  // join_date: use selected date or today, formatted as YYYY-MM-DD for the API
  const joinDateRaw = gv('en-dt') || new Date().toISOString().slice(0,10);
  const payload = {
    fname: fn, lname: ln, phone: gv('en-ph'), batch_id: bt,
    seat_type: gv('en-ac'), seat: gv('en-st'), course: gv('en-co'),
    join_date: joinDateRaw,
    base_fee: +gv('en-fe'), discount_type: gv('en-disc-type'),
    discount_value: +gv('en-disc-val') || 0,
    discount_reason: gv('en-disc-reason')
  };
  const res = await apiPost('add_student', payload);
  if (res.error) return toast(res.error, 'er');
  const waCheck = document.getElementById('en-wa');
  closeM('mEnroll');
  toast(`${fn} enrolled!`, 'ok');
  // ── Reset form ──
  ['en-fn','en-ln','en-ph','en-em','en-ad','en-co','en-dt','en-st',
   'en-fe','en-net-fe','en-disc-val','en-disc-reason'].forEach(id => {
    const el = document.getElementById(id); if (el) el.value = '';
  });
  const btEl = document.getElementById('en-bt'); if (btEl) btEl.value = '';
  const acEl = document.getElementById('en-ac'); if (acEl) acEl.value = 'non-ac';
  const dtEl = document.getElementById('en-disc-type'); if (dtEl) dtEl.value = 'none';
  const waEl = document.getElementById('en-wa'); if (waEl) waEl.checked = true;
  const feeNote = document.getElementById('en-fee-note'); if (feeNote) feeNote.style.display = 'none';
  await reloadDB();
  if (waCheck && waCheck.checked) {
    const newStu = DB.students.find(x => x.id === res.id);
    if (newStu) setTimeout(() => waQuick(newStu.id, 'welcome'), 800);
  }
}

// ── DELETE STUDENT ──
async function delStu(id) {
  if (!confirm('Remove this student?')) return;
  const res = await apiPost('delete_student', { id });
  if (res.error) return toast(res.error, 'er');
  toast('Removed', 'wn');
  await reloadDB();
}

// ── SAVE BATCH (add/edit) ──
async function saveBatch() {
  const nm=gv('ab-nm'), st=gv('ab-st'), et=gv('ab-et'), ts=+gv('ab-ts'), fe=+gv('ab-fe'), ac=+gv('ab-ac');
  if (!nm || !st || !et || !ts || !fe) return toast('Fill required fields', 'er');
  const payload = { name: nm, start_time: st, end_time: et, total_seats: ts, base_fee: fe, ac_extra: ac };
  if (editBatchIdx >= 0) payload.id = DB.batches[editBatchIdx].id;
  const res = await apiPost('save_batch', payload);
  if (res.error) return toast(res.error, 'er');
  toast(editBatchIdx >= 0 ? `"${nm}" updated!` : `Batch "${nm}" created!`, 'ok');
  editBatchIdx = -1;
  closeM('mAddBatch');
  document.getElementById('mAddBatchTitle').textContent = 'Add New Batch';
  document.getElementById('batchSaveBtn').textContent = 'Add Batch';
  await reloadDB();
}

// ── DELETE BATCH ──
async function delBatch(idx) {
  if (!confirm('Delete batch?')) return;
  const res = await apiPost('delete_batch', { id: DB.batches[idx].id });
  if (res.error) return toast(res.error, 'er');
  toast('Deleted', 'wn');
  await reloadDB();
}

// ── ALLOC SEAT ──
async function allocSeat() {
  const stuId=gv('as-stu'), bId=gv('as-bt'), seat=gv('as-st');
  if (!stuId || !bId || !seat) return toast('Fill all fields', 'er');
  const res = await apiPost('alloc_seat', { student_id: stuId, batch_id: bId, seat });
  if (res.error) return toast(res.error, 'er');
  closeM('mAllocSeat');
  toast(`Seat ${seat} allocated!`, 'ok');
  await reloadDB();
}

// ── ADD BOOK ──
async function addBook() {
  const tl=gv('bk-tl');
  if (!tl) return toast('Title required', 'er');
  const res = await apiPost('add_book', {
    title: tl, author: gv('bk-au'), isbn: gv('bk-is'),
    category: gv('bk-ca'), copies: +gv('bk-cp') || 1, shelf: gv('bk-sh')
  });
  if (res.error) return toast(res.error, 'er');
  closeM('mAddBook');
  toast(`"${tl}" added!`, 'ok');
  await reloadDB();
}

// ── ISSUE BOOK ──
async function issueBook() {
  const stuId=gv('ib-stu'), bkId=gv('ib-bk');
  if (!stuId || !bkId) return toast('Select student and book', 'er');
  const res = await apiPost('issue_book', { student_id: stuId, book_id: bkId });
  if (res.error) return toast(res.error, 'er');
  closeM('mIssueBook');
  toast('Book issued!', 'ok');
  await reloadDB();
}

// ── RETURN BOOK ──
async function returnBook() {
  const txId=gv('rb-tx'), cond=gv('rb-cd');
  if (!txId) return toast('Select transaction', 'er');
  const fine = +gv('rb-fn') || 0;
  const res = await apiPost('return_book', { tx_id: txId, fine, condition: cond });
  if (res.error) return toast(res.error, 'er');
  closeM('mReturnBook');
  toast(`Returned!${fine > 0 ? ' Fine: ₹' + fine : ''}`, 'ok');
  await reloadDB();
}

// ── COLLECT FEE ──
async function collectFee() {
  const stuId=gv('cf-stu');
  if (!stuId) return toast('Select student', 'er');
  const mode=gv('cf-mode'), isSplit=mode==='split'||mode==='split2';
  let amt, modeStr;
  if (isSplit) {
    const a1=+gv('cf-a1')||0, a2=+gv('cf-a2')||0;
    amt=a1+a2; modeStr=`${gv('cf-m1')} ₹${a1} + ${gv('cf-m2')} ₹${a2}`;
  } else {
    amt=+gv('cf-amt')||0; modeStr=mode;
  }
  if (!amt) return toast('Enter amount', 'er');
  const res = await apiPost('collect_fee', {
    student_id: stuId, amount: amt, mode: modeStr,
    month: gv('cf-mo') || new Date().toLocaleDateString('en-IN',{month:'long',year:'numeric'})
  });
  if (res.error) return toast(res.error, 'er');
  const bal = res.balance || 0;
  const waCheck = document.getElementById('cf-wa');
  closeM('mCollectFee');
  toast(`₹${amt} collected${bal > 0 ? ` — ₹${bal} still pending` : ''}!`, 'ok');
  await reloadDB();
  if (waCheck && waCheck.checked) {
    setTimeout(() => waQuick(stuId, res.fee_status==='paid' ? 'fee_receipt' : 'partial_payment'), 600);
  }
}

// ── GENERATE INVOICE ──
async function genInvoice() {
  const stuId=gv('gi-stu'), amt=+gv('gi-am');
  if (!stuId || !amt) return toast('Fill required', 'er');
  const res = await apiPost('gen_invoice', {
    student_id: stuId, amount: amt,
    type: gv('gi-tp'), month: gv('gi-mo') || new Date().toLocaleDateString('en-IN',{month:'long',year:'numeric'})
  });
  if (res.error) return toast(res.error, 'er');
  closeM('mGenInv');
  toast('Invoice generated!', 'ok');
  await reloadDB();
}

// ── ADD EXPENSE ──
async function addExp() {
  const nm=gv('ex-nm'), am=+gv('ex-am');
  if (!nm || !am) return toast('Fill required', 'er');
  const res = await apiPost('add_expense', {
    name: nm, amount: am, category: gv('ex-ca'),
    date: gv('ex-dt') || new Date().toLocaleDateString('en-IN',{day:'numeric',month:'short',year:'numeric'}),
    notes: gv('ex-nt')
  });
  if (res.error) return toast(res.error, 'er');
  closeM('mExpense');
  toast('Expense added!', 'ok');
  await reloadDB();
}

// ── DELETE EXPENSE ──
async function delExp(id) {
  const res = await apiPost('delete_expense', { id });
  if (res.error) return toast(res.error, 'er');
  toast('Removed', 'wn');
  await reloadDB();
}

// ── SAVE ATTENDANCE ──
async function saveAtt() {
  const res = await apiPost('save_attendance', {
    date: new Date().toISOString().split('T')[0],
    attendance: DB.attendance
  });
  if (res.error) return toast(res.error, 'er');
  const p = Object.values(DB.attendance).filter(v=>v==='present').length;
  toast(`Saved! ${p} present`, 'ok');
  updateBadges();
}

// ── SAVE STAFF ──
async function saveStaff() {
  const nm=gv('sf-nm'), rl=gv('sf-rl'), em=gv('sf-em');
  if (!nm || !rl || !em) return toast('Fill required', 'er');
  const perms = {};
  PERMS.forEach(p => { const el=document.getElementById('perm-'+p.key); perms[p.key]=el?el.checked:false; });
  const payload = {
    name: nm, role: rl, email: em,
    phone: gv('sf-ph'), username: gv('sf-un'), password: gv('sf-pw'), perms
  };
  if (editStaffIdx >= 0) payload.id = DB.staff[editStaffIdx].id;
  const res = await apiPost('save_staff', payload);
  if (res.error) return toast(res.error, 'er');
  toast(editStaffIdx >= 0 ? `${nm} updated!` : `${nm} added!`, 'ok');
  editStaffIdx = -1;
  closeM('mAddStaff');
  document.getElementById('staffModalTitle').textContent = 'Add Staff';
  document.getElementById('staffSaveBtn').textContent = 'Add Staff';
  await reloadDB();
}

// ── DELETE STAFF ──
async function delStaff(idx) {
  if (!confirm('Remove?')) return;
  const res = await apiPost('delete_staff', { id: DB.staff[idx].id });
  if (res.error) return toast(res.error, 'er');
  toast('Removed', 'wn');
  await reloadDB();
}

// ── MARK NOTIFICATION READ ──
async function markRead(id) {
  await apiPost('mark_read', { id });
  const n = DB.notifications.find(x => x.id === id);
  if (n) n.read = true;
  renderNotifs(); updateBadges();
}

// ── DELETE NOTIFICATION ──
async function delNotif(id) {
  await apiPost('delete_notif', { id });
  DB.notifications = DB.notifications.filter(x => x.id !== id);
  renderNotifs(); updateBadges();
}

// ── CLEAR ALL NOTIFICATIONS ──
async function clearNotifs() {
  await apiGet('clear_notifs');
  DB.notifications = [];
  renderNotifs(); updateBadges(); toast('Cleared', 'ok');
}

// ── SAVE SETTINGS ──
async function saveSettings() {
  const res = await apiPost('save_settings', {
    name: gv('s-name'), phone: gv('s-phone'), email: gv('s-email'),
    addr: gv('s-addr'), fine: +gv('s-fine'), days: +gv('s-days'),
    wa_number: gv('s-wa')
  });
  if (res.error) return toast(res.error, 'er');
  DB.settings.fine = +gv('s-fine');
  DB.settings.days = +gv('s-days');
  DB.settings.waNum = gv('s-wa');
  DB.settings.name = gv('s-name');
  toast('Settings saved!', 'ok');
}

// ── WA SEND LOG (persist to DB) ──
const _origWaSend = typeof waSend === 'function' ? waSend : null;
async function logWA(to, preview, type) {
  DB.waSendLog.unshift({ time: new Date().toLocaleTimeString(), to, preview: preview.slice(0,40)+'…', type });
  await apiPost('log_wa', { to, preview: preview.slice(0,60), type });
}

// ── RELOAD ALL DATA FROM SERVER ──
async function reloadDB() {
  await initData();
  const active = document.querySelector('.page.active');
  if (active) {
    const id = active.id.replace('page-', '');
    renderPage(id);
  }
}

// ── OVERRIDE addActivity / addNotif to be no-ops (server handles them) ──
function addActivity(icon, bg, text) {
  DB.activities.unshift({ icon, bg, text, time: 'Just now' });
  if (DB.activities.length > 20) DB.activities.pop();
}
function addNotif(type, title, msg) {
  DB.notifications.unshift({ id: Date.now(), type, title, msg, time: 'Just now', read: false });
}

// ── SETTINGS PAGE: populate from DB ──
const _origRenderSettings = renderSettings;
function renderSettings() {
  // Fill settings form from live DB
  const s = DB.settings;
  const fields = { 's-name': s.name, 's-phone': s.phone, 's-email': s.email, 
                   's-addr': s.addr, 's-fine': s.fine||s.fine_per_day, 
                   's-days': s.days||s.loan_days, 's-wa': s.waNum||s.wa_number };
  Object.entries(fields).forEach(([id, val]) => {
    const el = document.getElementById(id);
    if (el && val !== undefined) el.value = val;
  });
  _origRenderSettings();
}

// ═══ CHANGE PASSWORD ═══
async function doChangePassword() {
  const cur = document.getElementById('cp-cur').value;
  const nw  = document.getElementById('cp-new').value;
  const cf  = document.getElementById('cp-cf').value;
  if (!cur || !nw || !cf) return toast('Fill all fields', 'er');
  if (nw.length < 6) return toast('Password must be 6+ characters', 'er');
  if (nw !== cf) return toast('Passwords do not match', 'er');
  try {
    const res = await apiPost('change_password', { current_password: cur, new_password: nw });
    if (res.success) {
      toast('Password updated!', 'ok');
      closeM('mChangePw');
      document.getElementById('cp-cur').value = '';
      document.getElementById('cp-new').value = '';
      document.getElementById('cp-cf').value = '';
    } else {
      toast(res.error || 'Failed to update', 'er');
    }
  } catch(e) {
    toast('Error: ' + e.message, 'er');
  }
}

// ═══ BOOT ═══
document.getElementById('todayChip').textContent = new Date().toLocaleDateString('en-IN',{month:'long',year:'numeric'});
initData();
</script>
</body>
</html>
