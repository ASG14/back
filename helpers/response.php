<?php

header('Content-Type: application/json; charset=utf-8');

function successResponse($data = []) {

    echo json_encode([
        'success' => true,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


function errorResponse(
    string $message,
    int $statusCode = 400
) {

    http_response_code($statusCode);

    echo json_encode([
        'success' => false,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);

    exit;
}