# 项目说明（给 Claude）

企业内部管理系统：PHP 8.0 + MySQL + Layui，自研轻量 MVC，零 Composer 依赖。设计文档见 `docs/DESIGN.md`。

- 远程仓库：https://github.com/rebeccacui7/apppersonajelita （`main` 分支）
- 线上部署：宝塔 Git 部署，站点 app.personajelita.com（运行目录 `/public`），从 `main` 拉取

## Git 工作流（用户要求：改完自动提交）
每完成一个功能 / 修复后，**无需再次询问**，直接：
1. `git add` 相关文件，用中文写清晰的提交信息（`feat:` / `fix:` / `docs:` / `refactor:` 前缀，正文列出要点）
2. `git push origin main`
3. 回复中告知提交哈希和改动摘要

注意：
- 一个功能一次提交；未完成或明显有问题的半成品不要推送，先说明情况
- 绝不提交 `config/config.local.php`、密码、Token 等敏感信息
- 修改表结构时同步更新 `database/schema.sql`，并在 `database/migrations/` 新增 `YYYYMMDD_说明.sql` 增量脚本，方便线上执行
- 推送到 `main` 后线上需在宝塔里拉取（或配置 Webhook）才会更新，提醒用户

## 开发约定
- 新业务模块：继承 `App\Core\ResourceController` 声明字段 → `config/routes.php` 注册 `resource()` → `database/seed.sql` 补菜单与权限码
- 枚举值放 `app/Dict.php`；模板输出一律 `e()` 转义；SQL 一律参数绑定
- 本机未安装 PHP/MySQL，改动无法本地运行时要如实说明
