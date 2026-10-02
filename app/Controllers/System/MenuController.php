<?php
declare(strict_types=1);

namespace App\Controllers\System;

use App\Core\BizException;
use App\Core\DB;
use App\Core\ResourceController;
use App\Dict;

/** 系统管理 - 菜单与权限点 */
class MenuController extends ResourceController
{
    protected string $table = 'sys_menu';
    protected string $perm = 'menu';
    protected string $title = '菜单权限';
    protected string $path = '/system/menus';
    protected ?string $ownerField = null;

    protected function fields(): array
    {
        return [
            'name'      => ['label' => '名称', 'required' => true, 'search' => true],
            'parent_id' => ['label' => '上级', 'type' => 'relation', 'table' => 'sys_menu', 'display' => 'name', 'filter' => true],
            'type'      => ['label' => '类型', 'type' => 'select', 'options' => Dict::MENU_TYPE, 'default' => 2, 'required' => true, 'filter' => true, 'width' => 80],
            'perm'      => ['label' => '权限标识', 'search' => true, 'tips' => '如 customer.view；目录可留空'],
            'path'      => ['label' => '路由地址', 'tips' => '菜单必填，如 /customers'],
            'icon'      => ['label' => '图标', 'tips' => 'Layui 图标类名，如 layui-icon-user'],
            'sort'      => ['label' => '排序', 'type' => 'number', 'default' => 0, 'sort' => true, 'width' => 80],
            'status'    => ['label' => '状态', 'type' => 'select', 'options' => Dict::STATUS, 'default' => 1, 'required' => true, 'width' => 80],
        ];
    }

    protected function beforeSave(array $data, ?array $old): array
    {
        $data['perm'] = (string)$data['perm'];
        $data['path'] = (string)$data['path'];
        $data['icon'] = (string)$data['icon'];
        if ((int)$data['type'] === 2 && $data['path'] === '') {
            throw new BizException('菜单需填写路由地址');
        }
        if ((int)$data['type'] !== 1 && $data['perm'] === '') {
            throw new BizException('菜单/按钮需填写权限标识');
        }
        if ($old && (int)$data['parent_id'] === (int)$old['id']) {
            throw new BizException('上级不能是自身');
        }
        return $data;
    }

    protected function beforeDelete(array $row): void
    {
        if (DB::value('SELECT COUNT(*) FROM sys_menu WHERE parent_id = ? AND deleted_at IS NULL', [$row['id']]) > 0) {
            throw new BizException("「{$row['name']}」下有子项，不能删除");
        }
    }

    protected function afterDelete(array $row): void
    {
        DB::query('DELETE FROM sys_role_menu WHERE menu_id = ?', [$row['id']]);
    }
}
