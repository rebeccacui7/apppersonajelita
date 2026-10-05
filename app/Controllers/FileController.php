<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\BizException;
use App\Core\Controller;
use App\Core\DB;
use App\Core\Request;

/**
 * 附件上传 / 下载
 * - 文件保存在 storage/uploads/年月/ 下（不在 Web 根目录，不能被直接访问）
 * - 通过 32 位随机 file_key 下载，需登录
 */
class FileController extends Controller
{
    private const MAX_SIZE = 20 * 1024 * 1024;

    /** 允许的扩展名 => Content-Type */
    private const TYPES = [
        'pdf'  => 'application/pdf',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls'  => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt'  => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'txt'  => 'text/plain; charset=utf-8',
        'zip'  => 'application/zip',
        'rar'  => 'application/vnd.rar',
        '7z'   => 'application/x-7z-compressed',
    ];

    /** 浏览器内直接预览的类型，其余一律下载 */
    private const INLINE = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'];

    public static function allowedExts(): array
    {
        return array_keys(self::TYPES);
    }

    public function upload(): void
    {
        $f = $_FILES['file'] ?? null;
        if (!is_array($f) || is_array($f['name'])) {
            throw new BizException('请选择文件');
        }
        if ($f['error'] !== UPLOAD_ERR_OK) {
            throw new BizException(in_array($f['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
                ? '文件超过服务器允许的大小' : '上传失败（错误码 ' . $f['error'] . '）');
        }
        if ($f['size'] <= 0 || $f['size'] > self::MAX_SIZE) {
            throw new BizException('单个文件不能超过 20MB');
        }
        $name = trim(str_replace(["\0", '/', '\\'], '', (string)$f['name']));
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!isset(self::TYPES[$ext])) {
            throw new BizException('不支持的文件类型，仅允许：' . implode('、', self::allowedExts()));
        }
        if (!is_uploaded_file($f['tmp_name'])) {
            throw new BizException('非法上传');
        }

        $dir = 'uploads/' . date('Ym');
        $abs = ROOT . '/storage/' . $dir;
        if (!is_dir($abs) && !mkdir($abs, 0755, true) && !is_dir($abs)) {
            throw new BizException('上传目录不可写，请检查 storage 目录权限');
        }
        $key = bin2hex(random_bytes(16));
        $rel = $dir . '/' . $key . '.' . $ext;
        if (!move_uploaded_file($f['tmp_name'], ROOT . '/storage/' . $rel)) {
            throw new BizException('保存文件失败，请检查 storage 目录权限');
        }

        DB::insert('sys_file', [
            'file_key'      => $key,
            'original_name' => mb_substr($name, 0, 255),
            'path'          => $rel,
            'ext'           => $ext,
            'size'          => (int)$f['size'],
            'created_by'    => Auth::id(),
        ]);
        $this->success('上传成功', ['key' => $key, 'name' => $name, 'size' => (int)$f['size']]);
    }

    public function download(): void
    {
        $key = Request::str('key');
        $file = preg_match('/^[a-f0-9]{32}$/', $key)
            ? DB::row('SELECT * FROM sys_file WHERE file_key = ?', [$key])
            : null;
        $abs = $file ? ROOT . '/storage/' . $file['path'] : '';
        if (!$file || !is_file($abs)) {
            throw new BizException('文件不存在或已被删除');
        }
        $ext = $file['ext'];
        $disposition = in_array($ext, self::INLINE, true) && Request::get('download') !== '1' ? 'inline' : 'attachment';

        header('Content-Type: ' . (self::TYPES[$ext] ?? 'application/octet-stream'));
        header('Content-Length: ' . filesize($abs));
        header("Content-Disposition: $disposition; filename*=UTF-8''" . rawurlencode($file['original_name']));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=3600');
        readfile($abs);
    }
}
