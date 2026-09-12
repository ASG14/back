<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/validation.php';

header('Content-Type: application/json; charset=utf-8');

function sendResponse(
    bool $success,
    string $message,
    array $data = []
): void {

    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        sendResponse(false, 'Method not allowed');
    }

    $input = json_decode(
        file_get_contents('php://input'),
        true
    );

    if (!is_array($input)) {
        http_response_code(400);
        sendResponse(false, 'Invalid request');
    }

    $phone = trim($input['phone'] ?? '');
    $firstName = trim($input['first_name'] ?? '');
    $lastName = trim($input['last_name'] ?? '');
    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';

    /*
     * Validation
     */

    if ($phone === '') {
        sendResponse(false, 'Phone number is required');
    }

    if (!validatePhone($phone)) {
        sendResponse(false, 'Invalid phone number');
    }

    if ($firstName === '') {
        sendResponse(false, 'First name is required');
    }

    if ($lastName === '') {
        sendResponse(false, 'Last name is required');
    }

    if ($username === '') {
        sendResponse(false, 'Username is required');
    }

    if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
        sendResponse(false, 'Invalid username');
    }

    if (strlen($password) < 6) {
        sendResponse(
            false,
            'Password must be at least 6 characters'
        );
    }

    /*
     * Check existing phone
     */
    $stmt = $pdo->prepare("
        SELECT id
        FROM users
        WHERE phone = :phone
        LIMIT 1
    ");

    $stmt->execute([
        'phone' => $phone
    ]);

    if ($stmt->fetch()) {
        sendResponse(
            false,
            'A user with this phone number already exists'
        );
    }

    /*
     * Check existing username
     */
    $stmt = $pdo->prepare("
        SELECT id
        FROM temporary_login_users
        WHERE username = :username
        LIMIT 1
    ");

    $stmt->execute([
        'username' => $username
    ]);

    if ($stmt->fetch()) {
        sendResponse(
            false,
            'This username is already taken'
        );
    }

    /*
     * Transaction
     */
    $pdo->beginTransaction();

    /*
     * Create user
     */
    $stmt = $pdo->prepare("
        INSERT INTO users
            (phone, first_name, last_name)
        VALUES
            (:phone, :first_name, :last_name)
    ");

    $stmt->execute([
        'phone' => $phone,
        'first_name' => $firstName,
        'last_name' => $lastName
    ]);

    $userId = (int) $pdo->lastInsertId();

    /*
     * Create temporary login account
     */
    $passwordHash = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    $stmt = $pdo->prepare("
        INSERT INTO temporary_login_users
            (user_id, username, password_hash)
        VALUES
            (:user_id, :username, :password_hash)
    ");

    $stmt->execute([
        'user_id' => $userId,
        'username' => $username,
        'password_hash' => $passwordHash
    ]);

    /*
     * Create authentication token
     */
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
        'user_id' => $userId,
        'token' => hash('sha256', $token),
        'expires_at' => $expiresAt
    ]);

    $pdo->commit();

    sendResponse(
        true,
        'Registration successful',
        [
            'user_id' => $userId,
            'token' => $token,
            'expires_at' => $expiresAt
        ]
    );

} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);

    sendResponse(
        false,
        'Server error'
    );

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(400);

    sendResponse(
        false,
        $e->getMessage()
    );
}