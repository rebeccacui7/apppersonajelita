-- =====================================================================
-- 企业内部管理系统 数据库结构
-- MySQL 5.7+ / 8.0，字符集 utf8mb4
-- 约定：
--   * 业务表统一包含 created_by / created_at / updated_at / deleted_at(软删除)
--   * 枚举字段存 TINYINT 编码，含义见 app/Dict.php
--   * 金额统一 DECIMAL(14,2)
--   * 表前缀：sys_ 系统 / crm_ 客户与商机 / proj_ 执行 / fin_ 财务 / sup_ 供应商
-- =====================================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------- 系统管理
DROP TABLE IF EXISTS sys_dept;
CREATE TABLE sys_dept (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  parent_id   INT UNSIGNED NULL COMMENT '上级部门',
  name        VARCHAR(64)  NOT NULL COMMENT '部门名称',
  leader_id   INT UNSIGNED NULL COMMENT '负责人',
  sort        INT NULL DEFAULT 0,
  created_by  INT UNSIGNED NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at  DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_parent (parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='部门';

DROP TABLE IF EXISTS sys_user;
CREATE TABLE sys_user (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username      VARCHAR(32)  NOT NULL COMMENT '登录账号',
  password      VARCHAR(255) NOT NULL COMMENT 'password_hash',
  realname      VARCHAR(32)  NOT NULL COMMENT '姓名',
  dept_id       INT UNSIGNED NULL,
  mobile        VARCHAR(20)  NOT NULL DEFAULT '',
  email         VARCHAR(100) NOT NULL DEFAULT '',
  status        TINYINT NOT NULL DEFAULT 1 COMMENT '1启用 0停用',
  is_super      TINYINT NOT NULL DEFAULT 0 COMMENT '超级管理员',
  last_login_at DATETIME NULL,
  last_login_ip VARCHAR(45) NOT NULL DEFAULT '',
  created_by    INT UNSIGNED NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at    DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_username (username),
  KEY idx_dept (dept_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户';

DROP TABLE IF EXISTS sys_role;
CREATE TABLE sys_role (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name        VARCHAR(32) NOT NULL,
  code        VARCHAR(32) NOT NULL,
  data_scope  TINYINT NOT NULL DEFAULT 3 COMMENT '1全部 2本部门 3仅本人',
  status      TINYINT NOT NULL DEFAULT 1,
  sort        INT NULL DEFAULT 0,
  remark      VARCHAR(5000) NOT NULL DEFAULT '',
  created_by  INT UNSIGNED NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at  DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='角色';

DROP TABLE IF EXISTS sys_user_role;
CREATE TABLE sys_user_role (
  user_id INT UNSIGNED NOT NULL,
  role_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (user_id, role_id),
  KEY idx_role (role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户-角色';

DROP TABLE IF EXISTS sys_menu;
CREATE TABLE sys_menu (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  parent_id   INT UNSIGNED NULL,
  name        VARCHAR(32)  NOT NULL,
  type        TINYINT NOT NULL DEFAULT 2 COMMENT '1目录 2菜单 3按钮',
  perm        VARCHAR(64)  NOT NULL DEFAULT '' COMMENT '权限标识，如 customer.view',
  path        VARCHAR(128) NOT NULL DEFAULT '',
  icon        VARCHAR(64)  NOT NULL DEFAULT '',
  sort        INT NULL DEFAULT 0,
  status      TINYINT NOT NULL DEFAULT 1,
  created_by  INT UNSIGNED NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at  DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_parent (parent_id),
  KEY idx_perm (perm)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='菜单与权限点';

DROP TABLE IF EXISTS sys_role_menu;
CREATE TABLE sys_role_menu (
  role_id INT UNSIGNED NOT NULL,
  menu_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (role_id, menu_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='角色-菜单';

DROP TABLE IF EXISTS sys_log;
CREATE TABLE sys_log (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id     INT UNSIGNED NULL,
  username    VARCHAR(32)  NOT NULL DEFAULT '',
  module      VARCHAR(32)  NOT NULL DEFAULT '',
  action      VARCHAR(32)  NOT NULL DEFAULT '',
  target_id   BIGINT UNSIGNED NULL,
  content     TEXT NULL,
  ip          VARCHAR(45)  NOT NULL DEFAULT '',
  user_agent  VARCHAR(255) NOT NULL DEFAULT '',
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_user (user_id),
  KEY idx_module (module, action),
  KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='操作日志';

-- ---------------------------------------------------------------- 客户管理
DROP TABLE IF EXISTS crm_customer;
CREATE TABLE crm_customer (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name            VARCHAR(128) NOT NULL COMMENT '客户名称',
  type            TINYINT NULL COMMENT '1企业 2政府/事业单位 3个人',
  level           TINYINT NULL COMMENT '1A 2B 3C',
  status          TINYINT NULL DEFAULT 1 COMMENT '1潜在 2跟进中 3已成交 4已流失',
  industry        VARCHAR(64)  NOT NULL DEFAULT '',
  source          TINYINT NULL,
  phone           VARCHAR(32)  NOT NULL DEFAULT '',
  email           VARCHAR(100) NOT NULL DEFAULT '',
  address         VARCHAR(255) NOT NULL DEFAULT '',
  owner_id        INT UNSIGNED NULL COMMENT '负责人',
  next_follow_at  DATE NULL COMMENT '下次跟进日期',
  remark          TEXT NULL,
  created_by      INT UNSIGNED NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at      DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_name (name),
  KEY idx_owner (owner_id),
  KEY idx_follow (next_follow_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='客户';

DROP TABLE IF EXISTS crm_contact;
CREATE TABLE crm_contact (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  customer_id  INT UNSIGNED NOT NULL,
  name         VARCHAR(32)  NOT NULL,
  position     VARCHAR(64)  NOT NULL DEFAULT '',
  mobile       VARCHAR(32)  NOT NULL DEFAULT '',
  email        VARCHAR(100) NOT NULL DEFAULT '',
  wechat       VARCHAR(64)  NOT NULL DEFAULT '',
  is_primary   TINYINT NULL DEFAULT 0,
  remark       TEXT NULL,
  created_by   INT UNSIGNED NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at   DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_customer (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='客户联系人';

DROP TABLE IF EXISTS crm_followup;
CREATE TABLE crm_followup (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  customer_id     INT UNSIGNED NOT NULL,
  opportunity_id  INT UNSIGNED NULL,
  method          TINYINT NULL COMMENT '1电话 2拜访 3微信 4邮件 5会议',
  follow_at       DATETIME NOT NULL,
  content         TEXT NOT NULL,
  next_plan       VARCHAR(255) NOT NULL DEFAULT '',
  next_at         DATE NULL,
  owner_id        INT UNSIGNED NULL COMMENT '跟进人',
  created_by      INT UNSIGNED NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at      DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_customer (customer_id),
  KEY idx_opportunity (opportunity_id),
  KEY idx_owner (owner_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='客户跟进记录';

-- ---------------------------------------------------------------- 商机管理
DROP TABLE IF EXISTS crm_opportunity;
CREATE TABLE crm_opportunity (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title           VARCHAR(128) NOT NULL COMMENT '商机名称',
  customer_id     INT UNSIGNED NOT NULL,
  amount          DECIMAL(14,2) NULL COMMENT '预计金额',
  stage           TINYINT NOT NULL DEFAULT 1 COMMENT '1初步接洽 2需求确认 3方案报价 4商务谈判 5赢单 6输单',
  probability     DECIMAL(5,2) NULL COMMENT '赢率%',
  expected_close  DATE NULL,
  source          TINYINT NULL,
  owner_id        INT UNSIGNED NULL,
  competitor      VARCHAR(255) NOT NULL DEFAULT '',
  closed_at       DATE NULL COMMENT '赢单/输单日期',
  lost_reason     TEXT NULL,
  remark          TEXT NULL,
  created_by      INT UNSIGNED NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at      DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_customer (customer_id),
  KEY idx_owner_stage (owner_id, stage)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商机';

-- ---------------------------------------------------------------- 执行管理
DROP TABLE IF EXISTS proj_project;
CREATE TABLE proj_project (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  code             VARCHAR(32)  NOT NULL COMMENT '项目编号',
  name             VARCHAR(128) NOT NULL,
  customer_id      INT UNSIGNED NOT NULL,
  opportunity_id   INT UNSIGNED NULL COMMENT '来源商机',
  contract_no      VARCHAR(64)  NOT NULL DEFAULT '',
  contract_amount  DECIMAL(14,2) NULL,
  sign_date        DATE NULL,
  start_date       DATE NULL,
  end_date         DATE NULL,
  status           TINYINT NOT NULL DEFAULT 1 COMMENT '1未启动 2进行中 3已暂停 4已验收 5已关闭',
  progress         TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0-100',
  manager_id       INT UNSIGNED NULL COMMENT '项目经理',
  remark           TEXT NULL,
  created_by       INT UNSIGNED NULL,
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at       DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_code (code),
  KEY idx_customer (customer_id),
  KEY idx_opportunity (opportunity_id),
  KEY idx_manager (manager_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='项目（含合同信息）';

DROP TABLE IF EXISTS proj_task;
CREATE TABLE proj_task (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  project_id   INT UNSIGNED NOT NULL,
  name         VARCHAR(128) NOT NULL,
  type         TINYINT NOT NULL DEFAULT 2 COMMENT '1里程碑 2任务',
  priority     TINYINT NULL DEFAULT 2,
  assignee_id  INT UNSIGNED NULL,
  start_date   DATE NULL,
  due_date     DATE NULL,
  status       TINYINT NOT NULL DEFAULT 1 COMMENT '1待开始 2进行中 3已完成 4已延期',
  progress     TINYINT UNSIGNED NULL DEFAULT 0,
  description  TEXT NULL,
  created_by   INT UNSIGNED NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at   DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_project (project_id),
  KEY idx_assignee (assignee_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='项目任务/里程碑';

-- ---------------------------------------------------------------- 财务管理
DROP TABLE IF EXISTS fin_receivable;
CREATE TABLE fin_receivable (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title            VARCHAR(128) NOT NULL COMMENT '款项名称',
  customer_id      INT UNSIGNED NOT NULL,
  project_id       INT UNSIGNED NULL,
  amount           DECIMAL(14,2) NOT NULL COMMENT '应收金额',
  received_amount  DECIMAL(14,2) NOT NULL DEFAULT 0 COMMENT '已收金额（由流水汇总）',
  due_date         DATE NULL,
  status           TINYINT NOT NULL DEFAULT 1 COMMENT '1未结清 2部分结清 3已结清',
  owner_id         INT UNSIGNED NULL,
  remark           TEXT NULL,
  created_by       INT UNSIGNED NULL,
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at       DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_customer (customer_id),
  KEY idx_project (project_id),
  KEY idx_due (status, due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='应收款/回款计划';

DROP TABLE IF EXISTS fin_payable;
CREATE TABLE fin_payable (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title        VARCHAR(128) NOT NULL,
  supplier_id  INT UNSIGNED NOT NULL,
  purchase_id  INT UNSIGNED NULL,
  project_id   INT UNSIGNED NULL,
  amount       DECIMAL(14,2) NOT NULL,
  paid_amount  DECIMAL(14,2) NOT NULL DEFAULT 0,
  due_date     DATE NULL,
  status       TINYINT NOT NULL DEFAULT 1 COMMENT '1未结清 2部分结清 3已结清',
  owner_id     INT UNSIGNED NULL,
  remark       TEXT NULL,
  created_by   INT UNSIGNED NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at   DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_supplier (supplier_id),
  KEY idx_purchase (purchase_id),
  KEY idx_due (status, due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='应付款';

DROP TABLE IF EXISTS fin_transaction;
CREATE TABLE fin_transaction (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  summary        VARCHAR(255) NOT NULL COMMENT '摘要',
  type           TINYINT NOT NULL COMMENT '1收入 2支出',
  category       TINYINT NOT NULL,
  amount         DECIMAL(14,2) NOT NULL,
  trade_date     DATE NOT NULL,
  account        TINYINT NULL COMMENT '资金账户',
  customer_id    INT UNSIGNED NULL,
  supplier_id    INT UNSIGNED NULL,
  project_id     INT UNSIGNED NULL,
  receivable_id  INT UNSIGNED NULL,
  payable_id     INT UNSIGNED NULL,
  voucher_no     VARCHAR(64) NOT NULL DEFAULT '',
  handler_id     INT UNSIGNED NULL COMMENT '经办人',
  remark         TEXT NULL,
  created_by     INT UNSIGNED NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at     DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_date_type (trade_date, type),
  KEY idx_project (project_id),
  KEY idx_receivable (receivable_id),
  KEY idx_payable (payable_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='收支流水';

DROP TABLE IF EXISTS fin_expense;
CREATE TABLE fin_expense (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  code            VARCHAR(32) NOT NULL COMMENT '报销单号',
  applicant_id    INT UNSIGNED NULL,
  category        TINYINT NOT NULL,
  amount          DECIMAL(14,2) NOT NULL,
  expense_date    DATE NOT NULL,
  project_id      INT UNSIGNED NULL,
  description     TEXT NOT NULL,
  status          TINYINT NOT NULL DEFAULT 1 COMMENT '1待审批 2已通过 3已驳回 4已支付',
  approver_id     INT UNSIGNED NULL,
  approved_at     DATETIME NULL,
  approve_remark  VARCHAR(255) NOT NULL DEFAULT '',
  transaction_id  INT UNSIGNED NULL COMMENT '支付生成的流水',
  created_by      INT UNSIGNED NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at      DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_code (code),
  KEY idx_applicant (applicant_id, status),
  KEY idx_project (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='费用报销';

-- ---------------------------------------------------------------- 供应商管理
DROP TABLE IF EXISTS sup_supplier;
CREATE TABLE sup_supplier (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name          VARCHAR(128) NOT NULL,
  category      TINYINT NULL,
  contact_name  VARCHAR(32)  NOT NULL DEFAULT '',
  phone         VARCHAR(32)  NOT NULL DEFAULT '',
  email         VARCHAR(100) NOT NULL DEFAULT '',
  tax_no        VARCHAR(32)  NOT NULL DEFAULT '',
  bank_name     VARCHAR(100) NOT NULL DEFAULT '',
  bank_account  VARCHAR(64)  NOT NULL DEFAULT '',
  address       VARCHAR(255) NOT NULL DEFAULT '',
  rating        TINYINT NULL,
  status        TINYINT NULL DEFAULT 1 COMMENT '1合作中 2暂停 3黑名单',
  owner_id      INT UNSIGNED NULL,
  remark        TEXT NULL,
  created_by    INT UNSIGNED NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at    DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='供应商';

DROP TABLE IF EXISTS sup_purchase;
CREATE TABLE sup_purchase (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  code           VARCHAR(32)  NOT NULL,
  title          VARCHAR(128) NOT NULL,
  supplier_id    INT UNSIGNED NOT NULL,
  project_id     INT UNSIGNED NULL,
  amount         DECIMAL(14,2) NOT NULL,
  order_date     DATE NULL,
  delivery_date  DATE NULL,
  status         TINYINT NULL DEFAULT 1 COMMENT '1草稿 2已下单 3已到货 4已完成 5已取消',
  owner_id       INT UNSIGNED NULL,
  remark         TEXT NULL,
  created_by     INT UNSIGNED NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at     DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_code (code),
  KEY idx_supplier (supplier_id),
  KEY idx_project (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='采购订单';

-- ---------------------------------------------------------------- 执行管理（签证/公司注册）与附件
-- 执行业务：签证 / 公司注册 共用一张表，按 biz_type + stage 划分四个列表
--   stage 1 在途（签证表/公司注册表）→ 2 供应商应付 → 3 完成项目
DROP TABLE IF EXISTS exec_business;
CREATE TABLE exec_business (
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
DROP TABLE IF EXISTS sys_file;
CREATE TABLE sys_file (
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

SET FOREIGN_KEY_CHECKS = 1;
