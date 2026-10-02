<?php
declare(strict_types=1);

namespace App\Core;

class View
{
    /**
     * 渲染 app/Views 下的 PHP 模板
     * @param string|null $layout 布局模板，null 表示不套布局
     */
    public static function render(string $template, array $data = [], ?string $layout = 'layout/main'): string
    {
        $content = self::fetch($template, $data);
        if ($layout === null) {
            return $content;
        }
        return self::fetch($layout, $data + ['content' => $content]);
    }

    public static function fetch(string $template, array $data = []): string
    {
        $file = ROOT . '/app/Views/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("View not found: $template");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        try {
            include $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string)ob_get_clean();
    }
}
