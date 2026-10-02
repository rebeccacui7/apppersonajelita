<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BizException;
use App\Core\DB;
use App\Core\ResourceController;
use App\Dict;

/** 执行管理 - 项目（含合同信息） */
class ProjectController extends ResourceController
{
    protected string $table = 'proj_project';
    protected string $perm = 'project';
    protected string $title = '项目';
    protected string $path = '/projects';
    protected ?string $ownerField = 'manager_id';

    protected function fields(): array
    {
        return [
            'name'            => ['label' => '项目名称', 'required' => true, 'search' => true, 'width' => 200],
            'code'            => ['label' => '项目编号', 'search' => true, 'tips' => '留空自动生成', 'width' => 150],
            'customer_id'     => ['label' => '客户', 'type' => 'relation', 'table' => 'crm_customer', 'display' => 'name', 'required' => true, 'filter' => true, 'width' => 180],
            'opportunity_id'  => ['label' => '来源商机', 'type' => 'relation', 'table' => 'crm_opportunity', 'display' => 'title', 'list' => false],
            'contract_no'     => ['label' => '合同编号', 'search' => true],
            'contract_amount' => ['label' => '合同金额', 'type' => 'money', 'sort' => true],
            'sign_date'       => ['label' => '签约日期', 'type' => 'date', 'list' => false],
            'start_date'      => ['label' => '开始日期', 'type' => 'date', 'sort' => true],
            'end_date'        => ['label' => '计划完成', 'type' => 'date', 'sort' => true],
            'status'          => ['label' => '项目状态', 'type' => 'select', 'options' => Dict::PROJECT_STATUS, 'default' => 1, 'required' => true, 'filter' => true],
            'progress'        => ['label' => '进度(%)', 'type' => 'number', 'readonly' => true, 'tips' => '由任务进度自动汇总', 'width' => 90],
            'manager_id'      => ['label' => '项目经理', 'type' => 'user', 'filter' => true],
            'remark'          => ['label' => '备注', 'type' => 'textarea'],
        ];
    }

    protected function children(): array
    {
        return [
            ['title' => '任务/里程碑', 'path' => '/tasks', 'fk' => 'project_id', 'perm' => 'task.view'],
            ['title' => '应收款', 'path' => '/receivables', 'fk' => 'project_id', 'perm' => 'receivable.view'],
            ['title' => '采购订单', 'path' => '/purchases', 'fk' => 'project_id', 'perm' => 'purchase.view'],
            ['title' => '费用报销', 'path' => '/expenses', 'fk' => 'project_id', 'perm' => 'expense.view'],
            ['title' => '收支流水', 'path' => '/transactions', 'fk' => 'project_id', 'perm' => 'transaction.view'],
        ];
    }

    public function nextCode(): string
    {
        return $this->serial('PRJ');
    }

    protected function beforeSave(array $data, ?array $old): array
    {
        if ($data['code'] === '' || $data['code'] === null) {
            $data['code'] = $old['code'] ?? $this->nextCode();
        }
        if (DB::value('SELECT id FROM proj_project WHERE code = ? AND id <> ?', [$data['code'], $old['id'] ?? 0])) {
            throw new BizException('项目编号已存在');
        }
        if ($data['start_date'] && $data['end_date'] && $data['end_date'] < $data['start_date']) {
            throw new BizException('计划完成日期不能早于开始日期');
        }
        return $data;
    }

    /** 按任务进度重新计算项目进度（供 TaskController 调用） */
    public static function recalcProgress(int $projectId): void
    {
        $avg = DB::value('SELECT AVG(progress) FROM proj_task WHERE project_id = ? AND deleted_at IS NULL', [$projectId]);
        DB::query('UPDATE proj_project SET progress = ? WHERE id = ?', [$avg === null ? 0 : (int)round((float)$avg), $projectId]);
    }
}
