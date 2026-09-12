<?php

date_default_timezone_set('Asia/Tehran');

require_once '../../config/database.php';
require_once '../../helpers/auth.php';
require_once '../../helpers/validation.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$user = requireAuth($pdo);
$userId = (int) $user['id'];

$orderId = $_POST['order_id'] ?? '';

$errors = validateRequired([
    'order_id' => $orderId
]);

if (!empty($errors)) {
    validationError($errors);
}

if (!validateId($orderId)) {
    validationError([
        'order_id' => 'Invalid order ID'
    ]);
}

$orderId = (int) $orderId;

try {

    $stmt = $pdo->prepare("
        SELECT id
        FROM orders
        WHERE id = :order_id
        AND created_by = :user_id
        LIMIT 1
    ");

    $stmt->execute([
        'order_id' => $orderId,
        'user_id' => $userId
    ]);

    if (!$stmt->fetch()) {
        http_response_code(403);

        echo json_encode([
            'success' => false,
            'message' => 'You are not allowed to delete this order'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $stmt = $pdo->prepare("
        DELETE FROM orders
        WHERE id = :order_id
    ");

    $stmt->execute([
        'order_id' => $orderId
    ]);

    echo json_encode([
        'success' => true,
        'data' => [
            'message' => 'Order deleted successfully'
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Server error'
    ], JSON_UNESCAPED_UNICODE);
}