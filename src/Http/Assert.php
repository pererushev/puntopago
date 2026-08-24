<?php
namespace App\Http;

use App\Exception\PaymentException;

final class Assert
{
    public static function positiveInt(array $body, string $field): int
    {
        if (!array_key_exists($field, $body)) {
            throw new PaymentException("Missing field: {$field}");
        }

        $raw = $body[$field];
        if (is_bool($raw) || is_array($raw) || is_object($raw) || $raw === null) {
            throw new PaymentException("{$field} must be a positive integer");
        }

        $value = filter_var($raw, FILTER_VALIDATE_INT);
        if ($value === false || $value <= 0) {
            throw new PaymentException("{$field} must be a positive integer");
        }

        return $value;
    }

    public static function currency(array $body, string $field = 'currency'): string
    {
        if (!array_key_exists($field, $body) || $body[$field] === null || $body[$field] === '') {
            throw new PaymentException("Missing field: {$field}");
        }

        if (!is_string($body[$field])) {
            throw new PaymentException("{$field} must be a 3-letter ISO code");
        }

        $currency = strtoupper($body[$field]);
        if (!preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new PaymentException("{$field} must be a 3-letter ISO code");
        }

        return $currency;
    }

    public static function requireString(array $body, string $field, int $maxLength = 255): string
    {
        if (!array_key_exists($field, $body) || $body[$field] === null) {
            throw new PaymentException("Missing field: {$field}");
        }

        if (!is_string($body[$field])) {
            throw new PaymentException("{$field} must be a string");
        }

        $value = trim($body[$field]);
        if ($value === '') {
            throw new PaymentException("Missing field: {$field}");
        }

        if (strlen($value) > $maxLength) {
            throw new PaymentException("{$field} must be at most {$maxLength} characters");
        }

        return $value;
    }

    public static function optionalString(array $body, string $field, int $maxLength): string
    {
        if (!array_key_exists($field, $body) || $body[$field] === null) {
            return '';
        }

        if (!is_string($body[$field])) {
            throw new PaymentException("{$field} must be a string");
        }

        $value = trim($body[$field]);
        if (strlen($value) > $maxLength) {
            throw new PaymentException("{$field} must be at most {$maxLength} characters");
        }

        return $value;
    }

    public static function idempotencyKey(?string $key): string
    {
        if ($key === null || $key === '') {
            throw new PaymentException('Idempotency-Key header is required');
        }

        if (strlen($key) > 64) {
            throw new PaymentException('Idempotency-Key must be at most 64 characters');
        }

        return $key;
    }

    public static function requiredHeader(string $value, string $name): string
    {
        if ($value === '') {
            throw new PaymentException("{$name} header is required");
        }

        return $value;
    }
}
