<?php

date_default_timezone_set('Asia/Tehran');

require_once '../../config/database.php';
require_once '../../helpers/auth.php';
require_once '../../helpers/validation.php';

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

$title = trim($input['title'] ?? '');

$errors = validateRequired([
    'title' => $title
]);

if (!empty($errors)) {
    validationError($errors);
}

if (!validateGroupTitle($title)) {
    validationError([
        'title' => 'Group title must be between 1 and 100 characters'
    ]);
}

try {

    $pdo->beginTransaction();

    /*
     * Create group
     */
    $stmt = $pdo->prepare("
        INSERT INTO `groups` (
            title,
            creator_id
        )
        VALUES (
            :title,
            :creator_id
        )
    ");

    $stmt->execute([
        'title' => $title,
        'creator_id' => $userId
    ]);

    $groupId = (int) $pdo->lastInsertId();

    /*
     * Add creator as owner
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
            'owner'
        )
    ");

    $stmt->execute([
        'group_id' => $groupId,
        'user_id' => $userId
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Group created successfully',
        'data' => [
            'group' => [
                'id' => $groupId,
                'title' => $title,
                'creator_id' => $userId
            ]
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        'groups/create.php PDO Error: ' . $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Server error'
    ], JSON_UNESCAPED_UNICODE);
}