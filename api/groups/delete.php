<?php

date_default_timezone_set('Asia/Tehran');

require_once '../../config/database.php';
require_once '../../helpers/auth.php';
require_once '../../helpers/validation.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$user = requireAuth($pdo);

$userId = (int) $user['id'];

$groupId = $_GET['group_id'] ?? '';

if (!validateId($groupId)) {
    validationError([
        'group_id' => 'Invalid group ID'
    ]);
}

$groupId = (int) $groupId;

try {

    $pdo->beginTransaction();

    /*
     * Only group creator can delete the group.
     */
    $stmt = $pdo->prepare("
        SELECT
            id,
            title
        FROM groups
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
            'message' => 'You are not allowed to delete this group'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    /*
     * Delete all notifications belonging to this group.
     *
     * This is done explicitly because the foreign key uses
     * ON DELETE SET NULL for notifications.group_id.
     */
    $stmt = $pdo->prepare("
        DELETE FROM notifications
        WHERE group_id = :group_id
    ");

    $stmt->execute([
        'group_id' => $groupId
    ]);

    /*
     * Delete the group.
     *
     * Related records are removed automatically:
     *
     * group_members      -> CASCADE
     * orders             -> CASCADE
     * order_assignments  -> CASCADE
     * group_invites      -> CASCADE
     */
    $stmt = $pdo->prepare("
        DELETE FROM groups
        WHERE id = :group_id
    ");

    $stmt->execute([
        'group_id' => $groupId
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'data' => [
            'message' => 'Group deleted successfully'
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