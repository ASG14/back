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
    | Lock Order
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT
            id,
            group_id,
            title,
            status
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
        FOR UPDATE
    ");

    $stmt->execute([
        'order_id' => $orderId
    ]);

    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

    /*
    |--------------------------------------------------------------------------
    | No Active Assignment
    |--------------------------------------------------------------------------
    */

    if (!$assignment) {

        $pdo->rollBack();

        http_response_code(409);

        echo json_encode([
            'success' => false,
            'message' => 'This order has no active assignment'
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Only Responsible User Can Cancel
    |--------------------------------------------------------------------------
    */

    if ((int) $assignment['user_id'] !== $userId) {

        $pdo->rollBack();

        http_response_code(403);

        echo json_encode([
            'success' => false,
            'message' => 'Only the responsible user can cancel this assignment'
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Cancel Assignment
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        UPDATE order_assignments
        SET cancelled_at = CURRENT_TIMESTAMP
        WHERE id = :assignment_id
    ");

    $stmt->execute([
        'assignment_id' => $assignment['id']
    ]);

    /*
    |--------------------------------------------------------------------------
    | Return Order To Pending
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        UPDATE orders
        SET
            status = 'pending',
            updated_at = CURRENT_TIMESTAMP
        WHERE id = :order_id
    ");

    $stmt->execute([
        'order_id' => $orderId
    ]);

    /*
    |--------------------------------------------------------------------------
    | Notify Other Group Members
    |--------------------------------------------------------------------------
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
            'order_unassigned',
            'لغو مسئولیت سفارش',
            "مسئولیت سفارش «{$orderTitle}» لغو شد.",
            $groupId,
            $orderId
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Commit
    |--------------------------------------------------------------------------
    */

    $pdo->commit();

    /*
    |--------------------------------------------------------------------------
    | Success
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        'success' => true,
        'message' => 'Order assignment cancelled successfully'
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