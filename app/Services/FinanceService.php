<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\DB;

/** 财务汇总：根据收支流水回写应收/应付的已结金额与状态 */
class FinanceService
{
    public const TYPE_INCOME = 1;
    public const TYPE_EXPENSE = 2;

    public static function recalcReceivable(?int $id): void
    {
        if ($id) {
            self::recalc('fin_receivable', 'receivable_id', 'received_amount', self::TYPE_INCOME, $id);
        }
    }

    public static function recalcPayable(?int $id): void
    {
        if ($id) {
            self::recalc('fin_payable', 'payable_id', 'paid_amount', self::TYPE_EXPENSE, $id);
        }
    }

    private static function recalc(string $table, string $fk, string $column, int $type, int $id): void
    {
        $sum = (float)DB::value(
            "SELECT COALESCE(SUM(amount), 0) FROM fin_transaction WHERE $fk = ? AND type = ? AND deleted_at IS NULL",
            [$id, $type]
        );
        $amount = (float)DB::value("SELECT amount FROM $table WHERE id = ?", [$id]);
        $status = $sum <= 0 ? 1 : ($sum + 0.001 >= $amount ? 3 : 2);
        DB::query("UPDATE $table SET $column = ?, status = ? WHERE id = ?", [round($sum, 2), $status, $id]);
    }
}
