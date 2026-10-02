<?php
declare(strict_types=1);

use App\Core\Auth;
use App\Core\Config;
use App\Core\Csrf;

function config(string $key, mixed $default = null): mixed
{
    return Config::get($key, $default);
}

/** HTML 转义 */
function e(mixed $value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** 生成站内 URL，自动带上部署子目录 */
function url(string $path = '/', array $query = []): string
{
    $base = rtrim((string)config('app.base_url', ''), '/');
    if ($base === '') {
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    }
    $url = $base . '/' . ltrim($path, '/');
    return $query ? $url . '?' . http_build_query($query) : $url;
}

function asset(string $path): string
{
    $file = ROOT . '/public/assets/' . ltrim($path, '/');
    $v = is_file($file) ? filemtime($file) : 0;
    return url('assets/' . ltrim($path, '/')) . '?v=' . $v;
}

function can(string $perm): bool
{
    return Auth::can($perm);
}

function csrf_token(): string
{
    return Csrf::token();
}

function money(mixed $v): string
{
    return number_format((float)$v, 2);
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '';
}
