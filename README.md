# 企业内部管理系统（OA Admin）

基于 **PHP 8.0 + MySQL + Layui** 的轻量公司内部管理后台，零 Composer 依赖。

**模块**：仪表盘 · 客户管理 · 商机管理 · 执行管理（项目/任务） · 财务管理（应收/应付/收支/报销） · 供应商管理（供应商/采购） · 系统管理（用户/角色权限/部门/菜单/日志）

完整设计见 👉 [docs/DESIGN.md](docs/DESIGN.md)

## 快速开始

### 方式一：Docker（推荐体验）
```bash
docker compose up -d
```
访问 <http://localhost:8080>，默认账号 `admin / admin123`。
已自动导入演示数据，另有演示账号 `sales` / `pm` / `finance` / `buyer`（密码同为 `admin123`），可用来体验不同角色的权限与数据范围。

### 方式二：本地 PHP
要求：PHP ≥ 8.0（扩展 `pdo_mysql`、`mbstring`），MySQL 5.7+/8.0。

```bash
mysql -uroot -p -e "CREATE DATABASE oa_admin DEFAULT CHARSET utf8mb4"
mysql -uroot -p oa_admin < database/schema.sql
mysql -uroot -p oa_admin < database/seed.sql
mysql -uroot -p oa_admin < database/demo.sql   # 可选：演示数据
cp config/config.local.example.php config/config.local.php   # 修改数据库连接
php -S localhost:8000 -t public public/index.php
```
访问 <http://localhost:8000>。

### 生产部署
- Web 根目录指向 `public/`（Apache 已带 `.htaccess`；Nginx 参考 `docker/nginx.conf.example`）
- 通过环境变量或 `config/config.local.php` 配置数据库，确保 `APP_DEBUG` 关闭
- `storage/logs/` 需要可写
- **首次登录后立即修改 admin 密码**；不要在生产导入 `demo.sql`
- 纯内网环境：把 Layui / ECharts 下载到 `public/assets/vendor/` 并修改 `app/Views/layout/head.php` 与 `dashboard/index.php` 中的 CDN 地址

## 目录
```
app/Core          框架内核（路由、DB、鉴权、通用 CRUD）
app/Controllers   业务控制器
app/Views         页面模板
config/           配置与路由
database/         建表、初始化、演示数据
public/           入口与静态资源
docs/DESIGN.md    系统设计说明
```

## 扩展一个新模块
写一个继承 `ResourceController` 的类声明字段 → `config/routes.php` 加一行 `resource()` → 后台「菜单权限」里加菜单并授权。详见设计文档第 6 节。
