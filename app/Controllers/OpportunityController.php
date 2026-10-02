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

/** 商机管理 */
class OpportunityController extends ResourceController
{
    protected string $table = 'crm_opportunity';
    protected string $perm = 'opportunity';
    protected string $title = '商机';
    protected string $path = '/opportunities';
    protected string $nameField = 'title';

    protected function fields(): array
    {
        return [
            'title'          => ['label' => '商机名称', 'required' => true, 'search' => true, 'width' => 200],
            'customer_id'    => ['label' => '客户', 'type' => 'relation', 'table' => 'crm_customer', 'display' => 'name', 'required' => true, 'filter' => true, 'width' => 180],
            'amount'         => ['label' => '预计金额', 'type' => 'money', 'sort' => true],
            'stage'          => ['label' => '销售阶段', 'type' => 'select', 'options' => Dict::OPP_STAGE, 'default' => 1, 'required' => true, 'filter' => true],
            'probability'    => ['label' => '赢率(%)', 'type' => 'number', 'tips' => '留空则按阶段自动填充'],
            'expected_close' => ['label' => '预计成交日', 'type' => 'date', 'sort' => true],
            'source'         => ['label' => '来源', 'type' => 'select', 'options' => Dict::SOURCE, 'list' => false],
            'owner_id'       => ['label' => '负责人', 'type' => 'user', 'filter' => true],
            'competitor'     => ['label' => '竞争对手', 'list' => false],
            'closed_at'      => ['label' => '关闭日期', 'type' => 'date', 'readonly' => true, 'list' => false],
            'lost_reason'    => ['label' => '输单原因', 'type' => 'textarea'],
            'remark'         => ['label' => '备注', 'type' => 'textarea'],
        ];
    }

    protected function children(): array
    {
        return [
            ['title' => '跟进记录', 'path' => '/followups', 'fk' => 'opportunity_id', 'perm' => 'followup.view'],
            ['title' => '项目', 'path' => '/projects', 'fk' => 'opportunity_id', 'perm' => 'project.view'],
        ];
    }

    protected function rowActions(): array
    {
        return [[
            'text' => '转为项目', 'url' => '/opportunities/convert', 'perm' => 'project.edit',
            'type' => 'ajax', 'class' => 'layui-btn-warm',
            'confirm' => '将根据该商机创建执行项目，确定继续？',
            'when' => ['stage' => [Dict::OPP_WON]],
        ]];
    }

    protected function beforeSave(array $data, ?array $old): array
    {
        $stage = (int)$data['stage'];
        if ($data['probability'] === null) {
            $data['probability'] = Dict::OPP_STAGE_PROB[$stage] ?? 0;
        }
        if ($data['probability'] < 0 || $data['probability'] > 100) {
            throw new BizException('赢率需在 0~100 之间');
        }
        if ($stage === Dict::OPP_LOST && trim((string)$data['lost_reason']) === '') {
            throw new BizException('输单时请填写输单原因');
        }
        $closed = in_array($stage, [Dict::OPP_WON, Dict::OPP_LOST], true);
        $wasClosed = $old && in_array((int)$old['stage'], [Dict::OPP_WON, Dict::OPP_LOST], true);
        if ($closed && !$wasClosed) {
            $data['closed_at'] = date('Y-m-d');
        } elseif (!$closed) {
            $data['closed_at'] = null;
        }
        return $data;
    }

    protected function afterSave(int $id, array $data, ?array $old): void
    {
        if ((int)$data['stage'] === Dict::OPP_WON) {
            DB::query('UPDATE crm_customer SET status = 3 WHERE id = ?', [$data['customer_id']]);
        }
    }

    /** 赢单商机一键转为执行项目 */
    public function convert(): void
    {
        $id = Request::int('id');
        $opp = $this->findScoped($id);
        if (!$opp) {
            throw new BizException('商机不存在或无权操作');
        }
        if ((int)$opp['stage'] !== Dict::OPP_WON) {
            throw new BizException('只有赢单的商机才能转为项目');
        }
        if (DB::value('SELECT id FROM proj_project WHERE opportunity_id = ? AND deleted_at IS NULL', [$id])) {
            throw new BizException('该商机已转为项目');
        }
        $projectId = DB::insert('proj_project', [
            'code'            => (new ProjectController())->nextCode(),
            'name'            => $opp['title'],
            'customer_id'     => $opp['customer_id'],
            'opportunity_id'  => $id,
            'contract_amount' => $opp['amount'],
            'status'          => 1,
            'progress'        => 0,
            'manager_id'      => Auth::id(),
            'created_by'      => Auth::id(),
        ]);
        Logger::log('project', 'create', $projectId, "由商机#{$id}转入");
        $this->success('已创建项目', ['id' => $projectId]);
    }
}
