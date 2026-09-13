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
 * Get client IP address.
 *
 * REMOTE_ADDR is intentionally used because forwarded
 * headers can be spoofed unless a trusted proxy is configured.
 */
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';

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
     * Cooldown:
     * A new OTP request for the same phone number
     * is allowed only after 60 seconds.
     */
    $stmt = $pdo->prepare("
        SELECT created_at
        FROM otp_codes
        WHERE phone = :phone
        ORDER BY created_at DESC
        LIMIT 1
    ");

    $stmt->execute([
        'phone' => $phone
    ]);

    $lastCreatedAt = $stmt->fetchColumn();

    if ($lastCreatedAt !== false) {

        $secondsSinceLastRequest =
            time() - strtotime($lastCreatedAt);

        if ($secondsSinceLastRequest < 60) {
            http_response_code(429);

            echo json_encode([
                'success' => false,
                'message' => 'Please wait before requesting another OTP.'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }
    }

    /*
     * Phone rate limit:
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

    $phoneRequestCount = (int) $stmt->fetchColumn();

    if ($phoneRequestCount >= 3) {
        http_response_code(429);

        echo json_encode([
            'success' => false,
            'message' => 'Too many OTP requests. Please try again later.'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    /*
     * IP rate limit:
     * Maximum 10 OTP requests within 10 minutes
     * from the same IP address.
     */
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM otp_codes
        WHERE ip_address = :ip_address
          AND created_at >= DATE_SUB(
              CURRENT_TIMESTAMP,
              INTERVAL 10 MINUTE
          )
    ");

    $stmt->execute([
        'ip_address' => $ipAddress
    ]);

    $ipRequestCount = (int) $stmt->fetchColumn();

    if ($ipRequestCount >= 10) {
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
     * sendOtpSms() returns the actual OTP code.
     */
    $code = sendOtpSms($phone);

    if ($code === '') {
        throw new Exception(
            'SMS service did not return a verification code'
        );
    }

    /*
     * Hash OTP before storing it.
     *
     * The raw OTP is never stored in the database.
     */
    $codeHash = password_hash(
        $code,
        PASSWORD_DEFAULT
    );

    if ($codeHash === false) {
        throw new Exception(
            'Unable to securely hash verification code'
        );
    }

    /*
     * OTP validity: 2 minutes.
     */
    $expiresAt = date(
        'Y-m-d H:i:s',
        time() + (2 * 60)
    );

    /*
     * Store hashed OTP.
     */
    $stmt = $pdo->prepare("
        INSERT INTO otp_codes (
            phone,
            ip_address,
            code,
            attempts,
            expires_at,
            used_at
        )
        VALUES (
            :phone,
            :ip_address,
            :code,
            0,
            :expires_at,
            NULL
        )
    ");

    $stmt->execute([
        'phone' => $phone,
        'ip_address' => $ipAddress,
        'code' => $codeHash,
        'expires_at' => $expiresAt
    ]);

    /*
     * Delete expired OTP records only after
     * successful creation of the new OTP.
     *
     * This does not affect the rate-limit checks above.
     */
    $stmt = $pdo->prepare("
        DELETE FROM otp_codes
        WHERE expires_at <= NOW()
          AND used_at IS NULL
    ");

    $stmt->execute();

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
        'message' => 'Unable to send OTP. Please try again later.'
    ], JSON_UNESCAPED_UNICODE);
}