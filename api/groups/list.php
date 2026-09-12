<?php

date_default_timezone_set('Asia/Tehran');

require_once '../../config/database.php';
require_once '../../helpers/auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed',
        'data' => []
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {

    // احراز هویت
    $user = requireAuth($pdo);

    $userId = $user['id'];

    // اجرای کوئری
    $stmt = $pdo->prepare("
        SELECT
            g.id,
            g.title,
            g.creator_id,
            g.created_at
        FROM `groups` AS g
        INNER JOIN `group_members` AS gm
            ON gm.group_id = g.id
        WHERE gm.user_id = :user_id
        ORDER BY g.created_at DESC
    ");

    $stmt->execute([
        'user_id' => $userId
    ]);

    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => 'Groups retrieved successfully',
        'data' => [
            'groups' => $groups
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {

    // فقط برای دیباگ موقت
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'PDO ERROR',
        'error' => $e->getMessage(),
        'data' => []
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    // خطاهای غیر PDO
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'PHP ERROR',
        'error' => $e->getMessage(),
        'data' => []
    ], JSON_UNESCAPED_UNICODE);
}