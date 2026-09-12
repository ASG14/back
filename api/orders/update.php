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

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$orderId = $input['order_id'] ?? '';
$title = trim($input['title'] ?? '');
$quantity = trim($input['quantity'] ?? '');
$priority = $input['priority'] ?? 'medium';
$deadline = $input['deadline'] ?? null;

$errors = validateRequired([
    'order_id' => $orderId,
    'title' => $title
]);

if (!empty($errors)) {
    validationError($errors);
}

if (!validateId($orderId)) {
    validationError([
        'order_id' => 'Invalid order ID'
    ]);
}

if (!validateOrderTitle($title)) {
    validationError([
        'title' => 'Order title is invalid'
    ]);
}

if ($quantity !== '' && strlen($quantity) > 100) {
    validationError([
        'quantity' => 'Quantity must not exceed 100 characters'
    ]);
}

if (!in_array($priority, ['low', 'medium', 'high'], true)) {
    validationError([
        'priority' => 'Invalid priority'
    ]);
}

if ($deadline !== null && $deadline !== '') {
    $date = DateTime::createFromFormat('Y-m-d H:i:s', $deadline);

    if (!$date || $date->format('Y-m-d H:i:s') !== $deadline) {
        validationError([
            'deadline' => 'Invalid deadline format'
        ]);
    }
} else {
    $deadline = null;
}

$orderId = (int) $orderId;

try {

    /*
     * Only order creator can update the order.
     */
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
            'message' => 'You are not allowed to update this order'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    /*
     * Update order
     */
    $stmt = $pdo->prepare("
        UPDATE orders
        SET
            title = :title,
            quantity = :quantity,
            priority = :priority,
            deadline = :deadline
        WHERE id = :order_id
    ");

    $stmt->execute([
        'title' => $title,
        'quantity' => $quantity !== '' ? $quantity : null,
        'priority' => $priority,
        'deadline' => $deadline,
        'order_id' => $orderId
    ]);

    echo json_encode([
        'success' => true,
        'data' => [
            'message' => 'Order updated successfully'
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Server error'
    ], JSON_UNESCAPED_UNICODE);
}