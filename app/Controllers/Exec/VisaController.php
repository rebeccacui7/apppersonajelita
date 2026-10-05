<?php
declare(strict_types=1);

namespace App\Controllers\Exec;

use App\Dict;

/** 执行管理 - 签证表（在途签证） */
class VisaController extends ActiveBizController
{
    protected string $perm = 'visa';
    protected string $title = '签证';
    protected string $path = '/exec/visas';
    protected int $bizType = Dict::BIZ_VISA;

    protected function fields(): array
    {
        return $this->bizFields('办理状态', true);
    }
}
