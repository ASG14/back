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

if (!validateId($groupId)) {
    validationError([
        'group_id' => 'Invalid group ID'
    ]);
}

$groupId = (int) $groupId;

try {

    $pdo->beginTransaction();

    /*
     * Find the group and current user's membership.
     */
    $stmt = $pdo->prepare("
        SELECT
            g.id,
            g.title,
            g.creator_id,
            gm.role
        FROM `groups` AS g
        INNER JOIN group_members AS gm
            ON gm.group_id = g.id
           AND gm.user_id = :user_id
        WHERE g.id = :group_id
        LIMIT 1
    ");

    $stmt->execute([
        'group_id' => $groupId,
        'user_id' => $userId
    ]);

    $membership = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$membership) {
        $pdo->rollBack();

        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'You are not a member of this group'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    /*
     * Owner cannot leave the group.
     * The owner must delete the entire group instead.
     */
    if (
        $membership['role'] === 'owner' ||
        (int) $membership['creator_id'] === $userId
    ) {
        $pdo->rollBack();

        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'Group creator cannot leave the group. Delete the group instead.'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    /*
     * Find active assignments of the member.
     *
     * Only assignments that are currently active are affected.
     * Completed and previously cancelled assignments remain as history.
     */
    $stmt = $pdo->prepare("
        SELECT DISTINCT
            oa.id,
            oa.order_id
        FROM order_assignments AS oa
        INNER JOIN orders AS o
            ON o.id = oa.order_id
        WHERE o.group_id = :group_id
          AND oa.user_id = :user_id
          AND oa.completed_at IS NULL
          AND oa.cancelled_at IS NULL
        FOR UPDATE
    ");

    $stmt->execute([
        'group_id' => $groupId,
        'user_id' => $userId
    ]);

    $activeAssignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $assignmentIds = [];
    $assignedOrderIds = [];

    foreach ($activeAssignments as $assignment) {
        $assignmentIds[] = (int) $assignment['id'];
        $assignedOrderIds[] = (int) $assignment['order_id'];
    }

    /*
     * Cancel active assignments instead of deleting them.
     *
     * This preserves assignment history.
     */
    if (!empty($assignmentIds)) {

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
    }

    /*
     * Return affected reserved orders to pending.
     */
    if (!empty($assignedOrderIds)) {

        $assignedOrderIds = array_values(
            array_unique($assignedOrderIds)
        );

        $placeholders = implode(
            ',',
            array_fill(0, count($assignedOrderIds), '?')
        );

        $params = array_merge(
            [$groupId],
            array_map('intval', $assignedOrderIds)
        );

        $stmt = $pdo->prepare("
            UPDATE orders
            SET
                status = 'pending',
                updated_at = CURRENT_TIMESTAMP
            WHERE group_id = ?
              AND status = 'reserved'
              AND id IN ($placeholders)
        ");

        $stmt->execute($params);
    }

    /*
     * Find orders created by the member that are not completed.
     *
     * Completed orders are historical records and must remain.
     */
    $stmt = $pdo->prepare("
        SELECT
            id
        FROM orders
        WHERE group_id = :group_id
          AND created_by = :user_id
          AND status <> 'completed'
    ");

    $stmt->execute([
        'group_id' => $groupId,
        'user_id' => $userId
    ]);

    $createdOrderIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    /*
     * Delete active/non-completed orders created by the member.
     *
     * Related order_assignments are deleted automatically
     * because of ON DELETE CASCADE.
     *
     * Notifications remain with order_id = NULL because
     * notifications.order_id uses ON DELETE SET NULL.
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
              AND status <> 'completed'
        ");

        $stmt->execute($params);
    }

    /*
     * Remove member from the group.
     */
    $stmt = $pdo->prepare("
        DELETE FROM group_members
        WHERE group_id = :group_id
          AND user_id = :user_id
    ");

    $stmt->execute([
        'group_id' => $groupId,
        'user_id' => $userId
    ]);

    /*
     * Prepare member name for notification.
     */
    $memberName = trim(
        ($user['first_name'] ?? '') . ' ' .
        ($user['last_name'] ?? '')
    );

    if ($memberName === '') {
        $memberName = 'یک کاربر';
    }

    /*
     * Find remaining members.
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

    /*
     * Notify remaining members.
     */
    foreach ($remainingMembers as $remainingMemberId) {

        createNotification(
            $pdo,
            (int) $remainingMemberId,
            'member_left',
            'عضو از گروه خارج شد',
            "{$memberName} از گروه «{$membership['title']}» خارج شد.",
            $groupId,
            null
        );
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'data' => [
            'message' => 'You left the group successfully'
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