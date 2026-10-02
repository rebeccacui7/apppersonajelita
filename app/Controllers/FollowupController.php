<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\DB;
use App\Core\ResourceController;
use App\Dict;

/** 客户管理 - 跟进记录 */
class FollowupController extends ResourceController
{
    protected string $table = 'crm_followup';
    protected string $perm = 'followup';
    protected string $title = '跟进记录';
    protected string $path = '/followups';
    protected string $nameField = 'content';
    protected string $defaultSort = 'follow_at';
    protected ?string $dateField = 'follow_at';

    protected function fields(): array
    {
        return [
            'customer_id'    => ['label' => '客户', 'type' => 'relation', 'table' => 'crm_customer', 'display' => 'name', 'required' => true, 'filter' => true, 'width' => 180],
            'opportunity_id' => ['label' => '关联商机', 'type' => 'relation', 'table' => 'crm_opportunity', 'display' => 'title'],
            'method'         => ['label' => '跟进方式', 'type' => 'select', 'options' => Dict::FOLLOW_METHOD, 'default' => 1, 'filter' => true, 'width' => 100],
            'follow_at'      => ['label' => '跟进时间', 'type' => 'datetime', 'required' => true, 'default' => date('Y-m-d H:i:s'), 'sort' => true, 'width' => 170],
            'content'        => ['label' => '跟进内容', 'type' => 'textarea', 'required' => true, 'search' => true, 'list' => true, 'width' => 280],
            'next_plan'      => ['label' => '下一步计划'],
            'next_at'        => ['label' => '下次跟进日期', 'type' => 'date'],
            'owner_id'       => ['label' => '跟进人', 'type' => 'user', 'filter' => true],
        ];
    }

    /** 同步客户的下次跟进日期，并把“潜在”客户推进到“跟进中” */
    protected function afterSave(int $id, array $data, ?array $old): void
    {
        DB::query(
            'UPDATE crm_customer SET next_follow_at = ?, status = IF(status = 1, 2, status) WHERE id = ?',
            [$data['next_at'], $data['customer_id']]
        );
    }
}
