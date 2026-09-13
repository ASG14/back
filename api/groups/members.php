<?php

date_default_timezone_set('Asia/Tehran');

require_once '../../config/database.php';
require_once '../../helpers/auth.php';
require_once '../../helpers/validation.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {

    $user = requireAuth($pdo);

    $userId = (int) $user['id'];

    $groupId = $_GET['group_id'] ?? '';

    if (!validateId($groupId)) {
        validationError([
            'group_id' => 'Invalid group ID'
        ]);
    }

    $groupId = (int) $groupId;

    // بررسی عضویت کاربر در گروه
    $stmt = $pdo->prepare("
        SELECT 1
        FROM group_members
        WHERE group_id = :group_id
          AND user_id = :user_id
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
            'message' => 'You are not a member of this group'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    // دریافت حداقل اطلاعات مورد نیاز اعضای گروه
    $stmt = $pdo->prepare("
        SELECT
            u.id,
            u.first_name,
            u.last_name,
            CASE
                WHEN gm.role = 'owner' THEN 'admin'
                ELSE 'member'
            END AS role,
            'active' AS status,
            gm.joined_at
        FROM group_members AS gm
        INNER JOIN users AS u
            ON u.id = gm.user_id
        WHERE gm.group_id = :group_id
        ORDER BY gm.joined_at ASC
    ");

    $stmt->execute([
        'group_id' => $groupId
    ]);

    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'members' => $members
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Server error'
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Server error'
    ], JSON_UNESCAPED_UNICODE);
}