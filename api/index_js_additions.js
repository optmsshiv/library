/**
 * OPTMS Tech ERP — index.php  JavaScript Additions
 * ─────────────────────────────────────────────────────────────────
 * Paste this entire block just before the closing </script> tag
 * at the bottom of your index.php.
 *
 * It adds:
 *  1. generateReceipt(invoiceId)  — generates & downloads PDF
 *  2. PDF buttons wired into the invoices table
 *  3. PDF button in the student profile modal
 *  4. PDF + WA share after fee collection
 *  5. Bulk ZIP download in Invoices page header
 *  6. All UI styles for the PDF buttons & toast
 * ─────────────────────────────────────────────────────────────────
 */


// ══════════════════════════════════════════════════════════════════
// 1. CORE PDF GENERATOR FUNCTION
//    Call this anywhere: generateReceipt(invoiceId)
//    or: generateReceipt(null, { student_id, paid_amt, ... })
// ══════════════════════════════════════════════════════════════════

async function generateReceipt(invoiceId = null, inlineData = null) {
  const btn = event?.currentTarget;
  if (btn) { btn.disabled = true; btn.innerHTML = '<span class="mi sm" style="animation:spin .8s linear infinite">sync</span> Generating…'; }

  try {
    const payload = invoiceId
      ? { invoice_id: invoiceId }
      : { ...inlineData };

    const res = await apiPost('generate_receipt', payload);

    if (res.error) {
      toast('PDF error: ' + res.error, 'er');
      return null;
    }

    // Show download toast with action button
    showPdfToast(res.url, res.filename);
    return res;

  } catch (e) {
    toast('PDF generation failed', 'er');
    return null;
  } finally {
    if (btn) { btn.disabled = false; btn.innerHTML = '<span class="mi sm">picture_as_pdf</span> Receipt PDF'; }
  }
}


// ══════════════════════════════════════════════════════════════════
// 2. SHOW PDF READY TOAST  (download + WA share)
// ══════════════════════════════════════════════════════════════════

function showPdfToast(url, filename) {
  const wrap = document.getElementById('toastWrap');
  const t    = document.createElement('div');
  t.className = 'toast pdf-toast';
  t.innerHTML = `
    <span class="mi" style="color:#fff;font-size:18px">picture_as_pdf</span>
    <div style="flex:1">
      <div style="font-weight:700;font-size:12.5px">Receipt PDF ready!</div>
      <div style="font-size:10.5px;opacity:.85">${filename}</div>
    </div>
    <a href="${url}" download="${filename}" target="_blank"
       style="background:rgba(255,255,255,.25);color:#fff;padding:4px 10px;border-radius:6px;font-size:11px;font-weight:700;text-decoration:none;white-space:nowrap">
      Download
    </a>
    <button onclick="shareReceiptWA('${url}')"
       style="background:#25d366;border:none;color:#fff;padding:4px 10px;border-radius:6px;font-size:11px;font-weight:700;cursor:pointer;white-space:nowrap">
      WA Share
    </button>
  `;
  wrap.appendChild(t);
  setTimeout(() => { t.style.animation = 'tOut .28s ease forwards'; setTimeout(() => t.remove(), 300); }, 7000);
}

function shareReceiptWA(url) {
  // Build full absolute URL for sharing
  const fullUrl = window.location.origin + '/' + url.replace(/^\.\.\//, '');
  const msg     = encodeURIComponent(
    `📄 *Fee Receipt – ${DB.settings.name}*\n\nYour payment receipt is ready. Download it here:\n${fullUrl}\n\nThank you! 🙏`
  );
  const waNum   = (DB.settings.waNum || '').replace(/[^0-9]/g, '');
  window.open(`https://wa.me/${waNum}?text=${msg}`, '_blank');
}


// ══════════════════════════════════════════════════════════════════
// 3. PATCH renderInv() — add PDF button in invoices table
//    This overrides the invoice row rendering to include PDF action
// ══════════════════════════════════════════════════════════════════

const _origRenderInv = typeof renderInv === 'function' ? renderInv : null;

function renderInv() {
  if (_origRenderInv) _origRenderInv();

  // Patch each row in invTable to add PDF button
  const tbody = document.getElementById('invTable');
  if (!tbody) return;

  // Re-render rows with PDF button appended
  const rows = tbody.querySelectorAll('tr');
  rows.forEach((tr, i) => {
    const inv = DB.invoices[i];
    if (!inv) return;
    const lastTd = tr.querySelector('td:last-child');
    if (!lastTd) return;

    // Avoid double-adding
    if (lastTd.querySelector('.pdf-btn-inv')) return;

    const pdfBtn = document.createElement('button');
    pdfBtn.className = 'btn bg pdf-btn-inv';
    pdfBtn.style.cssText = 'font-size:10.5px;padding:4px 8px;margin-left:4px';
    pdfBtn.innerHTML = '<span class="mi sm">picture_as_pdf</span>';
    pdfBtn.title = 'Download Receipt PDF';
    pdfBtn.onclick = async (e) => {
      e.stopPropagation();
      await generateReceipt(inv.id);
    };
    lastTd.appendChild(pdfBtn);
  });
}


// ══════════════════════════════════════════════════════════════════
// 4. PATCH saveCollectFee() — auto-generate PDF after fee save
//    Find your existing saveCollectFee function and either:
//    (a) Replace the whole function with this version, OR
//    (b) Add the generateReceipt() call after your existing toast()
//
//    OPTION B — just add these 3 lines inside your existing
//    saveCollectFee() right after:  toast(`Fee saved!`, 'ok');
//
//        const latestInv = DB.invoices[DB.invoices.length - 1];
//        if (latestInv && autoGenPdf) generateReceipt(latestInv.id);
//
//    OPTION A — full replacement below:
// ══════════════════════════════════════════════════════════════════

let autoGenPdf = true; // toggle in Settings if needed

// Hook: run after fee collection reloads data
const _origSaveFee = typeof saveCollectFee === 'function' ? saveCollectFee : null;

async function saveCollectFeeWithPDF() {
  if (!_origSaveFee) return;
  await _origSaveFee();

  // After reloadDB, find the most recently created invoice
  if (!autoGenPdf) return;
  await new Promise(r => setTimeout(r, 600)); // wait for reloadDB
  const invs  = DB.invoices;
  if (!invs || !invs.length) return;
  const latest = invs[invs.length - 1];
  if (latest) {
    toast('Generating receipt PDF…', 'wn');
    await generateReceipt(latest.id);
  }
}

// Override only if original exists
if (_origSaveFee) {
  // We can't reassign a declared function, so wire via button instead:
  document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('collectFeeBtn') ||
                document.querySelector('[onclick*="saveCollectFee"]');
    if (btn) btn.setAttribute('onclick', 'saveCollectFeeWithPDF()');
  });
}


// ══════════════════════════════════════════════════════════════════
// 5. STUDENT PROFILE — add PDF button
//    In your mStudentProfile modal HTML, add this button next to
//    the existing action buttons:
//
//    <button class="btn bg" style="font-size:11px" onclick="pdfFromProfile()">
//      <span class="mi sm">picture_as_pdf</span> Receipt
//    </button>
// ══════════════════════════════════════════════════════════════════

async function pdfFromProfile() {
  const stuId = profileStudentId;
  if (!stuId) return;

  // Find latest invoice for this student
  const inv = [...(DB.invoices || [])].reverse().find(i => i.studentId === stuId);
  if (inv) {
    await generateReceipt(inv.id);
  } else {
    toast('No invoice found for this student', 'wn');
  }
}


// ══════════════════════════════════════════════════════════════════
// 6. INVOICES PAGE — add Bulk ZIP button
//    Add this button to the sec-hd in page-invoices:
//
//    <button class="btn bg" onclick="downloadBulkZip()" style="font-size:11px">
//      <span class="mi sm">folder_zip</span> ZIP This Month
//    </button>
// ══════════════════════════════════════════════════════════════════

async function downloadBulkZip() {
  const month = prompt('Enter month (YYYY-MM):', new Date().toISOString().slice(0, 7));
  if (!month || !/^\d{4}-\d{2}$/.test(month)) return toast('Invalid month format', 'er');
  toast('Building ZIP… this may take a moment', 'wn');
  const res = await apiPost('bulk_receipts_zip', { month });
  if (res.error) return toast(res.error, 'er');
  showPdfToast(res.url, res.filename);
  toast(`ZIP ready — ${res.count} receipts`, 'ok');
}


// ══════════════════════════════════════════════════════════════════
// 7. STYLES  (paste inside your <style> block OR keep here)
// ══════════════════════════════════════════════════════════════════

(function injectPdfStyles() {
  const style = document.createElement('style');
  style.textContent = `
    .pdf-toast{background:linear-gradient(135deg,#1e3fa8,#3d6ff0)!important;gap:10px}
    @keyframes spin{to{transform:rotate(360deg)}}
    .pdf-btn-inv{border-color:rgba(61,111,240,.3)!important;color:var(--ac)!important}
    .pdf-btn-inv:hover{background:rgba(61,111,240,.08)!important}
  `;
  document.head.appendChild(style);
})();


// ══════════════════════════════════════════════════════════════════
// ── HTML SNIPPETS TO ADD IN index.php ─────────────────────────────
// ══════════════════════════════════════════════════════════════════

/*
─── A) In page-invoices sec-hd, add next to Generate button: ─────

  <button class="btn bg" onclick="downloadBulkZip()" style="font-size:11px">
    <span class="mi sm">folder_zip</span> ZIP Month
  </button>


─── B) In mStudentProfile modal footer, add: ─────────────────────

  <button class="btn bg" style="font-size:11px;gap:4px" onclick="pdfFromProfile()">
    <span class="mi sm">picture_as_pdf</span> Receipt
  </button>


─── C) In mCollectFee modal footer, add after save button: ───────

  <div class="fgi full" style="flex-direction:row;align-items:center;gap:6px;margin-top:4px">
    <input type="checkbox" id="autoPdfCheck" checked onchange="autoGenPdf=this.checked">
    <label for="autoPdfCheck" style="font-size:11px;font-weight:400;color:var(--tx3)">
      Auto-generate receipt PDF after saving
    </label>
  </div>


─── D) SQL migration — run once in phpMyAdmin: ───────────────────

  ALTER TABLE invoices ADD COLUMN IF NOT EXISTS pdf_path VARCHAR(255) DEFAULT NULL;

*/
