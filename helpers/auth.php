<?php

function getAuthorizationToken(): ?string
{
    $headers = getallheaders();

    $authorization = $headers['Authorization']
        ?? $headers['authorization']
        ?? '';

    if ($authorization === '') {
        return null;
    }

    if (!preg_match('/Bearer\s+(.+)/i', $authorization, $matches)) {
        return null;
    }

    return trim($matches[1]);
}

function requireAuth(PDO $pdo): array
{
    $token = getAuthorizationToken();

    if ($token === null || $token === '') {
        http_response_code(401);

        echo json_encode([
            'success' => false,
            'message' => 'Authentication required'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    try {

        $tokenHash = hash('sha256', $token);

        $stmt = $pdo->prepare("
            SELECT
                u.id,
                u.phone,
                u.first_name,
                u.last_name,
                t.expires_at
            FROM user_tokens t
            INNER JOIN users u
                ON u.id = t.user_id
            WHERE t.token = :token
              AND t.expires_at > NOW()
            LIMIT 1
        ");

        $stmt->execute([
            'token' => $tokenHash
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(401);

            echo json_encode([
                'success' => false,
                'message' => 'Invalid or expired token'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        return $user;

    } catch (PDOException $e) {

        http_response_code(500);

        echo json_encode([
            'success' => false,
            'message' => 'Server error'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}