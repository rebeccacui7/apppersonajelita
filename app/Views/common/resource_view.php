<?php /** @var array $row @var array $fields @var array $children */ ?>
<div class="view-page">
  <div class="view-head">
    <h2><?= e($heading) ?></h2>
    <?php if ($editUrl): ?>
      <button class="layui-btn layui-btn-sm" data-open="<?= e($editUrl) ?>" data-title="编辑" data-reload="1">编辑</button>
    <?php endif; ?>
  </div>
  <table class="layui-table detail-table">
    <colgroup><col width="120"><col><col width="120"><col></colgroup>
    <tbody>
    <?php
    // $cells: [标签, 已转义的 HTML, 是否整行]
    $cells = [];
    foreach ($fields as $k => $f) {
        if ($f['type'] === 'checkbox') {
            continue;
        }
        if ($f['type'] === 'file') {
            $links = array_map(
                fn($fi) => '<a class="link" target="_blank" href="' . e(url('files/download', ['key' => $fi['key']])) . '">' . e($fi['name']) . '</a>',
                $row[$k . '__files'] ?? []
            );
            $cells[] = [$f['label'], $links ? implode('<br>', $links) : '', true];
            continue;
        }
        $v = $row[$k . '__text'] ?? $row[$k] ?? '';
        if ($f['type'] === 'money' && $v !== '') {
            $v = money($v);
        }
        $cells[] = [$f['label'], e($v), $f['type'] === 'textarea'];
    }
    $cells[] = ['创建人', e($row['created_by__text'] ?? ''), false];
    $cells[] = ['创建时间', e($row['created_at'] ?? ''), false];
    $buf = [];
    foreach ($cells as [$label, $html, $wide]) {
        if ($wide) {
            if ($buf) { echo '<tr><th>' . e($buf[0]) . '</th><td colspan="3">' . $buf[1] . '</td></tr>'; $buf = []; }
            echo '<tr><th>' . e($label) . '</th><td colspan="3" class="pre">' . $html . '</td></tr>';
        } elseif ($buf) {
            echo '<tr><th>' . e($buf[0]) . '</th><td>' . $buf[1] . '</td><th>' . e($label) . '</th><td>' . $html . '</td></tr>';
            $buf = [];
        } else {
            $buf = [$label, $html];
        }
    }
    if ($buf) echo '<tr><th>' . e($buf[0]) . '</th><td colspan="3">' . $buf[1] . '</td></tr>';
    ?>
    </tbody>
  </table>

  <?php if ($children): ?>
  <div class="layui-tab layui-tab-brief">
    <ul class="layui-tab-title">
      <?php foreach ($children as $i => $c): ?>
        <li class="<?= $i === 0 ? 'layui-this' : '' ?>"><?= e($c['title']) ?></li>
      <?php endforeach; ?>
    </ul>
    <div class="layui-tab-content">
      <?php foreach ($children as $i => $c): ?>
        <div class="layui-tab-item<?= $i === 0 ? ' layui-show' : '' ?>">
          <iframe class="child-frame" data-src="<?= e($c['url']) ?>" <?= $i === 0 ? 'src="' . e($c['url']) . '"' : '' ?>></iframe>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
<script>App.viewPage();</script>
