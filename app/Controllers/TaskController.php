<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\BizException;
use App\Core\ResourceController;
use App\Dict;

/** 执行管理 - 任务 / 里程碑 */
class TaskController extends ResourceController
{
    protected string $table = 'proj_task';
    protected string $perm = 'task';
    protected string $title = '任务';
    protected string $path = '/tasks';
    protected ?string $ownerField = 'assignee_id';
    protected string $defaultSort = 'due_date';

    protected function fields(): array
    {
        return [
            'name'        => ['label' => '任务名称', 'required' => true, 'search' => true, 'width' => 200],
            'project_id'  => ['label' => '所属项目', 'type' => 'relation', 'table' => 'proj_project', 'display' => 'name', 'required' => true, 'filter' => true, 'width' => 180],
            'type'        => ['label' => '类型', 'type' => 'select', 'options' => Dict::TASK_TYPE, 'default' => 2, 'required' => true, 'filter' => true, 'width' => 90],
            'priority'    => ['label' => '优先级', 'type' => 'select', 'options' => Dict::PRIORITY, 'default' => 2, 'width' => 80],
            'assignee_id' => ['label' => '负责人', 'type' => 'user', 'filter' => true],
            'start_date'  => ['label' => '开始日期', 'type' => 'date'],
            'due_date'    => ['label' => '截止日期', 'type' => 'date', 'sort' => true],
            'status'      => ['label' => '状态', 'type' => 'select', 'options' => Dict::TASK_STATUS, 'default' => 1, 'required' => true, 'filter' => true, 'width' => 90],
            'progress'    => ['label' => '进度(%)', 'type' => 'number', 'default' => 0, 'width' => 90],
            'description' => ['label' => '任务说明', 'type' => 'textarea'],
        ];
    }

    /** 项目经理能看到自己项目下的全部任务；其他人按负责人数据范围 */
    protected function scopeWhere(): array
    {
        [$sql, $params] = Auth::scopeSql('t.assignee_id');
        if ($sql === '') {
            return ['', []];
        }
        [$psql, $pparams] = Auth::scopeSql('p.manager_id');
        return ["($sql OR t.project_id IN (SELECT p.id FROM proj_project p WHERE $psql))", array_merge($params, $pparams)];
    }

    protected function beforeSave(array $data, ?array $old): array
    {
        $p = (int)($data['progress'] ?? 0);
        if ($p < 0 || $p > 100) {
            throw new BizException('进度需在 0~100 之间');
        }
        if ((int)$data['status'] === 3) {
            $data['progress'] = 100;
        } elseif ($p === 100) {
            $data['status'] = 3;
        }
        return $data;
    }

    protected function afterSave(int $id, array $data, ?array $old): void
    {
        ProjectController::recalcProgress((int)$data['project_id']);
        if ($old && (int)$old['project_id'] !== (int)$data['project_id']) {
            ProjectController::recalcProgress((int)$old['project_id']);
        }
    }

    protected function afterDelete(array $row): void
    {
        ProjectController::recalcProgress((int)$row['project_id']);
    }
}
