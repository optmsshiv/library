<?php
/**
 * OPTMS Tech ERP — PDF Receipt & Invoice Engine
 * File: api/pdf_receipt.php
 *
 * INSTALL TCPDF (one-time, no Composer needed):
 *   1. Download from https://github.com/tecnickcom/tcpdf/releases
 *      → tcpdf_min.zip  (lightweight, ~2MB)
 *   2. Extract to:  /your-project/tcpdf/
 *   3. That's it. This file does the rest.
 *
 * FOLDER SETUP (create these, make writable):
 *   mkdir receipts
 *   chmod 755 receipts
 *
 * USAGE — called from api/index.php:
 *   case 'generate_receipt': include 'pdf_receipt.php'; break;
 */

session_start();
if (empty($_SESSION['staff_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// ── CONFIG ─────────────────────────────────────────────────────────────────
define('TCPDF_PATH',    __DIR__ . '/../tcpdf/tcpdf.php');
define('RECEIPTS_DIR',  __DIR__ . '/../receipts/');
define('RECEIPTS_URL',  '../receipts/');   // relative URL for browser download

// ── DB CONNECTION ──────────────────────────────────────────────────────────
// Reuse your existing DB connection — adjust path/credentials as needed
require_once __DIR__ . '/db.php';   // should give you $pdo or $conn

// ── ROUTER ────────────────────────────────────────────────────────────────
$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $_GET['action'] ?? $input['action'] ?? '';

switch ($action) {
    case 'generate_receipt':  actionGenerateReceipt($pdo, $input); break;
    case 'get_receipt_url':   actionGetReceiptUrl($pdo, $input);   break;
    case 'bulk_receipts_zip': actionBulkZip($pdo, $input);         break;
    default:
        echo json_encode(['error' => 'Unknown PDF action']);
}

// ══════════════════════════════════════════════════════════════════════════
// ACTION: Generate single receipt PDF
// POST body: { invoice_id: 5 }   OR   { student_id: 12, fee_payment: {...} }
// ══════════════════════════════════════════════════════════════════════════
function actionGenerateReceipt($pdo, $input) {
    // ── 1. Load invoice data ──────────────────────────────────────────────
    if (!empty($input['invoice_id'])) {
        $stmt = $pdo->prepare("
            SELECT i.*, s.fname, s.lname, s.phone, s.seat, s.seat_type,
                   b.name AS batch_name, b.start_time, b.end_time,
                   st.name AS institute_name, st.phone AS inst_phone,
                   st.email AS inst_email, st.addr AS inst_addr
            FROM invoices i
            JOIN students s  ON s.id = i.student_id
            LEFT JOIN batches b ON b.id = s.batch_id
            JOIN settings st ON 1=1
            WHERE i.id = ?
            LIMIT 1
        ");
        $stmt->execute([$input['invoice_id']]);
        $inv = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$inv) { echo json_encode(['error' => 'Invoice not found']); return; }
    } else {
        // Called directly after fee collection — build inline data
        $inv = buildInvoiceFromInput($pdo, $input);
        if (!$inv) { echo json_encode(['error' => 'Insufficient data']); return; }
    }

    // ── 2. Load settings (institute info) ────────────────────────────────
    $settings = loadSettings($pdo);

    // ── 3. Generate PDF ───────────────────────────────────────────────────
    $filename = generatePDF($inv, $settings);
    if (!$filename) { echo json_encode(['error' => 'PDF generation failed']); return; }

    // ── 4. Save PDF path to invoices table ───────────────────────────────
    if (!empty($inv['id'])) {
        $pdo->prepare("UPDATE invoices SET pdf_path = ? WHERE id = ?")
            ->execute([$filename, $inv['id']]);
    }

    echo json_encode([
        'success'  => true,
        'filename' => $filename,
        'url'      => RECEIPTS_URL . $filename,
        'inv_no'   => $inv['inv_no'] ?? ('INV-' . ($inv['id'] ?? date('YmdHis')))
    ]);
}

// ══════════════════════════════════════════════════════════════════════════
// ACTION: Return existing receipt URL for an invoice
// ══════════════════════════════════════════════════════════════════════════
function actionGetReceiptUrl($pdo, $input) {
    if (empty($input['invoice_id'])) { echo json_encode(['error' => 'Missing invoice_id']); return; }
    $stmt = $pdo->prepare("SELECT pdf_path FROM invoices WHERE id = ?");
    $stmt->execute([$input['invoice_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && $row['pdf_path'] && file_exists(RECEIPTS_DIR . $row['pdf_path'])) {
        echo json_encode(['url' => RECEIPTS_URL . $row['pdf_path']]);
    } else {
        echo json_encode(['url' => null]);
    }
}

// ══════════════════════════════════════════════════════════════════════════
// ACTION: Bulk ZIP — all receipts for a given month
// POST body: { month: '2026-03' }
// ══════════════════════════════════════════════════════════════════════════
function actionBulkZip($pdo, $input) {
    if (!class_exists('ZipArchive')) { echo json_encode(['error' => 'ZipArchive not available on server']); return; }
    $month = $input['month'] ?? date('Y-m');

    $stmt = $pdo->prepare("
        SELECT i.*, s.fname, s.lname, i.pdf_path
        FROM invoices i
        JOIN students s ON s.id = i.student_id
        WHERE DATE_FORMAT(i.invoice_date, '%Y-%m') = ?
        ORDER BY i.invoice_date DESC
    ");
    $stmt->execute([$month]);
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $settings = loadSettings($pdo);
    $zip      = new ZipArchive();
    $zipName  = 'receipts_' . $month . '.zip';
    $zipPath  = RECEIPTS_DIR . $zipName;
    $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    $count = 0;
    foreach ($invoices as $inv) {
        // Generate if PDF doesn't exist yet
        if (empty($inv['pdf_path']) || !file_exists(RECEIPTS_DIR . $inv['pdf_path'])) {
            $inv['pdf_path'] = generatePDF($inv, $settings);
            if ($inv['id']) {
                $pdo->prepare("UPDATE invoices SET pdf_path = ? WHERE id = ?")
                    ->execute([$inv['pdf_path'], $inv['id']]);
            }
        }
        if ($inv['pdf_path'] && file_exists(RECEIPTS_DIR . $inv['pdf_path'])) {
            $zip->addFile(RECEIPTS_DIR . $inv['pdf_path'], $inv['pdf_path']);
            $count++;
        }
    }
    $zip->close();

    echo json_encode([
        'success'  => true,
        'url'      => RECEIPTS_URL . $zipName,
        'count'    => $count,
        'filename' => $zipName
    ]);
}

// ══════════════════════════════════════════════════════════════════════════
// CORE: Build PDF using TCPDF
// Returns filename on success, null on failure
// ══════════════════════════════════════════════════════════════════════════
function generatePDF($inv, $settings) {
    if (!file_exists(TCPDF_PATH)) {
        error_log('TCPDF not found at ' . TCPDF_PATH);
        return null;
    }
    require_once TCPDF_PATH;

    // ── Receipt metadata ──
    $invNo    = $inv['inv_no']       ?? ('INV-' . str_pad($inv['id'] ?? rand(1000,9999), 6, '0', STR_PAD_LEFT));
    $date     = $inv['invoice_date'] ?? date('Y-m-d');
    $dateDisp = date('d M Y', strtotime($date));
    $stuName  = trim(($inv['fname'] ?? '') . ' ' . ($inv['lname'] ?? ''));
    $phone    = $inv['phone']        ?? '—';
    $batch    = $inv['batch_name']   ?? '—';
    $seat     = $inv['seat']         ?? '—';
    $seatType = strtoupper($inv['seat_type'] ?? 'non-ac');
    $course   = $inv['course']       ?? '—';

    $baseFee  = (float)($inv['base_fee']  ?? $inv['amount']  ?? 0);
    $discount = (float)($inv['discount']  ?? 0);
    $netFee   = (float)($inv['net_fee']   ?? ($baseFee - $discount));
    $paidAmt  = (float)($inv['paid_amt']  ?? $inv['amount']  ?? $netFee);
    $balance  = (float)($inv['balance']   ?? max(0, $netFee - $paidAmt));
    $mode     = ucfirst($inv['mode']      ?? 'cash');
    $status   = ucfirst($inv['status']    ?? ($balance <= 0 ? 'paid' : 'partial'));

    // Institute info from settings
    $instName  = $settings['name']  ?? 'OPTMS Tech Study Library';
    $instPhone = $settings['phone'] ?? '';
    $instEmail = $settings['email'] ?? '';
    $instAddr  = $settings['addr']  ?? 'Madhepura, Bihar - 852113';

    // ── Colour palette ──
    $blue   = [61,  111, 240];
    $dkblue = [30,  60,  180];
    $green  = [22,  163, 74 ];
    $red    = [220, 38,  38 ];
    $amber  = [217, 119, 6  ];
    $gray   = [100, 116, 139];
    $light  = [241, 245, 249];
    $white  = [255, 255, 255];
    $dark   = [15,  23,  42 ];

    // ── Create PDF ──
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('OPTMS Tech ERP');
    $pdf->SetAuthor($instName);
    $pdf->SetTitle('Fee Receipt ' . $invNo);
    $pdf->SetSubject('Fee Receipt');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(0, 0, 0);
    $pdf->SetAutoPageBreak(false);
    $pdf->AddPage();

    $W = 210; // A4 width mm
    $H = 297; // A4 height mm

    // ────────────────────────────────────────────────────────────────
    // HEADER BAND
    // ────────────────────────────────────────────────────────────────
    $pdf->SetFillColor(...$blue);
    $pdf->Rect(0, 0, $W, 42, 'F');

    // Top accent stripe
    $pdf->SetFillColor(...$dkblue);
    $pdf->Rect(0, 0, $W, 2, 'F');

    // Institute icon circle
    $pdf->SetFillColor(255, 255, 255, 30);
    $pdf->SetFillColor(80, 130, 250);
    $pdf->Circle(20, 21, 11, 0, 360, 'F');
    $pdf->SetTextColor(...$white);
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetXY(12, 15);
    $pdf->Cell(16, 10, 'O', 0, 0, 'C');

    // Institute name
    $pdf->SetTextColor(...$white);
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetXY(36, 10);
    $pdf->Cell(120, 8, $instName, 0, 1, 'L');

    // Institute sub info
    $pdf->SetFont('helvetica', '', 8.5);
    $pdf->SetTextColor(200, 220, 255);
    $pdf->SetXY(36, 19);
    $pdf->Cell(120, 5, $instAddr, 0, 1, 'L');
    $pdf->SetXY(36, 24);
    $contact = [];
    if ($instPhone) $contact[] = $instPhone;
    if ($instEmail) $contact[] = $instEmail;
    $pdf->Cell(120, 5, implode('  |  ', $contact), 0, 1, 'L');

    // RECEIPT label on right
    $pdf->SetFillColor(255, 255, 255, 20);
    $pdf->SetFillColor(30, 60, 180);
    $pdf->RoundedRect(148, 8, 50, 26, 4, '1111', 'F');
    $pdf->SetTextColor(...$white);
    $pdf->SetFont('helvetica', 'B', 13);
    $pdf->SetXY(148, 12);
    $pdf->Cell(50, 7, 'FEE RECEIPT', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetXY(148, 20);
    $pdf->Cell(50, 5, $invNo, 0, 1, 'C');
    $pdf->SetXY(148, 25);
    $pdf->Cell(50, 5, $dateDisp, 0, 1, 'C');

    // ────────────────────────────────────────────────────────────────
    // STATUS BANNER
    // ────────────────────────────────────────────────────────────────
    $statusColor = $status === 'paid' ? $green : ($status === 'partial' ? $amber : $red);
    $pdf->SetFillColor(...$statusColor);
    $pdf->Rect(0, 42, $W, 10, 'F');
    $pdf->SetTextColor(...$white);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetXY(0, 44);
    $label = strtoupper($status) . ($status === 'paid' ? '  ✓  Payment complete' : ($status === 'partial' ? '  ⚡  Partial payment received' : '  ⚠  Balance due'));
    $pdf->Cell($W, 6, $label, 0, 1, 'C');

    // ────────────────────────────────────────────────────────────────
    // STUDENT INFO SECTION
    // ────────────────────────────────────────────────────────────────
    $y = 58;
    $pdf->SetTextColor(...$dark);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetXY(14, $y);
    $pdf->Cell(80, 6, 'Student Details', 0, 1, 'L');

    // Divider
    $pdf->SetDrawColor(...$blue);
    $pdf->SetLineWidth(0.6);
    $pdf->Line(14, $y + 7, 90, $y + 7);
    $pdf->SetLineWidth(0.2);
    $pdf->SetDrawColor(200, 210, 230);

    $fields = [
        ['Name',       $stuName],
        ['Phone',      $phone],
        ['Batch',      $batch],
        ['Seat No.',   $seat . ($seatType === 'AC' ? '  (AC)' : '')],
        ['Course',     $course],
        ['Join Date',  $inv['join_date'] ?? '—'],
    ];

    $fy = $y + 10;
    foreach ($fields as $f) {
        $pdf->SetFont('helvetica', '', 8.5);
        $pdf->SetTextColor(...$gray);
        $pdf->SetXY(14, $fy);
        $pdf->Cell(28, 6, $f[0] . ':', 0, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 8.5);
        $pdf->SetTextColor(...$dark);
        $pdf->Cell(55, 6, $f[1], 0, 1, 'L');
        $fy += 6.5;
    }

    // ────────────────────────────────────────────────────────────────
    // PAYMENT SUMMARY BOX (right column)
    // ────────────────────────────────────────────────────────────────
    $bx = 115; $by = 58; $bw = 82; $bh = 90;
    $pdf->SetFillColor(...$light);
    $pdf->RoundedRect($bx, $by, $bw, $bh, 4, '1111', 'F');
    $pdf->SetDrawColor(200, 210, 230);
    $pdf->RoundedRect($bx, $by, $bw, $bh, 4, '1111', 'D');

    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetTextColor(...$dark);
    $pdf->SetXY($bx, $by + 4);
    $pdf->Cell($bw, 7, 'Payment Summary', 0, 1, 'C');

    $pdf->SetDrawColor(200, 210, 230);
    $pdf->Line($bx + 6, $by + 12, $bx + $bw - 6, $by + 12);

    $rows = [
        ['Course Fee',    '+ Rs. ' . number_format($baseFee, 2),  $dark,  ''],
        ['Discount',      '- Rs. ' . number_format($discount, 2), $green, ''],
        ['Net Fee',       'Rs. '  . number_format($netFee, 2),    $dark,  'B'],
        ['Amount Paid',   'Rs. '  . number_format($paidAmt, 2),   $green, 'B'],
        ['Balance Due',   'Rs. '  . number_format($balance, 2),   $balance > 0 ? $red : $green, 'B'],
    ];

    $ry = $by + 15;
    foreach ($rows as $i => $r) {
        if ($i === 2) {  // Net Fee separator
            $pdf->SetDrawColor(180, 195, 220);
            $pdf->Line($bx + 6, $ry - 1, $bx + $bw - 6, $ry - 1);
        }
        $pdf->SetFont('helvetica', $r[3], 8.5);
        $pdf->SetTextColor(...$gray);
        $pdf->SetXY($bx + 6, $ry);
        $pdf->Cell(35, 7, $r[0], 0, 0, 'L');
        $pdf->SetTextColor(...$r[2]);
        $pdf->SetXY($bx + 42, $ry);
        $pdf->Cell($bw - 48, 7, $r[1], 0, 0, 'R');
        $ry += 13;
    }

    // Payment mode badge
    $ry += 2;
    $pdf->SetFillColor(...$blue);
    $pdf->RoundedRect($bx + 10, $ry, $bw - 20, 9, 3, '1111', 'F');
    $pdf->SetTextColor(...$white);
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetXY($bx + 10, $ry + 1);
    $pdf->Cell($bw - 20, 7, 'Mode: ' . strtoupper($mode), 0, 0, 'C');

    // ────────────────────────────────────────────────────────────────
    // AMOUNT IN WORDS
    // ────────────────────────────────────────────────────────────────
    $wordsY = 160;
    $pdf->SetFillColor(...$light);
    $pdf->RoundedRect(14, $wordsY, 182, 14, 3, '1111', 'F');
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor(...$gray);
    $pdf->SetXY(18, $wordsY + 2);
    $pdf->Cell(40, 5, 'Amount in words:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetTextColor(...$dark);
    $pdf->Cell(135, 5, 'Rupees ' . numberToWords((int)$paidAmt) . ' Only', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor(...$gray);
    $pdf->SetXY(18, $wordsY + 7);
    $pdf->Cell(100, 5, 'Transaction reference: ' . strtoupper($invNo) . '-' . date('Ymd', strtotime($date)), 0, 0, 'L');

    // ────────────────────────────────────────────────────────────────
    // TERMS / NOTES
    // ────────────────────────────────────────────────────────────────
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetTextColor(...$dark);
    $pdf->SetXY(14, 182);
    $pdf->Cell(80, 6, 'Terms & Conditions', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor(...$gray);
    $terms = [
        '1. This receipt is valid as proof of payment for the stated amount only.',
        '2. Fee once paid is non-refundable except as per institute policy.',
        '3. For queries contact the front desk or WhatsApp ' . $instPhone . '.',
        '4. Please carry this receipt during any fee-related queries.',
    ];
    $ty = 189;
    foreach ($terms as $t) {
        $pdf->SetXY(14, $ty);
        $pdf->Cell(130, 5, $t, 0, 1, 'L');
        $ty += 5;
    }

    // ────────────────────────────────────────────────────────────────
    // SIGNATURE + STAMP AREA
    // ────────────────────────────────────────────────────────────────
    // Left: Received by
    $pdf->SetDrawColor(200, 210, 230);
    $pdf->SetLineWidth(0.3);
    $pdf->Line(14, 222, 80, 222);
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor(...$gray);
    $pdf->SetXY(14, 224);
    $pdf->Cell(66, 5, 'Received by / Staff signature', 0, 1, 'C');

    // Right: Stamp area
    $pdf->SetDrawColor(200, 210, 230);
    $pdf->DashedRect(140, 210, 56, 26, 2, 2);
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor(...$gray);
    $pdf->SetXY(140, 222);
    $pdf->Cell(56, 5, 'Official stamp', 0, 1, 'C');

    // ────────────────────────────────────────────────────────────────
    // DOTTED TEAR LINE
    // ────────────────────────────────────────────────────────────────
    $pdf->SetDrawColor(180, 195, 220);
    $pdf->SetLineWidth(0.4);
    $pdf->SetLineDash([3, 3]);
    $pdf->Line(0, 244, $W, 244);
    $pdf->SetLineDash(); // reset
    $pdf->SetFont('helvetica', '', 7);
    $pdf->SetTextColor(...$gray);
    $pdf->SetXY(0, 245);
    $pdf->Cell($W, 4, '- - - - - - Student Copy (Tear here) - - - - - -', 0, 1, 'C');

    // ────────────────────────────────────────────────────────────────
    // STUDENT COPY (mini — bottom quarter)
    // ────────────────────────────────────────────────────────────────
    $sy = 250;
    $pdf->SetFillColor(...$blue);
    $pdf->Rect(0, $sy, $W, 8, 'F');
    $pdf->SetTextColor(...$white);
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetXY(0, $sy + 1);
    $pdf->Cell($W, 6, $instName . '  —  Student Copy', 0, 1, 'C');

    $pdf->SetTextColor(...$dark);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetXY(14, $sy + 12);
    $pdf->Cell(60, 5, $stuName, 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor(...$gray);
    $pdf->Cell(50, 5, 'Receipt: ' . $invNo, 0, 0, 'C');
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetTextColor(...$statusColor);
    $pdf->Cell(50, 5, 'Rs. ' . number_format($paidAmt, 2) . '  ' . strtoupper($status), 0, 0, 'R');

    $pdf->SetTextColor(...$gray);
    $pdf->SetFont('helvetica', '', 7.5);
    $pdf->SetXY(14, $sy + 19);
    $pdf->Cell(60, 5, 'Batch: ' . $batch . '  |  Seat: ' . $seat, 0, 0, 'L');
    $pdf->Cell(80, 5, 'Date: ' . $dateDisp . '  |  Mode: ' . $mode, 0, 0, 'C');
    if ($balance > 0) {
        $pdf->SetTextColor(...$red);
        $pdf->Cell(40, 5, 'Bal due: Rs. ' . number_format($balance, 2), 0, 0, 'R');
    }

    // ── Footer bar ──
    $pdf->SetFillColor(...$dkblue);
    $pdf->Rect(0, $H - 8, $W, 8, 'F');
    $pdf->SetTextColor(...$white);
    $pdf->SetFont('helvetica', '', 7);
    $pdf->SetXY(0, $H - 7);
    $pdf->Cell($W, 5, 'Generated by OPTMS Tech ERP  •  ' . $instName . '  •  ' . date('d M Y H:i'), 0, 0, 'C');

    // ── Save PDF ──
    if (!is_dir(RECEIPTS_DIR)) mkdir(RECEIPTS_DIR, 0755, true);
    $filename = 'receipt_' . preg_replace('/[^a-z0-9]/i', '_', $invNo) . '_' . date('Ymd') . '.pdf';
    $pdf->Output(RECEIPTS_DIR . $filename, 'F');

    return $filename;
}

// ══════════════════════════════════════════════════════════════════════════
// HELPERS
// ══════════════════════════════════════════════════════════════════════════

function loadSettings($pdo) {
    try {
        $row = $pdo->query("SELECT * FROM settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        return $row ?: [];
    } catch (Exception $e) { return []; }
}

function buildInvoiceFromInput($pdo, $input) {
    if (empty($input['student_id'])) return null;
    $stmt = $pdo->prepare("
        SELECT s.*, b.name AS batch_name, b.start_time, b.end_time
        FROM students s
        LEFT JOIN batches b ON b.id = s.batch_id
        WHERE s.id = ? LIMIT 1
    ");
    $stmt->execute([$input['student_id']]);
    $s = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$s) return null;

    return array_merge($s, [
        'id'           => $input['invoice_id'] ?? null,
        'inv_no'       => $input['inv_no']  ?? ('RCP-' . date('YmdHis')),
        'invoice_date' => $input['date']    ?? date('Y-m-d'),
        'base_fee'     => $input['base_fee']  ?? $s['base_fee']  ?? 0,
        'discount'     => $input['discount']  ?? 0,
        'net_fee'      => $input['net_fee']   ?? $s['net_fee']   ?? 0,
        'paid_amt'     => $input['paid_amt']  ?? 0,
        'balance'      => $input['balance']   ?? 0,
        'mode'         => $input['mode']      ?? 'cash',
        'status'       => $input['status']    ?? 'partial',
    ]);
}

function numberToWords($n) {
    if ($n == 0) return 'Zero';
    $ones = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine',
             'Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen',
             'Seventeen','Eighteen','Nineteen'];
    $tens = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
    if ($n < 0) return 'Minus ' . numberToWords(-$n);
    if ($n < 20) return $ones[$n];
    if ($n < 100) return $tens[(int)($n/10)] . ($n%10 ? ' ' . $ones[$n%10] : '');
    if ($n < 1000) return $ones[(int)($n/100)] . ' Hundred' . ($n%100 ? ' ' . numberToWords($n%100) : '');
    if ($n < 100000) return numberToWords((int)($n/1000)) . ' Thousand' . ($n%1000 ? ' ' . numberToWords($n%1000) : '');
    if ($n < 10000000) return numberToWords((int)($n/100000)) . ' Lakh' . ($n%100000 ? ' ' . numberToWords($n%100000) : '');
    return numberToWords((int)($n/10000000)) . ' Crore' . ($n%10000000 ? ' ' . numberToWords($n%10000000) : '');
}
