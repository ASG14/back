<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';

header('Content-Type: application/json; charset=utf-8');

try {

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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
        SELECT
            n.id,
            n.actor_user_id,
            n.type,
            n.title,
            n.message,
            n.group_id,
            n.order_id,

            CONCAT(
                COALESCE(actor.first_name, ''),
                CASE
                    WHEN actor.first_name IS NOT NULL
                     AND actor.last_name IS NOT NULL
                    THEN ' '
                    ELSE ''
                END,
                COALESCE(actor.last_name, '')
            ) AS actor_name,

            g.title AS group_title,
            o.title AS order_title,

            n.is_read,
            n.created_at,
            n.read_at

        FROM notifications n

        LEFT JOIN users actor
            ON actor.id = n.actor_user_id

        LEFT JOIN `groups` g
            ON g.id = n.group_id

        LEFT JOIN orders o
            ON o.id = n.order_id

        WHERE n.user_id = ?

        ORDER BY n.created_at DESC, n.id DESC
    ");

    $stmt->execute([
        $userId,
    ]);

    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $unreadStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM notifications
        WHERE user_id = ?
          AND is_read = 0
    ");

    $unreadStmt->execute([
        $userId,
    ]);

    $unreadCount = (int) $unreadStmt->fetchColumn();

    foreach ($notifications as &$notification) {

        $notification['id'] = (int) $notification['id'];

        $notification['actor_user_id'] =
            $notification['actor_user_id'] !== null
                ? (int) $notification['actor_user_id']
                : null;

        $notification['group_id'] =
            $notification['group_id'] !== null
                ? (int) $notification['group_id']
                : null;

        $notification['order_id'] =
            $notification['order_id'] !== null
                ? (int) $notification['order_id']
                : null;

        $notification['is_read'] =
            (bool) $notification['is_read'];

        /*
         * MySQL timestamp را به ISO 8601 UTC تبدیل می‌کنیم
         * تا Flutter بتواند آن را بدون ابهام به زمان محلی تبدیل کند.
         */
        if (!empty($notification['created_at'])) {
            $date = new DateTime(
                $notification['created_at'],
                new DateTimeZone('UTC')
            );

            $notification['created_at'] =
                $date->format('Y-m-d\TH:i:s\Z');
        }

        if (!empty($notification['read_at'])) {
            $date = new DateTime(
                $notification['read_at'],
                new DateTimeZone('UTC')
            );

            $notification['read_at'] =
                $date->format('Y-m-d\TH:i:s\Z');
        }
    }

    unset($notification);

    http_response_code(200);

    echo json_encode([
        'success' => true,
        'data' => [
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ],
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Server error',
    ], JSON_UNESCAPED_UNICODE);
}