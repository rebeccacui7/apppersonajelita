<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BizException;
use App\Core\DB;
use App\Core\ResourceController;
use App\Dict;

/** 客户管理 - 客户 */
class CustomerController extends ResourceController
{
    protected string $table = 'crm_customer';
    protected string $perm = 'customer';
    protected string $title = '客户';
    protected string $path = '/customers';

    protected function fields(): array
    {
        return [
            'name'           => ['label' => '客户名称', 'required' => true, 'search' => true, 'width' => 200],
            'type'           => ['label' => '客户类型', 'type' => 'select', 'options' => Dict::CUSTOMER_TYPE, 'default' => 1, 'filter' => true],
            'level'          => ['label' => '客户等级', 'type' => 'select', 'options' => Dict::CUSTOMER_LEVEL, 'default' => 2, 'filter' => true],
            'status'         => ['label' => '客户状态', 'type' => 'select', 'options' => Dict::CUSTOMER_STATUS, 'default' => 1, 'filter' => true],
            'industry'       => ['label' => '所属行业', 'search' => true],
            'source'         => ['label' => '客户来源', 'type' => 'select', 'options' => Dict::SOURCE, 'list' => false],
            'phone'          => ['label' => '联系电话', 'search' => true],
            'email'          => ['label' => '邮箱', 'list' => false],
            'address'        => ['label' => '地址', 'list' => false],
            'owner_id'       => ['label' => '负责人', 'type' => 'user', 'filter' => true, 'tips' => '默认为当前用户'],
            'next_follow_at' => ['label' => '下次跟进', 'type' => 'date', 'sort' => true],
            'remark'         => ['label' => '备注', 'type' => 'textarea'],
        ];
    }

    protected function children(): array
    {
        return [
            ['title' => '联系人', 'path' => '/contacts', 'fk' => 'customer_id', 'perm' => 'contact.view'],
            ['title' => '跟进记录', 'path' => '/followups', 'fk' => 'customer_id', 'perm' => 'followup.view'],
            ['title' => '商机', 'path' => '/opportunities', 'fk' => 'customer_id', 'perm' => 'opportunity.view'],
            ['title' => '项目', 'path' => '/projects', 'fk' => 'customer_id', 'perm' => 'project.view'],
            ['title' => '应收款', 'path' => '/receivables', 'fk' => 'customer_id', 'perm' => 'receivable.view'],
        ];
    }

    protected function beforeSave(array $data, ?array $old): array
    {
        $dup = DB::value(
            'SELECT id FROM crm_customer WHERE name = ? AND deleted_at IS NULL AND id <> ?',
            [$data['name'], $old['id'] ?? 0]
        );
        if ($dup) {
            throw new BizException('客户名称已存在（客户查重）');
        }
        return $data;
    }

    protected function beforeDelete(array $row): void
    {
        $n = DB::value('SELECT COUNT(*) FROM proj_project WHERE customer_id = ? AND deleted_at IS NULL', [$row['id']]);
        if ($n > 0) {
            throw new BizException("客户「{$row['name']}」下存在项目，不能删除");
        }
    }
}
