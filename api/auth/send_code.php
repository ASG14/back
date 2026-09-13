<?php

date_default_timezone_set('Asia/Tehran');

require_once '../../config/database.php';
require_once '../../helpers/validation.php';
require_once '../../helpers/sms.php';

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
 * Get phone number.
 */
$phone = trim($_POST['phone'] ?? '');

/*
 * Validate required fields.
 */
$errors = validateRequired([
    'phone' => $phone
]);

if (!empty($errors)) {
    validationError($errors);
}

/*
 * Normalize Iranian phone number.
 *
 * +989123456789 -> 09123456789
 * 989123456789  -> 09123456789
 */
if (str_starts_with($phone, '+98')) {
    $phone = '0' . substr($phone, 3);
} elseif (str_starts_with($phone, '98')) {
    $phone = '0' . substr($phone, 2);
}

/*
 * Validate normalized phone number.
 */
if (!validatePhone($phone)) {
    validationError([
        'phone' => 'Invalid phone number'
    ]);
}

try {

    /*
     * Delete expired OTP codes.
     */
    $stmt = $pdo->prepare("
        DELETE FROM otp_codes
        WHERE expires_at <= NOW()
    ");

    $stmt->execute();

    /*
     * Rate limit:
     * Maximum 3 OTP requests within 10 minutes
     * for the same phone number.
     */
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM otp_codes
        WHERE phone = :phone
          AND created_at >= DATE_SUB(
              CURRENT_TIMESTAMP,
              INTERVAL 10 MINUTE
          )
    ");

    $stmt->execute([
        'phone' => $phone
    ]);

    $requestCount = (int) $stmt->fetchColumn();

    if ($requestCount >= 3) {
        http_response_code(429);

        echo json_encode([
            'success' => false,
            'message' => 'Too many OTP requests. Please try again later.'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    /*
     * Send OTP through SMS service.
     *
     * The SMS provider generates the code
     * and returns it to us.
     */
    $code = sendOtpSms($phone);

    if ($code === '') {
        throw new Exception('SMS service did not return a verification code');
    }

    /*
     * OTP validity: 2 minutes.
     */
    $expiresAt = date(
        'Y-m-d H:i:s',
        time() + (2 * 60)
    );

    /*
     * Store OTP.
     */
    $stmt = $pdo->prepare("
        INSERT INTO otp_codes (
            phone,
            code,
            attempts,
            expires_at
        )
        VALUES (
            :phone,
            :code,
            0,
            :expires_at
        )
    ");

    $stmt->execute([
        'phone' => $phone,
        'code' => $code,
        'expires_at' => $expiresAt
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'کد ورود برای شما پیامک شد'
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {

    error_log(
        'OTP database error: ' . $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Server error'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {

    error_log(
        'OTP SMS error: ' . $e->getMessage()
    );

    http_response_code(502);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}