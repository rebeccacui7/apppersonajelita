<?php
declare(strict_types=1);

namespace App\Controllers\Exec;

use App\Dict;

/** 执行管理 - 公司注册表（在途公司注册） */
class CompanyRegController extends ActiveBizController
{
    protected string $perm = 'company';
    protected string $title = '公司注册';
    protected string $path = '/exec/companies';
    protected int $bizType = Dict::BIZ_COMPANY;

    protected function fields(): array
    {
        return $this->bizFields('业务状态', false);
    }
}
