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

$input = $_POST;

$token = trim($input['token'] ?? '');

$errors = validateRequired([
    'token' => $token
]);

if (!empty($errors)) {
    validationError($errors);
}

/*
 * Invite token must be exactly 8 characters.
 *
 * Allowed characters:
 * - A-Z except I, O
 * - a-z except l, o
 * - 2-9
 */
if (!preg_match('/^[A-HJ-NP-Za-hj-km-z2-9]{8}$/', $token)) {
    validationError([
        'token' => 'Invalid invite token'
    ]);
}

try {

    $pdo->beginTransaction();

    /*
     * Find invite and related group
     */
    $stmt = $pdo->prepare("
        SELECT
            gi.group_id,
            g.title AS group_title,
            g.creator_id
        FROM group_invites AS gi
        INNER JOIN `groups` AS g
            ON g.id = gi.group_id
        WHERE gi.token = :token
        LIMIT 1
    ");

    $stmt->execute([
        'token' => $token
    ]);

    $invite = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$invite) {
        $pdo->rollBack();

        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Invalid or expired invite'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $groupId = (int) $invite['group_id'];
    $groupTitle = $invite['group_title'];
    $creatorId = (int) $invite['creator_id'];

    /*
     * Check whether the user is already a member
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

    if ($stmt->fetchColumn()) {
        $pdo->rollBack();

        echo json_encode([
            'success' => true,
            'message' => 'You are already a member of this group',
            'data' => [
                'group_id' => $groupId,
                'group_title' => $groupTitle
            ]
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    /*
     * Add user to group
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
        'user_id' => $userId
    ]);

    /*
     * Get joining user's name
     */
    $stmt = $pdo->prepare("
        SELECT
            id,
            first_name,
            last_name
        FROM users
        WHERE id = :user_id
        LIMIT 1
    ");

    $stmt->execute([
        'user_id' => $userId
    ]);

    $joiningUser = $stmt->fetch(PDO::FETCH_ASSOC);

    $memberName = trim(
        ($joiningUser['first_name'] ?? '') . ' ' .
        ($joiningUser['last_name'] ?? '')
    );

    if ($memberName === '') {
        $memberName = 'یک کاربر';
    }

    /*
     * Notify the user who joined
     */
    createNotification(
        $pdo,
        $userId,
        $userId,
        'member_added',
        'به گروه پیوستید',
        "شما به گروه «{$groupTitle}» پیوستید.",
        $groupId,
        null
    );

    /*
     * Notify existing group members
     *
     * The newly joined user is excluded because they already
     * received their own "member_added" notification above.
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

    $existingMembers = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($existingMembers as $existingMemberId) {

        createNotification(
            $pdo,
            (int) $existingMemberId,
            $userId,
            'member_joined',
            'عضو جدید',
            "{$memberName} به گروه «{$groupTitle}» پیوست.",
            $groupId,
            null
        );
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Successfully joined the group',
        'data' => [
            'group_id' => $groupId,
            'group_title' => $groupTitle
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

    exit;
}
