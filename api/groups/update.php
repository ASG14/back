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
$title = trim($input['title'] ?? '');

$errors = validateRequired([
    'group_id' => $groupId,
    'title' => $title
]);

if (!empty($errors)) {
    validationError($errors);
}

if (!validateId($groupId)) {
    validationError([
        'group_id' => 'Invalid group ID'
    ]);
}

if (!validateGroupTitle($title)) {
    validationError([
        'title' => 'Group title must be between 1 and 100 characters'
    ]);
}

$groupId = (int) $groupId;

try {

    // بررسی مالکیت گروه
    $stmt = $pdo->prepare("
        SELECT id
        FROM `groups`
        WHERE id = :group_id
          AND creator_id = :user_id
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
            'message' => 'You are not allowed to update this group'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    // تغییر نام گروه
    $stmt = $pdo->prepare("
        UPDATE `groups`
        SET title = :title
        WHERE id = :group_id
    ");

    $stmt->execute([
        'title' => $title,
        'group_id' => $groupId
    ]);

    echo json_encode([
        'success' => true,
        'data' => [
            'message' => 'Group updated successfully'
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Server error'
    ], JSON_UNESCAPED_UNICODE);
}