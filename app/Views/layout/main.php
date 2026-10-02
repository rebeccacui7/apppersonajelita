<?php
use App\Core\Auth;

$user = Auth::user();
$menus = Auth::menus();
$current = '/' . trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/');
$isActive = function (array $m) use ($current): bool {
    $p = rtrim(url($m['path'] ?: '#'), '/') ?: '/';
    return $current === $p || ($p !== '/' && $p !== rtrim(url('/'), '/') && str_starts_with($current, $p . '/'));
};
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<?php include __DIR__ . '/head.php'; ?>
</head>
<body>
<div class="layui-layout layui-layout-admin">
  <div class="layui-header">
    <div class="layui-logo"><i class="layui-icon layui-icon-component"></i> <?= e(config('app.name')) ?></div>
    <ul class="layui-nav layui-layout-left">
      <li class="layui-nav-item layui-hide-xs" lay-unselect>
        <a href="javascript:;" id="side-toggle" title="收起/展开"><i class="layui-icon layui-icon-shrink-right"></i></a>
      </li>
    </ul>
    <ul class="layui-nav layui-layout-right">
      <li class="layui-nav-item" lay-unselect>
        <a href="javascript:;"><i class="layui-icon layui-icon-username"></i> <?= e($user['realname'] ?? '') ?></a>
        <dl class="layui-nav-child">
          <dd><a href="javascript:;" data-open="<?= e(url('profile/password')) ?>" data-title="修改密码" data-area="460px,360px">修改密码</a></dd>
          <dd><a href="javascript:;" id="logout-btn" data-url="<?= e(url('logout')) ?>">退出登录</a></dd>
        </dl>
      </li>
    </ul>
  </div>

  <div class="layui-side layui-bg-black">
    <div class="layui-side-scroll">
      <ul class="layui-nav layui-nav-tree" lay-shrink="all">
        <?php foreach ($menus as $m): ?>
          <?php if ((int)$m['type'] === 1):
              $open = (bool)array_filter($m['children'], $isActive); ?>
            <li class="layui-nav-item<?= $open ? ' layui-nav-itemed' : '' ?>">
              <a href="javascript:;"><i class="layui-icon <?= e($m['icon']) ?>"></i> <span><?= e($m['name']) ?></span></a>
              <dl class="layui-nav-child">
                <?php foreach ($m['children'] as $c): ?>
                  <dd class="<?= $isActive($c) ? 'layui-this' : '' ?>"><a href="<?= e(url($c['path'])) ?>"><?= e($c['name']) ?></a></dd>
                <?php endforeach; ?>
              </dl>
            </li>
          <?php else: ?>
            <li class="layui-nav-item<?= $isActive($m) ? ' layui-this' : '' ?>">
              <a href="<?= e(url($m['path'])) ?>"><i class="layui-icon <?= e($m['icon']) ?>"></i> <span><?= e($m['name']) ?></span></a>
            </li>
          <?php endif; ?>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>

  <div class="layui-body">
    <div class="page-wrap">
      <?= $content ?>
    </div>
  </div>
</div>
</body>
</html>
