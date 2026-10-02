<div class="form-page" style="padding:16px 20px 70px">
  <div style="margin-bottom:10px">角色：<b><?= e($role['name']) ?></b>
    <span class="layui-text-em" style="margin-left:8px">勾选菜单代表可见，勾选按钮代表可操作</span></div>
  <div id="perm-tree"></div>
  <div class="form-footer">
    <button type="button" class="layui-btn layui-btn-primary layui-btn-sm" id="expand-all">全部展开</button>
    <button type="button" class="layui-btn" id="save-perms">保存</button>
  </div>
</div>
<script>
layui.use(['tree', 'layer'], function () {
  var tree = layui.tree, data = <?= json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
  var render = function (spread) {
    if (spread) (function walk(ns) { ns.forEach(function (n) { n.spread = true; if (n.children) walk(n.children); }); })(data);
    tree.render({ elem: '#perm-tree', id: 'perm', data: data, showCheckbox: true, onlyIconControl: true });
  };
  render(false);
  layui.$('#expand-all').on('click', function () {
    // 重新渲染前保留当前勾选
    var checked = {};
    (function walk(ns) { ns.forEach(function (n) { checked[n.id] = true; if (n.children) walk(n.children); }); })(tree.getChecked('perm'));
    (function walk(ns) { ns.forEach(function (n) { if (!n.children) n.checked = !!checked[n.id]; else walk(n.children); }); })(data);
    render(true);
  });
  layui.$('#save-perms').on('click', function () {
    var ids = [];
    (function walk(ns) { ns.forEach(function (n) { ids.push(n.id); if (n.children) walk(n.children); }); })(tree.getChecked('perm'));
    App.post(App.url('system/roles/perms'), { id: <?= (int)$role['id'] ?>, menu_ids: ids }, function (res) {
      layui.layer.msg(res.msg, { icon: 1, time: 600 }, function () { App.closeSelf(true); });
    });
  });
});
</script>
