# OPTMS Tech ERP — PDF Receipt Engine
## Installation Guide

---

## Step 1 — Install TCPDF (5 minutes)

1. Go to: https://github.com/tecnickcom/tcpdf/releases
2. Download `tcpdf_min.zip` (lightweight version, ~2MB)
3. Extract it so you have this folder:

```
your-project/
  tcpdf/
    tcpdf.php       ← main file
    config/
    include/
    fonts/
```

That's it. No Composer, no npm, no server setup.

---

## Step 2 — Create receipts folder

```bash
mkdir receipts
chmod 755 receipts
```

Or in cPanel File Manager: create folder `receipts/` at project root, set permissions to 755.

---

## Step 3 — Add pdf_receipt.php

Copy `api/pdf_receipt.php` into your `api/` folder.

Then open `api/index.php` and add **near the top** (after session_start):

```php
// PDF functions
if (!function_exists('generatePDF')) {
    require_once __DIR__ . '/pdf_receipt.php';
}
```

---

## Step 4 — Add API cases to api/index.php

Find your `switch ($action)` block and paste these 3 cases from `api/api_additions.php`:

- `case 'generate_receipt':`
- `case 'get_receipt_url':`
- `case 'bulk_receipts_zip':`

---

## Step 5 — Run SQL migration

In phpMyAdmin, run:

```sql
ALTER TABLE invoices ADD COLUMN IF NOT EXISTS pdf_path VARCHAR(255) DEFAULT NULL;
```

---

## Step 6 — Add JavaScript to index.php

Paste the entire content of `index_js_additions.js` just before the closing `</script>` tag at the bottom of `index.php`.

---

## Step 7 — Add HTML snippets

**A) Invoices page** — add ZIP button in `page-invoices` header:
```html
<button class="btn bg" onclick="downloadBulkZip()" style="font-size:11px">
  <span class="mi sm">folder_zip</span> ZIP Month
</button>
```

**B) Student profile modal** — add receipt button in footer:
```html
<button class="btn bg" style="font-size:11px" onclick="pdfFromProfile()">
  <span class="mi sm">picture_as_pdf</span> Receipt
</button>
```

**C) Collect Fee modal** — add auto-PDF checkbox before save button:
```html
<div style="display:flex;align-items:center;gap:6px;margin-top:4px">
  <input type="checkbox" id="autoPdfCheck" checked onchange="autoGenPdf=this.checked">
  <label for="autoPdfCheck" style="font-size:11px;color:var(--tx3)">
    Auto-generate receipt PDF after saving
  </label>
</div>
```

---

## What you get after setup

| Feature | Where |
|---|---|
| Auto PDF after fee collection | Collect Fee modal — checkbox to enable/disable |
| Download PDF for any invoice | Invoices table — PDF icon button in each row |
| Download PDF from student profile | Student profile modal — Receipt button |
| WA share receipt link | Toast popup after generation — WA Share button |
| Bulk ZIP download | Invoices page header — ZIP Month button |

---

## Receipt PDF contains

- Institute header with name, address, contact
- Color-coded status banner (green=paid, amber=partial, red=overdue)
- Student details: name, phone, batch, seat, course
- Payment summary: base fee, discount, net fee, paid amount, balance
- Amount in words (Indian numbering: Lakh, Crore)
- Payment mode badge
- Terms & conditions
- Signature + stamp area
- Tear-off student copy at bottom
- Auto-generated invoice reference number

---

## Troubleshooting

**"TCPDF not found"** → Check TCPDF_PATH in pdf_receipt.php matches where you extracted tcpdf/

**"Failed to open stream: Permission denied"** → `chmod 755 receipts/` or set folder permissions in cPanel

**"PDF generation failed"** → Enable PHP error logging: `error_reporting(E_ALL); ini_set('display_errors',1);` temporarily

**Blank PDF / Missing fonts** → Make sure the `tcpdf/fonts/` folder is present (sometimes stripped from minimal zip)

**WA share opens wrong number** → Set wa_number in Settings page (without +, e.g. 917282071620)
