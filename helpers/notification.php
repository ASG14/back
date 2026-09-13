<?php

function createNotification(
    PDO $pdo,
    int $userId,
    int $actorUserId,
    string $type,
    string $title,
    string $message,
    ?int $groupId = null,
    ?int $orderId = null
): void {
    $stmt = $pdo->prepare("
        INSERT INTO notifications (
            user_id,
            actor_user_id,
            type,
            title,
            message,
            group_id,
            order_id
        )
        VALUES (
            :user_id,
            :actor_user_id,
            :type,
            :title,
            :message,
            :group_id,
            :order_id
        )
    ");

    $stmt->execute([
        'user_id' => $userId,
        'actor_user_id' => $actorUserId,
        'type' => $type,
        'title' => $title,
        'message' => $message,
        'group_id' => $groupId,
        'order_id' => $orderId,
    ]);
}