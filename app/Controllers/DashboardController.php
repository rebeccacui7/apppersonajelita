<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\DB;
use App\Dict;

/** 仪表盘：各模块数据按当前用户的权限与数据范围展示 */
class DashboardController extends Controller
{
    public function index(): void
    {
        $kpis = [];
        $charts = [];
        $lists = [];
        $monthStart = date('Y-m-01');

        if (Auth::can('customer.view')) {
            [$w, $p] = $this->scope('owner_id');
            $kpis[] = [
                'label' => '客户总数',
                'value' => (int)DB::value("SELECT COUNT(*) FROM crm_customer WHERE deleted_at IS NULL $w", $p),
                'sub'   => '本月新增 ' . (int)DB::value("SELECT COUNT(*) FROM crm_customer WHERE deleted_at IS NULL AND created_at >= ? $w", array_merge([$monthStart], $p)),
            ];
            $lists['follow'] = DB::all(
                "SELECT id, name, next_follow_at FROM crm_customer
                 WHERE deleted_at IS NULL AND next_follow_at IS NOT NULL AND next_follow_at <= ? $w
                 ORDER BY next_follow_at LIMIT 8",
                array_merge([date('Y-m-d', strtotime('+7 days'))], $p)
            );
        }

        if (Auth::can('opportunity.view')) {
            [$w, $p] = $this->scope('owner_id');
            $open = DB::row(
                "SELECT COUNT(*) n, COALESCE(SUM(amount),0) amt, COALESCE(SUM(amount * probability / 100),0) weighted
                 FROM crm_opportunity WHERE deleted_at IS NULL AND stage < ? $w",
                array_merge([Dict::OPP_WON], $p)
            );
            $kpis[] = [
                'label' => '进行中商机金额',
                'value' => '¥' . money($open['amt']),
                'sub'   => $open['n'] . ' 个商机 · 加权 ¥' . money($open['weighted']),
            ];
            $rows = DB::all(
                "SELECT stage, COUNT(*) n, COALESCE(SUM(amount),0) amt FROM crm_opportunity
                 WHERE deleted_at IS NULL AND stage <= ? $w GROUP BY stage",
                array_merge([Dict::OPP_WON], $p)
            );
            $byStage = array_column($rows, null, 'stage');
            $funnel = [];
            foreach (Dict::OPP_STAGE as $k => $name) {
                if ($k <= Dict::OPP_WON) {
                    $funnel[] = ['name' => $name, 'value' => (int)($byStage[$k]['n'] ?? 0), 'amount' => (float)($byStage[$k]['amt'] ?? 0)];
                }
            }
            $charts['funnel'] = $funnel;
        }

        // 执行业务：签证 / 公司注册（按权限只统计可见的业务类型）
        $types = [];
        if (Auth::can('visa.view')) {
            $types[Dict::BIZ_VISA] = '签证';
        }
        if (Auth::can('company.view')) {
            $types[Dict::BIZ_COMPANY] = '公司注册';
        }
        if ($types) {
            $in = implode(',', array_map('intval', array_keys($types)));
            $active = array_column(DB::all(
                "SELECT biz_type, COUNT(*) n FROM exec_business WHERE deleted_at IS NULL AND stage = ? AND biz_type IN ($in) GROUP BY biz_type",
                [Dict::STAGE_ACTIVE]
            ), 'n', 'biz_type');
            foreach ($types as $t => $name) {
                $overdue = (int)DB::value(
                    'SELECT COUNT(*) FROM exec_business WHERE deleted_at IS NULL AND stage = ? AND biz_type = ? AND end_date < CURDATE()',
                    [Dict::STAGE_ACTIVE, $t]
                );
                $kpis[] = ['label' => "在途{$name}", 'value' => (int)($active[$t] ?? 0), 'sub' => "已超结束日期 {$overdue} 件", 'warn' => $overdue > 0];
            }
            $lists['bizDue'] = DB::all(
                "SELECT id, biz_type, group_name, business, end_date FROM exec_business
                 WHERE deleted_at IS NULL AND stage = ? AND biz_type IN ($in) AND end_date IS NOT NULL AND end_date <= ?
                 ORDER BY end_date LIMIT 8",
                [Dict::STAGE_ACTIVE, date('Y-m-d', strtotime('+7 days'))]
            );
        }
        if (Auth::can('bizpay.view')) {
            $p = DB::row(
                'SELECT COUNT(*) n, COALESCE(SUM(pay_amount), 0) amt FROM exec_business WHERE deleted_at IS NULL AND stage = ?',
                [Dict::STAGE_PAYABLE]
            );
            $kpis[] = ['label' => '待付供应商款', 'value' => '¥' . money($p['amt']), 'sub' => (int)$p['n'] . ' 笔待付款'];
        }
        if ($types || Auth::can('bizpay.view') || Auth::can('bizdone.view')) {
            $c = DB::row(
                "SELECT SUM(stage = 1 AND biz_type = 1) visa, SUM(stage = 1 AND biz_type = 2) company,
                        SUM(stage = 2) payable, SUM(stage = 3 AND paid_date >= ?) done
                 FROM exec_business WHERE deleted_at IS NULL",
                [$monthStart]
            );
            $charts['biz'] = [
                ['name' => '在途签证', 'value' => (int)$c['visa']],
                ['name' => '在途公司注册', 'value' => (int)$c['company']],
                ['name' => '待付供应商', 'value' => (int)$c['payable']],
                ['name' => '本月完成', 'value' => (int)$c['done']],
            ];
        }

        if (Auth::can('receivable.view')) {
            [$w, $p] = $this->scope('owner_id');
            $r = DB::row(
                "SELECT COALESCE(SUM(amount - received_amount),0) amt,
                        SUM(due_date < CURDATE()) overdue
                 FROM fin_receivable WHERE deleted_at IS NULL AND status < 3 $w",
                $p
            );
            $kpis[] = ['label' => '应收未收', 'value' => '¥' . money($r['amt']), 'sub' => '逾期 ' . (int)$r['overdue'] . ' 笔', 'warn' => (int)$r['overdue'] > 0];
            [$w, $p] = $this->scope('r.owner_id');
            $lists['receivables'] = DB::all(
                "SELECT r.id, r.title, r.due_date, r.amount - r.received_amount AS remain, c.name customer
                 FROM fin_receivable r LEFT JOIN crm_customer c ON c.id = r.customer_id
                 WHERE r.deleted_at IS NULL AND r.status < 3 AND r.due_date <= ? $w
                 ORDER BY r.due_date LIMIT 8",
                array_merge([date('Y-m-d', strtotime('+30 days'))], $p)
            );
        }

        if (Auth::can('transaction.view')) {
            [$w, $p] = $this->scope('handler_id');
            $m = DB::row(
                "SELECT COALESCE(SUM(IF(type=1, amount, 0)),0) income, COALESCE(SUM(IF(type=2, amount, 0)),0) expense
                 FROM fin_transaction WHERE deleted_at IS NULL AND trade_date >= ? $w",
                array_merge([$monthStart], $p)
            );
            $kpis[] = ['label' => '本月收入', 'value' => '¥' . money($m['income']), 'sub' => '本月支出 ¥' . money($m['expense'])];

            $from = date('Y-m-01', strtotime('-11 months', strtotime($monthStart)));
            $rows = DB::all(
                "SELECT DATE_FORMAT(trade_date, '%Y-%m') ym, type, SUM(amount) amt FROM fin_transaction
                 WHERE deleted_at IS NULL AND trade_date >= ? $w GROUP BY ym, type",
                array_merge([$from], $p)
            );
            $months = [];
            for ($i = 0; $i < 12; $i++) {
                $months[date('Y-m', strtotime("+$i months", strtotime($from)))] = [0, 0];
            }
            foreach ($rows as $r) {
                if (isset($months[$r['ym']])) {
                    $months[$r['ym']][(int)$r['type'] - 1] = round((float)$r['amt'], 2);
                }
            }
            $charts['trend'] = [
                'months'  => array_keys($months),
                'income'  => array_column(array_values($months), 0),
                'expense' => array_column(array_values($months), 1),
            ];
        }

        if (Auth::can('expense.approve')) {
            $kpis[] = [
                'label' => '待审批报销',
                'value' => (int)DB::value('SELECT COUNT(*) FROM fin_expense WHERE deleted_at IS NULL AND status = 1'),
                'sub'   => '金额 ¥' . money(DB::value('SELECT COALESCE(SUM(amount),0) FROM fin_expense WHERE deleted_at IS NULL AND status = 1')),
            ];
        }

        $this->render('dashboard/index', ['title' => '仪表盘', 'kpis' => $kpis, 'charts' => $charts, 'lists' => $lists]);
    }

    /** 返回 " AND 条件" 形式的数据范围过滤 */
    private function scope(string $column): array
    {
        [$sql, $params] = Auth::scopeSql($column);
        return [$sql === '' ? '' : " AND $sql", $params];
    }
}
