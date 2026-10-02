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
use App\Services\FinanceService;

/**
 * 财务管理 - 费用报销
 * 流程：待审批 -> 已通过 / 已驳回(可修改后重新提交) -> 已支付(自动生成支出流水)
 */
class ExpenseController extends ResourceController
{
    private const PENDING = 1;
    private const APPROVED = 2;
    private const REJECTED = 3;
    private const PAID = 4;

    protected string $table = 'fin_expense';
    protected string $perm = 'expense';
    protected string $title = '费用报销';
    protected string $path = '/expenses';
    protected ?string $ownerField = 'applicant_id';
    protected string $nameField = 'code';
    protected ?string $dateField = 'expense_date';

    protected function fields(): array
    {
        return [
            'code'           => ['label' => '报销单号', 'readonly' => true, 'search' => true, 'width' => 150, 'tips' => '保存后自动生成'],
            'applicant_id'   => ['label' => '申请人', 'type' => 'user', 'filter' => true],
            'category'       => ['label' => '费用类别', 'type' => 'select', 'options' => Dict::EXPENSE_CATEGORY, 'required' => true, 'filter' => true],
            'amount'         => ['label' => '报销金额', 'type' => 'money', 'required' => true, 'sort' => true],
            'expense_date'   => ['label' => '发生日期', 'type' => 'date', 'required' => true, 'default' => date('Y-m-d'), 'sort' => true],
            'project_id'     => ['label' => '关联项目', 'type' => 'relation', 'table' => 'proj_project', 'display' => 'name', 'filter' => true],
            'status'         => ['label' => '状态', 'type' => 'select', 'options' => Dict::EXPENSE_STATUS, 'readonly' => true, 'filter' => true, 'width' => 90],
            'approver_id'    => ['label' => '审批人', 'type' => 'user', 'readonly' => true],
            'approved_at'    => ['label' => '审批时间', 'type' => 'datetime', 'readonly' => true, 'list' => false],
            'approve_remark' => ['label' => '审批意见', 'readonly' => true, 'list' => false],
            'description'    => ['label' => '费用说明', 'type' => 'textarea', 'required' => true, 'search' => true],
        ];
    }

    protected function rowActions(): array
    {
        return [
            ['text' => '通过', 'url' => '/expenses/approve', 'perm' => 'expense.approve', 'confirm' => '确认审批通过？', 'when' => ['status' => [self::PENDING]]],
            ['text' => '驳回', 'url' => '/expenses/reject', 'perm' => 'expense.approve', 'class' => 'layui-btn-warm', 'confirm' => '确认驳回该报销？', 'when' => ['status' => [self::PENDING]]],
            ['text' => '支付', 'url' => '/expenses/pay', 'perm' => 'expense.pay', 'confirm' => '确认已支付？将自动生成一笔支出流水', 'when' => ['status' => [self::APPROVED]]],
        ];
    }

    protected function beforeSave(array $data, ?array $old): array
    {
        if ($data['amount'] <= 0) {
            throw new BizException('报销金额必须大于 0');
        }
        if ($old) {
            if (!in_array((int)$old['status'], [self::PENDING, self::REJECTED], true)) {
                throw new BizException('已审批的报销单不能修改');
            }
            // 被驳回的单据修改后重新进入审批
            $data['status'] = self::PENDING;
            $data['approver_id'] = null;
            $data['approved_at'] = null;
        } else {
            $data['code'] = $this->serial('BX');
            $data['status'] = self::PENDING;
        }
        return $data;
    }

    protected function beforeDelete(array $row): void
    {
        if (!in_array((int)$row['status'], [self::PENDING, self::REJECTED], true)) {
            throw new BizException("报销单 {$row['code']} 已审批，不能删除");
        }
    }

    public function approve(): void
    {
        $this->decide(self::APPROVED, '审批通过');
    }

    public function reject(): void
    {
        $this->decide(self::REJECTED, '已驳回');
    }

    private function decide(int $status, string $msg): void
    {
        $row = $this->loadForAction(self::PENDING);
        if ((int)$row['applicant_id'] === Auth::id() && !Auth::isSuper()) {
            throw new BizException('不能审批自己提交的报销单');
        }
        DB::update($this->table, [
            'status'         => $status,
            'approver_id'    => Auth::id(),
            'approved_at'    => date('Y-m-d H:i:s'),
            'approve_remark' => mb_substr(Request::str('remark'), 0, 255),
        ], (int)$row['id']);
        Logger::log($this->perm, $status === self::APPROVED ? 'approve' : 'reject', (int)$row['id'], $row['code']);
        $this->success($msg);
    }

    public function pay(): void
    {
        $row = $this->loadForAction(self::APPROVED);
        DB::transaction(function () use ($row) {
            $txId = DB::insert('fin_transaction', [
                'summary'    => '费用报销 ' . $row['code'],
                'type'       => FinanceService::TYPE_EXPENSE,
                'category'   => 3,
                'amount'     => $row['amount'],
                'trade_date' => date('Y-m-d'),
                'account'    => 1,
                'project_id' => $row['project_id'],
                'handler_id' => Auth::id(),
                'created_by' => Auth::id(),
            ]);
            DB::update($this->table, ['status' => self::PAID, 'transaction_id' => $txId], (int)$row['id']);
        });
        Logger::log($this->perm, 'pay', (int)$row['id'], $row['code']);
        $this->success('已支付并生成支出流水');
    }

    private function loadForAction(int $expectStatus): array
    {
        $row = $this->findScoped(Request::int('id'));
        if (!$row) {
            throw new BizException('报销单不存在或无权操作');
        }
        if ((int)$row['status'] !== $expectStatus) {
            throw new BizException('当前状态不允许该操作');
        }
        return $row;
    }
}
