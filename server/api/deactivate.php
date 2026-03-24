<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db/database.php';

$input = json_decode(file_get_contents('php://input'), true);
$license_key = $input['license_key'] ?? '';
$site_url = $input['site_url'] ?? '';

if (empty($license_key) || empty($site_url)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
    exit;
}

$db = new WPTS_DB(__DIR__ . '/../db/licenses.sqlite');
$db->deactivate($license_key, $site_url);
echo json_encode(['status' => 'deactivated', 'message' => 'License deactivated']);
