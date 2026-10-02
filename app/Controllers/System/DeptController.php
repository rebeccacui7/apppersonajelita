<?php
declare(strict_types=1);

namespace App\Controllers\System;

use App\Core\BizException;
use App\Core\DB;
use App\Core\ResourceController;

/** 系统管理 - 部门 */
class DeptController extends ResourceController
{
    protected string $table = 'sys_dept';
    protected string $perm = 'dept';
    protected string $title = '部门';
    protected string $path = '/system/depts';
    protected ?string $ownerField = null;

    protected function fields(): array
    {
        return [
            'name'      => ['label' => '部门名称', 'required' => true, 'search' => true],
            'parent_id' => ['label' => '上级部门', 'type' => 'relation', 'table' => 'sys_dept', 'display' => 'name'],
            'leader_id' => ['label' => '负责人', 'type' => 'user'],
            'sort'      => ['label' => '排序', 'type' => 'number', 'default' => 0, 'sort' => true],
        ];
    }

    protected function children(): array
    {
        return [['title' => '部门成员', 'path' => '/system/users', 'fk' => 'dept_id', 'perm' => 'user.view']];
    }

    protected function beforeSave(array $data, ?array $old): array
    {
        if ($old && $data['parent_id']) {
            // 防止把部门挂到自己或自己的下级下面形成环
            $pid = (int)$data['parent_id'];
            for ($i = 0; $pid && $i < 50; $i++) {
                if ($pid === (int)$old['id']) {
                    throw new BizException('上级部门不能是自身或其下级部门');
                }
                $pid = (int)DB::value('SELECT parent_id FROM sys_dept WHERE id = ?', [$pid]);
            }
        }
        return $data;
    }

    protected function beforeDelete(array $row): void
    {
        if (DB::value('SELECT COUNT(*) FROM sys_dept WHERE parent_id = ? AND deleted_at IS NULL', [$row['id']]) > 0) {
            throw new BizException("「{$row['name']}」下有子部门，不能删除");
        }
        if (DB::value('SELECT COUNT(*) FROM sys_user WHERE dept_id = ? AND deleted_at IS NULL', [$row['id']]) > 0) {
            throw new BizException("「{$row['name']}」下有成员，不能删除");
        }
    }
}
