<?php
declare(strict_types=1);

namespace App\Controllers\Exec;

use App\Core\BizException;
use App\Core\DB;
use App\Core\Logger;
use App\Core\Request;
use App\Core\ResourceController;
use App\Dict;

/** 执行管理 - 完成项目表（供应商已付款的业务，只读归档） */
class CompletedBizController extends ResourceController
{
    protected string $table = 'exec_business';
    protected string $perm = 'bizdone';
    protected string $title = '完成项目';
    protected string $path = '/exec/done';
    protected ?string $ownerField = null;
    protected string $nameField = 'group_name';
    protected string $defaultSort = 'paid_date';
    protected ?string $dateField = 'paid_date';
    protected bool $readonly = true;

    protected function baseWhere(): array
    {
        return ['t.stage = ?', [Dict::STAGE_DONE]];
    }

    protected function fields(): array
    {
        return [
            'group_name'   => ['label' => '业务群名', 'search' => true, 'width' => 180],
            'biz_type'     => ['label' => '业务类型', 'type' => 'select', 'options' => Dict::BIZ_TYPE, 'filter' => true, 'width' => 100],
            'contact_name' => ['label' => '对接人', 'search' => true],
            'business'     => ['label' => '具体业务', 'search' => true, 'width' => 180],
            'applicant'    => ['label' => '申请对象', 'search' => true, 'list' => false],
            'supplier_id'  => ['label' => '供应商', 'type' => 'relation', 'table' => 'sup_supplier', 'display' => 'name', 'filter' => true, 'width' => 160],
            'start_date'   => ['label' => '开始日期', 'type' => 'date', 'sort' => true],
            'end_date'     => ['label' => '结束日期', 'type' => 'date', 'sort' => true],
            'completed_at' => ['label' => '完成时间', 'type' => 'datetime', 'list' => false],
            'pay_amount'   => ['label' => '应付金额', 'type' => 'money', 'sort' => true],
            'paid_date'    => ['label' => '付款日期', 'type' => 'date', 'sort' => true],
            'pay_remark'   => ['label' => '付款备注', 'list' => false],
            'files'        => ['label' => '交付文件', 'type' => 'file', 'width' => 200],
        ];
    }

    protected function rowActions(): array
    {
        return [[
            'text' => '撤回到应付', 'url' => '/exec/done/revert', 'perm' => 'bizdone.revert', 'class' => 'layui-btn-warm',
            'confirm' => '撤回后付款状态改回「待付款」，记录回到「供应商应付」，确定？',
        ]];
    }

    public function revert(): void
    {
        $row = $this->findScoped(Request::int('id'));
        if (!$row) {
            throw new BizException('记录不存在或已不在完成项目表中');
        }
        DB::update($this->table, [
            'stage'      => Dict::STAGE_PAYABLE,
            'pay_status' => Dict::BIZ_UNPAID,
            'paid_date'  => null,
        ], (int)$row['id']);
        Logger::log($this->perm, 'revert', (int)$row['id'], (string)$row['group_name']);
        $this->success('已撤回到「供应商应付」');
    }
}
