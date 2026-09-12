<?php

date_default_timezone_set('Asia/Tehran');

require_once '../../config/database.php';
require_once '../../helpers/auth.php';
require_once '../../helpers/validation.php';
require_once '../../helpers/notification.php';

header('Content-Type: application/json; charset=utf-8');

/*
|--------------------------------------------------------------------------
| Check HTTP Method
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

$user = requireAuth($pdo);

$userId = (int) $user['id'];

/*
|--------------------------------------------------------------------------
| Get Input
|--------------------------------------------------------------------------
*/

$orderId = $_POST['order_id'] ?? '';

/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

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

/*
|--------------------------------------------------------------------------
| Start Transaction
|--------------------------------------------------------------------------
*/

try {

    $pdo->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | Lock the order row
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT
            id,
            group_id,
            created_by,
            title
        FROM orders
        WHERE id = :order_id
        FOR UPDATE
    ");

    $stmt->execute([
        'order_id' => $orderId
    ]);

    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    /*
    |--------------------------------------------------------------------------
    | Check Order Exists
    |--------------------------------------------------------------------------
    */

    if (!$order) {

        $pdo->rollBack();

        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Order not found'
        ]);

        exit;
    }

    $groupId = (int) $order['group_id'];
    $createdBy = (int) $order['created_by'];
    $orderTitle = $order['title'];

    /*
    |--------------------------------------------------------------------------
    | Check User Is Group Member
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT id
        FROM group_members
        WHERE group_id = :group_id
        AND user_id = :user_id
        LIMIT 1
    ");

    $stmt->execute([
        'group_id' => $groupId,
        'user_id' => $userId
    ]);

    $membership = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$membership) {

        $pdo->rollBack();

        http_response_code(403);

        echo json_encode([
            'success' => false,
            'message' => 'You are not a member of this group'
        ]);

        exit;
    }

   /*
|--------------------------------------------------------------------------
| Check Active Assignment
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        user_id,
        assigned_at
    FROM order_assignments
    WHERE order_id = :order_id
    AND completed_at IS NULL
    AND cancelled_at IS NULL
    LIMIT 1
");

$stmt->execute([
    'order_id' => $orderId
]);

$activeAssignment = $stmt->fetch(PDO::FETCH_ASSOC);

    /*
    |--------------------------------------------------------------------------
    | Order Already Has An Active Assignment
    |--------------------------------------------------------------------------
    */

    if ($activeAssignment) {

        if ((int) $activeAssignment['user_id'] === $userId) {

            $pdo->rollBack();

            http_response_code(409);

            echo json_encode([
                'success' => false,
                'message' => 'You are already responsible for this order'
            ]);

            exit;
        }

        $pdo->rollBack();

        http_response_code(409);

        echo json_encode([
            'success' => false,
            'message' => 'This order is already assigned to another user'
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Create Assignment
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        INSERT INTO order_assignments (
            order_id,
            user_id
        )
        VALUES (
            :order_id,
            :user_id
        )
    ");

    $stmt->execute([
        'order_id' => $orderId,
        'user_id' => $userId
    ]);

    $assignmentId = $pdo->lastInsertId();
    $stmt = $pdo->prepare("
        UPDATE orders
     SET
        status = 'reserved',
        updated_at = CURRENT_TIMESTAMP
        WHERE id = :order_id
");

$stmt->execute([
    'order_id' => $orderId
]);
    /*
    |--------------------------------------------------------------------------
    | Notify Group Members
    |--------------------------------------------------------------------------
    |
    | The user who reserved the order does not receive a notification.
    |
    */

    $stmt = $pdo->prepare("
        SELECT user_id
        FROM group_members
        WHERE group_id = :group_id
          AND user_id != :user_id
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
            'order_reserved',
            'سفارش رزرو شد',
            "سفارش «{$orderTitle}» توسط یکی از اعضای گروه رزرو شد.",
            $groupId,
            $orderId
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Commit Transaction
    |--------------------------------------------------------------------------
    */

    $pdo->commit();

    /*
    |--------------------------------------------------------------------------
    | Success Response
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        'success' => true,
        'message' => 'Order assigned successfully',
        'data' => [
            'assignment' => [
                'id' => $assignmentId,
                'order_id' => $orderId,
                'user_id' => $userId
            ]
        ]
    ]);

} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Server error'
    ]);
}