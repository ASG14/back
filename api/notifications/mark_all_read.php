<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';

header('Content-Type: application/json; charset=utf-8');

try {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);

        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed',
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $user = requireAuth($pdo);
    $userId = (int) $user['id'];

    $stmt = $pdo->prepare("
        UPDATE notifications
        SET
            is_read = 1,
            read_at = CURRENT_TIMESTAMP
        WHERE user_id = ?
          AND is_read = 0
    ");

    $stmt->execute([
        $userId,
    ]);

    http_response_code(200);

    echo json_encode([
        'success' => true,
        'data' => [
            'unread_count' => 0,
        ],
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Server error',
    ], JSON_UNESCAPED_UNICODE);
}