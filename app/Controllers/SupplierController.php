<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BizException;
use App\Core\DB;
use App\Core\ResourceController;
use App\Dict;

/** 供应商管理 - 供应商档案 */
class SupplierController extends ResourceController
{
    protected string $table = 'sup_supplier';
    protected string $perm = 'supplier';
    protected string $title = '供应商';
    protected string $path = '/suppliers';
    // 供应商为公司共享资源，不按负责人做数据隔离
    protected ?string $ownerField = null;

    protected function fields(): array
    {
        return [
            'name'         => ['label' => '供应商名称', 'required' => true, 'search' => true, 'width' => 200],
            'category'     => ['label' => '供应类别', 'type' => 'select', 'options' => Dict::SUPPLIER_CATEGORY, 'filter' => true],
            'contact_name' => ['label' => '联系人', 'search' => true],
            'phone'        => ['label' => '联系电话', 'search' => true],
            'email'        => ['label' => '邮箱', 'list' => false],
            'tax_no'       => ['label' => '纳税识别号', 'list' => false],
            'bank_name'    => ['label' => '开户银行', 'list' => false],
            'bank_account' => ['label' => '银行账号', 'list' => false],
            'address'      => ['label' => '地址', 'list' => false],
            'rating'       => ['label' => '供应商评级', 'type' => 'select', 'options' => Dict::SUPPLIER_RATING, 'default' => 2, 'filter' => true],
            'status'       => ['label' => '合作状态', 'type' => 'select', 'options' => Dict::SUPPLIER_STATUS, 'default' => 1, 'filter' => true],
            'owner_id'     => ['label' => '对接人', 'type' => 'user'],
            'remark'       => ['label' => '备注', 'type' => 'textarea'],
        ];
    }

    protected function children(): array
    {
        return [
            ['title' => '采购订单', 'path' => '/purchases', 'fk' => 'supplier_id', 'perm' => 'purchase.view'],
            ['title' => '应付款', 'path' => '/payables', 'fk' => 'supplier_id', 'perm' => 'payable.view'],
        ];
    }

    protected function beforeSave(array $data, ?array $old): array
    {
        if (DB::value('SELECT id FROM sup_supplier WHERE name = ? AND deleted_at IS NULL AND id <> ?', [$data['name'], $old['id'] ?? 0])) {
            throw new BizException('供应商名称已存在');
        }
        return $data;
    }

    protected function beforeDelete(array $row): void
    {
        if (DB::value('SELECT COUNT(*) FROM sup_purchase WHERE supplier_id = ? AND deleted_at IS NULL', [$row['id']]) > 0) {
            throw new BizException("供应商「{$row['name']}」存在采购订单，不能删除，可改为“暂停合作”");
        }
    }
}
