<?php
/**
 * OPTMS Tech ERP — api/index.php  ADDITIONS
 * ─────────────────────────────────────────────────────────────────
 * INSTRUCTIONS:
 *   Find your existing switch($action) block in api/index.php
 *   and paste the cases below into it.
 *
 *   Also add this ONE line near the top of api/index.php
 *   (after session_start, before the switch):
 *
 *       require_once __DIR__ . '/pdf_receipt.php';
 *
 *   Wait — actually, DO NOT require_once at top (it calls
 *   session_start and exits). Instead, just add these three
 *   cases to your switch and they call the functions directly.
 *   Copy pdf_receipt.php to your api/ folder and add at top:
 *
 *       // PDF functions — include once, functions are available
 *       if (!function_exists('generatePDF')) {
 *           require_once __DIR__ . '/pdf_receipt.php';
 *       }
 * ─────────────────────────────────────────────────────────────────
 */

// ══════════════════════════════════════════════════════════════════
// PASTE THESE CASES into your existing switch($action) { ... }
// ══════════════════════════════════════════════════════════════════

    case 'generate_receipt':
        $input    = json_decode(file_get_contents('php://input'), true) ?? [];
        $settings = loadSettings($pdo);

        // Load invoice from DB if invoice_id provided
        if (!empty($input['invoice_id'])) {
            $stmt = $pdo->prepare("
                SELECT i.*, s.fname, s.lname, s.phone, s.seat, s.seat_type,
                       s.course, s.join_date,
                       b.name AS batch_name,
                       st.name, st.phone AS inst_phone,
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
        } else {
            $inv = buildInvoiceFromInput($pdo, $input);
        }

        if (!$inv) { echo json_encode(['error' => 'Invoice not found']); break; }

        $filename = generatePDF($inv, $settings);
        if (!$filename) { echo json_encode(['error' => 'PDF generation failed. Check TCPDF is installed.']); break; }

        // Save pdf_path back to invoices table
        if (!empty($inv['id'])) {
            $pdo->prepare("UPDATE invoices SET pdf_path = ? WHERE id = ?")
                ->execute([$filename, $inv['id']]);
        }

        echo json_encode([
            'success'  => true,
            'url'      => '../receipts/' . $filename,
            'filename' => $filename
        ]);
        break;

    case 'get_receipt_url':
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $id    = $input['invoice_id'] ?? null;
        if (!$id) { echo json_encode(['url' => null]); break; }
        $stmt = $pdo->prepare("SELECT pdf_path FROM invoices WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $url = ($row && $row['pdf_path'] && file_exists('../receipts/' . $row['pdf_path']))
             ? '../receipts/' . $row['pdf_path']
             : null;
        echo json_encode(['url' => $url]);
        break;

    case 'bulk_receipts_zip':
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $month = $input['month'] ?? date('Y-m');
        if (!class_exists('ZipArchive')) { echo json_encode(['error' => 'ZipArchive not available']); break; }

        $stmt = $pdo->prepare("
            SELECT i.*, s.fname, s.lname, s.phone, s.seat, s.seat_type, s.course, s.join_date,
                   b.name AS batch_name
            FROM invoices i
            JOIN students s ON s.id = i.student_id
            LEFT JOIN batches b ON b.id = s.batch_id
            WHERE DATE_FORMAT(i.invoice_date, '%Y-%m') = ?
            ORDER BY i.invoice_date DESC
        ");
        $stmt->execute([$month]);
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $settings = loadSettings($pdo);

        $zip     = new ZipArchive();
        $zipName = 'receipts_' . $month . '.zip';
        $zipPath = '../receipts/' . $zipName;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $count = 0;
        foreach ($invoices as $inv) {
            if (empty($inv['pdf_path']) || !file_exists('../receipts/' . $inv['pdf_path'])) {
                $inv['pdf_path'] = generatePDF($inv, $settings);
                if (!empty($inv['id'])) {
                    $pdo->prepare("UPDATE invoices SET pdf_path=? WHERE id=?")->execute([$inv['pdf_path'], $inv['id']]);
                }
            }
            if ($inv['pdf_path'] && file_exists('../receipts/' . $inv['pdf_path'])) {
                $zip->addFile('../receipts/' . $inv['pdf_path'], $inv['pdf_path']);
                $count++;
            }
        }
        $zip->close();

        echo json_encode([
            'success'  => true,
            'url'      => '../receipts/' . $zipName,
            'count'    => $count,
            'filename' => $zipName
        ]);
        break;


// ══════════════════════════════════════════════════════════════════
// ALSO ADD THIS to your invoices table (run once in phpMyAdmin):
// ══════════════════════════════════════════════════════════════════
/*
ALTER TABLE invoices ADD COLUMN pdf_path VARCHAR(255) DEFAULT NULL;
*/
