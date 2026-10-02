<?php
declare(strict_types=1);

namespace App\Core;

class Router
{
    /** @var array<string, array{0:string,1:string,2:?string,3:bool}> */
    private array $routes = [];

    public function get(string $path, string $controller, string $method, ?string $perm = null, bool $public = false): self
    {
        $this->routes['GET ' . $this->norm($path)] = [$controller, $method, $perm, $public];
        return $this;
    }

    public function post(string $path, string $controller, string $method, ?string $perm = null, bool $public = false): self
    {
        $this->routes['POST ' . $this->norm($path)] = [$controller, $method, $perm, $public];
        return $this;
    }

    /**
     * 注册标准 CRUD 路由（配合 ResourceController）
     *  GET  /x          列表页        perm.view
     *  GET  /x/list     列表数据 JSON  perm.view
     *  GET  /x/export   导出 CSV      perm.view
     *  GET  /x/view     详情页        perm.view
     *  GET  /x/form     新增/编辑表单  perm.edit
     *  POST /x/save     保存          perm.edit
     *  POST /x/delete   删除          perm.delete
     */
    public function resource(string $path, string $controller, string $perm): self
    {
        $path = $this->norm($path);
        return $this
            ->get($path, $controller, 'index', "$perm.view")
            ->get("$path/list", $controller, 'list', "$perm.view")
            ->get("$path/export", $controller, 'export', "$perm.view")
            ->get("$path/view", $controller, 'view', "$perm.view")
            ->get("$path/form", $controller, 'form', "$perm.edit")
            ->post("$path/save", $controller, 'save', "$perm.edit")
            ->post("$path/delete", $controller, 'delete', "$perm.delete");
    }

    public function match(string $httpMethod, string $path): ?array
    {
        return $this->routes[strtoupper($httpMethod) . ' ' . $this->norm($path)] ?? null;
    }

    private function norm(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '/' ? '/' : rtrim($path, '/');
    }
}
