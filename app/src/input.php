<?php
/**
 * Input validation. All authentication and other inputs are validated for
 * expected type, length and format before they reach any query. On failure a
 * ValidationError is raised and no query executes.
 */
declare(strict_types=1);

require_once __DIR__ . '/logger.php';

final class ValidationError extends RuntimeException
{
    /** @var array<string,string> */
    public array $errors;

    /** @param array<string,string> $errors field => message */
    public function __construct(array $errors, string $message = 'Validation failed.')
    {
        $this->errors = $errors;
        parent::__construct($message);
    }
}

final class Input
{
    /** Reject input, log a structured validation event, then throw. */
    private static function reject(string $field, string $reason, array $errors): void
    {
        Logger::log('validation_rejected', ['field' => $field, 'reason' => $reason], 'warning');
        throw new ValidationError($errors);
    }

    public static function trimmed(?string $value): string
    {
        return trim($value ?? '');
    }

    public static function email(?string $value, string $field = 'email'): string
    {
        $value = self::trimmed($value);
        if ($value === '') {
            self::reject($field, 'required', [$field => 'Email is required.']);
        }
        if (strlen($value) > 254) {
            self::reject($field, 'too_long', [$field => 'Email is too long.']);
        }
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            self::reject($field, 'invalid_format', [$field => 'Email is not a valid address.']);
        }
        return strtolower($value);
    }

    public static function password(?string $value, string $field = 'password'): string
    {
        $value = (string) ($value ?? '');
        if ($value === '') {
            self::reject($field, 'required', [$field => 'Password is required.']);
        }
        if (strlen($value) < 8 || strlen($value) > 128) {
            self::reject($field, 'invalid_length', [$field => 'Password must be between 8 and 128 characters.']);
        }
        return $value;
    }

    public static function name(?string $value, string $field = 'full_name'): string
    {
        $value = self::trimmed($value);
        if ($value === '') {
            self::reject($field, 'required', [$field => 'Full name is required.']);
        }
        if (strlen($value) > 100) {
            self::reject($field, 'too_long', [$field => 'Full name is too long (max 100 characters).']);
        }
        return $value;
    }

    public static function matricNo(?string $value, string $field = 'matric_no'): string
    {
        $value = self::trimmed($value);
        if ($value === '') {
            self::reject($field, 'required', [$field => 'Matriculation number is required.']);
        }
        // FUT Minna format, e.g. 2022/1-10111 (fictitious).
        if (!preg_match('/^[0-9]{4}\/[1-2]-[0-9]{4,6}$/', $value)) {
            self::reject($field, 'invalid_format', [$field => 'Matric number format is invalid.']);
        }
        return $value;
    }

    public static function phone(?string $value, string $field = 'phone'): string
    {
        $value = self::trimmed($value);
        if ($value === '') {
            return '';
        }
        if (strlen($value) > 20 || !preg_match('/^[0-9+()\-\s]{7,20}$/', $value)) {
            self::reject($field, 'invalid_format', [$field => 'Phone number is invalid.']);
        }
        return $value;
    }

    public static function intRange(
        mixed $value,
        int $min,
        int $max,
        string $field
    ): int {
        $v = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => $min, 'max_range' => $max],
        ]);
        if ($v === false) {
            self::reject($field, 'out_of_range', [$field => "Value must be between $min and $max."]);
        }
        return (int) $v;
    }

    public static function csrfToken(): string
    {
        $token = (string) ($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if ($token === '' || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            self::reject('csrf_token', 'missing_or_invalid', ['csrf_token' => 'Security token is missing or invalid.']);
        }
        return $token;
    }
}
