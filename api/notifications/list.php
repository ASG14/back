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

    /*
     * The application stores timestamps in Tehran time.
     * Convert them explicitly to UTC before sending to Flutter.
     */
    $timezone = new DateTimeZone('Asia/Tehran');
    $utc = new DateTimeZone('UTC');

    /*
     * Get notifications
     */
    $stmt = $pdo->prepare("
        SELECT
            n.id,
            n.actor_user_id,
            n.type,
            n.title,
            n.message,
            n.group_id,
            n.order_id,

            CASE
                WHEN n.actor_user_id IS NULL THEN NULL
                WHEN TRIM(
                    CONCAT_WS(
                        ' ',
                        NULLIF(TRIM(actor.first_name), ''),
                        NULLIF(TRIM(actor.last_name), '')
                    )
                ) = '' THEN CONCAT('کاربر ', actor.id)
                ELSE TRIM(
                    CONCAT_WS(
                        ' ',
                        NULLIF(TRIM(actor.first_name), ''),
                        NULLIF(TRIM(actor.last_name), '')
                    )
                )
            END AS actor_name,

            g.title AS group_title,
            o.title AS order_title,

            n.is_read,
            n.created_at,
            n.read_at

        FROM notifications AS n

        LEFT JOIN users AS actor
            ON actor.id = n.actor_user_id

        LEFT JOIN `groups` AS g
            ON g.id = n.group_id

        LEFT JOIN orders AS o
            ON o.id = n.order_id

        WHERE n.user_id = :user_id

        ORDER BY
            n.created_at DESC,
            n.id DESC
    ");

    $stmt->execute([
        'user_id' => $userId
    ]);

    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /*
     * Get unread count
     */
    $unreadStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM notifications
        WHERE user_id = :user_id
          AND is_read = 0
    ");

    $unreadStmt->execute([
        'user_id' => $userId
    ]);

    $unreadCount = (int) $unreadStmt->fetchColumn();

    /*
     * Normalize response
     */
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
         * created_at
         */
        if (!empty($notification['created_at'])) {

            $date = new DateTime(
                $notification['created_at'],
                $timezone
            );

            $date->setTimezone($utc);

            $notification['created_at'] =
                $date->format('Y-m-d\TH:i:s\Z');
        }

        /*
         * read_at
         */
        if (!empty($notification['read_at'])) {

            $date = new DateTime(
                $notification['read_at'],
                $timezone
            );

            $date->setTimezone($utc);

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