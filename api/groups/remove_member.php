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
$memberId = $input['member_id'] ?? '';

$errors = validateRequired([
    'group_id' => $groupId,
    'member_id' => $memberId
]);

if (!empty($errors)) {
    validationError($errors);
}

if (!validateId($groupId)) {
    validationError([
        'group_id' => 'Invalid group ID'
    ]);
}

if (!validateId($memberId)) {
    validationError([
        'member_id' => 'Invalid member ID'
    ]);
}

$groupId = (int) $groupId;
$memberId = (int) $memberId;

try {

    $pdo->beginTransaction();

    /*
     * Only group creator can remove members
     */
    $stmt = $pdo->prepare("
    SELECT id, title
    FROM `groups`
    WHERE id = :group_id
      AND creator_id = :user_id
    LIMIT 1
");

    $stmt->execute([
        'group_id' => $groupId,
        'user_id' => $userId
    ]);

    $group = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$group) {
        $pdo->rollBack();

        http_response_code(403);

        echo json_encode([
            'success' => false,
            'message' => 'You are not allowed to remove members from this group'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    /*
     * Prevent creator from removing himself
     */
    if ($memberId === $userId) {
        $pdo->rollBack();

        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'Group creator cannot be removed'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    /*
     * Find member before deleting
     */
    $stmt = $pdo->prepare("
        SELECT
            u.id,
            u.phone,
            u.first_name,
            u.last_name
        FROM group_members gm
        INNER JOIN users u
            ON u.id = gm.user_id
        WHERE gm.group_id = :group_id
          AND gm.user_id = :member_id
        LIMIT 1
    ");

    $stmt->execute([
        'group_id' => $groupId,
        'member_id' => $memberId
    ]);

    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$member) {
        $pdo->rollBack();

        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Member not found in this group'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    /*
     * Find ACTIVE assignments of the member.
     *
     * Active assignment means:
     * - not completed
     * - not cancelled
     *
     * Cancelled and completed assignments are historical
     * records and must remain untouched.
     */
    $stmt = $pdo->prepare("
        SELECT DISTINCT
            oa.id,
            oa.order_id
        FROM order_assignments oa
        INNER JOIN orders o
            ON o.id = oa.order_id
        WHERE o.group_id = :group_id
          AND oa.user_id = :member_id
          AND oa.completed_at IS NULL
          AND oa.cancelled_at IS NULL
        FOR UPDATE
    ");

    $stmt->execute([
        'group_id' => $groupId,
        'member_id' => $memberId
    ]);

    $activeAssignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /*
     * Cancel active assignments instead of deleting them.
     *
     * This preserves assignment history.
     */
    if (!empty($activeAssignments)) {

        $assignmentIds = array_map(
            fn($assignment) => (int) $assignment['id'],
            $activeAssignments
        );

        $placeholders = implode(
            ',',
            array_fill(0, count($assignmentIds), '?')
        );

        $stmt = $pdo->prepare("
            UPDATE order_assignments
            SET cancelled_at = CURRENT_TIMESTAMP
            WHERE id IN ($placeholders)
              AND completed_at IS NULL
              AND cancelled_at IS NULL
        ");

        $stmt->execute($assignmentIds);

        /*
         * Return affected reserved orders to pending.
         */
        $orderIds = array_map(
            fn($assignment) => (int) $assignment['order_id'],
            $activeAssignments
        );

        $orderPlaceholders = implode(
            ',',
            array_fill(0, count($orderIds), '?')
        );

        $params = array_merge(
            [$groupId],
            $orderIds
        );

        $stmt = $pdo->prepare("
            UPDATE orders
            SET
                status = 'pending',
                updated_at = CURRENT_TIMESTAMP
            WHERE group_id = ?
              AND status = 'reserved'
              AND id IN ($orderPlaceholders)
        ");

        $stmt->execute($params);
    }

    /*
     * Find orders created by the removed member
     */
    $stmt = $pdo->prepare("
        SELECT
            id
        FROM orders
        WHERE group_id = :group_id
          AND created_by = :member_id
    ");

    $stmt->execute([
        'group_id' => $groupId,
        'member_id' => $memberId
    ]);

    $createdOrderIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    /*
     * Delete orders created by the removed member.
     *
     * order_assignments:
     * ON DELETE CASCADE
     *
     * notifications.order_id:
     * ON DELETE SET NULL
     */
    if (!empty($createdOrderIds)) {

        $placeholders = implode(
            ',',
            array_fill(0, count($createdOrderIds), '?')
        );

        $params = array_merge(
            [$groupId],
            array_map('intval', $createdOrderIds)
        );

        $stmt = $pdo->prepare("
            DELETE FROM orders
            WHERE group_id = ?
              AND id IN ($placeholders)
        ");

        $stmt->execute($params);
    }

    /*
     * Remove member from group
     */
    $stmt = $pdo->prepare("
        DELETE FROM group_members
        WHERE group_id = :group_id
          AND user_id = :member_id
    ");

    $stmt->execute([
        'group_id' => $groupId,
        'member_id' => $memberId
    ]);

    /*
     * Notify removed member
     */
    createNotification(
        $pdo,
        $memberId,
        'member_removed',
        'از گروه حذف شدید',
        "شما از گروه «{$group['title']}» حذف شدید.",
        $groupId,
        null
    );

    /*
     * Notify remaining members
     */
    $stmt = $pdo->prepare("
        SELECT user_id
        FROM group_members
        WHERE group_id = :group_id
    ");

    $stmt->execute([
        'group_id' => $groupId
    ]);

    $remainingMembers = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $memberName = trim(
        ($member['first_name'] ?? '') . ' ' .
        ($member['last_name'] ?? '')
    );

    if ($memberName === '') {
        $memberName = 'یک کاربر';
    }

    foreach ($remainingMembers as $remainingMemberId) {

        createNotification(
            $pdo,
            (int) $remainingMemberId,
            'member_removed',
            'عضو از گروه حذف شد',
            "{$memberName} از گروه «{$group['title']}» حذف شد.",
            $groupId,
            null
        );
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'data' => [
            'message' => 'Member removed successfully'
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

