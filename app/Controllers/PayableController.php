<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BizException;
use App\Core\ResourceController;
use App\Dict;
use App\Services\FinanceService;

/** 财务管理 - 应付款 */
class PayableController extends ResourceController
{
    protected string $table = 'fin_payable';
    protected string $perm = 'payable';
    protected string $title = '应付款';
    protected string $path = '/payables';
    protected string $nameField = 'title';
    protected string $defaultSort = 'due_date';
    protected ?string $dateField = 'due_date';

    protected function fields(): array
    {
        return [
            'title'       => ['label' => '款项名称', 'required' => true, 'search' => true, 'width' => 180],
            'supplier_id' => ['label' => '供应商', 'type' => 'relation', 'table' => 'sup_supplier', 'display' => 'name', 'required' => true, 'filter' => true, 'width' => 180],
            'purchase_id' => ['label' => '采购订单', 'type' => 'relation', 'table' => 'sup_purchase', 'display' => 'title', 'list' => false],
            'project_id'  => ['label' => '项目', 'type' => 'relation', 'table' => 'proj_project', 'display' => 'name', 'filter' => true],
            'amount'      => ['label' => '应付金额', 'type' => 'money', 'required' => true, 'sort' => true],
            'paid_amount' => ['label' => '已付金额', 'type' => 'money', 'readonly' => true, 'tips' => '登记支出流水后自动更新'],
            'due_date'    => ['label' => '计划付款日', 'type' => 'date', 'sort' => true],
            'status'      => ['label' => '状态', 'type' => 'select', 'options' => Dict::PAY_STATUS, 'readonly' => true, 'filter' => true, 'width' => 100],
            'owner_id'    => ['label' => '负责人', 'type' => 'user'],
            'remark'      => ['label' => '备注', 'type' => 'textarea'],
        ];
    }

    protected function children(): array
    {
        return [['title' => '付款流水', 'path' => '/transactions', 'fk' => 'payable_id', 'perm' => 'transaction.view']];
    }

    protected function beforeSave(array $data, ?array $old): array
    {
        if ($data['amount'] <= 0) {
            throw new BizException('应付金额必须大于 0');
        }
        return $data;
    }

    protected function afterSave(int $id, array $data, ?array $old): void
    {
        FinanceService::recalcPayable($id);
    }

    protected function beforeDelete(array $row): void
    {
        if ((float)$row['paid_amount'] > 0) {
            throw new BizException("「{$row['title']}」已有付款记录，不能删除");
        }
    }
}
