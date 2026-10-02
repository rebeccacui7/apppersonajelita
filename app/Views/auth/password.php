<form class="layui-form form-page" lay-filter="edit-form" data-action="<?= e(url('profile/password')) ?>" onsubmit="return false">
  <div class="layui-form-item">
    <label class="layui-form-label required">原密码</label>
    <div class="layui-input-block"><input type="password" name="old_password" lay-verify="required" class="layui-input" autocomplete="current-password"></div>
  </div>
  <div class="layui-form-item">
    <label class="layui-form-label required">新密码</label>
    <div class="layui-input-block"><input type="password" name="new_password" lay-verify="required" class="layui-input" placeholder="至少 8 位" autocomplete="new-password"></div>
  </div>
  <div class="layui-form-item">
    <label class="layui-form-label required">确认新密码</label>
    <div class="layui-input-block"><input type="password" name="confirm_password" lay-verify="required" class="layui-input" autocomplete="new-password"></div>
  </div>
  <div class="form-footer">
    <button class="layui-btn" lay-submit lay-filter="save">保存</button>
  </div>
</form>
<script>App.formPage();</script>
