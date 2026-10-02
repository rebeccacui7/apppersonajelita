<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\BizException;
use App\Core\DB;
use App\Core\Logger;
use App\Core\Request;
use App\Core\ResourceController;
use App\Dict;

/** 供应商管理 - 采购订单 */
class PurchaseController extends ResourceController
{
    protected string $table = 'sup_purchase';
    protected string $perm = 'purchase';
    protected string $title = '采购订单';
    protected string $path = '/purchases';
    protected string $nameField = 'title';
    protected ?string $dateField = 'order_date';

    protected function fields(): array
    {
        return [
            'title'         => ['label' => '采购内容', 'required' => true, 'search' => true, 'width' => 200],
            'code'          => ['label' => '采购单号', 'readonly' => true, 'search' => true, 'width' => 150, 'tips' => '保存后自动生成'],
            'supplier_id'   => ['label' => '供应商', 'type' => 'relation', 'table' => 'sup_supplier', 'display' => 'name', 'required' => true, 'filter' => true, 'width' => 180],
            'project_id'    => ['label' => '关联项目', 'type' => 'relation', 'table' => 'proj_project', 'display' => 'name', 'filter' => true],
            'amount'        => ['label' => '采购金额', 'type' => 'money', 'required' => true, 'sort' => true],
            'order_date'    => ['label' => '下单日期', 'type' => 'date', 'default' => date('Y-m-d'), 'sort' => true],
            'delivery_date' => ['label' => '交付日期', 'type' => 'date'],
            'status'        => ['label' => '状态', 'type' => 'select', 'options' => Dict::PURCHASE_STATUS, 'default' => 1, 'filter' => true, 'width' => 90],
            'owner_id'      => ['label' => '采购负责人', 'type' => 'user'],
            'remark'        => ['label' => '备注', 'type' => 'textarea'],
        ];
    }

    protected function children(): array
    {
        return [['title' => '应付款', 'path' => '/payables', 'fk' => 'purchase_id', 'perm' => 'payable.view']];
    }

    protected function rowActions(): array
    {
        return [[
            'text' => '生成应付', 'url' => '/purchases/payable', 'perm' => 'payable.edit',
            'confirm' => '按采购金额生成一笔应付款？', 'when' => ['status' => [2, 3, 4]],
        ]];
    }

    protected function beforeSave(array $data, ?array $old): array
    {
        if ($data['amount'] <= 0) {
            throw new BizException('采购金额必须大于 0');
        }
        if (!$old) {
            $data['code'] = $this->serial('PO');
        }
        $status = DB::value('SELECT status FROM sup_supplier WHERE id = ?', [$data['supplier_id']]);
        if ((int)$status === 3) {
            throw new BizException('该供应商已列入黑名单，不能下单');
        }
        return $data;
    }

    /** 根据采购单生成应付款 */
    public function payable(): void
    {
        $row = $this->findScoped(Request::int('id'));
        if (!$row) {
            throw new BizException('采购单不存在或无权操作');
        }
        if (DB::value('SELECT id FROM fin_payable WHERE purchase_id = ? AND deleted_at IS NULL', [$row['id']])) {
            throw new BizException('该采购单已生成应付款');
        }
        $id = DB::insert('fin_payable', [
            'title'       => '采购款 ' . $row['code'],
            'supplier_id' => $row['supplier_id'],
            'purchase_id' => $row['id'],
            'project_id'  => $row['project_id'],
            'amount'      => $row['amount'],
            'due_date'    => $row['delivery_date'],
            'status'      => 1,
            'owner_id'    => Auth::id(),
            'created_by'  => Auth::id(),
        ]);
        Logger::log('payable', 'create', $id, '由采购单 ' . $row['code'] . ' 生成');
        $this->success('已生成应付款');
    }
}
