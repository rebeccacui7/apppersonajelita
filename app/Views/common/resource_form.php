<?php
/** @var array $fields @var array $row @var int $id @var string $action */
$val = fn(string $k) => $row[$k] ?? '';
?>
<form class="layui-form form-page" lay-filter="edit-form" data-action="<?= e($action) ?>" onsubmit="return false">
  <input type="hidden" name="id" value="<?= (int)$id ?>">
  <div class="layui-row layui-col-space10">
  <?php foreach ($fields as $k => $f):
      $wide = in_array($f['type'], ['textarea', 'checkbox'], true);
      $disabled = $f['readonly'] ? 'disabled' : '';
      $req = $f['required'] ? 'lay-verify="required"' : '';
  ?>
    <div class="<?= $wide ? 'layui-col-xs12' : 'layui-col-xs12 layui-col-sm6' ?>">
      <div class="layui-form-item">
        <label class="layui-form-label<?= $f['required'] ? ' required' : '' ?>"><?= e($f['label']) ?></label>
        <div class="layui-input-block">
          <?php switch ($f['type']):
            case 'textarea': ?>
              <textarea name="<?= e($k) ?>" class="layui-textarea" <?= $req ?> <?= $disabled ?>><?= e($val($k)) ?></textarea>
              <?php break;
            case 'select':
            case 'user':
            case 'relation': ?>
              <select name="<?= e($k) ?>" lay-search <?= $req ?> <?= $disabled ?>>
                <option value="">请选择</option>
                <?php foreach ($f['options'] as $v => $t): ?>
                  <option value="<?= e($v) ?>" <?= (string)$val($k) === (string)$v ? 'selected' : '' ?>><?= e($t) ?></option>
                <?php endforeach; ?>
              </select>
              <?php break;
            case 'checkbox':
              $checked = array_map('strval', (array)($row[$k] ?? [])); ?>
              <?php foreach ($f['options'] as $v => $t): ?>
                <input type="checkbox" name="<?= e($k) ?>[<?= e($v) ?>]" title="<?= e($t) ?>" lay-skin="primary" <?= in_array((string)$v, $checked, true) ? 'checked' : '' ?> <?= $disabled ?>>
              <?php endforeach; ?>
              <?php break;
            case 'date':
            case 'datetime': ?>
              <input type="text" name="<?= e($k) ?>" value="<?= e($f['type'] === 'date' ? substr((string)$val($k), 0, 10) : $val($k)) ?>" class="layui-input" <?= $f['type'] === 'datetime' ? 'data-datetime' : 'data-date' ?> autocomplete="off" <?= $req ?> <?= $disabled ?>>
              <?php break;
            case 'password': ?>
              <input type="password" name="<?= e($k) ?>" class="layui-input" autocomplete="new-password" placeholder="<?= $id ? '不修改请留空' : '' ?>" <?= $id ? '' : $req ?>>
              <?php break;
            default: ?>
              <input type="<?= in_array($f['type'], ['number', 'money'], true) ? 'number' : 'text' ?>" <?= $f['type'] === 'money' ? 'step="0.01"' : ($f['type'] === 'number' ? 'step="any"' : '') ?>
                     name="<?= e($k) ?>" value="<?= e($val($k)) ?>" class="layui-input" autocomplete="off" <?= $req ?> <?= $disabled ?>>
          <?php endswitch; ?>
          <?php if ($f['tips']): ?><div class="layui-form-mid layui-text-em"><?= e($f['tips']) ?></div><?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
  </div>
  <div class="form-footer">
    <button class="layui-btn" lay-submit lay-filter="save">保存</button>
    <button type="button" class="layui-btn layui-btn-primary" onclick="App.closeSelf()">取消</button>
  </div>
</form>
<script>App.formPage();</script>
