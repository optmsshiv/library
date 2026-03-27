// ═══ OPTMS Tech ERP — Shared JS ═══

// ── TOAST ──
function toast(msg, type = 'ok') {
  const c = document.getElementById('toastWrap');
  if (!c) return;
  const t = document.createElement('div');
  t.className = `toast ${type}`;
  const icons = { ok: '✅', er: '❌', wn: '⚠️', wa: '💬' };
  t.innerHTML = `${icons[type] || ''} ${msg}`;
  c.appendChild(t);
  setTimeout(() => {
    t.style.animation = 'tOut .3s ease forwards';
    setTimeout(() => t.remove(), 300);
  }, 3500);
}

// ── GLOBAL MODAL CLOSE ON BACKDROP CLICK ──
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.mo').forEach(modal => {
    modal.addEventListener('click', e => {
      if (e.target === modal) modal.classList.remove('open');
    });
  });

  // Set today chip
  const chip = document.getElementById('todayChip');
  if (chip) chip.textContent = new Date().toLocaleDateString('en-IN', { month: 'long', year: 'numeric' });

  // Auto-close alerts
  document.querySelectorAll('.alert').forEach(a => {
    setTimeout(() => a.style.opacity = '0', 4000);
    setTimeout(() => a.remove(), 4500);
  });
});

// ── CONFIRM DELETE ──
function confirmDelete(msg = 'Are you sure you want to delete this?') {
  return confirm(msg);
}

// ── FETCH API HELPER ──
async function apiPost(url, data) {
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams(data).toString()
  });
  return res.json();
}

// ── FORMAT CURRENCY ──
function fmtINR(n) {
  if (n >= 100000) return '₹' + (n / 100000).toFixed(1) + 'L';
  if (n >= 1000)   return '₹' + n.toLocaleString('en-IN');
  return '₹' + n;
}

// ── AUTO SEARCH DEBOUNCE ──
function debounce(fn, delay = 300) {
  let t;
  return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), delay); };
}

// ── MOBILE SIDEBAR TOGGLE ──
const sbToggleBtn = document.getElementById('sbToggle');
if (sbToggleBtn) {
  sbToggleBtn.addEventListener('click', () => {
    document.querySelector('.sb')?.classList.toggle('open');
  });
}
