<?php

date_default_timezone_set('Asia/Tehran');

require_once '../../config/database.php';
require_once '../../helpers/auth.php';
require_once '../../helpers/validation.php';

header('Content-Type: application/json; charset=utf-8');

/*
|--------------------------------------------------------------------------
| Method
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ], JSON_UNESCAPED_UNICODE);

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
| Input
|--------------------------------------------------------------------------
*/

$input = $_POST;

/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

$token = trim(
    $input['token'] ?? ''
);

$errors = validateRequired([
    'token' => $token
]);

if (!empty($errors)) {
    validationError($errors);
}

/*
|--------------------------------------------------------------------------
| Validate Invite Token
|--------------------------------------------------------------------------
*/

if (
    preg_match(
        '/^[A-HJ-NP-Za-hj-km-z2-9]{8}$/',
        $token
    ) !== 1
) {
    validationError([
        'token' => 'Invalid invite token'
    ]);
}

/*
|--------------------------------------------------------------------------
| Main
|--------------------------------------------------------------------------
*/

try {

    /*
    |--------------------------------------------------------------------------
    | Find Invite
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT
            gi.id,
            gi.group_id,
            g.title
        FROM `group_invites` AS gi
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
        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Invite not found'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $groupId = (int) $invite['group_id'];

    /*
    |--------------------------------------------------------------------------
    | Check Existing Membership
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT id
        FROM `group_members`
        WHERE group_id = :group_id
          AND user_id = :user_id
        LIMIT 1
    ");

    $stmt->execute([
        'group_id' => $groupId,
        'user_id' => $userId
    ]);

    $existingMember = $stmt->fetch(PDO::FETCH_ASSOC);

    /*
    |--------------------------------------------------------------------------
    | Already Member
    |--------------------------------------------------------------------------
    */

    if ($existingMember) {

        echo json_encode([
            'success' => true,
            'message' => 'Already a member of this group',
            'data' => [
                'group_id' => $groupId,
                'group_title' => $invite['title']
            ]
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Add Member
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        INSERT INTO `group_members`
            (
                group_id,
                user_id,
                role
            )
        VALUES
            (
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
    |--------------------------------------------------------------------------
    | Response
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        'success' => true,
        'message' => 'Successfully joined the group',
        'data' => [
            'group_id' => $groupId,
            'group_title' => $invite['title']
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {

    error_log(
        'Join group error: ' . $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Server error'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}