<?php
declare(strict_types=1);

namespace App;

/** 业务字典（枚举值统一在此维护，数据库中存储键值） */
final class Dict
{
    // ---- 客户
    public const CUSTOMER_TYPE   = [1 => '企业', 2 => '政府/事业单位', 3 => '个人'];
    public const CUSTOMER_LEVEL  = [1 => 'A 重点客户', 2 => 'B 普通客户', 3 => 'C 潜在客户'];
    public const CUSTOMER_STATUS = [1 => '潜在', 2 => '跟进中', 3 => '已成交', 4 => '已流失'];
    public const SOURCE          = [1 => '官网', 2 => '转介绍', 3 => '展会', 4 => '电话营销', 5 => '老客户', 9 => '其他'];
    public const FOLLOW_METHOD   = [1 => '电话', 2 => '拜访', 3 => '微信', 4 => '邮件', 5 => '会议'];
    public const YES_NO          = [0 => '否', 1 => '是'];

    // ---- 商机
    public const OPP_STAGE = [1 => '初步接洽', 2 => '需求确认', 3 => '方案报价', 4 => '商务谈判', 5 => '赢单', 6 => '输单'];
    /** 各阶段默认赢率 % */
    public const OPP_STAGE_PROB = [1 => 10, 2 => 30, 3 => 50, 4 => 70, 5 => 100, 6 => 0];
    public const OPP_WON = 5;
    public const OPP_LOST = 6;

    // ---- 执行
    public const PROJECT_STATUS = [1 => '未启动', 2 => '进行中', 3 => '已暂停', 4 => '已验收', 5 => '已关闭'];
    public const TASK_TYPE      = [1 => '里程碑', 2 => '任务'];
    public const TASK_STATUS    = [1 => '待开始', 2 => '进行中', 3 => '已完成', 4 => '已延期'];
    public const PRIORITY       = [1 => '高', 2 => '中', 3 => '低'];

    // ---- 执行业务（签证 / 公司注册）
    public const BIZ_TYPE   = [1 => '签证', 2 => '公司注册'];
    public const BIZ_VISA    = 1;
    public const BIZ_COMPANY = 2;
    /** 办理状态 / 业务状态；选「已完成」后自动转入供应商应付 */
    public const BIZ_STATUS = [1 => '待办理', 2 => '办理中', 3 => '已完成'];
    public const BIZ_STATUS_DOING = 2;
    public const BIZ_STATUS_DONE  = 3;
    /** 业务阶段：决定数据出现在哪张表 */
    public const BIZ_STAGE  = [1 => '在途', 2 => '供应商应付', 3 => '完成项目'];
    public const STAGE_ACTIVE  = 1;
    public const STAGE_PAYABLE = 2;
    public const STAGE_DONE    = 3;
    public const BIZ_PAY_STATUS = [1 => '待付款', 2 => '已付款'];
    public const BIZ_UNPAID = 1;
    public const BIZ_PAID   = 2;

    // ---- 财务
    public const PAY_STATUS     = [1 => '未结清', 2 => '部分结清', 3 => '已结清'];
    public const TRADE_TYPE     = [1 => '收入', 2 => '支出'];
    public const TRADE_CATEGORY = [1 => '项目回款', 2 => '采购付款', 3 => '费用报销', 4 => '工资社保', 5 => '税费', 6 => '房租水电', 9 => '其他'];
    public const ACCOUNT        = [1 => '对公账户', 2 => '现金', 3 => '支付宝', 4 => '微信'];
    public const EXPENSE_CATEGORY = [1 => '差旅', 2 => '业务招待', 3 => '办公用品', 4 => '交通', 5 => '通讯', 9 => '其他'];
    public const EXPENSE_STATUS   = [1 => '待审批', 2 => '已通过', 3 => '已驳回', 4 => '已支付'];

    // ---- 供应商
    public const SUPPLIER_CATEGORY = [1 => '硬件设备', 2 => '软件服务', 3 => '外包服务', 4 => '办公用品', 9 => '其他'];
    public const SUPPLIER_RATING   = [1 => 'A 优秀', 2 => 'B 良好', 3 => 'C 一般', 4 => 'D 较差'];
    public const SUPPLIER_STATUS   = [1 => '合作中', 2 => '暂停合作', 3 => '黑名单'];
    public const PURCHASE_STATUS   = [1 => '草稿', 2 => '已下单', 3 => '已到货', 4 => '已完成', 5 => '已取消'];

    // ---- 系统
    public const STATUS     = [1 => '启用', 0 => '停用'];
    public const DATA_SCOPE = [1 => '全部数据', 2 => '本部门数据', 3 => '仅本人数据'];
    public const MENU_TYPE  = [1 => '目录', 2 => '菜单', 3 => '按钮'];
}
