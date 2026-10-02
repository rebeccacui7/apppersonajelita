<?php
// 默认配置：优先读取环境变量；本地覆盖请复制为 config.local.php（已加入 .gitignore）
return [
    'app' => [
        'name'     => getenv('APP_NAME') ?: '企业内部管理系统',
        'debug'    => (getenv('APP_DEBUG') ?: '0') === '1',
        'timezone' => 'Asia/Shanghai',
        // 部署在子目录且无 URL 重写时，可设为 '/子目录/index.php'
        'base_url' => getenv('APP_BASE_URL') ?: '',
    ],
    'db' => [
        'host'     => getenv('DB_HOST') ?: '127.0.0.1',
        'port'     => (int)(getenv('DB_PORT') ?: 3306),
        'database' => getenv('DB_DATABASE') ?: 'oa_admin',
        'username' => getenv('DB_USERNAME') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: '',
        'charset'  => 'utf8mb4',
    ],
];
