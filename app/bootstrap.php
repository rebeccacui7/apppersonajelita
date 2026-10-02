<?php
declare(strict_types=1);

// PSR-4 风格自动加载：App\ => app/
spl_autoload_register(function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $file = ROOT . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

require ROOT . '/app/helpers.php';

$config = require ROOT . '/config/config.php';
if (is_file(ROOT . '/config/config.local.php')) {
    $config = array_replace_recursive($config, require ROOT . '/config/config.local.php');
}
App\Core\Config::load($config);

date_default_timezone_set(config('app.timezone', 'Asia/Shanghai'));
error_reporting(E_ALL);
ini_set('display_errors', config('app.debug') ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', ROOT . '/storage/logs/php-error.log');

session_name('OASESSID');
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();
