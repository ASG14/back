<?php

date_default_timezone_set('Asia/Tehran');

require_once '../../config/database.php';
require_once '../../helpers/auth.php';
require_once '../../helpers/validation.php';
require_once '../../helpers/notification.php';

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

$groupId = $input['group_id'] ?? '';
$title = trim($input['title'] ?? '');
$quantity = trim($input['quantity'] ?? '');
$priority = $input['priority'] ?? 'medium';
$deadline = $input['deadline'] ?? null;

$errors = validateRequired([
    'group_id' => $groupId,
    'title' => $title
]);

if (!empty($errors)) {
    validationError($errors);
}

if (!validateId($groupId)) {
    validationError([
        'group_id' => 'Invalid group ID'
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
    $date = DateTime::createFromFormat(
        'Y-m-d H:i:s',
        $deadline,
        new DateTimeZone('Asia/Tehran')
    );

    if (
        !$date ||
        $date->format('Y-m-d H:i:s') !== $deadline
    ) {
        validationError([
            'deadline' => 'Invalid deadline format'
        ]);
    }
} else {
    $deadline = null;
}

$groupId = (int) $groupId;

try {

    $pdo->beginTransaction();

    /*
     * Check group membership
     */
    $stmt = $pdo->prepare("
        SELECT 1
        FROM group_members
        WHERE group_id = :group_id
          AND user_id = :user_id
        LIMIT 1
    ");

    $stmt->execute([
        'group_id' => $groupId,
        'user_id' => $userId
    ]);

    if (!$stmt->fetchColumn()) {
        $pdo->rollBack();

        http_response_code(403);

        echo json_encode([
            'success' => false,
            'message' => 'You are not a member of this group'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    /*
     * Create order
     */
    $stmt = $pdo->prepare("
        INSERT INTO orders (
            group_id,
            created_by,
            title,
            quantity,
            priority,
            deadline
        )
        VALUES (
            :group_id,
            :created_by,
            :title,
            :quantity,
            :priority,
            :deadline
        )
    ");

    $stmt->execute([
        'group_id' => $groupId,
        'created_by' => $userId,
        'title' => $title,
        'quantity' => $quantity !== '' ? $quantity : null,
        'priority' => $priority,
        'deadline' => $deadline
    ]);

    $orderId = (int) $pdo->lastInsertId();

    /*
     * Get created order
     */
    $stmt = $pdo->prepare("
        SELECT
            id,
            group_id,
            created_by,
            title,
            quantity,
            priority,
            status,
            deadline,
            created_at,
            updated_at
        FROM orders
        WHERE id = :order_id
        LIMIT 1
    ");

    $stmt->execute([
        'order_id' => $orderId
    ]);

    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    /*
     * Get group members except creator
     */
    $stmt = $pdo->prepare("
        SELECT user_id
        FROM group_members
        WHERE group_id = :group_id
          AND user_id <> :user_id
    ");

    $stmt->execute([
        'group_id' => $groupId,
        'user_id' => $userId
    ]);

    $members = $stmt->fetchAll(PDO::FETCH_COLUMN);

    /*
     * Create notifications
     *
     * actor_user_id = creator
     */
    foreach ($members as $memberId) {
        createNotification(
            $pdo,
            (int) $memberId,
            $userId,
            'order_created',
            'سفارش جدید',
            "سفارش «{$title}» به گروه اضافه شد.",
            $groupId,
            $orderId
        );
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'data' => [
            'order' => $order
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Server error'
    ], JSON_UNESCAPED_UNICODE);
}