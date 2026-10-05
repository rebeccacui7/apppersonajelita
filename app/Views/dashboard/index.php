<?php
use App\Dict;

/** @var array $kpis @var array $charts @var array $lists */
?>
<div class="layui-row layui-col-space15">
  <?php foreach ($kpis as $k): ?>
    <div class="layui-col-xs12 layui-col-sm6 layui-col-md4 layui-col-lg2">
      <div class="kpi">
        <div class="kpi-label"><?= e($k['label']) ?></div>
        <div class="kpi-value"><?= e($k['value']) ?></div>
        <div class="kpi-sub"<?= !empty($k['warn']) ? ' style="color:#ff5722"' : '' ?>><?= e($k['sub']) ?></div>
      </div>
    </div>
  <?php endforeach; ?>
  <?php if (!$kpis): ?>
    <div class="layui-col-xs12"><div class="kpi">欢迎使用 <?= e(config('app.name')) ?>。当前账号暂未分配业务模块权限，请联系管理员。</div></div>
  <?php endif; ?>
</div>

<div class="layui-row layui-col-space15" style="margin-top:0">
  <?php if (isset($charts['trend'])): ?>
    <div class="layui-col-xs12 layui-col-md8">
      <div class="layui-card"><div class="layui-card-header">近 12 个月收支趋势</div>
        <div class="layui-card-body"><div id="chart-trend" class="chart"></div></div></div>
    </div>
  <?php endif; ?>
  <?php if (isset($charts['funnel'])): ?>
    <div class="layui-col-xs12 layui-col-md4">
      <div class="layui-card"><div class="layui-card-header">商机漏斗</div>
        <div class="layui-card-body"><div id="chart-funnel" class="chart"></div></div></div>
    </div>
  <?php endif; ?>
  <?php if (isset($charts['biz'])): ?>
    <div class="layui-col-xs12 layui-col-md4">
      <div class="layui-card"><div class="layui-card-header">执行业务概况</div>
        <div class="layui-card-body"><div id="chart-biz" class="chart"></div></div></div>
    </div>
  <?php endif; ?>

  <?php if (isset($lists['bizDue'])): ?>
    <div class="layui-col-xs12 layui-col-md4">
      <div class="layui-card"><div class="layui-card-header">7 天内到期的在途业务</div>
        <div class="layui-card-body">
          <ul class="dash-list">
            <?php foreach ($lists['bizDue'] as $b):
                $late = $b['end_date'] < date('Y-m-d'); ?>
              <li><span><?= e($b['group_name']) ?> <span class="muted">· <?= e(Dict::BIZ_TYPE[$b['biz_type']] ?? '') ?> <?= e($b['business']) ?></span></span>
                <span class="muted"<?= $late ? ' style="color:#ff5722"' : '' ?>><?= e($b['end_date']) ?></span></li>
            <?php endforeach; ?>
            <?php if (!$lists['bizDue']): ?><li class="muted">暂无</li><?php endif; ?>
          </ul>
        </div></div>
    </div>
  <?php endif; ?>

  <?php if (isset($lists['follow'])): ?>
    <div class="layui-col-xs12 layui-col-md4">
      <div class="layui-card"><div class="layui-card-header">近 7 天需跟进客户</div>
        <div class="layui-card-body">
          <ul class="dash-list">
            <?php foreach ($lists['follow'] as $c): ?>
              <li><span><?= e($c['name']) ?></span>
                <span class="muted"<?= $c['next_follow_at'] < date('Y-m-d') ? ' style="color:#ff5722"' : '' ?>><?= e($c['next_follow_at']) ?></span></li>
            <?php endforeach; ?>
            <?php if (!$lists['follow']): ?><li class="muted">暂无</li><?php endif; ?>
          </ul>
        </div></div>
    </div>
  <?php endif; ?>

  <?php if (isset($lists['receivables'])): ?>
    <div class="layui-col-xs12 layui-col-md4">
      <div class="layui-card"><div class="layui-card-header">30 天内到期应收</div>
        <div class="layui-card-body">
          <ul class="dash-list">
            <?php foreach ($lists['receivables'] as $r): ?>
              <li><span><?= e($r['customer']) ?> · <?= e($r['title']) ?></span>
                <span class="muted"<?= $r['due_date'] < date('Y-m-d') ? ' style="color:#ff5722"' : '' ?>>¥<?= money($r['remain']) ?> · <?= e($r['due_date']) ?></span></li>
            <?php endforeach; ?>
            <?php if (!$lists['receivables']): ?><li class="muted">暂无</li><?php endif; ?>
          </ul>
        </div></div>
    </div>
  <?php endif; ?>
</div>

<?php if ($charts): ?>
<script src="https://cdn.jsdelivr.net/npm/echarts@5.5.1/dist/echarts.min.js"></script>
<script>
(function () {
  var data = <?= json_encode($charts, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
  var charts = [];
  var init = function (id, option) {
    var el = document.getElementById(id);
    if (!el) return;
    var c = echarts.init(el);
    c.setOption(option);
    charts.push(c);
  };
  var green = '#16b777', orange = '#ff9f43';

  if (data.trend) {
    init('chart-trend', {
      tooltip: { trigger: 'axis', valueFormatter: function (v) { return '¥' + App.fmtMoney(v); } },
      legend: { data: ['收入', '支出'] },
      grid: { left: 60, right: 20, top: 40, bottom: 30 },
      xAxis: { type: 'category', data: data.trend.months },
      yAxis: { type: 'value' },
      series: [
        { name: '收入', type: 'bar', data: data.trend.income, itemStyle: { color: green }, barMaxWidth: 18 },
        { name: '支出', type: 'bar', data: data.trend.expense, itemStyle: { color: orange }, barMaxWidth: 18 }
      ]
    });
  }
  if (data.funnel) {
    init('chart-funnel', {
      tooltip: { formatter: function (p) { return p.name + '<br>' + p.value + ' 个 · ¥' + App.fmtMoney(p.data.amount); } },
      series: [{ type: 'funnel', sort: 'none', left: '10%', width: '80%', minSize: '20%', gap: 2,
        label: { position: 'inside', formatter: '{b} {c}' }, data: data.funnel }]
    });
  }
  if (data.biz) {
    init('chart-biz', {
      tooltip: { trigger: 'item' },
      legend: { bottom: 0 },
      color: ['#1e9fff', '#16b777', '#ff9f43', '#a0a7b4'],
      series: [{ type: 'pie', radius: ['40%', '68%'], center: ['50%', '45%'], label: { formatter: '{b}\n{c}' }, data: data.biz }]
    });
  }
  window.addEventListener('resize', function () { charts.forEach(function (c) { c.resize(); }); });
})();
</script>
<?php endif; ?>
