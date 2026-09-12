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

$input = json_decode(
    file_get_contents('php://input'),
    true
);

if (!is_array($input)) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$groupId = $input['group_id'] ?? '';

/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

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

/*
|--------------------------------------------------------------------------
| Generate Invite Token
|--------------------------------------------------------------------------
|
| 8 characters
| Uses cryptographically secure random_int().
|
| Characters such as 0, O, I, l and 1 are removed
| to make manual reading easier.
|
*/

function generateInviteToken(int $length = 8): string
{
    $characters =
        'ABCDEFGHJKLMNPQRSTUVWXYZ' .
        'abcdefghijkmnopqrstuvwxyz' .
        '23456789';

    $maxIndex = strlen($characters) - 1;

    $token = '';

    for ($i = 0; $i < $length; $i++) {
        $token .= $characters[random_int(0, $maxIndex)];
    }

    return $token;
}

/*
|--------------------------------------------------------------------------
| Validate Token Format
|--------------------------------------------------------------------------
*/

function isValidInviteToken(string $token): bool
{
    return preg_match(
        '/^[A-HJ-NP-Za-hj-km-z2-9]{8}$/',
        $token
    ) === 1;
}

/*
|--------------------------------------------------------------------------
| Main
|--------------------------------------------------------------------------
*/

try {

    /*
    |--------------------------------------------------------------------------
    | Check Group Membership
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT
            g.id,
            g.title
        FROM `groups` AS g
        INNER JOIN `group_members` AS gm
            ON gm.group_id = g.id
        WHERE g.id = :group_id
          AND gm.user_id = :user_id
        LIMIT 1
    ");

    $stmt->execute([
        'group_id' => $groupId,
        'user_id' => $userId
    ]);

    $group = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$group) {
        http_response_code(403);

        echo json_encode([
            'success' => false,
            'message' => 'You are not a member of this group'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Find Existing Invite
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT
            id,
            token
        FROM `group_invites`
        WHERE group_id = :group_id
        ORDER BY id ASC
        LIMIT 1
    ");

    $stmt->execute([
        'group_id' => $groupId
    ]);

    $invite = $stmt->fetch(PDO::FETCH_ASSOC);

    /*
    |--------------------------------------------------------------------------
    | Existing Valid Token
    |--------------------------------------------------------------------------
    */

    if (
        $invite &&
        isset($invite['token']) &&
        isValidInviteToken($invite['token'])
    ) {
        $token = $invite['token'];
    } else {

        /*
        |--------------------------------------------------------------------------
        | Generate Unique Token
        |--------------------------------------------------------------------------
        */

        do {

            $token = generateInviteToken();

            $stmt = $pdo->prepare("
                SELECT id
                FROM `group_invites`
                WHERE token = :token
                LIMIT 1
            ");

            $stmt->execute([
                'token' => $token
            ]);

            $tokenExists = $stmt->fetch(PDO::FETCH_ASSOC);

        } while ($tokenExists);

        /*
        |--------------------------------------------------------------------------
        | Create or Replace Invite
        |--------------------------------------------------------------------------
        */

        if ($invite) {

            $stmt = $pdo->prepare("
                UPDATE `group_invites`
                SET token = :token
                WHERE id = :id
            ");

            $stmt->execute([
                'token' => $token,
                'id' => $invite['id']
            ]);

        } else {

            $stmt = $pdo->prepare("
                INSERT INTO `group_invites`
                    (group_id, token)
                VALUES
                    (:group_id, :token)
            ");

            $stmt->execute([
                'group_id' => $groupId,
                'token' => $token
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Response
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        'success' => true,
        'data' => [
            'group_id' => $groupId,
            'group_title' => $group['title'],
            'token' => $token
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {

    error_log(
        'Create invite error: ' . $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Server error'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}