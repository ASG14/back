<?php

/**
 * Check whether a value is empty.
 */
function isEmptyValue($value): bool
{
    return $value === null || trim((string) $value) === '';
}


/**
 * Validate required fields.
 *
 * Example:
 * validateRequired([
 *     'title' => $title,
 *     'group_id' => $groupId
 * ]);
 */
function validateRequired(array $fields): array
{
    $errors = [];

    foreach ($fields as $name => $value) {
        if (isEmptyValue($value)) {
            $errors[$name] = "$name is required";
        }
    }

    return $errors;
}


/**
 * Validate integer ID.
 */
function validateId($value): bool
{
    return filter_var(
        $value,
        FILTER_VALIDATE_INT,
        [
            'options' => [
                'min_range' => 1
            ]
        ]
    ) !== false;
}


/**
 * Validate phone number.
 *
 * Current format:
 * 09xxxxxxxxx
 */
function validatePhone(string $phone): bool
{
    return preg_match('/^09[0-9]{9}$/', $phone) === 1;
}


/**
 * Validate verification code.
 *
 * Current code format:
 * 6 digits
 */
function validateVerificationCode(string $code): bool
{
    return preg_match('/^[0-9]{6}$/', $code) === 1;
}


/**
 * Validate text length.
 */
function validateTextLength(
    string $value,
    int $minLength = 1,
    int $maxLength = 255
): bool {
    $length = mb_strlen(trim($value));

    return $length >= $minLength && $length <= $maxLength;
}


/**
 * Validate group title.
 */
function validateGroupTitle(string $title): bool
{
    return validateTextLength($title, 1, 100);
}


/**
 * Validate order title.
 */
function validateOrderTitle(string $title): bool
{
    return validateTextLength($title, 1, 255);
}


/**
 * Validate order description.
 */
function validateOrderDescription(string $description): bool
{
    return validateTextLength($description, 0, 1000);
}


/**
 * Return validation error response.
 */
function validationError(array $errors): void
{
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $errors
    ]);

    exit;
}