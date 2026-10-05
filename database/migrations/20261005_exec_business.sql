-- =====================================================================
-- 2026-10-05 执行管理改造：签证表 / 公司注册表 / 供应商应付 / 完成项目 + 附件上传
-- 线上已有数据库执行本脚本即可（可重复执行）
-- =====================================================================
SET NAMES utf8mb4;

-- 执行业务：签证 / 公司注册 共用一张表，按 biz_type + stage 划分四个列表
--   stage 1 在途（签证表/公司注册表）→ 2 供应商应付 → 3 完成项目
CREATE TABLE IF NOT EXISTS exec_business (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  biz_type      TINYINT NOT NULL COMMENT '1签证 2公司注册',
  stage         TINYINT NOT NULL DEFAULT 1 COMMENT '1在途 2供应商应付 3完成项目',
  group_name    VARCHAR(255) NOT NULL COMMENT '业务群名',
  contact_name  VARCHAR(255) NOT NULL DEFAULT '' COMMENT '对接人',
  business      VARCHAR(255) NOT NULL DEFAULT '' COMMENT '具体业务',
  applicant     VARCHAR(255) NOT NULL DEFAULT '' COMMENT '申请对象（签证）',
  start_date    DATE NULL,
  end_date      DATE NULL,
  supplier_id   INT UNSIGNED NULL COMMENT '供应商',
  files         TEXT NULL COMMENT '交付文件：sys_file.file_key 的 JSON 数组',
  status        TINYINT NOT NULL DEFAULT 1 COMMENT '办理/业务状态 1待办理 2办理中 3已完成',
  completed_at  DATETIME NULL COMMENT '业务完成时间（进入供应商应付）',
  pay_amount    DECIMAL(14,2) NULL COMMENT '应付供应商金额',
  pay_status    TINYINT NOT NULL DEFAULT 1 COMMENT '1待付款 2已付款',
  paid_date     DATE NULL COMMENT '付款日期（进入完成项目）',
  pay_remark    VARCHAR(255) NOT NULL DEFAULT '',
  created_by    INT UNSIGNED NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at    DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_type_stage (biz_type, stage),
  KEY idx_stage (stage, pay_status),
  KEY idx_supplier (supplier_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='执行业务（签证/公司注册）';

-- 附件
CREATE TABLE IF NOT EXISTS sys_file (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  file_key       CHAR(32) NOT NULL COMMENT '随机下载标识',
  original_name  VARCHAR(255) NOT NULL,
  path           VARCHAR(255) NOT NULL COMMENT '相对 storage/ 的路径',
  ext            VARCHAR(16) NOT NULL DEFAULT '',
  size           INT UNSIGNED NOT NULL DEFAULT 0,
  created_by     INT UNSIGNED NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_key (file_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='上传文件';

-- 执行管理菜单：停用旧「项目列表 / 任务」，新增 4 张表
UPDATE sys_menu SET status = 0 WHERE id IN (31, 311, 312, 32, 321, 322);

INSERT IGNORE INTO sys_menu (id, parent_id, name, type, perm, path, icon, sort) VALUES
 (33,  30, '签证表',     2, 'visa.view',      '/exec/visas',     '', 3),
 (331, 33, '新增/编辑',  3, 'visa.edit',      '', '', 1),
 (332, 33, '删除',       3, 'visa.delete',    '', '', 2),
 (34,  30, '公司注册表', 2, 'company.view',   '/exec/companies', '', 4),
 (341, 34, '新增/编辑',  3, 'company.edit',   '', '', 1),
 (342, 34, '删除',       3, 'company.delete', '', '', 2),
 (35,  30, '供应商应付', 2, 'bizpay.view',    '/exec/payables',  '', 5),
 (351, 35, '编辑/付款/退回', 3, 'bizpay.edit', '', '', 1),
 (36,  30, '完成项目',   2, 'bizdone.view',   '/exec/done',      '', 6),
 (361, 36, '撤回到应付', 3, 'bizdone.revert', '', '', 1);

-- 授权：管理员全部；项目经理办理业务；财务负责付款；销售/采购只读
INSERT IGNORE INTO sys_role_menu (role_id, menu_id) SELECT 1, id FROM sys_menu WHERE id IN (30, 33, 331, 332, 34, 341, 342, 35, 351, 36, 361);
INSERT IGNORE INTO sys_role_menu (role_id, menu_id) SELECT 3, id FROM sys_menu WHERE id IN (30, 33, 331, 332, 34, 341, 342, 35, 36);
INSERT IGNORE INTO sys_role_menu (role_id, menu_id) SELECT 4, id FROM sys_menu WHERE id IN (30, 33, 34, 35, 351, 36, 361);
INSERT IGNORE INTO sys_role_menu (role_id, menu_id) SELECT 2, id FROM sys_menu WHERE id IN (30, 33, 34);
INSERT IGNORE INTO sys_role_menu (role_id, menu_id) SELECT 5, id FROM sys_menu WHERE id IN (30, 35, 36);
