<?php
declare(strict_types=1);

namespace App\Core;

class Request
{
    public static function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public static function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    public static function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    public static function int(string $key, int $default = 0): int
    {
        $v = self::input($key);
        return is_numeric($v) ? (int)$v : $default;
    }

    public static function str(string $key, string $default = ''): string
    {
        $v = self::input($key);
        return is_string($v) ? trim($v) : $default;
    }

    /** 读取 ids（数组或逗号分隔），只保留正整数 */
    public static function ids(string $key = 'ids'): array
    {
        $v = self::input($key, []);
        if (is_string($v)) {
            $v = explode(',', $v);
        }
        if (!is_array($v)) {
            return [];
        }
        return array_values(array_unique(array_filter(array_map('intval', $v), fn($i) => $i > 0)));
    }

    public static function isAjax(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }
}
