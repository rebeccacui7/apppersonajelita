-- =====================================================================
-- 完整体验用测试数据（覆盖全部模块、各种状态）
--
-- * 前置：已导入 schema.sql + seed.sql
-- * 只使用独立 ID 段：用户 101~108，业务数据 1001~1999
--   → 不影响你手工录入的数据；可重复导入（会先清掉旧的测试数据再重建）
-- * 日期均相对“导入当天”生成，仪表盘随时导入都有数据
-- * 清除：导入 database/testdata_clear.sql
-- * 测试账号密码统一为 Test@1234（正式使用前请删除测试数据与账号）
-- =====================================================================
SET NAMES utf8mb4;
START TRANSACTION;

-- ---------------------------------------------------------------- 先清理旧测试数据
DELETE FROM sys_user_role   WHERE user_id BETWEEN 101 AND 199;
DELETE FROM sys_user        WHERE id BETWEEN 101 AND 199;
DELETE FROM crm_contact     WHERE id BETWEEN 1001 AND 1999;
DELETE FROM crm_followup    WHERE id BETWEEN 1001 AND 1999;
DELETE FROM crm_opportunity WHERE id BETWEEN 1001 AND 1999;
DELETE FROM crm_customer    WHERE id BETWEEN 1001 AND 1999;
DELETE FROM proj_task       WHERE id BETWEEN 1001 AND 1999;
DELETE FROM proj_project    WHERE id BETWEEN 1001 AND 1999;
DELETE FROM fin_transaction WHERE id BETWEEN 1001 AND 1999;
DELETE FROM fin_expense     WHERE id BETWEEN 1001 AND 1999;
DELETE FROM fin_receivable  WHERE id BETWEEN 1001 AND 1999;
DELETE FROM fin_payable     WHERE id BETWEEN 1001 AND 1999;
DELETE FROM sup_purchase    WHERE id BETWEEN 1001 AND 1999;
DELETE FROM sup_supplier    WHERE id BETWEEN 1001 AND 1999;

-- ---------------------------------------------------------------- 测试账号（密码 Test@1234）
SET @pwd = '$2y$10$eSEaSU998DDaGWdDsSomdOtctDqNieem2FTlGZPi9H9v3CORLA6u6';
INSERT INTO sys_user (id, username, password, realname, dept_id, mobile, email, status, created_by) VALUES
 (101, 'sales01',   @pwd, '刘一鸣', 2, '13811110001', 'liuyiming@example.com', 1, 1),
 (102, 'sales02',   @pwd, '王思琪', 2, '13811110002', 'wangsiqi@example.com', 1, 1),
 (103, 'sales03',   @pwd, '赵子豪', 2, '13811110003', 'zhaozihao@example.com', 1, 1),
 (104, 'pm01',      @pwd, '孙建国', 3, '13811110004', 'sunjianguo@example.com', 1, 1),
 (105, 'pm02',      @pwd, '周雨桐', 3, '13811110005', 'zhouyutong@example.com', 1, 1),
 (106, 'finance01', @pwd, '吴佳怡', 4, '13811110006', 'wujiayi@example.com', 1, 1),
 (107, 'buyer01',   @pwd, '郑浩然', 5, '13811110007', 'zhenghaoran@example.com', 1, 1),
 (108, 'boss01',    @pwd, '钱总',   1, '13811110008', 'qian@example.com', 1, 1);
-- 角色：1管理员 2销售 3项目经理 4财务 5采购
INSERT INTO sys_user_role (user_id, role_id) VALUES
 (101, 2), (102, 2), (103, 2), (104, 3), (105, 3), (106, 4), (107, 5), (108, 1);

-- ---------------------------------------------------------------- 客户（20）
-- type 1企业 2政府/事业 3个人 | level 1A 2B 3C | status 1潜在 2跟进中 3已成交 4已流失
-- source 1官网 2转介绍 3展会 4电话营销 5老客户 9其他
INSERT INTO crm_customer (id, name, type, level, status, industry, source, phone, email, address, owner_id, next_follow_at, remark, created_by, created_at) VALUES
 (1001, '杭州云帆科技有限公司',       1, 1, 3, '软件',     2, '0571-86001001', 'contact@yunfan.example.com',   '杭州市西湖区文三路 398 号',     101, CURDATE() + INTERVAL 5 DAY,  'OA 项目已验收，可挖掘移动端二期', 101, NOW() - INTERVAL 120 DAY),
 (1002, '上海宏达物流股份有限公司',   1, 1, 3, '物流',     3, '021-56001002',  'it@hongda.example.com',        '上海市浦东新区张江路 88 号',    101, CURDATE() + INTERVAL 2 DAY,  'TMS 实施中，WMS 二期在谈',       101, NOW() - INTERVAL 110 DAY),
 (1003, '苏州市第二人民医院',         2, 1, 3, '医疗',     1, '0512-65001003', 'xxk@hospital2.example.com',    '苏州市姑苏区道前街 26 号',      102, CURDATE() + INTERVAL 10 DAY, '政府采购流程，付款周期较长',     102, NOW() - INTERVAL 100 DAY),
 (1004, '南京智造装备集团',           1, 1, 2, '装备制造', 4, '025-84001004',  'mes@zhizao.example.com',       '南京市江宁区天元东路 1009 号',  101, CURDATE() - INTERVAL 2 DAY,  'MES 商务谈判阶段，竞品用友',     101, NOW() - INTERVAL 80 DAY),
 (1005, '宁波海港贸易有限公司',       1, 2, 2, '进出口贸易', 2, '0574-87001005', 'admin@haigang.example.com',   '宁波市北仑区明州路 520 号',     102, CURDATE() + INTERVAL 1 DAY,  '',                               102, NOW() - INTERVAL 75 DAY),
 (1006, '广州悦享餐饮管理有限公司',   1, 2, 2, '餐饮',     5, '020-38001006',  'ops@yuexiang.example.com',     '广州市天河区体育西路 191 号',   102, CURDATE() + INTERVAL 3 DAY,  '连锁 46 家门店',                 102, NOW() - INTERVAL 60 DAY),
 (1007, '深圳星辰教育科技有限公司',   1, 2, 2, '教育',     1, '0755-26001007', 'bd@xingchen.example.com',      '深圳市南山区科技园南区 R2 栋',  103, CURDATE() + INTERVAL 6 DAY,  '',                               103, NOW() - INTERVAL 50 DAY),
 (1008, '成都天府文旅集团',           1, 1, 2, '文旅',     3, '028-85001008',  'zhjq@tianfu.example.com',      '成都市锦江区红星路三段 1 号',   103, CURDATE(),                   '智慧景区项目，年底前预算需花完', 103, NOW() - INTERVAL 45 DAY),
 (1009, '武汉长江新材料有限公司',     1, 2, 4, '新材料',   4, '027-83001009',  '',                             '武汉市东湖高新区光谷大道 77 号', 101, NULL,                       '已输单给竞品，保持关系',         101, NOW() - INTERVAL 150 DAY),
 (1010, '厦门鹭岛电子商务有限公司',   1, 3, 1, '电商',     1, '0592-55001010', 'tech@ludao.example.com',       '厦门市思明区软件园二期',         103, CURDATE() + INTERVAL 12 DAY, '官网留资线索',                   103, NOW() - INTERVAL 20 DAY),
 (1011, '青岛蓝海水产有限公司',       1, 3, 1, '农业',     9, '0532-80001011', '',                             '青岛市黄岛区海港路 6 号',       102, NULL,                        '',                               102, NOW() - INTERVAL 15 DAY),
 (1012, '北京中科慧眼信息技术有限公司', 1, 1, 3, '人工智能', 2, '010-62001012', 'pm@huiyan.example.com',       '北京市海淀区中关村大街 27 号',  103, CURDATE() + INTERVAL 20 DAY, '老客户，一期运维 + 二期数据中台', 103, NOW() - INTERVAL 130 DAY),
 (1013, '天津市滨海新区政务服务中心', 2, 1, 2, '政府',     3, '022-25001013',  '',                             '天津市滨海新区中央大道 2009 号', 101, CURDATE() + INTERVAL 4 DAY,  '需走公开招标',                   101, NOW() - INTERVAL 40 DAY),
 (1014, '西安秦川汽车零部件有限公司', 1, 2, 2, '汽车零部件', 4, '029-86001014', 'qa@qinchuan.example.com',     '西安市经开区凤城十二路',         102, CURDATE() - INTERVAL 5 DAY,  '跟进已逾期',                     102, NOW() - INTERVAL 70 DAY),
 (1015, '长沙湘江传媒有限公司',       1, 3, 1, '传媒',     1, '0731-88001015', '',                             '长沙市岳麓区麓谷大道 658 号',   103, NULL,                        '',                               103, NOW() - INTERVAL 7 DAY),
 (1016, '郑州中原粮油集团',           1, 2, 4, '食品',     4, '0371-65001016', '',                             '郑州市金水区花园路 39 号',      102, NULL,                        '项目取消',                       102, NOW() - INTERVAL 140 DAY),
 (1017, '合肥讯达智能科技有限公司',   1, 2, 2, '软件',     2, '0551-63001017', 'it@xunda.example.com',         '合肥市高新区望江西路 666 号',   101, CURDATE() + INTERVAL 8 DAY,  '',                               101, NOW() - INTERVAL 30 DAY),
 (1018, '张三设计工作室',             3, 3, 1, '设计',     9, '13900001018',   'zhangsan@example.com',         '杭州市滨江区',                   103, NULL,                        '个人客户',                       103, NOW() - INTERVAL 3 DAY),
 (1019, '昆明七彩云南旅游有限公司',   1, 3, 1, '旅游',     1, '0871-63001019', '',                             '昆明市盘龙区北京路 1088 号',    101, CURDATE() + INTERVAL 15 DAY, '',                               101, NOW() - INTERVAL 10 DAY),
 (1020, '福州闽江医药连锁有限公司',   1, 2, 2, '医药',     3, '0591-87001020', 'erp@minjiang.example.com',     '福州市鼓楼区五四路 158 号',     102, CURDATE() + INTERVAL 2 DAY,  '',                               102, NOW() - INTERVAL 25 DAY);

-- ---------------------------------------------------------------- 联系人
INSERT INTO crm_contact (id, customer_id, name, position, mobile, email, wechat, is_primary, created_by) VALUES
 (1001, 1001, '陈立',   'CTO',          '13900011001', 'chenli@yunfan.example.com',     'chenli_yf', 1, 101),
 (1002, 1001, '林晓',   '行政总监',     '13900011002', 'linxiao@yunfan.example.com',    '',          0, 101),
 (1003, 1002, '黄国强', '信息部总经理', '13900011003', 'hgq@hongda.example.com',        'hgq1979',   1, 101),
 (1004, 1002, '许敏',   '运营经理',     '13900011004', '',                              '',          0, 101),
 (1005, 1003, '周建明', '信息科主任',   '13900011005', 'zjm@hospital2.example.com',     '',          1, 102),
 (1006, 1003, '吴倩',   '采购办',       '13900011006', '',                              '',          0, 102),
 (1007, 1004, '马骏',   '副总裁',       '13900011007', 'majun@zhizao.example.com',      'majun_zz',  1, 101),
 (1008, 1004, '杨帆',   'IT 经理',      '13900011008', '',                              '',          0, 101),
 (1009, 1005, '何丽',   '总经理',       '13900011009', '',                              'heli_hg',   1, 102),
 (1010, 1006, '郭磊',   '运营总监',     '13900011010', '',                              '',          1, 102),
 (1011, 1007, '罗静',   '产品负责人',   '13900011011', 'luojing@xingchen.example.com',  '',          1, 103),
 (1012, 1008, '邓超',   '信息中心主任', '13900011012', '',                              'dengchao',  1, 103),
 (1013, 1012, '梁晨',   '技术总监',     '13900011013', 'liangchen@huiyan.example.com',  '',          1, 103),
 (1014, 1013, '冯主任', '综合科科长',   '13900011014', '',                              '',          1, 101),
 (1015, 1014, '韩伟',   '质量部经理',   '13900011015', '',                              '',          1, 102),
 (1016, 1017, '唐宁',   'CIO',          '13900011016', '',                              'tangning',  1, 101),
 (1017, 1020, '曹敏',   '信息部经理',   '13900011017', '',                              '',          1, 102),
 (1018, 1010, '谢鹏',   '技术合伙人',   '13900011018', '',                              '',          1, 103);

-- ---------------------------------------------------------------- 商机（18）
-- stage 1初步接洽 2需求确认 3方案报价 4商务谈判 5赢单 6输单
INSERT INTO crm_opportunity (id, title, customer_id, amount, stage, probability, expected_close, source, owner_id, competitor, closed_at, lost_reason, remark, created_by, created_at) VALUES
 (1001, '云帆科技 OA 协同平台',        1001,  380000, 5, 100, CURDATE() - INTERVAL 95 DAY, 2, 101, '泛微',     CURDATE() - INTERVAL 95 DAY, NULL, '', 101, NOW() - INTERVAL 118 DAY),
 (1002, '宏达物流 TMS 运输管理系统',   1002,  860000, 5, 100, CURDATE() - INTERVAL 60 DAY, 3, 101, '',         CURDATE() - INTERVAL 60 DAY, NULL, '', 101, NOW() - INTERVAL 105 DAY),
 (1003, '二院 HIS 接口与运维服务',     1003,  450000, 5, 100, CURDATE() - INTERVAL 40 DAY, 1, 102, '东软',     CURDATE() - INTERVAL 40 DAY, NULL, '', 102, NOW() - INTERVAL 98 DAY),
 (1004, '中科慧眼 数据中台二期',       1012, 1200000, 5, 100, CURDATE() - INTERVAL 15 DAY, 5, 103, '',         CURDATE() - INTERVAL 15 DAY, NULL, '赢单后尚未转项目，可体验「转为项目」', 103, NOW() - INTERVAL 125 DAY),
 (1005, '智造装备 MES 制造执行系统',   1004,  980000, 4, 70,  CURDATE() + INTERVAL 20 DAY, 4, 101, '用友',     NULL, NULL, '价格谈判中，客户希望 9 折', 101, NOW() - INTERVAL 78 DAY),
 (1006, '海港贸易 报关系统定制',       1005,  260000, 3, 50,  CURDATE() + INTERVAL 35 DAY, 2, 102, '',         NULL, NULL, '', 102, NOW() - INTERVAL 70 DAY),
 (1007, '悦享餐饮 门店收银与会员',     1006,  180000, 3, 50,  CURDATE() + INTERVAL 25 DAY, 5, 102, '客如云',   NULL, NULL, '', 102, NOW() - INTERVAL 55 DAY),
 (1008, '星辰教育 在线课堂平台',       1007,  520000, 2, 30,  CURDATE() + INTERVAL 60 DAY, 1, 103, '',         NULL, NULL, '', 103, NOW() - INTERVAL 48 DAY),
 (1009, '天府文旅 智慧景区票务',       1008,  750000, 4, 70,  CURDATE() + INTERVAL 15 DAY, 3, 103, '',         NULL, NULL, '', 103, NOW() - INTERVAL 44 DAY),
 (1010, '鹭岛电商 ERP 对接',           1010,  120000, 1, 10,  CURDATE() + INTERVAL 90 DAY, 1, 103, '',         NULL, NULL, '', 103, NOW() - INTERVAL 18 DAY),
 (1011, '政务中心 预约叫号系统',       1013,  330000, 2, 30,  CURDATE() + INTERVAL 45 DAY, 3, 101, '',         NULL, NULL, '', 101, NOW() - INTERVAL 38 DAY),
 (1012, '秦川汽车 质量追溯系统',       1014,  410000, 3, 50,  CURDATE() + INTERVAL 30 DAY, 4, 102, '',         NULL, NULL, '', 102, NOW() - INTERVAL 65 DAY),
 (1013, '讯达智能 IT 运维外包',        1017,  150000, 1, 10,  CURDATE() + INTERVAL 75 DAY, 2, 101, '',         NULL, NULL, '', 101, NOW() - INTERVAL 28 DAY),
 (1014, '闽江医药 进销存升级',         1020,  280000, 2, 30,  CURDATE() + INTERVAL 50 DAY, 3, 102, '',         NULL, NULL, '', 102, NOW() - INTERVAL 22 DAY),
 (1015, '长江新材料 ERP 实施',         1009,  600000, 6, 0,   CURDATE() - INTERVAL 70 DAY, 4, 101, '金蝶',     CURDATE() - INTERVAL 70 DAY, '客户预算削减，选择了报价更低的竞品', '', 101, NOW() - INTERVAL 148 DAY),
 (1016, '中原粮油 仓储管理',           1016,  350000, 6, 0,   CURDATE() - INTERVAL 100 DAY, 4, 102, '',        CURDATE() - INTERVAL 100 DAY, '客户内部组织调整，项目取消', '', 102, NOW() - INTERVAL 138 DAY),
 (1017, '宏达物流 二期 WMS 仓储系统',  1002,  540000, 2, 30,  CURDATE() + INTERVAL 80 DAY, 5, 101, '',         NULL, NULL, 'TMS 客户增购', 101, NOW() - INTERVAL 12 DAY),
 (1018, '云帆科技 移动端扩展',         1001,   90000, 3, 50,  CURDATE() + INTERVAL 20 DAY, 5, 101, '',         NULL, NULL, '', 101, NOW() - INTERVAL 9 DAY);

-- ---------------------------------------------------------------- 跟进记录
-- method 1电话 2拜访 3微信 4邮件 5会议
INSERT INTO crm_followup (id, customer_id, opportunity_id, method, follow_at, content, next_plan, next_at, owner_id, created_by) VALUES
 (1001, 1001, 1001, 2, NOW() - INTERVAL 110 DAY, '首次拜访陈总，了解现有审批流程痛点：纸质审批多、出差无法处理。', '提交初步方案',       NULL, 101, 101),
 (1002, 1001, 1001, 5, NOW() - INTERVAL 100 DAY, '方案汇报会，客户认可流程引擎和移动审批。', '商务报价',                     NULL, 101, 101),
 (1003, 1001, 1018, 3, NOW() - INTERVAL 6 DAY,   '陈总提出希望增加移动端考勤与外勤打卡。', '出移动端扩展报价',             CURDATE() + INTERVAL 5 DAY, 101, 101),
 (1004, 1002, 1002, 2, NOW() - INTERVAL 95 DAY,  '展会后上门交流，黄总关注运输调度与运费结算。', '安排 Demo',               NULL, 101, 101),
 (1005, 1002, 1017, 5, NOW() - INTERVAL 10 DAY,  'TMS 月度例会上客户提出仓储也要信息化，初步沟通 WMS 需求。', '整理 WMS 需求清单', CURDATE() + INTERVAL 2 DAY, 101, 101),
 (1006, 1003, 1003, 2, NOW() - INTERVAL 90 DAY,  '与信息科周主任交流 HIS 接口现状，涉及 12 个第三方系统。', '提交接口清单',    NULL, 102, 102),
 (1007, 1003, 1003, 4, NOW() - INTERVAL 50 DAY,  '发送正式报价及服务方案，等待院办审批。', '跟进招标流程',                   NULL, 102, 102),
 (1008, 1003, NULL, 1, NOW() - INTERVAL 4 DAY,   '电话确认中期款审批进度，院方表示财务在走流程。', '下周再次确认回款', CURDATE() + INTERVAL 10 DAY, 102, 102),
 (1009, 1004, 1005, 2, NOW() - INTERVAL 70 DAY,  '拜访马总，参观生产车间，确认 MES 覆盖 3 条产线。', '出方案',            NULL, 101, 101),
 (1010, 1004, 1005, 5, NOW() - INTERVAL 30 DAY,  '方案评审会，客户技术部认可，进入商务环节。', '准备商务报价',           NULL, 101, 101),
 (1011, 1004, 1005, 1, NOW() - INTERVAL 9 DAY,   '马总反馈用友报价低 15%，希望我们再让利。', '内部申请特价',             CURDATE() - INTERVAL 2 DAY, 101, 101),
 (1012, 1005, 1006, 3, NOW() - INTERVAL 12 DAY,  '何总微信发来报关单样例，确认需对接单一窗口。', '出报价',                CURDATE() + INTERVAL 1 DAY, 102, 102),
 (1013, 1006, 1007, 2, NOW() - INTERVAL 15 DAY,  '门店实地调研，收银高峰期排队严重，会员体系缺失。', '方案报价',          CURDATE() + INTERVAL 3 DAY, 102, 102),
 (1014, 1007, 1008, 1, NOW() - INTERVAL 20 DAY,  '电话沟通在线课堂并发需求，预计 5000 人同时在线。', '安排技术交流',      CURDATE() + INTERVAL 6 DAY, 103, 103),
 (1015, 1008, 1009, 2, NOW() - INTERVAL 25 DAY,  '实地考察景区入口闸机，需与现有票务系统集成。', '商务谈判',             NULL, 103, 103),
 (1016, 1008, 1009, 5, NOW() - INTERVAL 3 DAY,   '集团信息中心评审通过，等待领导签批。', '今天电话确认签批结果',          CURDATE(), 103, 103),
 (1017, 1010, 1010, 4, NOW() - INTERVAL 17 DAY,  '官网留资，邮件发送公司介绍和案例。', '电话约访',                       CURDATE() + INTERVAL 12 DAY, 103, 103),
 (1018, 1012, 1004, 5, NOW() - INTERVAL 20 DAY,  '数据中台二期合同评审会，条款基本达成一致。', '签约',                   NULL, 103, 103),
 (1019, 1012, NULL, 1, NOW() - INTERVAL 2 DAY,   '梁总反馈一期运维巡检有遗漏，已协调项目组处理。', '月底回访',           CURDATE() + INTERVAL 20 DAY, 103, 103),
 (1020, 1013, 1011, 2, NOW() - INTERVAL 30 DAY,  '拜访冯科长，了解大厅叫号与预约现状。', '提交需求确认书',               CURDATE() + INTERVAL 4 DAY, 101, 101),
 (1021, 1014, 1012, 1, NOW() - INTERVAL 18 DAY,  '韩经理确认质量追溯需覆盖供应商来料。', '出报价',                       CURDATE() - INTERVAL 5 DAY, 102, 102),
 (1022, 1017, 1013, 3, NOW() - INTERVAL 14 DAY,  '唐总微信沟通运维外包意向。', '约见面',                                   CURDATE() + INTERVAL 8 DAY, 101, 101),
 (1023, 1020, 1014, 2, NOW() - INTERVAL 8 DAY,   '拜访曹经理，现有进销存系统老旧，需支持 GSP。', '需求确认',              CURDATE() + INTERVAL 2 DAY, 102, 102),
 (1024, 1009, 1015, 1, NOW() - INTERVAL 72 DAY,  '客户告知已选择竞品，原因是预算。', '保持关系，明年再跟',                NULL, 101, 101),
 (1025, 1019, NULL, 1, NOW() - INTERVAL 5 DAY,   '官网线索，初步电话沟通，对票务分销有兴趣。', '寄送资料',              CURDATE() + INTERVAL 15 DAY, 101, 101);

-- ---------------------------------------------------------------- 项目（5）
-- status 1未启动 2进行中 3已暂停 4已验收 5已关闭
INSERT INTO proj_project (id, code, name, customer_id, opportunity_id, contract_no, contract_amount, sign_date, start_date, end_date, status, progress, manager_id, remark, created_by, created_at) VALUES
 (1001, 'PRJ-T001', '云帆科技 OA 协同平台',     1001, 1001, 'HT-2026-101',  380000, CURDATE() - INTERVAL 93 DAY,  CURDATE() - INTERVAL 90 DAY,  CURDATE() - INTERVAL 10 DAY, 4, 0, 104, '已验收，质保期一年', 104, NOW() - INTERVAL 93 DAY),
 (1002, 'PRJ-T002', '宏达物流 TMS 运输管理系统', 1002, 1002, 'HT-2026-115',  860000, CURDATE() - INTERVAL 58 DAY,  CURDATE() - INTERVAL 55 DAY,  CURDATE() + INTERVAL 65 DAY, 2, 0, 104, '', 104, NOW() - INTERVAL 58 DAY),
 (1003, 'PRJ-T003', '二院 HIS 接口与运维服务',   1003, 1003, 'HT-2026-128',  450000, CURDATE() - INTERVAL 38 DAY,  CURDATE() - INTERVAL 35 DAY,  CURDATE() + INTERVAL 90 DAY, 2, 0, 105, '', 105, NOW() - INTERVAL 38 DAY),
 (1004, 'PRJ-T004', '慧眼一期运维服务',         1012, NULL, 'HT-2025-088',  200000, CURDATE() - INTERVAL 200 DAY, CURDATE() - INTERVAL 190 DAY, CURDATE() + INTERVAL 30 DAY, 3, 0, 104, '客户机房搬迁，暂停一个月', 104, NOW() - INTERVAL 200 DAY),
 (1005, 'PRJ-T005', '悦享餐饮 门店收银试点',     1006, NULL, '',              0,     NULL,                         CURDATE() + INTERVAL 10 DAY, CURDATE() + INTERVAL 40 DAY, 1, 0, 105, '商务未签，先做 2 家门店 POC', 105, NOW() - INTERVAL 5 DAY);

-- ---------------------------------------------------------------- 任务（status 1待开始 2进行中 3已完成 4已延期；type 1里程碑 2任务）
-- 其中 2 条分配给 admin(1)，登录 admin 在仪表盘「我的待办任务」可见
INSERT INTO proj_task (id, project_id, name, type, priority, assignee_id, start_date, due_date, status, progress, description, created_by) VALUES
 (1001, 1001, '需求调研与确认',     1, 1, 104, CURDATE() - INTERVAL 90 DAY, CURDATE() - INTERVAL 75 DAY, 3, 100, '', 104),
 (1002, 1001, '流程引擎配置与开发', 2, 1, 104, CURDATE() - INTERVAL 74 DAY, CURDATE() - INTERVAL 30 DAY, 3, 100, '', 104),
 (1003, 1001, '上线验收',           1, 1, 104, CURDATE() - INTERVAL 29 DAY, CURDATE() - INTERVAL 10 DAY, 3, 100, '', 104),
 (1004, 1002, '需求调研与蓝图设计', 1, 1, 104, CURDATE() - INTERVAL 55 DAY, CURDATE() - INTERVAL 40 DAY, 3, 100, '', 104),
 (1005, 1002, '运输调度模块开发',   2, 1, 104, CURDATE() - INTERVAL 39 DAY, CURDATE() + INTERVAL 10 DAY, 2, 70,  '', 104),
 (1006, 1002, '运费结算模块开发',   2, 2, 104, CURDATE() - INTERVAL 20 DAY, CURDATE() + INTERVAL 30 DAY, 2, 40,  '', 104),
 (1007, 1002, '司机 APP 开发',      2, 2, 104, CURDATE() + INTERVAL 5 DAY,  CURDATE() + INTERVAL 40 DAY, 1, 0,   '外包团队负责（采购单 POT002）', 104),
 (1008, 1002, '系统联调与 UAT',     2, 1, 104, CURDATE() + INTERVAL 40 DAY, CURDATE() + INTERVAL 55 DAY, 1, 0,   '', 104),
 (1009, 1002, '上线验收',           1, 1, 104, CURDATE() + INTERVAL 55 DAY, CURDATE() + INTERVAL 65 DAY, 1, 0,   '', 104),
 (1010, 1002, '合同变更评审（WMS 二期）', 2, 2, 1, CURDATE() - INTERVAL 2 DAY, CURDATE() + INTERVAL 3 DAY, 2, 20, '请管理员审阅增购条款', 104),
 (1011, 1003, '接口清单梳理',       1, 1, 105, CURDATE() - INTERVAL 35 DAY, CURDATE() - INTERVAL 25 DAY, 3, 100, '', 105),
 (1012, 1003, 'HIS 接口开发',       2, 1, 105, CURDATE() - INTERVAL 24 DAY, CURDATE() + INTERVAL 20 DAY, 2, 60,  '', 105),
 (1013, 1003, '驻场运维排班',       2, 2, 105, CURDATE() - INTERVAL 20 DAY, CURDATE() - INTERVAL 3 DAY,  4, 30,  '人员未到位，已延期', 105),
 (1014, 1003, '月度项目复盘会',     2, 3, 1,   CURDATE(),                   CURDATE() + INTERVAL 7 DAY,  1, 0,   '', 105),
 (1015, 1004, '版本升级',           2, 2, 104, CURDATE() - INTERVAL 60 DAY, CURDATE() - INTERVAL 40 DAY, 3, 100, '', 104),
 (1016, 1004, '季度巡检',           2, 2, 104, CURDATE() - INTERVAL 20 DAY, CURDATE() - INTERVAL 10 DAY, 4, 50,  '客户机房搬迁暂停', 104),
 (1017, 1005, 'POC 启动会',         1, 2, 105, CURDATE() + INTERVAL 10 DAY, CURDATE() + INTERVAL 10 DAY, 1, 0,   '', 105),
 (1018, 1005, '门店设备调研',       2, 3, 105, CURDATE() + INTERVAL 10 DAY, CURDATE() + INTERVAL 20 DAY, 1, 0,   '', 105);

-- ---------------------------------------------------------------- 应收款（已收金额/状态在文末由流水自动汇总）
INSERT INTO fin_receivable (id, title, customer_id, project_id, amount, received_amount, due_date, status, owner_id, remark, created_by) VALUES
 (1001, '首付款 30%',   1001, 1001, 114000, 0, CURDATE() - INTERVAL 85 DAY, 1, 101, '', 106),
 (1002, '验收款 60%',   1001, 1001, 228000, 0, CURDATE() - INTERVAL 8 DAY,  1, 101, '', 106),
 (1003, '质保金 10%',   1001, 1001,  38000, 0, CURDATE() + INTERVAL 355 DAY, 1, 101, '质保期满一年后支付', 106),
 (1004, '首付款 30%',   1002, 1002, 258000, 0, CURDATE() - INTERVAL 50 DAY, 1, 101, '', 106),
 (1005, '进度款 40%',   1002, 1002, 344000, 0, CURDATE() + INTERVAL 5 DAY,  1, 101, '客户已先付一部分', 106),
 (1006, '尾款 30%',     1002, 1002, 258000, 0, CURDATE() + INTERVAL 70 DAY, 1, 101, '', 106),
 (1007, '首付款 40%',   1003, 1003, 180000, 0, CURDATE() - INTERVAL 30 DAY, 1, 102, '', 106),
 (1008, '中期款 30%',   1003, 1003, 135000, 0, CURDATE() - INTERVAL 5 DAY,  1, 102, '已逾期，院方财务流程中', 106),
 (1009, '尾款 30%',     1003, 1003, 135000, 0, CURDATE() + INTERVAL 95 DAY, 1, 102, '', 106),
 (1010, 'Q3 运维费',    1012, 1004,  50000, 0, CURDATE() - INTERVAL 20 DAY, 1, 103, '已逾期', 106),
 (1011, 'Q4 运维费',    1012, 1004,  50000, 0, CURDATE() + INTERVAL 25 DAY, 1, 103, '', 106);

-- ---------------------------------------------------------------- 供应商
-- category 1硬件 2软件服务 3外包 4办公 | rating 1A 2B 3C 4D | status 1合作中 2暂停 3黑名单
INSERT INTO sup_supplier (id, name, category, contact_name, phone, email, tax_no, bank_name, bank_account, address, rating, status, owner_id, remark, created_by) VALUES
 (1001, '华信服务器设备有限公司', 1, '孙经理', '13700001001', 'sales@huaxin.example.com', '91330100MA0000001A', '招商银行杭州分行', '5719 0000 0000 1001', '杭州市滨江区网商路 599 号', 1, 1, 107, '主力硬件供应商，账期 30 天', 107),
 (1002, '云栈软件技术服务有限公司', 2, '钱工', '13700001002', '', '91310000MA0000002B', '工商银行上海分行', '1001 0000 0000 1002', '上海市徐汇区漕河泾', 2, 1, 107, '', 107),
 (1003, '码农部落外包工作室', 3, '吴工', '13700001003', '', '', '建设银行杭州分行', '3305 0000 0000 1003', '杭州市余杭区', 2, 1, 107, '按人月结算', 107),
 (1004, '得力办公用品商城', 4, '客服', '400-000-1004', '', '91330200MA0000004D', '', '', '线上', 1, 1, 107, '', 107),
 (1005, '速达网络设备有限公司', 1, '李经理', '13700001005', '', '', '', '', '广州市', 3, 2, 107, '交付延期两次，暂停合作', 107),
 (1006, '黑石外包服务有限公司', 3, '未知', '13700001006', '', '', '', '', '', 4, 3, 107, '质量问题严重，列入黑名单（体验：下单会被拦截）', 107);

-- ---------------------------------------------------------------- 采购订单
-- status 1草稿 2已下单 3已到货 4已完成 5已取消
INSERT INTO sup_purchase (id, code, title, supplier_id, project_id, amount, order_date, delivery_date, status, owner_id, remark, created_by) VALUES
 (1001, 'POT001', '应用服务器 x2',          1001, 1001,  46000, CURDATE() - INTERVAL 80 DAY, CURDATE() - INTERVAL 70 DAY, 4, 107, '', 107),
 (1002, 'POT002', '外包开发人力（3 人月）', 1003, 1002,  90000, CURDATE() - INTERVAL 45 DAY, CURDATE() + INTERVAL 15 DAY, 2, 107, '司机 APP 外包', 107),
 (1003, 'POT003', '数据库商业授权',         1002, 1002,  38000, CURDATE() - INTERVAL 40 DAY, CURDATE() - INTERVAL 30 DAY, 3, 107, '', 107),
 (1004, 'POT004', '机房交换机',             1001, 1003,  22000, CURDATE() - INTERVAL 25 DAY, CURDATE() - INTERVAL 15 DAY, 4, 107, '', 107),
 (1005, 'POT005', '办公用品季度采购',       1004, NULL,   6800, CURDATE() - INTERVAL 10 DAY, CURDATE() - INTERVAL 5 DAY,  4, 107, '', 107),
 (1006, 'POT006', 'GPU 服务器',             1001, NULL, 168000, CURDATE() - INTERVAL 2 DAY,  CURDATE() + INTERVAL 20 DAY, 2, 107, '已下单未生成应付（体验：点「生成应付」）', 107),
 (1007, 'POT007', '测试用网络设备',         1005, 1003,  15000, CURDATE() - INTERVAL 60 DAY, NULL,                        5, 107, '供应商延期，已取消', 107),
 (1008, 'POT008', '移动端测试机',           1004, 1002,   9600, CURDATE(),                   CURDATE() + INTERVAL 7 DAY,  1, 107, '草稿', 107);

-- ---------------------------------------------------------------- 应付款（已付金额/状态在文末由流水自动汇总）
INSERT INTO fin_payable (id, title, supplier_id, purchase_id, project_id, amount, paid_amount, due_date, status, owner_id, remark, created_by) VALUES
 (1001, '采购款 POT001', 1001, 1001, 1001, 46000, 0, CURDATE() - INTERVAL 65 DAY, 1, 107, '', 107),
 (1002, '采购款 POT002', 1003, 1002, 1002, 90000, 0, CURDATE() + INTERVAL 20 DAY, 1, 107, '预付 1/3', 107),
 (1003, '采购款 POT003', 1002, 1003, 1002, 38000, 0, CURDATE() - INTERVAL 10 DAY, 1, 107, '已到期未付', 107),
 (1004, '采购款 POT004', 1001, 1004, 1003, 22000, 0, CURDATE() + INTERVAL 10 DAY, 1, 107, '', 107),
 (1005, '采购款 POT005', 1004, 1005, NULL,  6800, 0, CURDATE() - INTERVAL 2 DAY,  1, 107, '', 107);

-- ---------------------------------------------------------------- 收支流水
-- type 1收入 2支出 | category 1项目回款 2采购付款 3费用报销 4工资社保 5税费 6房租水电 9其他 | account 1对公 2现金 3支付宝 4微信
INSERT INTO fin_transaction (id, summary, type, category, amount, trade_date, account, customer_id, supplier_id, project_id, receivable_id, payable_id, voucher_no, handler_id, remark, created_by) VALUES
 -- 回款（核销应收）
 (1001, '云帆科技 首付款',         1, 1, 114000, CURDATE() - INTERVAL 84 DAY, 1, 1001, NULL, 1001, 1001, NULL, 'PZ-T001', 106, '', 106),
 (1002, '云帆科技 验收款',         1, 1, 228000, CURDATE() - INTERVAL 6 DAY,  1, 1001, NULL, 1001, 1002, NULL, 'PZ-T002', 106, '', 106),
 (1003, '宏达物流 首付款',         1, 1, 258000, CURDATE() - INTERVAL 48 DAY, 1, 1002, NULL, 1002, 1004, NULL, 'PZ-T003', 106, '', 106),
 (1004, '宏达物流 进度款（部分）', 1, 1, 150000, CURDATE() - INTERVAL 2 DAY,  1, 1002, NULL, 1002, 1005, NULL, 'PZ-T004', 106, '剩余 194000 待付', 106),
 (1005, '二院 首付款',             1, 1, 180000, CURDATE() - INTERVAL 28 DAY, 1, 1003, NULL, 1003, 1007, NULL, 'PZ-T005', 106, '', 106),
 -- 采购付款（核销应付）
 (1006, '华信 服务器采购款',       2, 2,  46000, CURDATE() - INTERVAL 66 DAY, 1, NULL, 1001, 1001, NULL, 1001, 'PZ-T006', 106, '', 106),
 (1007, '码农部落 外包预付款',     2, 2,  30000, CURDATE() - INTERVAL 40 DAY, 1, NULL, 1003, 1002, NULL, 1002, 'PZ-T007', 106, '', 106),
 (1008, '华信 交换机采购款',       2, 2,  22000, CURDATE() - INTERVAL 14 DAY, 1, NULL, 1001, 1003, NULL, 1004, 'PZ-T008', 106, '', 106),
 (1009, '得力 办公用品',           2, 2,   6800, CURDATE() - INTERVAL 3 DAY,  3, NULL, 1004, NULL, NULL, 1005, 'PZ-T009', 106, '', 106),
 -- 其他收支
 (1010, '慧眼一期 运维费 Q1',      1, 1,  50000, CURDATE() - INTERVAL 200 DAY, 1, 1012, NULL, 1004, NULL, NULL, 'PZ-T010', 106, '', 106),
 (1011, '慧眼一期 运维费 Q2',      1, 1,  50000, CURDATE() - INTERVAL 110 DAY, 1, 1012, NULL, 1004, NULL, NULL, 'PZ-T011', 106, '', 106),
 (1012, '技术咨询服务费',          1, 9,  25000, CURDATE() - INTERVAL 160 DAY, 1, 1004, NULL, NULL, NULL, NULL, 'PZ-T012', 106, '', 106),
 (1013, '软件许可续费',            1, 9,  48000, CURDATE() - INTERVAL 250 DAY, 1, 1001, NULL, NULL, NULL, NULL, 'PZ-T013', 106, '', 106),
 (1014, '老客户二次开发',          1, 1,  86000, CURDATE() - INTERVAL 300 DAY, 1, 1012, NULL, NULL, NULL, NULL, 'PZ-T014', 106, '', 106),
 (1015, '季度增值税',              2, 5,  32000, CURDATE() - INTERVAL 15 DAY,  1, NULL, NULL, NULL, NULL, NULL, 'PZ-T015', 106, '', 106),
 (1016, '季度增值税',              2, 5,  21000, CURDATE() - INTERVAL 105 DAY, 1, NULL, NULL, NULL, NULL, NULL, 'PZ-T016', 106, '', 106),
 -- 报销支付（由费用报销「支付」生成，关联见下方 fin_expense.transaction_id）
 (1020, '费用报销 BXT001',         2, 3,   3260, CURDATE() - INTERVAL 17 DAY, 1, NULL, NULL, 1002, NULL, NULL, '', 106, '', 106),
 (1021, '费用报销 BXT002',         2, 3,   5480.5, CURDATE() - INTERVAL 12 DAY, 1, NULL, NULL, 1003, NULL, NULL, '', 106, '', 106);

-- 近 12 个月固定支出：房租（每月 1 日）、工资社保（每月 10 日，从上月起）
INSERT INTO fin_transaction (id, summary, type, category, amount, trade_date, account, voucher_no, handler_id, created_by)
SELECT 1100 + n, CONCAT(DATE_FORMAT(CURDATE() - INTERVAL n MONTH, '%Y年%c月'), ' 办公室房租'), 2, 6, 18000,
       DATE_FORMAT(CURDATE() - INTERVAL n MONTH, '%Y-%m-01'), 1, '', 106, 106
FROM (SELECT 0 n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5
      UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10 UNION ALL SELECT 11) m;

INSERT INTO fin_transaction (id, summary, type, category, amount, trade_date, account, voucher_no, handler_id, created_by)
SELECT 1200 + n, CONCAT(DATE_FORMAT(CURDATE() - INTERVAL n MONTH, '%Y年%c月'), ' 工资社保'), 2, 4, 128000 + n * 1500,
       DATE_FORMAT(CURDATE() - INTERVAL n MONTH, '%Y-%m-10'), 1, '', 106, 106
FROM (SELECT 1 n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6
      UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10 UNION ALL SELECT 11) m;

-- 近 12 个月 SaaS 订阅类收入（让收支趋势图每月都有数据）
INSERT INTO fin_transaction (id, summary, type, category, amount, trade_date, account, voucher_no, handler_id, created_by)
SELECT 1300 + n, CONCAT(DATE_FORMAT(CURDATE() - INTERVAL n MONTH, '%Y年%c月'), ' SaaS 订阅收入'), 1, 9, 62000 + (12 - n) * 4500,
       DATE_FORMAT(CURDATE() - INTERVAL n MONTH, '%Y-%m-05'), 1, '', 106, 106
FROM (SELECT 1 n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6
      UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10 UNION ALL SELECT 11) m;

-- ---------------------------------------------------------------- 费用报销
-- status 1待审批 2已通过 3已驳回 4已支付 | category 1差旅 2招待 3办公 4交通 5通讯 9其他
INSERT INTO fin_expense (id, code, applicant_id, category, amount, expense_date, project_id, description, status, approver_id, approved_at, approve_remark, transaction_id, created_by) VALUES
 (1001, 'BXT001', 101, 1, 3260.00, CURDATE() - INTERVAL 20 DAY, 1002, '赴上海宏达物流现场调研（高铁往返 + 酒店 2 晚）', 4, 106, NOW() - INTERVAL 18 DAY, '同意', 1020, 101),
 (1002, 'BXT002', 104, 1, 5480.50, CURDATE() - INTERVAL 15 DAY, 1003, '苏州二院驻场差旅（一周）', 4, 106, NOW() - INTERVAL 13 DAY, '', 1021, 104),
 (1003, 'BXT003', 102, 2, 1860.00, CURDATE() - INTERVAL 8 DAY,  NULL, '宁波海港贸易客户招待餐费', 2, 106, NOW() - INTERVAL 6 DAY, '', NULL, 102),
 (1004, 'BXT004', 105, 4,  420.00, CURDATE() - INTERVAL 5 DAY,  1003, '往返医院打车费', 1, NULL, NULL, '', NULL, 105),
 (1005, 'BXT005', 101, 2, 2600.00, CURDATE() - INTERVAL 4 DAY,  NULL, '南京智造装备商务宴请', 1, NULL, NULL, '', NULL, 101),
 (1006, 'BXT006', 103, 3,  899.00, CURDATE() - INTERVAL 3 DAY,  NULL, '演示用投影仪配件', 3, 106, NOW() - INTERVAL 2 DAY, '办公设备请走采购流程', NULL, 103),
 (1007, 'BXT007', 107, 5,  300.00, CURDATE() - INTERVAL 2 DAY,  NULL, '9 月话费补贴', 1, NULL, NULL, '', NULL, 107),
 (1008, 'BXT008', 106, 4,  120.00, CURDATE() - INTERVAL 1 DAY,  NULL, '税务局办事交通费（财务本人提交，需他人审批）', 1, NULL, NULL, '', NULL, 106);

-- ---------------------------------------------------------------- 汇总回写，保证数据一致
UPDATE fin_receivable r SET received_amount = (
  SELECT COALESCE(SUM(t.amount), 0) FROM fin_transaction t WHERE t.receivable_id = r.id AND t.type = 1 AND t.deleted_at IS NULL
) WHERE r.id BETWEEN 1001 AND 1999;
UPDATE fin_receivable SET status = IF(received_amount <= 0, 1, IF(received_amount >= amount, 3, 2)) WHERE id BETWEEN 1001 AND 1999;

UPDATE fin_payable p SET paid_amount = (
  SELECT COALESCE(SUM(t.amount), 0) FROM fin_transaction t WHERE t.payable_id = p.id AND t.type = 2 AND t.deleted_at IS NULL
) WHERE p.id BETWEEN 1001 AND 1999;
UPDATE fin_payable SET status = IF(paid_amount <= 0, 1, IF(paid_amount >= amount, 3, 2)) WHERE id BETWEEN 1001 AND 1999;

UPDATE proj_project p SET progress = COALESCE((
  SELECT ROUND(AVG(t.progress)) FROM proj_task t WHERE t.project_id = p.id AND t.deleted_at IS NULL
), 0) WHERE p.id BETWEEN 1001 AND 1999;

COMMIT;

-- 把自增起点移出测试 ID 段：之后在页面上新建的数据不会落入测试段，重导/清除测试数据时不会被误删
ALTER TABLE sys_user        AUTO_INCREMENT = 200;
ALTER TABLE crm_customer    AUTO_INCREMENT = 2000;
ALTER TABLE crm_contact     AUTO_INCREMENT = 2000;
ALTER TABLE crm_followup    AUTO_INCREMENT = 2000;
ALTER TABLE crm_opportunity AUTO_INCREMENT = 2000;
ALTER TABLE proj_project    AUTO_INCREMENT = 2000;
ALTER TABLE proj_task       AUTO_INCREMENT = 2000;
ALTER TABLE fin_receivable  AUTO_INCREMENT = 2000;
ALTER TABLE fin_payable     AUTO_INCREMENT = 2000;
ALTER TABLE fin_transaction AUTO_INCREMENT = 2000;
ALTER TABLE fin_expense     AUTO_INCREMENT = 2000;
ALTER TABLE sup_supplier    AUTO_INCREMENT = 2000;
ALTER TABLE sup_purchase    AUTO_INCREMENT = 2000;
