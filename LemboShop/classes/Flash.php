<?php
declare(strict_types=1);

/**
 * One-request flash messages, form errors and old input (for redisplaying forms).
 */
final class Flash
{
    public static function add(string $type, string $message): void
    {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    public static function success(string $message): void { self::add('success', $message); }
    public static function error(string $message): void { self::add('error', $message); }
    public static function info(string $message): void { self::add('info', $message); }

    public static function messages(): array
    {
        return Session::pull('_flash', []);
    }

    /** Store validation errors and submitted input for the next request. */
    public static function withInput(array $errors, array $input): void
    {
        // Never round-trip secrets through the session
        unset($input['password'], $input['password_confirm'], $input['current_password'], $input['_csrf']);
        $_SESSION['_errors'] = $errors;
        $_SESSION['_old'] = $input;
    }

    public static function errors(): array
    {
        static $errors = null;
        return $errors ??= Session::pull('_errors', []);
    }

    public static function old(): array
    {
        static $old = null;
        return $old ??= Session::pull('_old', []);
    }
}
