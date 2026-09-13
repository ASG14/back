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

$errors = validateRequired([
    'phone' => $phone,
    'code' => $code
]);

if (!empty($errors)) {
    validationError($errors);
}

if (!validatePhone($phone)) {
    validationError([
        'phone' => 'Invalid phone number'
    ]);
}

if (!validateVerificationCode($code)) {
    validationError([
        'code' => 'Invalid verification code'
    ]);
}

try {

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT
            id,
            phone,
            code,
            attempts,
            expires_at,
            used_at
        FROM otp_codes
        WHERE phone = :phone
          AND used_at IS NULL
        ORDER BY created_at DESC
        LIMIT 1
        FOR UPDATE
    ");

    $stmt->execute([
        'phone' => $phone
    ]);

    $otp = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$otp) {

        $pdo->rollBack();

        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'Verification code not found'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    if (strtotime($otp['expires_at']) <= time()) {

        $stmt = $pdo->prepare("
            UPDATE otp_codes
            SET used_at = NOW()
            WHERE id = :id
        ");

        $stmt->execute([
            'id' => $otp['id']
        ]);

        $pdo->commit();

        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'Verification code has expired'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    if ((int) $otp['attempts'] >= 5) {

        $pdo->rollBack();

        http_response_code(429);

        echo json_encode([
            'success' => false,
            'message' => 'Too many verification attempts'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    if (!password_verify($code, $otp['code'])) {

        $stmt = $pdo->prepare("
            UPDATE otp_codes
            SET attempts = attempts + 1
            WHERE id = :id
        ");

        $stmt->execute([
            'id' => $otp['id']
        ]);

        $currentAttempts = (int) $otp['attempts'] + 1;

        $remainingAttempts = max(
            0,
            5 - $currentAttempts
        );

        $pdo->commit();

        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'Invalid verification code',
            'remaining_attempts' => $remainingAttempts
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    /*
     * Mark OTP as used.
     */
    $stmt = $pdo->prepare("
        UPDATE otp_codes
        SET used_at = NOW()
        WHERE id = :id
    ");

    $stmt->execute([
        'id' => $otp['id']
    ]);

    /*
     * Find user.
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
     * Create user if necessary.
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
     * Generate secure authentication token.
     */
    $token = bin2hex(random_bytes(32));

    /*
     * Token validity: 30 days.
     */
    $expiresAt = date(
        'Y-m-d H:i:s',
        time() + (60 * 60 * 24 * 30)
    );

    /*
     * Store only token hash in database.
     */
    $tokenHash = hash(
        'sha256',
        $token
    );

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
        'token' => $tokenHash,
        'expires_at' => $expiresAt
    ]);

    /*
     * Remove expired tokens for this user.
     */
    $stmt = $pdo->prepare("
        DELETE FROM user_tokens
        WHERE user_id = :user_id
          AND expires_at <= NOW()
    ");

    $stmt->execute([
        'user_id' => $userId
    ]);

    $pdo->commit();

    /*
     * --------------------------------------------------
     * Flutter Web authentication cookie
     * --------------------------------------------------
     *
     * HttpOnly:
     * JavaScript cannot read the token.
     *
     * Secure:
     * Cookie is sent only over HTTPS.
     *
     * SameSite:
     * Prevents cross-site cookie sending.
     *
     * Path:
     * Cookie is available to the API.
     */
    setcookie(
        'auth_token',
        $token,
        [
            'expires' => time() + (60 * 60 * 24 * 30),
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ]
    );

    echo json_encode([
        'success' => true,
        'message' => 'Verification successful',
        'data' => [
            /*
             * Keep token in response for mobile applications.
             *
             * Flutter Web will use the HttpOnly cookie instead.
             */
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

    error_log(
        'OTP verification database error: ' . $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Server error'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        'OTP verification error: ' . $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Server error'
    ], JSON_UNESCAPED_UNICODE);
}