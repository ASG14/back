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

    $pdo->beginTransaction();

    /*
     * Lock order
     */
    $stmt = $pdo->prepare("
        SELECT
            id,
            group_id,
            title,
            status
        FROM orders
        WHERE id = :order_id
        LIMIT 1
        FOR UPDATE
    ");

    $stmt->execute([
        'order_id' => $orderId
    ]);

    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        $pdo->rollBack();

        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Order not found'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $groupId = (int) $order['group_id'];
    $orderTitle = $order['title'];

    if ($order['status'] === 'completed') {
        $pdo->rollBack();

        http_response_code(409);

        echo json_encode([
            'success' => false,
            'message' => 'Order is already completed'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    /*
     * Check membership
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
     * Find active assignment
     */
    $stmt = $pdo->prepare("
        SELECT
            id,
            user_id
        FROM order_assignments
        WHERE order_id = :order_id
          AND completed_at IS NULL
          AND cancelled_at IS NULL
        LIMIT 1
        FOR UPDATE
    ");

    $stmt->execute([
        'order_id' => $orderId
    ]);

    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

    /*
     * If assigned, only assigned user can complete.
     */
    if ($assignment) {

        $assignedUserId = (int) $assignment['user_id'];

        if ($assignedUserId !== $userId) {
            $pdo->rollBack();

            http_response_code(403);

            echo json_encode([
                'success' => false,
                'message' => 'You are not responsible for this order'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        /*
         * Complete assignment
         */
        $stmt = $pdo->prepare("
            UPDATE order_assignments
            SET completed_at = CURRENT_TIMESTAMP
            WHERE id = :assignment_id
              AND completed_at IS NULL
              AND cancelled_at IS NULL
        ");

        $stmt->execute([
            'assignment_id' => (int) $assignment['id']
        ]);
    }

    /*
     * Complete order
     */
    $stmt = $pdo->prepare("
        UPDATE orders
        SET
            status = 'completed',
            updated_at = CURRENT_TIMESTAMP
        WHERE id = :order_id
          AND status <> 'completed'
    ");

    $stmt->execute([
        'order_id' => $orderId
    ]);

    /*
     * Notify other members
     *
     * actor_user_id = user who completed the order
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

    foreach ($members as $memberId) {

        createNotification(
            $pdo,
            (int) $memberId,
            $userId,
            'order_completed',
            'سفارش خریداری شد',
            "سفارش «{$orderTitle}» خریداری شد.",
            $groupId,
            $orderId
        );
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'data' => [
            'message' => 'Order completed successfully'
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