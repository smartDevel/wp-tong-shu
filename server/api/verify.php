<?php
/**
 * License Verification API
 * 
 * Endpoints:
 *   POST /api/verify.php — Verify license
 *   POST /api/activate.php — Activate license
 *   POST /api/deactivate.php — Deactivate license
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../db/database.php';

$secret = 'WPTS-SECRET-KEY-CHANGE-ME'; // Change this!

$input = json_decode(file_get_contents('php://input'), true);
$license_key = $input['license_key'] ?? '';
$site_url = $input['site_url'] ?? '';

if (empty($license_key) || empty($site_url)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing license_key or site_url']);
    exit;
}

$db = new WPTS_DB(__DIR__ . '/../db/licenses.sqlite');

$license = $db->get_license($license_key);

if (!$license) {
    echo json_encode(['status' => 'invalid', 'message' => 'License key not found']);
    exit;
}

if ($license['status'] === 'expired') {
    echo json_encode(['status' => 'expired', 'message' => 'License expired', 'expires' => $license['expires_at']]);
    exit;
}

// Check site activation
$activations = $db->get_activations($license_key);
$is_activated = false;
foreach ($activations as $act) {
    if ($act['site_url'] === $site_url) {
        $is_activated = true;
        break;
    }
}

if ($license['max_sites'] > 0 && count($activations) >= $license['max_sites'] && !$is_activated) {
    echo json_encode(['status' => 'limit', 'message' => 'Max sites reached', 'max_sites' => $license['max_sites']]);
    exit;
}

// Auto-activate if not yet activated
if (!$is_activated) {
    $db->activate($license_key, $site_url);
}

echo json_encode([
    'status' => 'active',
    'message' => 'License valid',
    'license' => [
        'key' => $license_key,
        'type' => $license['type'],
        'expires' => $license['expires_at'],
        'sites' => count($activations) + ($is_activated ? 0 : 1),
        'max_sites' => $license['max_sites'],
    ],
]);
