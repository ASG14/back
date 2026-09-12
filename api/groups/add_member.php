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
$phone = trim($input['phone'] ?? '');

$errors = validateRequired([
    'group_id' => $groupId,
    'phone' => $phone
]);

if (!empty($errors)) {
    validationError($errors);
}

if (!validateId($groupId)) {
    validationError([
        'group_id' => 'Invalid group ID'
    ]);
}

if (!validatePhone($phone)) {
    validationError([
        'phone' => 'Invalid phone number'
    ]);
}

$groupId = (int) $groupId;

try {

    $pdo->beginTransaction();

    /*
     * Only group creator can add members
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
            'message' => 'You are not allowed to add members to this group'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    /*
     * Find user by phone
     */
    $stmt = $pdo->prepare("
        SELECT
            id,
            phone,
            first_name,
            last_name
        FROM users
        WHERE phone = :phone
        LIMIT 1
    ");

    $stmt->execute([
        'phone' => $phone
    ]);

    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$member) {
        $pdo->rollBack();

        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $memberId = (int) $member['id'];

    /*
     * Check existing membership
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
        'user_id' => $memberId
    ]);

    if ($stmt->fetch()) {
        $pdo->rollBack();

        http_response_code(409);

        echo json_encode([
            'success' => false,
            'message' => 'User is already a member of this group'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    /*
     * Add member
     */
    $stmt = $pdo->prepare("
        INSERT INTO group_members (
            group_id,
            user_id,
            role
        )
        VALUES (
            :group_id,
            :user_id,
            'member'
        )
    ");

    $stmt->execute([
        'group_id' => $groupId,
        'user_id' => $memberId
    ]);

    /*
     * Notify the new member
     */
    createNotification(
        $pdo,
        $memberId,
        'member_added',
        'به گروه اضافه شدید',
        "شما به گروه «{$group['title']}» اضافه شدید.",
        $groupId,
        null
    );

    /*
     * Notify existing members except the new member
     */
    $stmt = $pdo->prepare("
        SELECT user_id
        FROM group_members
        WHERE group_id = :group_id
          AND user_id != :member_id
    ");

    $stmt->execute([
        'group_id' => $groupId,
        'member_id' => $memberId
    ]);

    $members = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $memberName = trim(
        ($member['first_name'] ?? '') . ' ' .
        ($member['last_name'] ?? '')
    );

    if ($memberName === '') {
        $memberName = 'یک کاربر';
    }

    foreach ($members as $existingMemberId) {
        createNotification(
            $pdo,
            (int) $existingMemberId,
            'member_joined',
            'عضو جدید',
            "{$memberName} به گروه «{$group['title']}» اضافه شد.",
            $groupId,
            null
        );
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'data' => [
            'member' => $member
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