<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BizException;
use App\Core\ResourceController;
use App\Dict;
use App\Services\FinanceService;

/** 财务管理 - 应收款 / 回款计划 */
class ReceivableController extends ResourceController
{
    protected string $table = 'fin_receivable';
    protected string $perm = 'receivable';
    protected string $title = '应收款';
    protected string $path = '/receivables';
    protected string $nameField = 'title';
    protected string $defaultSort = 'due_date';
    protected ?string $dateField = 'due_date';

    protected function fields(): array
    {
        return [
            'title'           => ['label' => '款项名称', 'required' => true, 'search' => true, 'width' => 180, 'tips' => '如：首付款 30%'],
            'customer_id'     => ['label' => '客户', 'type' => 'relation', 'table' => 'crm_customer', 'display' => 'name', 'required' => true, 'filter' => true, 'width' => 180],
            'project_id'      => ['label' => '项目', 'type' => 'relation', 'table' => 'proj_project', 'display' => 'name', 'filter' => true, 'width' => 180],
            'amount'          => ['label' => '应收金额', 'type' => 'money', 'required' => true, 'sort' => true],
            'received_amount' => ['label' => '已收金额', 'type' => 'money', 'readonly' => true, 'tips' => '登记收入流水后自动更新'],
            'due_date'        => ['label' => '计划收款日', 'type' => 'date', 'sort' => true],
            'status'          => ['label' => '状态', 'type' => 'select', 'options' => Dict::PAY_STATUS, 'readonly' => true, 'filter' => true, 'width' => 100],
            'owner_id'        => ['label' => '负责人', 'type' => 'user', 'filter' => true],
            'remark'          => ['label' => '备注', 'type' => 'textarea'],
        ];
    }

    protected function children(): array
    {
        return [['title' => '收款流水', 'path' => '/transactions', 'fk' => 'receivable_id', 'perm' => 'transaction.view']];
    }

    protected function beforeSave(array $data, ?array $old): array
    {
        if ($data['amount'] <= 0) {
            throw new BizException('应收金额必须大于 0');
        }
        return $data;
    }

    protected function afterSave(int $id, array $data, ?array $old): void
    {
        FinanceService::recalcReceivable($id);
    }

    protected function beforeDelete(array $row): void
    {
        if ((float)$row['received_amount'] > 0) {
            throw new BizException("「{$row['title']}」已有收款记录，不能删除");
        }
    }
}
