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

$firstName = trim($input['first_name'] ?? '');
$lastName = trim($input['last_name'] ?? '');

$errors = validateRequired([
    'first_name' => $firstName,
    'last_name' => $lastName
]);

if (!empty($errors)) {
    validationError($errors);
}

if (!validateTextLength($firstName, 1, 100)) {
    validationError([
        'first_name' => 'First name must be between 1 and 100 characters'
    ]);
}

if (!validateTextLength($lastName, 1, 100)) {
    validationError([
        'last_name' => 'Last name must be between 1 and 100 characters'
    ]);
}

try {
    $stmt = $pdo->prepare("
        UPDATE users
        SET first_name = :first_name,
            last_name = :last_name
        WHERE id = :user_id
        LIMIT 1
    ");

    $stmt->execute([
        'first_name' => $firstName,
        'last_name' => $lastName,
        'user_id' => $userId
    ]);

    $stmt = $pdo->prepare("
        SELECT
            id,
            phone,
            first_name,
            last_name
        FROM users
        WHERE id = :user_id
        LIMIT 1
    ");

    $stmt->execute([
        'user_id' => $userId
    ]);

    $updatedUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$updatedUser) {
        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully',
        'data' => [
            'user' => [
                'id' => $updatedUser['id'],
                'phone' => $updatedUser['phone'],
                'first_name' => $updatedUser['first_name'],
                'last_name' => $updatedUser['last_name']
            ]
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Server error'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}