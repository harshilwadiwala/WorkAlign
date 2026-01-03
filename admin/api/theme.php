<?php
require_once '../config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Get JSON data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['dark_mode'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing dark_mode parameter']);
    exit();
}

// Save to session
$_SESSION['dark_mode'] = $data['dark_mode'] === true;

echo json_encode(['success' => true, 'dark_mode' => $_SESSION['dark_mode']]);
?>
