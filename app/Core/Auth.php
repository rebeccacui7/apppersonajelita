<?php
declare(strict_types=1);

namespace App\Core;

/**
 * 认证与 RBAC 授权
 *  - 用户 -> 多角色 -> 菜单/按钮权限码（perm）
 *  - 数据范围取用户所有角色中最宽的一个：1 全部 / 2 本部门 / 3 仅本人
 *  - is_super = 1 的用户拥有全部权限
 */
class Auth
{
    public const SCOPE_ALL  = 1;
    public const SCOPE_DEPT = 2;
    public const SCOPE_SELF = 3;

    private static ?array $user = null;
    private static ?array $perms = null;
    private static ?int $scope = null;

    public static function attempt(string $username, string $password): array
    {
        $user = DB::row('SELECT * FROM sys_user WHERE username = ? AND deleted_at IS NULL', [$username]);
        if (!$user || !password_verify($password, $user['password'])) {
            throw new BizException('用户名或密码错误');
        }
        if ((int)$user['status'] !== 1) {
            throw new BizException('账号已被禁用');
        }
        if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
            DB::update('sys_user', ['password' => password_hash($password, PASSWORD_DEFAULT)], (int)$user['id']);
        }
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$user['id'];
        DB::update('sys_user', ['last_login_at' => date('Y-m-d H:i:s'), 'last_login_ip' => client_ip()], (int)$user['id']);
        self::$user = null;
        return $user;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
        self::$user = self::$perms = null;
        self::$scope = null;
    }

    public static function id(): ?int
    {
        return isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : null;
    }

    /** 每次请求从库中读取，禁用/改权限即时生效 */
    public static function user(): ?array
    {
        if (self::$user === null && self::id()) {
            $u = DB::row(
                'SELECT id, username, realname, dept_id, is_super, status FROM sys_user WHERE id = ? AND deleted_at IS NULL',
                [self::id()]
            );
            if (!$u || (int)$u['status'] !== 1) {
                unset($_SESSION['uid']);
                return null;
            }
            self::$user = $u;
        }
        return self::$user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function isSuper(): bool
    {
        return (int)(self::user()['is_super'] ?? 0) === 1;
    }

    public static function permissions(): array
    {
        if (self::$perms === null) {
            self::$perms = [];
            if (self::id()) {
                $rows = DB::all(
                    'SELECT DISTINCT m.perm FROM sys_user_role ur
                     JOIN sys_role r ON r.id = ur.role_id AND r.status = 1 AND r.deleted_at IS NULL
                     JOIN sys_role_menu rm ON rm.role_id = r.id
                     JOIN sys_menu m ON m.id = rm.menu_id AND m.status = 1 AND m.deleted_at IS NULL
                     WHERE ur.user_id = ? AND m.perm <> \'\'',
                    [self::id()]
                );
                self::$perms = array_fill_keys(array_column($rows, 'perm'), true);
            }
        }
        return self::$perms;
    }

    public static function can(string $perm): bool
    {
        if (!self::check()) {
            return false;
        }
        return self::isSuper() || isset(self::permissions()[$perm]);
    }

    public static function dataScope(): int
    {
        if (self::$scope === null) {
            if (self::isSuper()) {
                self::$scope = self::SCOPE_ALL;
            } else {
                $v = DB::value(
                    'SELECT MIN(r.data_scope) FROM sys_user_role ur
                     JOIN sys_role r ON r.id = ur.role_id AND r.status = 1 AND r.deleted_at IS NULL
                     WHERE ur.user_id = ?',
                    [self::id()]
                );
                self::$scope = $v ? (int)$v : self::SCOPE_SELF;
            }
        }
        return self::$scope;
    }

    /**
     * 根据数据范围生成对“负责人列”的过滤条件
     * @return array{0:string,1:array} [sql, params]，sql 为空表示不过滤
     */
    public static function scopeSql(string $ownerColumn): array
    {
        $scope = self::dataScope();
        if ($scope === self::SCOPE_ALL) {
            return ['', []];
        }
        $deptId = self::user()['dept_id'] ?? null;
        if ($scope === self::SCOPE_DEPT && $deptId) {
            return ["$ownerColumn IN (SELECT id FROM sys_user WHERE dept_id = ?)", [(int)$deptId]];
        }
        return ["$ownerColumn = ?", [self::id()]];
    }

    /** 当前用户可见的侧边栏菜单树 */
    public static function menus(): array
    {
        $rows = DB::all(
            'SELECT id, parent_id, name, type, perm, path, icon FROM sys_menu
             WHERE type IN (1, 2) AND status = 1 AND deleted_at IS NULL ORDER BY sort, id'
        );
        $byParent = [];
        foreach ($rows as $r) {
            $byParent[(int)$r['parent_id']][] = $r;
        }
        $build = function (int $pid) use (&$build, $byParent): array {
            $out = [];
            foreach ($byParent[$pid] ?? [] as $m) {
                if ((int)$m['type'] === 1) {
                    $m['children'] = $build((int)$m['id']);
                    if ($m['children']) {
                        $out[] = $m;
                    }
                } elseif ($m['perm'] === '' || self::can($m['perm'])) {
                    $out[] = $m;
                }
            }
            return $out;
        };
        return $build(0);
    }
}
