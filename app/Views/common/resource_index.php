<?php /** @var array $page */ ?>
<div class="layui-card">
  <?php if (empty($page['fixed'])): ?>
  <div class="layui-card-header"><?= e($page['title']) ?></div>
  <?php endif; ?>
  <div class="layui-card-body">
    <form class="layui-form search-bar" lay-filter="search" onsubmit="return false">
      <?php if ($page['keyword']): ?>
        <div class="layui-inline">
          <input type="text" name="keyword" placeholder="关键字搜索" class="layui-input" autocomplete="off">
        </div>
      <?php endif; ?>
      <?php foreach ($page['filters'] as $f): ?>
        <div class="layui-inline">
          <select name="f_<?= e($f['field']) ?>" lay-search>
            <option value=""><?= e($f['label']) ?>（全部）</option>
            <?php foreach ($f['options'] as $v => $t): ?>
              <option value="<?= e($v) ?>"><?= e($t) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endforeach; ?>
      <?php if ($page['dateField']): ?>
        <div class="layui-inline">
          <input type="text" name="date_from" class="layui-input" data-date placeholder="<?= e($page['dateField']) ?>起" autocomplete="off">
        </div>
        <div class="layui-inline">
          <input type="text" name="date_to" class="layui-input" data-date placeholder="<?= e($page['dateField']) ?>止" autocomplete="off">
        </div>
      <?php endif; ?>
      <div class="layui-inline">
        <button class="layui-btn" lay-submit lay-filter="do-search"><i class="layui-icon layui-icon-search"></i> 搜索</button>
        <button type="reset" class="layui-btn layui-btn-primary">重置</button>
      </div>
    </form>

    <table id="tbl" lay-filter="tbl"></table>
  </div>
</div>

<script>
  App.resourcePage(<?= json_encode($page, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
</script>
