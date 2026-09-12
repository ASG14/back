<?php

date_default_timezone_set('Asia/Tehran');

require_once '../../config/database.php';
require_once '../../helpers/auth.php';
require_once '../../helpers/validation.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed',
        'data' => []
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$user = requireAuth($pdo);

$userId = (int) $user['id'];

$groupId = $_GET['group_id'] ?? '';

$errors = validateRequired([
    'group_id' => $groupId
]);

if (!empty($errors)) {
    validationError($errors);
}

if (!validateId($groupId)) {
    validationError([
        'group_id' => 'Invalid group ID'
    ]);
}

$groupId = (int) $groupId;

try {

    // بررسی عضویت کاربر در گروه
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

    if (!$stmt->fetch()) {

        http_response_code(403);

        echo json_encode([
            'success' => false,
            'message' => 'You are not a member of this group'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    /*
     * دریافت سفارش‌ها
     *
     * برای سفارش‌های pending و reserved فقط assignment فعال
     * برگردانده می‌شود.
     *
     * assignment فعال:
     * completed_at IS NULL
     * cancelled_at IS NULL
     *
     * برای سفارش completed آخرین assignment برگردانده می‌شود
     * تا مسئول خرید در تاریخچه حفظ شود.
     *
     * اگر یک سفارش pending باشد و آخرین assignment آن cancelled شده
     * باشد، assigned_user_id برابر NULL خواهد بود.
     */

    $stmt = $pdo->prepare("
        SELECT
            o.id,
            o.group_id,
            o.created_by,
            o.title,
            o.quantity,
            o.priority,
            o.status,
            o.deadline,
            o.created_at,
            o.updated_at,

            oa.user_id AS assigned_user_id,

            CONCAT(
                COALESCE(u.first_name, ''),
                CASE
                    WHEN u.first_name IS NOT NULL
                         AND u.first_name <> ''
                         AND u.last_name IS NOT NULL
                         AND u.last_name <> ''
                    THEN ' '
                    ELSE ''
                END,
                COALESCE(u.last_name, '')
            ) AS assigned_user_name

        FROM orders AS o

        LEFT JOIN order_assignments AS oa
            ON oa.id = (
                SELECT MAX(oa2.id)
                FROM order_assignments AS oa2
                WHERE oa2.order_id = o.id
                  AND (
                      (
                          o.status IN ('pending', 'reserved')
                          AND oa2.completed_at IS NULL
                          AND oa2.cancelled_at IS NULL
                      )
                      OR
                      (
                          o.status = 'completed'
                      )
                  )
            )

        LEFT JOIN users AS u
            ON u.id = oa.user_id

        WHERE o.group_id = :group_id

        ORDER BY o.created_at DESC
    ");

    $stmt->execute([
        'group_id' => $groupId
    ]);

    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'orders' => $orders
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Server error'
    ], JSON_UNESCAPED_UNICODE);

}