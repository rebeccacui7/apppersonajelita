<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BizException;
use App\Core\DB;
use App\Core\ResourceController;
use App\Dict;
use App\Services\FinanceService;

/** 财务管理 - 收支流水（实际发生的资金进出） */
class TransactionController extends ResourceController
{
    protected string $table = 'fin_transaction';
    protected string $perm = 'transaction';
    protected string $title = '收支流水';
    protected string $path = '/transactions';
    protected ?string $ownerField = 'handler_id';
    protected string $nameField = 'summary';
    protected string $defaultSort = 'trade_date';
    protected ?string $dateField = 'trade_date';

    protected function fields(): array
    {
        return [
            'summary'       => ['label' => '摘要', 'required' => true, 'search' => true, 'width' => 200],
            'type'          => ['label' => '收支类型', 'type' => 'select', 'options' => Dict::TRADE_TYPE, 'required' => true, 'filter' => true, 'width' => 90],
            'category'      => ['label' => '类别', 'type' => 'select', 'options' => Dict::TRADE_CATEGORY, 'required' => true, 'filter' => true, 'width' => 100],
            'amount'        => ['label' => '金额', 'type' => 'money', 'required' => true, 'sort' => true],
            'trade_date'    => ['label' => '发生日期', 'type' => 'date', 'required' => true, 'default' => date('Y-m-d'), 'sort' => true],
            'account'       => ['label' => '资金账户', 'type' => 'select', 'options' => Dict::ACCOUNT, 'default' => 1, 'filter' => true],
            'customer_id'   => ['label' => '客户', 'type' => 'relation', 'table' => 'crm_customer', 'display' => 'name', 'list' => false],
            'supplier_id'   => ['label' => '供应商', 'type' => 'relation', 'table' => 'sup_supplier', 'display' => 'name', 'list' => false],
            'project_id'    => ['label' => '项目', 'type' => 'relation', 'table' => 'proj_project', 'display' => 'name', 'filter' => true],
            'receivable_id' => ['label' => '核销应收', 'type' => 'relation', 'table' => 'fin_receivable', 'display' => 'title', 'list' => false, 'tips' => '收入时可选，自动更新应收已收金额'],
            'payable_id'    => ['label' => '核销应付', 'type' => 'relation', 'table' => 'fin_payable', 'display' => 'title', 'list' => false, 'tips' => '支出时可选，自动更新应付已付金额'],
            'voucher_no'    => ['label' => '凭证号', 'search' => true],
            'handler_id'    => ['label' => '经办人', 'type' => 'user'],
            'remark'        => ['label' => '备注', 'type' => 'textarea'],
        ];
    }

    protected function beforeSave(array $data, ?array $old): array
    {
        if ($data['amount'] <= 0) {
            throw new BizException('金额必须大于 0');
        }
        $type = (int)$data['type'];
        if ($data['receivable_id']) {
            if ($type !== FinanceService::TYPE_INCOME) {
                throw new BizException('核销应收时收支类型必须为“收入”');
            }
            $r = DB::row('SELECT customer_id, project_id FROM fin_receivable WHERE id = ? AND deleted_at IS NULL', [$data['receivable_id']]);
            if (!$r) {
                throw new BizException('应收款不存在');
            }
            $data['customer_id'] = $data['customer_id'] ?: $r['customer_id'];
            $data['project_id'] = $data['project_id'] ?: $r['project_id'];
        }
        if ($data['payable_id']) {
            if ($type !== FinanceService::TYPE_EXPENSE) {
                throw new BizException('核销应付时收支类型必须为“支出”');
            }
            $p = DB::row('SELECT supplier_id, project_id FROM fin_payable WHERE id = ? AND deleted_at IS NULL', [$data['payable_id']]);
            if (!$p) {
                throw new BizException('应付款不存在');
            }
            $data['supplier_id'] = $data['supplier_id'] ?: $p['supplier_id'];
            $data['project_id'] = $data['project_id'] ?: $p['project_id'];
        }
        return $data;
    }

    protected function afterSave(int $id, array $data, ?array $old): void
    {
        FinanceService::recalcReceivable($data['receivable_id']);
        FinanceService::recalcPayable($data['payable_id']);
        if ($old) {
            if ((int)$old['receivable_id'] !== (int)$data['receivable_id']) {
                FinanceService::recalcReceivable($old['receivable_id'] ? (int)$old['receivable_id'] : null);
            }
            if ((int)$old['payable_id'] !== (int)$data['payable_id']) {
                FinanceService::recalcPayable($old['payable_id'] ? (int)$old['payable_id'] : null);
            }
        }
    }

    protected function beforeDelete(array $row): void
    {
        if (DB::value('SELECT id FROM fin_expense WHERE transaction_id = ? AND deleted_at IS NULL', [$row['id']])) {
            throw new BizException('该流水由费用报销支付生成，不能直接删除');
        }
    }

    protected function afterDelete(array $row): void
    {
        FinanceService::recalcReceivable($row['receivable_id'] ? (int)$row['receivable_id'] : null);
        FinanceService::recalcPayable($row['payable_id'] ? (int)$row['payable_id'] : null);
    }
}
