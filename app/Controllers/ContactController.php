<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\DB;
use App\Core\ResourceController;
use App\Dict;

/** 客户管理 - 联系人（数据范围跟随所属客户） */
class ContactController extends ResourceController
{
    protected string $table = 'crm_contact';
    protected string $perm = 'contact';
    protected string $title = '联系人';
    protected string $path = '/contacts';
    protected ?string $ownerField = null;

    protected function fields(): array
    {
        return [
            'name'        => ['label' => '姓名', 'required' => true, 'search' => true],
            'customer_id' => ['label' => '所属客户', 'type' => 'relation', 'table' => 'crm_customer', 'display' => 'name', 'required' => true, 'filter' => true, 'width' => 200],
            'position'    => ['label' => '职务'],
            'mobile'      => ['label' => '手机', 'search' => true],
            'email'       => ['label' => '邮箱'],
            'wechat'      => ['label' => '微信'],
            'is_primary'  => ['label' => '主联系人', 'type' => 'select', 'options' => Dict::YES_NO, 'default' => 0],
            'remark'      => ['label' => '备注', 'type' => 'textarea'],
        ];
    }

    protected function scopeWhere(): array
    {
        [$sql, $params] = Auth::scopeSql('c.owner_id');
        if ($sql === '') {
            return ['', []];
        }
        return ["t.customer_id IN (SELECT c.id FROM crm_customer c WHERE $sql)", $params];
    }

    protected function afterSave(int $id, array $data, ?array $old): void
    {
        // 每个客户只保留一个主联系人
        if ((int)($data['is_primary'] ?? 0) === 1) {
            DB::query('UPDATE crm_contact SET is_primary = 0 WHERE customer_id = ? AND id <> ?', [$data['customer_id'], $id]);
        }
    }
}
