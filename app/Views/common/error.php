<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($code) ?> - <?= e(config('app.name')) ?></title>
<style>
  body{margin:0;font-family:-apple-system,"PingFang SC","Microsoft YaHei",sans-serif;background:#f5f7fa;color:#333;display:flex;align-items:center;justify-content:center;min-height:100vh}
  .box{text-align:center;padding:24px}
  .code{font-size:64px;font-weight:600;color:#16b777;margin:0}
  .msg{font-size:16px;margin:12px 0 24px}
  a{color:#16b777;text-decoration:none}
</style>
</head>
<body>
<div class="box">
  <p class="code"><?= e($code) ?></p>
  <p class="msg"><?= e($msg) ?></p>
  <a href="<?= e(url('/')) ?>">返回首页</a>
</div>
</body>
</html>
