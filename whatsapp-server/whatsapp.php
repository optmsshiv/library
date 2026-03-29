<?php
/**
 * ═══════════════════════════════════════════════════════
 *  OPTMS Tech ERP — WhatsApp Proxy
 *  Bridges PHP backend → local Node whatsapp-web.js server
 *  Node server runs at http://localhost:3001
 * ═══════════════════════════════════════════════════════
 */
session_start();
if (empty($_SESSION['staff_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');

$db     = getDB();
$action = $_GET['action'] ?? '';
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

define('WA_NODE', 'http://127.0.0.1:3001');

function curlNode($path, $method = 'GET', $data = null) {
    $url = WA_NODE . $path;
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }
    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    curl_close($ch);
    if ($err) return ['success' => false, 'error' => 'WhatsApp server offline. Run whatsapp-server/START-WINDOWS.bat first.'];
    $json = json_decode($resp, true);
    return $json ?? ['success' => false, 'error' => 'Bad response'];
}

function normalizePhone($phone) {
    $clean = preg_replace('/\D/', '', $phone);
    if (strlen($clean) === 10) $clean = '91' . $clean;
    if (substr($clean, 0, 1) === '0') $clean = '91' . substr($clean, 1);
    return $clean;
}

function logWA($db, $to, $preview, $type, $status, $error = null) {
    try {
        $db->prepare("INSERT INTO wa_send_log (sent_to, preview, type, status, gateway, error_msg) VALUES (?,?,?,?,?,?)")
           ->execute([$to, mb_substr($preview, 0, 80), $type, $status, 'whatsapp-web', $error]);
    } catch (Exception $e) {
        try { $db->prepare("INSERT INTO wa_send_log (sent_to, preview, type) VALUES (?,?,?)")->execute([$to, mb_substr($preview, 0, 80), $type]); } catch (Exception $e2) {}
    }
}

switch ($action) {

    case 'status':
        echo json_encode(curlNode('/status'));
        break;

    case 'qr':
        $r = curlNode('/status');
        if (!empty($r['qr'])) echo json_encode(['success'=>true,'qr'=>$r['qr'],'connected'=>false]);
        elseif (!empty($r['connected'])) echo json_encode(['success'=>true,'connected'=>true,'client'=>$r['client']]);
        else echo json_encode(['success'=>false,'error'=>$r['error']??'Server not ready']);
        break;

    case 'send':
        $to = normalizePhone($input['to'] ?? '');
        $msg = trim($input['message'] ?? '');
        $name = $input['student_name'] ?? $to;
        if (!$to || !$msg) { echo json_encode(['success'=>false,'error'=>'Phone and message required']); break; }
        $r = curlNode('/send', 'POST', ['to'=>$to,'message'=>$msg]);
        logWA($db, $name, $msg, 'single', $r['success']?'sent':'failed', $r['error']??null);
        if ($r['success']) {
            try { $db->prepare("INSERT INTO activity_log (icon,bg,text) VALUES (?,?,?)")->execute(['💬','rgba(37,211,102,.14)',"WhatsApp → <strong>{$name}</strong>"]); } catch(Exception $e){}
        }
        echo json_encode($r);
        break;

    case 'send_bulk':
        $messages = $input['messages'] ?? [];
        if (empty($messages)) { echo json_encode(['success'=>false,'error'=>'No messages']); break; }
        foreach ($messages as &$m) $m['to'] = normalizePhone($m['to']??'');
        unset($m);
        $r = curlNode('/send-bulk','POST',['messages'=>$messages]);
        foreach ($r['results']??[] as $res) logWA($db, $res['name']??$res['to'], '(bulk)', 'bulk', $res['success']?'sent':'failed', $res['error']??null);
        if (!empty($r['sent'])) try { $db->prepare("INSERT INTO activity_log (icon,bg,text) VALUES (?,?,?)")->execute(['📢','rgba(37,211,102,.14)',"Bulk WA: <strong>{$r['sent']} sent</strong>"]); } catch(Exception $e){}
        echo json_encode($r);
        break;

    case 'test':
        $s  = $db->query("SELECT * FROM settings WHERE id=1")->fetch(PDO::FETCH_ASSOC);
        $to = normalizePhone($s['wa_number']??'');
        if (!$to) { echo json_encode(['success'=>false,'error'=>'Set WhatsApp number in Settings']); break; }
        $msg = "✅ *Test from OPTMS ERP*\n\nWhatsApp is connected and working! 🎉\n🏫 ".($s['name']??'Library')."\n⏰ ".date('d M Y, h:i A');
        echo json_encode(curlNode('/send','POST',['to'=>$to,'message'=>$msg]));
        break;

    case 'logout':
        echo json_encode(curlNode('/logout','POST',[]));
        break;

    case 'reconnect':
        echo json_encode(curlNode('/reconnect','POST',[]));
        break;

    case 'get_log':
        try { echo json_encode($db->query("SELECT * FROM wa_send_log ORDER BY created_at DESC LIMIT 30")->fetchAll(PDO::FETCH_ASSOC)); }
        catch (Exception $e) { echo json_encode([]); }
        break;

    default:
        echo json_encode(['success'=>false,'error'=>'Unknown action']);
}
