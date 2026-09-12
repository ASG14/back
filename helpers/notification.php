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
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $userId,
        $actorUserId,
        $type,
        $title,
        $message,
        $groupId,
        $orderId,
    ]);
}