<?php

function sendOtpSms(string $mobile): string
{
    $config = require __DIR__ . '/../config/sms.php';

    $data = [
        'to' => $mobile
    ];

    $dataString = json_encode(
        $data,
        JSON_UNESCAPED_UNICODE
    );

    $ch = curl_init($config['otp_url']);

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($dataString)
    ]);

    $result = curl_exec($ch);

    if ($result === false) {
        $error = curl_error($ch);
        curl_close($ch);

        throw new Exception(
            'SMS service connection failed: ' . $error
        );
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($httpCode < 200 || $httpCode >= 300) {
        throw new Exception(
            'SMS service returned HTTP ' . $httpCode .
            ' | RESPONSE: ' . $result
        );
    }

    $response = json_decode($result, true);

    if (!is_array($response)) {
        throw new Exception('Invalid SMS service response');
    }

    if (
        !isset($response['code']) ||
        empty($response['code'])
    ) {
        $status = $response['status'] ?? 'Unknown error';

        throw new Exception(
            'SMS service error: ' . $status
        );
    }

    return (string) $response['code'];
}