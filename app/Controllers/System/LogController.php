<?php
declare(strict_types=1);

namespace App\Controllers\System;

use App\Core\ResourceController;

/** 系统管理 - 操作日志（只读） */
class LogController extends ResourceController
{
    protected string $table = 'sys_log';
    protected string $perm = 'log';
    protected string $title = '操作日志';
    protected string $path = '/system/logs';
    protected ?string $ownerField = null;
    protected string $nameField = 'action';
    protected bool $softDelete = false;
    protected bool $hasCreatedBy = false;
    protected bool $readonly = true;
    protected ?string $dateField = 'created_at';

    protected function fields(): array
    {
        return [
            'username'   => ['label' => '操作人', 'search' => true, 'width' => 110],
            'module'     => ['label' => '模块', 'search' => true, 'width' => 110],
            'action'     => ['label' => '动作', 'search' => true, 'width' => 110],
            'target_id'  => ['label' => '对象ID', 'type' => 'number', 'width' => 90],
            'content'    => ['label' => '内容', 'type' => 'textarea', 'list' => true, 'search' => true, 'width' => 320],
            'ip'         => ['label' => 'IP', 'width' => 130],
            'created_at' => ['label' => '时间', 'type' => 'datetime', 'width' => 170],
        ];
    }
}
