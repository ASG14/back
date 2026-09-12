<?php

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

function sendResponse(bool $success, string $message, array $data = []): void
{
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }

    $input = json_decode(file_get_contents('php://input'), true);

    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';

    if ($username === '' || $password === '') {
        throw new Exception('Username and password are required');
    }

    $stmt = $pdo->prepare("
        SELECT 
            t.id,
            t.user_id,
            t.username,
            t.password_hash
        FROM temporary_login_users t
        WHERE t.username = :username
        LIMIT 1
    ");

    $stmt->execute([
        ':username' => $username
    ]);

    $loginUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$loginUser || !password_verify($password, $loginUser['password_hash'])) {
        throw new Exception('Invalid username or password');
    }

    $token = bin2hex(random_bytes(32));

    $expiresAt = date(
        'Y-m-d H:i:s',
        time() + (30 * 24 * 60 * 60)
    );

    $stmt = $pdo->prepare("
        INSERT INTO user_tokens
            (user_id, token, expires_at)
        VALUES
            (:user_id, :token, :expires_at)
    ");

    $stmt->execute([
        ':user_id' => $loginUser['user_id'],
        ':token' => hash('sha256', $token),
        ':expires_at' => $expiresAt
    ]);

    sendResponse(true, 'Login successful', [
        'token' => $token,
        'user_id' => (int) $loginUser['user_id'],
        'expires_at' => $expiresAt
    ]);

} catch (Exception $e) {

    sendResponse(false, $e->getMessage());
}