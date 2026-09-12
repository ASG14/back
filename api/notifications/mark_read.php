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

    $input = json_decode(
        file_get_contents('php://input'),
        true
    );

    if (!is_array($input)) {
        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'Invalid JSON body',
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $notificationId = $input['notification_id'] ?? null;

    if (
        $notificationId === null ||
        filter_var($notificationId, FILTER_VALIDATE_INT) === false ||
        (int) $notificationId <= 0
    ) {
        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'Invalid notification_id',
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $notificationId = (int) $notificationId;

    /*
     * فقط اعلان متعلق به کاربر فعلی
     * قابل تغییر است.
     */
    $stmt = $pdo->prepare("
        UPDATE notifications
        SET
            is_read = 1,
            read_at = CURRENT_TIMESTAMP
        WHERE id = ?
          AND user_id = ?
          AND is_read = 0
    ");

    $stmt->execute([
        $notificationId,
        $userId,
    ]);

    if ($stmt->rowCount() === 0) {

        /*
         * ممکن است اعلان از قبل خوانده شده باشد.
         * در این حالت خطا محسوب نمی‌شود.
         *
         * ابتدا بررسی می‌کنیم اعلان متعلق به
         * کاربر فعلی هست یا خیر.
         */
        $checkStmt = $pdo->prepare("
            SELECT id
            FROM notifications
            WHERE id = ?
              AND user_id = ?
            LIMIT 1
        ");

        $checkStmt->execute([
            $notificationId,
            $userId,
        ]);

        $exists = $checkStmt->fetchColumn();

        if (!$exists) {
            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' => 'Notification not found',
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }
    }

    http_response_code(200);

    echo json_encode([
        'success' => true,
        'data' => [
            'message' => 'Notification marked as read',
        ],
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Server error',
    ], JSON_UNESCAPED_UNICODE);
}