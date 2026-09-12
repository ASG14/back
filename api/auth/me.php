<?php

date_default_timezone_set('Asia/Tehran');

require_once '../../config/database.php';
require_once '../../helpers/auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);

    exit;
}

$user = requireAuth($pdo);

echo json_encode([
    'success' => true,
    'data' => [
        'user' => [
            'id' => $user['id'],
            'phone' => $user['phone'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name']
        ]
    ]
]);