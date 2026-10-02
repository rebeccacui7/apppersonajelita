<?php
declare(strict_types=1);

namespace App\Core;

/** 操作日志，写入 sys_log */
class Logger
{
    public static function log(string $module, string $action, ?int $targetId = null, string $content = ''): void
    {
        try {
            $user = Auth::user();
            DB::insert('sys_log', [
                'user_id'    => $user['id'] ?? null,
                'username'   => $user['username'] ?? '',
                'module'     => $module,
                'action'     => $action,
                'target_id'  => $targetId,
                'content'    => mb_substr($content, 0, 2000),
                'ip'         => client_ip(),
                'user_agent' => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            ]);
        } catch (\Throwable $e) {
            error_log('[sys_log] ' . $e->getMessage());
        }
    }
}
