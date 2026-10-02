<?php
declare(strict_types=1);

namespace App\Controllers\System;

use App\Core\Auth;
use App\Core\BizException;
use App\Core\DB;
use App\Core\ResourceController;
use App\Dict;

/** 系统管理 - 用户 */
class UserController extends ResourceController
{
    protected string $table = 'sys_user';
    protected string $perm = 'user';
    protected string $title = '用户';
    protected string $path = '/system/users';
    protected ?string $ownerField = null;
    protected string $nameField = 'realname';
    protected array $hidden = ['password'];

    protected function fields(): array
    {
        $roles = DB::all('SELECT id, name FROM sys_role WHERE deleted_at IS NULL ORDER BY sort, id');
        return [
            'username'      => ['label' => '登录账号', 'required' => true, 'search' => true, 'tips' => '字母、数字、下划线，3~32 位'],
            'realname'      => ['label' => '姓名', 'required' => true, 'search' => true],
            'password'      => ['label' => '密码', 'type' => 'password', 'tips' => '至少 8 位'],
            'dept_id'       => ['label' => '部门', 'type' => 'relation', 'table' => 'sys_dept', 'display' => 'name', 'filter' => true],
            'mobile'        => ['label' => '手机', 'search' => true],
            'email'         => ['label' => '邮箱'],
            'status'        => ['label' => '状态', 'type' => 'select', 'options' => Dict::STATUS, 'default' => 1, 'required' => true, 'filter' => true, 'width' => 80],
            'roles'         => ['label' => '角色', 'type' => 'checkbox', 'virtual' => true, 'options' => array_column($roles, 'name', 'id')],
            'last_login_at' => ['label' => '最后登录', 'type' => 'datetime', 'form' => false, 'width' => 170],
        ];
    }

    protected function formRow(array $row): array
    {
        if (!empty($row['id'])) {
            $row['roles'] = array_column(DB::all('SELECT role_id FROM sys_user_role WHERE user_id = ?', [$row['id']]), 'role_id');
        }
        unset($row['password']);
        return $row;
    }

    protected function beforeSave(array $data, ?array $old): array
    {
        if (!preg_match('/^[A-Za-z0-9_]{3,32}$/', (string)$data['username'])) {
            throw new BizException('登录账号格式不正确');
        }
        // 包含已删除账号，避免唯一索引冲突
        if (DB::value('SELECT id FROM sys_user WHERE username = ? AND id <> ?', [$data['username'], $old['id'] ?? 0])) {
            throw new BizException('登录账号已存在');
        }
        if ($old && (int)$old['is_super'] === 1 && !Auth::isSuper()) {
            throw new BizException('无权修改超级管理员');
        }
        if ($old && (int)$old['id'] === Auth::id() && (int)$data['status'] !== 1) {
            throw new BizException('不能禁用自己的账号');
        }

        $password = (string)($_POST['password'] ?? '');
        if ($password !== '') {
            if (strlen($password) < 8) {
                throw new BizException('密码至少 8 位');
            }
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        } elseif (!$old) {
            throw new BizException('请设置初始密码');
        }
        return $data;
    }

    protected function afterSave(int $id, array $data, ?array $old): void
    {
        $roleIds = array_map('intval', array_keys((array)($_POST['roles'] ?? [])));
        $valid = $roleIds ? array_column(DB::all(
            'SELECT id FROM sys_role WHERE deleted_at IS NULL AND id IN (' . implode(',', array_fill(0, count($roleIds), '?')) . ')',
            $roleIds
        ), 'id') : [];

        // 防止非超管给自己提权
        if ($id === Auth::id() && !Auth::isSuper()) {
            $current = array_map('intval', array_column(DB::all('SELECT role_id FROM sys_user_role WHERE user_id = ?', [$id]), 'role_id'));
            sort($current);
            $next = array_map('intval', $valid);
            sort($next);
            if ($current !== $next) {
                throw new BizException('不能修改自己的角色');
            }
            return;
        }

        DB::query('DELETE FROM sys_user_role WHERE user_id = ?', [$id]);
        foreach ($valid as $rid) {
            DB::insert('sys_user_role', ['user_id' => $id, 'role_id' => (int)$rid]);
        }
    }

    protected function beforeDelete(array $row): void
    {
        if ((int)$row['id'] === Auth::id()) {
            throw new BizException('不能删除自己');
        }
        if ((int)$row['is_super'] === 1) {
            throw new BizException('超级管理员不能删除');
        }
    }

    protected function afterDelete(array $row): void
    {
        DB::query('DELETE FROM sys_user_role WHERE user_id = ?', [$row['id']]);
    }
}
