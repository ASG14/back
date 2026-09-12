<?php

date_default_timezone_set('Asia/Tehran');

require_once '../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

/*
 * Get Authorization header
 */
$headers = getallheaders();

$authorization = $headers['Authorization']
    ?? $headers['authorization']
    ?? '';

if ($authorization === '') {

    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Authorization token is required'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

/*
 * Extract Bearer token
 */
if (!preg_match('/Bearer\s+(.+)/i', $authorization, $matches)) {

    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid authorization header'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$token = trim($matches[1]);

if ($token === '') {

    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid token'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {

    /*
     * Hash token before deleting it.
     *
     * login.php stores SHA-256(token) in the database.
     */
    $hashedToken = hash('sha256', $token);

    /*
     * Delete token
     */
    $stmt = $pdo->prepare("
        DELETE FROM user_tokens
        WHERE token = :token
    ");

    $stmt->execute([
        'token' => $hashedToken
    ]);

    echo json_encode([
        'success' => true,
        'data' => [
            'message' => 'Logged out successfully'
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Server error'
    ], JSON_UNESCAPED_UNICODE);
}