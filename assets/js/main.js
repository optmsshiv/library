// assets/js/main.js
let currentPage = 'dashboard';

function navTo(page) {
    document.querySelectorAll('.ni').forEach(n => n.classList.remove('active'));
    const ni = document.querySelector(`.ni[data-page="${page}"]`);
    if (ni) ni.classList.add('active');

    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    const pg = document.getElementById('page-' + page);
    if (pg) pg.classList.add('active');

    document.getElementById('topTitle').textContent = page.charAt(0).toUpperCase() + page.slice(1);
    currentPage = page;
    if (typeof window['render' + page.charAt(0).toUpperCase() + page.slice(1)] === 'function') {
        window['render' + page.charAt(0).toUpperCase() + page.slice(1)]();
    }
}

function openM(modalId) {
    document.getElementById(modalId).classList.add('open');
}

function closeM(modalId) {
    document.getElementById(modalId).classList.remove('open');
}

function toast(msg, type = 'ok') {
    const wrap = document.getElementById('toastWrap');
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.innerHTML = `${type === 'ok' ? '✅' : type === 'er' ? '❌' : '⚠️'} ${msg}`;
    wrap.appendChild(t);
    setTimeout(() => t.remove(), 3500);
}

// Global search
function globalSearch(val) {
    if (!val.trim()) return;
    // You can implement AJAX search later
    toast('Searching for: ' + val);
}

// Make functions global
window.navTo = navTo;
window.openM = openM;
window.closeM = closeM;
window.toast = toast;
window.globalSearch = globalSearch;