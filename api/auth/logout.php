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
 * Get token from Authorization header first.
 * If it does not exist, use Web authentication cookie.
 */
$token = null;

$headers = getallheaders();

$authorization = $headers['Authorization']
    ?? $headers['authorization']
    ?? '';

if ($authorization !== '') {

    if (preg_match('/Bearer\s+(.+)/i', $authorization, $matches)) {
        $token = trim($matches[1]);
    }
}

/*
 * Flutter Web fallback.
 */
if (($token === null || $token === '') && !empty($_COOKIE['auth_token'])) {
    $token = $_COOKIE['auth_token'];
}

if ($token === null || $token === '') {

    /*
     * Even if there is no token, clear the cookie.
     */
    setcookie(
        'auth_token',
        '',
        [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ]
    );

    echo json_encode([
        'success' => true,
        'data' => [
            'message' => 'Logged out successfully'
        ]
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {

    $hashedToken = hash(
        'sha256',
        $token
    );

    $stmt = $pdo->prepare("
        DELETE FROM user_tokens
        WHERE token = :token
    ");

    $stmt->execute([
        'token' => $hashedToken
    ]);

    /*
     * Clear Web authentication cookie.
     */
    setcookie(
        'auth_token',
        '',
        [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ]
    );

    echo json_encode([
        'success' => true,
        'data' => [
            'message' => 'Logged out successfully'
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {

    error_log(
        'Logout database error: ' . $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Server error'
    ], JSON_UNESCAPED_UNICODE);
}