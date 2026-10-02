<?php
declare(strict_types=1);

namespace App\Core;

class Response
{
    public static function json(array $data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function redirect(string $url): void
    {
        header('Location: ' . $url);
    }

    /** 输出 CSV（带 BOM，Excel 打开不乱码） */
    public static function csv(string $filename, array $header, iterable $rows): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode($filename));
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $header);
        foreach ($rows as $row) {
            // 防 CSV 公式注入
            $row = array_map(fn($v) => is_string($v) && preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v, $row);
            fputcsv($out, $row);
        }
        fclose($out);
    }
}
