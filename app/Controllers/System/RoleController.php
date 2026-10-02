<?php
declare(strict_types=1);

namespace App\Controllers\System;

use App\Core\BizException;
use App\Core\DB;
use App\Core\Logger;
use App\Core\Request;
use App\Core\ResourceController;
use App\Dict;

/** 系统管理 - 角色与权限分配 */
class RoleController extends ResourceController
{
    protected string $table = 'sys_role';
    protected string $perm = 'role';
    protected string $title = '角色';
    protected string $path = '/system/roles';
    protected ?string $ownerField = null;

    protected function fields(): array
    {
        return [
            'name'       => ['label' => '角色名称', 'required' => true, 'search' => true],
            'code'       => ['label' => '角色编码', 'required' => true, 'search' => true],
            'data_scope' => ['label' => '数据范围', 'type' => 'select', 'options' => Dict::DATA_SCOPE, 'default' => 3, 'required' => true,
                             'tips' => '多角色时取最宽的范围'],
            'status'     => ['label' => '状态', 'type' => 'select', 'options' => Dict::STATUS, 'default' => 1, 'required' => true],
            'sort'       => ['label' => '排序', 'type' => 'number', 'default' => 0],
            'remark'     => ['label' => '备注', 'type' => 'textarea'],
        ];
    }

    protected function rowActions(): array
    {
        return [[
            'text' => '分配权限', 'url' => '/system/roles/perms', 'perm' => 'role.edit',
            'type' => 'open', 'area' => ['520px', '86%'], 'class' => 'layui-btn-warm',
        ]];
    }

    protected function beforeSave(array $data, ?array $old): array
    {
        if (!preg_match('/^[a-z][a-z0-9_]{1,31}$/', (string)$data['code'])) {
            throw new BizException('角色编码需为小写字母开头的字母/数字/下划线');
        }
        if (DB::value('SELECT id FROM sys_role WHERE code = ? AND id <> ?', [$data['code'], $old['id'] ?? 0])) {
            throw new BizException('角色编码已存在');
        }
        return $data;
    }

    protected function beforeDelete(array $row): void
    {
        if (DB::value('SELECT COUNT(*) FROM sys_user_role WHERE role_id = ?', [$row['id']]) > 0) {
            throw new BizException("角色「{$row['name']}」下仍有用户，请先移除");
        }
    }

    protected function afterDelete(array $row): void
    {
        DB::query('DELETE FROM sys_role_menu WHERE role_id = ?', [$row['id']]);
    }

    /** 权限分配页（菜单/按钮树） */
    public function permsPage(): void
    {
        $role = DB::row('SELECT id, name FROM sys_role WHERE id = ? AND deleted_at IS NULL', [Request::int('id')]);
        if (!$role) {
            throw new BizException('角色不存在');
        }
        $menus = DB::all('SELECT id, parent_id, name, type, perm FROM sys_menu WHERE deleted_at IS NULL AND status = 1 ORDER BY sort, id');
        $checked = array_fill_keys(array_column(DB::all('SELECT menu_id FROM sys_role_menu WHERE role_id = ?', [$role['id']]), 'menu_id'), true);

        $byParent = [];
        foreach ($menus as $m) {
            $byParent[(int)$m['parent_id']][] = $m;
        }
        $build = function (int $pid) use (&$build, $byParent, $checked): array {
            $out = [];
            foreach ($byParent[$pid] ?? [] as $m) {
                $children = $build((int)$m['id']);
                $node = [
                    'id'     => (int)$m['id'],
                    'title'  => $m['name'] . ($m['perm'] ? "（{$m['perm']}）" : ''),
                    'spread' => (int)$m['type'] === 1,
                ];
                if ($children) {
                    $node['children'] = $children;
                } elseif (isset($checked[$m['id']])) {
                    // 只勾选叶子节点，父节点状态由 tree 组件推导
                    $node['checked'] = true;
                }
                $out[] = $node;
            }
            return $out;
        };

        $this->render('system/role_perms', ['title' => '分配权限', 'role' => $role, 'tree' => $build(0)], 'layout/blank');
    }

    public function savePerms(): void
    {
        $id = Request::int('id');
        if (!DB::value('SELECT id FROM sys_role WHERE id = ? AND deleted_at IS NULL', [$id])) {
            throw new BizException('角色不存在');
        }
        $menuIds = Request::ids('menu_ids');
        DB::transaction(function () use ($id, $menuIds) {
            DB::query('DELETE FROM sys_role_menu WHERE role_id = ?', [$id]);
            if ($menuIds) {
                $valid = DB::all(
                    'SELECT id FROM sys_menu WHERE id IN (' . implode(',', array_fill(0, count($menuIds), '?')) . ')',
                    $menuIds
                );
                foreach (array_column($valid, 'id') as $mid) {
                    DB::insert('sys_role_menu', ['role_id' => $id, 'menu_id' => (int)$mid]);
                }
            }
        });
        Logger::log('role', 'assign_perms', $id, implode(',', $menuIds));
        $this->success('权限已保存');
    }
}
