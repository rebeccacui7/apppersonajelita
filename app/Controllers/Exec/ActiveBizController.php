<?php
declare(strict_types=1);

namespace App\Controllers\Exec;

use App\Core\BizException;
use App\Core\ResourceController;
use App\Dict;

/**
 * 执行管理 - 在途业务基类（签证表 / 公司注册表）
 *
 * 签证、公司注册、供应商应付、完成项目 四张表共用 exec_business，
 * 用 biz_type（业务类型）+ stage（阶段）区分：
 *   在途(1) --办理状态=已完成--> 供应商应付(2) --付款状态=已付款--> 完成项目(3)
 * 数据只改变阶段、不复制，交付文件与历史信息全程保留。
 */
abstract class ActiveBizController extends ResourceController
{
    protected string $table = 'exec_business';
    protected ?string $ownerField = null;
    protected string $nameField = 'group_name';
    protected ?string $dateField = 'start_date';
    protected int $bizType = 0;

    protected function baseWhere(): array
    {
        return ['t.biz_type = ? AND t.stage = ?', [$this->bizType, Dict::STAGE_ACTIVE]];
    }

    /** 按截图字段顺序生成字段定义 */
    protected function bizFields(string $statusLabel, bool $withApplicant): array
    {
        $fields = [
            'group_name'   => ['label' => '业务群名', 'required' => true, 'search' => true, 'width' => 180],
            'contact_name' => ['label' => '对接人', 'search' => true],
            'business'     => ['label' => '具体业务', 'search' => true, 'width' => 180],
        ];
        if ($withApplicant) {
            $fields['applicant'] = ['label' => '申请对象', 'search' => true];
        }
        return $fields + [
            'start_date'  => ['label' => '开始日期', 'type' => 'date', 'default' => date('Y-m-d'), 'sort' => true],
            'supplier_id' => ['label' => '供应商', 'type' => 'relation', 'table' => 'sup_supplier', 'display' => 'name', 'filter' => true, 'width' => 160],
            'end_date'    => ['label' => '结束日期', 'type' => 'date', 'sort' => true],
            'files'       => ['label' => '交付文件', 'type' => 'file', 'width' => 200],
            'status'      => ['label' => $statusLabel, 'type' => 'select', 'options' => Dict::BIZ_STATUS, 'default' => 1,
                              'required' => true, 'filter' => true, 'width' => 100,
                              'tips' => '选择「已完成」并保存后，自动转入「供应商应付」'],
        ];
    }

    protected function beforeSave(array $data, ?array $old): array
    {
        if (!$old) {
            $data['biz_type'] = $this->bizType;
            $data['stage'] = Dict::STAGE_ACTIVE;
        }
        if ($data['start_date'] && $data['end_date'] && $data['end_date'] < $data['start_date']) {
            throw new BizException('结束日期不能早于开始日期');
        }
        if ((int)$data['status'] === Dict::BIZ_STATUS_DONE) {
            $data['stage'] = Dict::STAGE_PAYABLE;
            $data['completed_at'] = date('Y-m-d H:i:s');
            $data['pay_status'] = Dict::BIZ_UNPAID;
            $data['paid_date'] = null;
            $this->saveMessage = '业务已完成，已转入「供应商应付」';
        }
        return $data;
    }
}
