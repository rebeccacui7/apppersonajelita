<?php
declare(strict_types=1);

namespace App\Controllers\Exec;

use App\Core\BizException;
use App\Core\DB;
use App\Core\Logger;
use App\Core\Request;
use App\Core\ResourceController;
use App\Dict;

/**
 * 执行管理 - 供应商应付表
 * 数据来源：签证 / 公司注册 的办理状态改为「已完成」后自动进入；不支持直接新增。
 * 付款状态改为「已付款」后自动转入「完成项目」。
 */
class SupplierPayableController extends ResourceController
{
    protected string $table = 'exec_business';
    protected string $perm = 'bizpay';
    protected string $title = '供应商应付';
    protected string $path = '/exec/payables';
    protected ?string $ownerField = null;
    protected string $nameField = 'group_name';
    protected string $defaultSort = 'completed_at';
    protected ?string $dateField = 'completed_at';
    protected bool $allowCreate = false;

    protected function baseWhere(): array
    {
        return ['t.stage = ?', [Dict::STAGE_PAYABLE]];
    }

    protected function fields(): array
    {
        return [
            'group_name'   => ['label' => '业务群名', 'readonly' => true, 'search' => true, 'width' => 180],
            'biz_type'     => ['label' => '业务类型', 'type' => 'select', 'options' => Dict::BIZ_TYPE, 'readonly' => true, 'filter' => true, 'width' => 100],
            'business'     => ['label' => '具体业务', 'readonly' => true, 'search' => true, 'width' => 180],
            'applicant'    => ['label' => '申请对象', 'readonly' => true, 'search' => true, 'list' => false],
            'contact_name' => ['label' => '对接人', 'readonly' => true, 'list' => false],
            'start_date'   => ['label' => '开始日期', 'type' => 'date', 'readonly' => true, 'list' => false],
            'end_date'     => ['label' => '结束日期', 'type' => 'date', 'readonly' => true, 'list' => false],
            'completed_at' => ['label' => '完成时间', 'type' => 'datetime', 'readonly' => true, 'sort' => true, 'width' => 170],
            'files'        => ['label' => '交付文件', 'type' => 'file', 'readonly' => true, 'width' => 200],
            'supplier_id'  => ['label' => '供应商', 'type' => 'relation', 'table' => 'sup_supplier', 'display' => 'name', 'filter' => true, 'width' => 160],
            'pay_amount'   => ['label' => '应付金额', 'type' => 'money', 'sort' => true],
            'pay_status'   => ['label' => '付款状态', 'type' => 'select', 'options' => Dict::BIZ_PAY_STATUS, 'required' => true, 'filter' => true, 'width' => 100,
                               'tips' => '改为「已付款」并保存后，自动转入「完成项目」'],
            'paid_date'    => ['label' => '付款日期', 'type' => 'date', 'list' => false, 'tips' => '已付款时留空则默认今天'],
            'pay_remark'   => ['label' => '付款备注', 'list' => false],
        ];
    }

    protected function rowActions(): array
    {
        return [
            ['text' => '标记已付款', 'url' => '/exec/payables/pay', 'perm' => 'bizpay.edit', 'class' => 'layui-btn-normal',
             'confirm' => '确认已付款？该记录将转入「完成项目」'],
            ['text' => '退回在途', 'url' => '/exec/payables/back', 'perm' => 'bizpay.edit', 'class' => 'layui-btn-warm',
             'confirm' => '退回后该业务回到签证/公司注册列表（状态：办理中），确定？'],
        ];
    }

    protected function beforeSave(array $data, ?array $old): array
    {
        if ($data['pay_amount'] !== null && $data['pay_amount'] < 0) {
            throw new BizException('应付金额不能为负数');
        }
        if ((int)$data['pay_status'] === Dict::BIZ_PAID) {
            $data['stage'] = Dict::STAGE_DONE;
            $data['paid_date'] = $data['paid_date'] ?: date('Y-m-d');
            $this->saveMessage = '已付款，已转入「完成项目」';
        } else {
            $data['paid_date'] = null;
        }
        return $data;
    }

    /** 快捷操作：标记已付款 */
    public function pay(): void
    {
        $row = $this->mustFind();
        DB::update($this->table, [
            'pay_status' => Dict::BIZ_PAID,
            'paid_date'  => date('Y-m-d'),
            'stage'      => Dict::STAGE_DONE,
        ], (int)$row['id']);
        Logger::log($this->perm, 'pay', (int)$row['id'], (string)$row['group_name']);
        $this->success('已付款，已转入「完成项目」');
    }

    /** 误操作时退回在途列表 */
    public function back(): void
    {
        $row = $this->mustFind();
        DB::update($this->table, [
            'stage'        => Dict::STAGE_ACTIVE,
            'status'       => Dict::BIZ_STATUS_DOING,
            'completed_at' => null,
        ], (int)$row['id']);
        Logger::log($this->perm, 'back', (int)$row['id'], (string)$row['group_name']);
        $this->success('已退回「' . (Dict::BIZ_TYPE[$row['biz_type']] ?? '') . '」在途列表');
    }

    private function mustFind(): array
    {
        $row = $this->findScoped(Request::int('id'));
        if (!$row) {
            throw new BizException('记录不存在或已不在供应商应付表中');
        }
        return $row;
    }
}
