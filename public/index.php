<?php
declare(strict_types=1);

// php -S 内置服务器：静态文件直接返回
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file)) {
        return false;
    }
}

define('ROOT', dirname(__DIR__));
require ROOT . '/app/bootstrap.php';

(new App\Core\App())->run();
