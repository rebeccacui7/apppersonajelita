<!DOCTYPE html>
<html lang="zh-CN">
<head>
<?php include ROOT . '/app/Views/layout/head.php'; ?>
</head>
<body>
<div class="login-wrap">
  <div class="login-box">
    <h1><?= e(config('app.name')) ?></h1>
    <form class="layui-form" lay-filter="login" onsubmit="return false">
      <div class="layui-form-item">
        <div class="layui-input-wrap">
          <div class="layui-input-prefix"><i class="layui-icon layui-icon-username"></i></div>
          <input type="text" name="username" lay-verify="required" placeholder="用户名" class="layui-input" autocomplete="username" autofocus>
        </div>
      </div>
      <div class="layui-form-item">
        <div class="layui-input-wrap">
          <div class="layui-input-prefix"><i class="layui-icon layui-icon-password"></i></div>
          <input type="password" name="password" lay-verify="required" placeholder="密码" class="layui-input" autocomplete="current-password" lay-affix="eye">
        </div>
      </div>
      <div class="layui-form-item">
        <button class="layui-btn layui-btn-fluid" lay-submit lay-filter="do-login">登 录</button>
      </div>
    </form>
  </div>
</div>
<script>
layui.use(['form', 'layer'], function () {
  layui.form.on('submit(do-login)', function (d) {
    App.post(App.url('login'), d.field, function (res) {
      location.href = res.data.redirect;
    });
    return false;
  });
});
</script>
</body>
</html>
