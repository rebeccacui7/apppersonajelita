-- =====================================================================
-- 演示数据（可选，仅用于本地体验，请勿导入生产环境）
-- 演示账号密码均为 admin123：sales / pm / finance / buyer
-- =====================================================================
SET NAMES utf8mb4;

INSERT INTO sys_user (id, username, password, realname, dept_id, status) VALUES
 (2, 'sales',   '$2y$10$5bYEIdvvLy4sZnsmYbuZt.2xVWnY3/P2cul1anqD6XyPI8TRQEU8e', '张销售', 2, 1),
 (3, 'pm',      '$2y$10$5bYEIdvvLy4sZnsmYbuZt.2xVWnY3/P2cul1anqD6XyPI8TRQEU8e', '李项目', 3, 1),
 (4, 'finance', '$2y$10$5bYEIdvvLy4sZnsmYbuZt.2xVWnY3/P2cul1anqD6XyPI8TRQEU8e', '王财务', 4, 1),
 (5, 'buyer',   '$2y$10$5bYEIdvvLy4sZnsmYbuZt.2xVWnY3/P2cul1anqD6XyPI8TRQEU8e', '赵采购', 5, 1);
INSERT INTO sys_user_role (user_id, role_id) VALUES (2, 2), (3, 3), (4, 4), (5, 5);

INSERT INTO crm_customer (id, name, type, level, status, industry, source, phone, owner_id, next_follow_at, created_by, created_at) VALUES
 (1, '星河科技有限公司', 1, 1, 3, '互联网', 2, '010-88886666', 2, CURDATE() + INTERVAL 3 DAY, 2, NOW() - INTERVAL 60 DAY),
 (2, '远航物流集团',     1, 1, 2, '物流',   3, '021-66668888', 2, CURDATE() + INTERVAL 1 DAY, 2, NOW() - INTERVAL 30 DAY),
 (3, '市第一人民医院',   2, 2, 2, '医疗',   1, '0755-1234567', 2, CURDATE() - INTERVAL 1 DAY, 2, NOW() - INTERVAL 10 DAY),
 (4, '青禾餐饮连锁',     1, 3, 1, '餐饮',   4, '0571-7654321', 1, NULL, 1, NOW() - INTERVAL 2 DAY);

INSERT INTO crm_contact (customer_id, name, position, mobile, is_primary, created_by) VALUES
 (1, '陈总', 'CTO', '13800000001', 1, 2),
 (2, '刘经理', '信息部经理', '13800000002', 1, 2),
 (3, '周主任', '信息科主任', '13800000003', 1, 2);

INSERT INTO crm_opportunity (id, title, customer_id, amount, stage, probability, expected_close, owner_id, closed_at, created_by) VALUES
 (1, '星河科技 OA 系统建设', 1, 380000, 5, 100, CURDATE() - INTERVAL 40 DAY, 2, CURDATE() - INTERVAL 40 DAY, 2),
 (2, '远航物流 TMS 升级',    2, 650000, 4, 70,  CURDATE() + INTERVAL 20 DAY, 2, NULL, 2),
 (3, '医院运维外包',         3, 220000, 2, 30,  CURDATE() + INTERVAL 60 DAY, 2, NULL, 2),
 (4, '青禾门店收银',         4, 80000,  1, 10,  CURDATE() + INTERVAL 90 DAY, 1, NULL, 1);

INSERT INTO crm_followup (customer_id, opportunity_id, method, follow_at, content, next_plan, next_at, owner_id, created_by) VALUES
 (2, 2, 2, NOW() - INTERVAL 2 DAY, '上门演示 TMS 方案，客户认可调度模块。', '发送正式报价', CURDATE() + INTERVAL 1 DAY, 2, 2),
 (3, 3, 1, NOW() - INTERVAL 5 DAY, '电话沟通运维需求范围。', '提交需求确认书', CURDATE() - INTERVAL 1 DAY, 2, 2);

INSERT INTO proj_project (id, code, name, customer_id, opportunity_id, contract_no, contract_amount, sign_date, start_date, end_date, status, progress, manager_id, created_by) VALUES
 (1, 'PRJ20260801001', '星河科技 OA 系统建设', 1, 1, 'HT-2026-018', 380000, CURDATE() - INTERVAL 38 DAY, CURDATE() - INTERVAL 35 DAY, CURDATE() + INTERVAL 55 DAY, 2, 45, 3, 1);

INSERT INTO proj_task (project_id, name, type, priority, assignee_id, start_date, due_date, status, progress, created_by) VALUES
 (1, '需求调研与确认', 1, 1, 3, CURDATE() - INTERVAL 35 DAY, CURDATE() - INTERVAL 20 DAY, 3, 100, 3),
 (1, '系统开发',       2, 1, 3, CURDATE() - INTERVAL 19 DAY, CURDATE() + INTERVAL 25 DAY, 2, 50,  3),
 (1, '上线验收',       1, 2, 3, CURDATE() + INTERVAL 40 DAY, CURDATE() + INTERVAL 55 DAY, 1, 0,   3);
-- 与任务进度保持一致：(100+50+0)/3
UPDATE proj_project SET progress = 50 WHERE id = 1;

INSERT INTO fin_receivable (id, title, customer_id, project_id, amount, received_amount, due_date, status, owner_id, created_by) VALUES
 (1, '首付款 40%', 1, 1, 152000, 152000, CURDATE() - INTERVAL 30 DAY, 3, 2, 4),
 (2, '进度款 40%', 1, 1, 152000, 0,      CURDATE() + INTERVAL 15 DAY, 1, 2, 4),
 (3, '尾款 20%',   1, 1, 76000,  0,      CURDATE() + INTERVAL 60 DAY, 1, 2, 4);

INSERT INTO sup_supplier (id, name, category, contact_name, phone, rating, status, created_by) VALUES
 (1, '华信服务器设备有限公司', 1, '孙经理', '13900000001', 1, 1, 5),
 (2, '码农外包工作室',         3, '吴工',   '13900000002', 2, 1, 5);

INSERT INTO sup_purchase (id, code, title, supplier_id, project_id, amount, order_date, delivery_date, status, owner_id, created_by) VALUES
 (1, 'PO20260810001', '应用服务器 x2', 1, 1, 46000, CURDATE() - INTERVAL 25 DAY, CURDATE() - INTERVAL 10 DAY, 3, 5, 5);

INSERT INTO fin_payable (id, title, supplier_id, purchase_id, project_id, amount, paid_amount, due_date, status, owner_id, created_by) VALUES
 (1, '采购款 PO20260810001', 1, 1, 1, 46000, 20000, CURDATE() + INTERVAL 5 DAY, 2, 5, 5);

INSERT INTO fin_transaction (summary, type, category, amount, trade_date, account, customer_id, supplier_id, project_id, receivable_id, payable_id, handler_id, created_by) VALUES
 ('星河科技首付款',     1, 1, 152000, CURDATE() - INTERVAL 29 DAY, 1, 1, NULL, 1, 1, NULL, 4, 4),
 ('服务器采购预付款',   2, 2, 20000,  CURDATE() - INTERVAL 20 DAY, 1, NULL, 1, 1, NULL, 1, 4, 4),
 ('9月办公室房租',      2, 6, 18000,  CURDATE() - INTERVAL 32 DAY, 1, NULL, NULL, NULL, NULL, NULL, 4, 4),
 ('8月工资社保',        2, 4, 96000,  CURDATE() - INTERVAL 45 DAY, 1, NULL, NULL, NULL, NULL, NULL, 4, 4),
 ('老客户维护费',       1, 1, 30000,  CURDATE() - INTERVAL 75 DAY, 1, NULL, NULL, NULL, NULL, NULL, 4, 4);

INSERT INTO fin_expense (code, applicant_id, category, amount, expense_date, project_id, description, status, created_by) VALUES
 ('BX20260920001', 3, 1, 2380.50, CURDATE() - INTERVAL 7 DAY, 1, '赴客户现场差旅（高铁+住宿 2 晚）', 1, 3),
 ('BX20260921001', 2, 2, 860.00,  CURDATE() - INTERVAL 5 DAY, NULL, '客户招待餐费', 1, 2);
