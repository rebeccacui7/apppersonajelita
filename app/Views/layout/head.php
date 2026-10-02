<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<meta name="base-url" content="<?= e(rtrim(url('/'), '/')) ?>">
<title><?= e(($title ?? '') ? $title . ' - ' : '') ?><?= e(config('app.name')) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/layui@2.9.18/dist/css/layui.css">
<link rel="stylesheet" href="<?= asset('app.css') ?>">
<script src="https://cdn.jsdelivr.net/npm/layui@2.9.18/dist/layui.js"></script>
<script src="<?= asset('app.js') ?>"></script>
