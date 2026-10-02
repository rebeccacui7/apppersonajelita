<?php
declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function render(string $template, array $data = [], ?string $layout = 'layout/main'): void
    {
        echo View::render($template, $data, $layout);
    }

    protected function success(string $msg = '操作成功', mixed $data = null): void
    {
        Response::json(['code' => 0, 'msg' => $msg, 'data' => $data]);
    }

    protected function fail(string $msg, int $code = 1): void
    {
        Response::json(['code' => $code, 'msg' => $msg]);
    }

    /** Layui table 标准返回格式 */
    protected function table(array $rows, int $count): void
    {
        Response::json(['code' => 0, 'msg' => '', 'count' => $count, 'data' => $rows]);
    }
}
