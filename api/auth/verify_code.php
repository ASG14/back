<?php

date_default_timezone_set('Asia/Tehran');

require_once '../../config/database.php';
require_once '../../helpers/validation.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$phone = trim($_POST['phone'] ?? '');
$code = trim($_POST['code'] ?? '');

/*
 * Validate required fields
 */
$errors = validateRequired([
    'phone' => $phone,
    'code' => $code
]);

if (!empty($errors)) {
    validationError($errors);
}

/*
 * Validate phone
 */
if (!validatePhone($phone)) {
    validationError([
        'phone' => 'Invalid phone number'
    ]);
}

/*
 * Validate OTP
 */
if (!validateVerificationCode($code)) {
    validationError([
        'code' => 'Invalid verification code'
    ]);
}

try {

    /*
     * Get latest OTP for this phone.
     */
    $stmt = $pdo->prepare("
        SELECT
            id,
            phone,
            code,
            attempts,
            expires_at
        FROM otp_codes
        WHERE phone = :phone
        ORDER BY created_at DESC
        LIMIT 1
    ");

    $stmt->execute([
        'phone' => $phone
    ]);

    $otp = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$otp) {
        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'Verification code not found'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    /*
     * Check expiration.
     */
    if (strtotime($otp['expires_at']) <= time()) {
        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'Verification code has expired'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    /*
     * Maximum 5 attempts.
     */
    if ((int) $otp['attempts'] >= 5) {
        http_response_code(429);

        echo json_encode([
            'success' => false,
            'message' => 'Too many verification attempts'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    /*
     * Check code.
     */
    if (!hash_equals(
        (string) $otp['code'],
        (string) $code
    )) {

        $stmt = $pdo->prepare("
            UPDATE otp_codes
            SET attempts = attempts + 1
            WHERE id = :id
        ");

        $stmt->execute([
            'id' => $otp['id']
        ]);

        $remainingAttempts = max(
            0,
            4 - (int) $otp['attempts']
        );

        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'Invalid verification code',
            'remaining_attempts' => $remainingAttempts
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    /*
     * OTP is correct.
     */
    $pdo->beginTransaction();

    /*
     * Find existing user.
     */
    $stmt = $pdo->prepare("
        SELECT
            id,
            phone,
            first_name,
            last_name
        FROM users
        WHERE phone = :phone
        LIMIT 1
    ");

    $stmt->execute([
        'phone' => $phone
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    /*
     * Create user if it does not exist.
     */
    if (!$user) {

        $stmt = $pdo->prepare("
            INSERT INTO users (
                phone
            )
            VALUES (
                :phone
            )
        ");

        $stmt->execute([
            'phone' => $phone
        ]);

        $userId = (int) $pdo->lastInsertId();

        $user = [
            'id' => $userId,
            'phone' => $phone,
            'first_name' => null,
            'last_name' => null
        ];

    } else {

        $userId = (int) $user['id'];
    }

    /*
     * Generate authentication token.
     */
    $token = bin2hex(random_bytes(32));

    $expiresAt = date(
        'Y-m-d H:i:s',
        time() + (60 * 60 * 24 * 30)
    );

    /*
     * Store only the SHA-256 hash of the token.
     */
    $stmt = $pdo->prepare("
        INSERT INTO user_tokens (
            user_id,
            token,
            expires_at
        )
        VALUES (
            :user_id,
            :token,
            :expires_at
        )
    ");

    $stmt->execute([
        'user_id' => $userId,
        'token' => hash('sha256', $token),
        'expires_at' => $expiresAt
    ]);

    /*
     * Delete used OTP.
     */
    $stmt = $pdo->prepare("
        DELETE FROM otp_codes
        WHERE id = :id
    ");

    $stmt->execute([
        'id' => $otp['id']
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Verification successful',
        'data' => [
            'token' => $token,
            'expires_at' => $expiresAt,
            'user' => [
                'id' => $user['id'],
                'phone' => $user['phone'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name']
            ]
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Server error'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Server error'
    ], JSON_UNESCAPED_UNICODE);
}