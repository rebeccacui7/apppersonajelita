-- =====================================================================
-- 初始化数据：部门 / 角色 / 菜单权限 / 超级管理员
-- 默认管理员 admin / admin123 —— 首次登录后请立即修改密码！
-- =====================================================================
SET NAMES utf8mb4;

-- 部门
INSERT INTO sys_dept (id, parent_id, name, sort) VALUES
 (1, NULL, '总经办', 1),
 (2, 1, '销售部', 2),
 (3, 1, '项目交付部', 3),
 (4, 1, '财务部', 4),
 (5, 1, '采购部', 5);

-- 角色（data_scope: 1全部 2本部门 3仅本人）
INSERT INTO sys_role (id, name, code, data_scope, sort, remark) VALUES
 (1, '管理员',   'admin',    1, 1, '拥有全部菜单权限'),
 (2, '销售',     'sales',    3, 2, '管理自己负责的客户、商机'),
 (3, '项目经理', 'pm',       2, 3, '负责项目执行与任务'),
 (4, '财务',     'finance',  1, 4, '应收应付、收支流水、报销审批与支付'),
 (5, '采购',     'purchase', 1, 5, '供应商与采购订单');

-- 超级管理员 admin / admin123（bcrypt）
INSERT INTO sys_user (id, username, password, realname, dept_id, is_super, status) VALUES
 (1, 'admin', '$2y$10$5bYEIdvvLy4sZnsmYbuZt.2xVWnY3/P2cul1anqD6XyPI8TRQEU8e', '系统管理员', 1, 1, 1);
INSERT INTO sys_user_role (user_id, role_id) VALUES (1, 1);

-- 菜单与权限点（type: 1目录 2菜单 3按钮）
INSERT INTO sys_menu (id, parent_id, name, type, perm, path, icon, sort) VALUES
 (1,  NULL, '仪表盘',   2, 'dashboard.view', '/', 'layui-icon-console', 1),

 (10, NULL, '客户管理', 1, '', '', 'layui-icon-user', 10),
 (11, 10,   '客户列表', 2, 'customer.view', '/customers', '', 1),
 (111, 11,  '新增/编辑', 3, 'customer.edit', '', '', 1),
 (112, 11,  '删除',     3, 'customer.delete', '', '', 2),
 (12, 10,   '联系人',   2, 'contact.view', '/contacts', '', 2),
 (121, 12,  '新增/编辑', 3, 'contact.edit', '', '', 1),
 (122, 12,  '删除',     3, 'contact.delete', '', '', 2),
 (13, 10,   '跟进记录', 2, 'followup.view', '/followups', '', 3),
 (131, 13,  '新增/编辑', 3, 'followup.edit', '', '', 1),
 (132, 13,  '删除',     3, 'followup.delete', '', '', 2),

 (20, NULL, '商机管理', 1, '', '', 'layui-icon-diamond', 20),
 (21, 20,   '商机列表', 2, 'opportunity.view', '/opportunities', '', 1),
 (211, 21,  '新增/编辑', 3, 'opportunity.edit', '', '', 1),
 (212, 21,  '删除',     3, 'opportunity.delete', '', '', 2),

 (30, NULL, '执行管理', 1, '', '', 'layui-icon-flag', 30),
 (31, 30,   '项目列表', 2, 'project.view', '/projects', '', 1),
 (311, 31,  '新增/编辑/商机转项目', 3, 'project.edit', '', '', 1),
 (312, 31,  '删除',     3, 'project.delete', '', '', 2),
 (32, 30,   '任务/里程碑', 2, 'task.view', '/tasks', '', 2),
 (321, 32,  '新增/编辑', 3, 'task.edit', '', '', 1),
 (322, 32,  '删除',     3, 'task.delete', '', '', 2),

 (40, NULL, '财务管理', 1, '', '', 'layui-icon-rmb', 40),
 (41, 40,   '应收款',   2, 'receivable.view', '/receivables', '', 1),
 (411, 41,  '新增/编辑', 3, 'receivable.edit', '', '', 1),
 (412, 41,  '删除',     3, 'receivable.delete', '', '', 2),
 (42, 40,   '应付款',   2, 'payable.view', '/payables', '', 2),
 (421, 42,  '新增/编辑/采购生成应付', 3, 'payable.edit', '', '', 1),
 (422, 42,  '删除',     3, 'payable.delete', '', '', 2),
 (43, 40,   '收支流水', 2, 'transaction.view', '/transactions', '', 3),
 (431, 43,  '新增/编辑', 3, 'transaction.edit', '', '', 1),
 (432, 43,  '删除',     3, 'transaction.delete', '', '', 2),
 (44, 40,   '费用报销', 2, 'expense.view', '/expenses', '', 4),
 (441, 44,  '提交/编辑', 3, 'expense.edit', '', '', 1),
 (442, 44,  '删除',     3, 'expense.delete', '', '', 2),
 (443, 44,  '审批',     3, 'expense.approve', '', '', 3),
 (444, 44,  '支付',     3, 'expense.pay', '', '', 4),

 (50, NULL, '供应商管理', 1, '', '', 'layui-icon-cart', 50),
 (51, 50,   '供应商',   2, 'supplier.view', '/suppliers', '', 1),
 (511, 51,  '新增/编辑', 3, 'supplier.edit', '', '', 1),
 (512, 51,  '删除',     3, 'supplier.delete', '', '', 2),
 (52, 50,   '采购订单', 2, 'purchase.view', '/purchases', '', 2),
 (521, 52,  '新增/编辑', 3, 'purchase.edit', '', '', 1),
 (522, 52,  '删除',     3, 'purchase.delete', '', '', 2),

 (90, NULL, '系统管理', 1, '', '', 'layui-icon-set', 90),
 (91, 90,   '用户管理', 2, 'user.view', '/system/users', '', 1),
 (911, 91,  '新增/编辑', 3, 'user.edit', '', '', 1),
 (912, 91,  '删除',     3, 'user.delete', '', '', 2),
 (92, 90,   '角色权限', 2, 'role.view', '/system/roles', '', 2),
 (921, 92,  '新增/编辑/分配权限', 3, 'role.edit', '', '', 1),
 (922, 92,  '删除',     3, 'role.delete', '', '', 2),
 (93, 90,   '部门管理', 2, 'dept.view', '/system/depts', '', 3),
 (931, 93,  '新增/编辑', 3, 'dept.edit', '', '', 1),
 (932, 93,  '删除',     3, 'dept.delete', '', '', 2),
 (94, 90,   '菜单权限', 2, 'menu.view', '/system/menus', '', 4),
 (941, 94,  '新增/编辑', 3, 'menu.edit', '', '', 1),
 (942, 94,  '删除',     3, 'menu.delete', '', '', 2),
 (95, 90,   '操作日志', 2, 'log.view', '/system/logs', '', 5);

-- 角色授权 ------------------------------------------------------------
-- 管理员：全部
INSERT INTO sys_role_menu (role_id, menu_id) SELECT 1, id FROM sys_menu;

-- 销售：客户、商机全部；项目/任务/应收只读；可提交报销
INSERT INTO sys_role_menu (role_id, menu_id) SELECT 2, id FROM sys_menu
 WHERE SUBSTRING_INDEX(perm, '.', 1) IN ('dashboard', 'customer', 'contact', 'followup', 'opportunity')
    OR perm IN ('project.view', 'task.view', 'receivable.view', 'expense.view', 'expense.edit');

-- 项目经理：项目、任务全部；客户/商机只读；可发起采购、提交报销
INSERT INTO sys_role_menu (role_id, menu_id) SELECT 3, id FROM sys_menu
 WHERE SUBSTRING_INDEX(perm, '.', 1) IN ('dashboard', 'project', 'task')
    OR perm IN ('customer.view', 'contact.view', 'followup.view', 'opportunity.view', 'receivable.view',
                'supplier.view', 'purchase.view', 'purchase.edit', 'expense.view', 'expense.edit');

-- 财务：财务模块全部；客户/项目/供应商/采购只读
INSERT INTO sys_role_menu (role_id, menu_id) SELECT 4, id FROM sys_menu
 WHERE SUBSTRING_INDEX(perm, '.', 1) IN ('dashboard', 'receivable', 'payable', 'transaction', 'expense')
    OR perm IN ('customer.view', 'project.view', 'supplier.view', 'purchase.view');

-- 采购：供应商、采购全部；应付只读，可由采购单生成应付
INSERT INTO sys_role_menu (role_id, menu_id) SELECT 5, id FROM sys_menu
 WHERE SUBSTRING_INDEX(perm, '.', 1) IN ('dashboard', 'supplier', 'purchase')
    OR perm IN ('payable.view', 'payable.edit', 'project.view', 'expense.view', 'expense.edit');

-- 把有可见子项的目录也勾上，便于在“分配权限”树中正确显示
INSERT IGNORE INTO sys_role_menu (role_id, menu_id)
SELECT DISTINCT rm.role_id, m.parent_id FROM sys_role_menu rm
JOIN sys_menu m ON m.id = rm.menu_id WHERE m.parent_id IS NOT NULL;
INSERT IGNORE INTO sys_role_menu (role_id, menu_id)
SELECT DISTINCT rm.role_id, m.parent_id FROM sys_role_menu rm
JOIN sys_menu m ON m.id = rm.menu_id WHERE m.parent_id IS NOT NULL;
