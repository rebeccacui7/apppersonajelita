/**
 * 前端公共脚本（基于 Layui 2.9）
 * - App.resourcePage(cfg)  通用列表页
 * - App.formPage()         通用表单页（弹层 iframe 内）
 * - App.viewPage()         通用详情页
 */
(function (win) {
  'use strict';

  var meta = function (name) {
    var el = document.querySelector('meta[name="' + name + '"]');
    return el ? el.getAttribute('content') : '';
  };

  var esc = function (s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  };

  var fmtMoney = function (v) {
    if (v === null || v === undefined || v === '') return '';
    return Number(v).toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  };

  var App = {
    base: meta('base-url'),
    esc: esc,
    fmtMoney: fmtMoney,
    _dirty: false,

    url: function (path) {
      return App.base + '/' + String(path).replace(/^\//, '');
    },

    /** POST 请求，code=0 时回调 ok */
    post: function (url, data, ok, fail) {
      var $ = layui.$;
      var loading = layui.layer.load(2);
      $.ajax({ url: url, type: 'POST', data: data || {}, dataType: 'json' })
        .done(function (res) {
          if (res.code === 0) {
            ok && ok(res);
          } else if (res.code === 401) {
            (win.top || win).location.href = App.url('login');
          } else {
            layui.layer.msg(res.msg || '操作失败', { icon: 2 });
            fail && fail(res);
          }
        })
        .fail(function () { layui.layer.msg('网络错误，请重试', { icon: 2 }); })
        .always(function () { layui.layer.close(loading); });
    },

    /** 弹出 iframe 层；子页面设置 parent.App._dirty=true 后关闭会触发 onDirty */
    open: function (url, title, area, onDirty) {
      var mobile = win.innerWidth < 768;
      App._dirty = false;
      layui.layer.open({
        type: 2,
        title: title || '',
        content: url,
        maxmin: true,
        area: mobile ? ['100%', '100%'] : (area || ['760px', '86%']),
        end: function () {
          if (App._dirty) {
            App._dirty = false;
            onDirty && onDirty();
          }
        }
      });
    },

    /** 关闭自身所在弹层 */
    closeSelf: function (dirty) {
      if (win.parent && win.parent !== win && win.parent.layui) {
        if (dirty && win.parent.App) win.parent.App._dirty = true;
        win.parent.layui.layer.close(win.parent.layui.layer.getFrameIndex(win.name));
      }
    },

    renderDates: function (scope) {
      layui.use('laydate', function () {
        var nodes = (scope || document).querySelectorAll('[data-date],[data-datetime]');
        Array.prototype.forEach.call(nodes, function (el) {
          if (el.disabled) return;
          layui.laydate.render({ elem: el, type: el.hasAttribute('data-datetime') ? 'datetime' : 'date' });
        });
      });
    },

    /* ------------------------------------------------------------ 通用列表页 */
    resourcePage: function (cfg) {
      layui.use(['table', 'form', 'layer'], function () {
        var table = layui.table, form = layui.form, layer = layui.layer, $ = layui.$;

        var fixedWhere = {};
        Object.keys(cfg.fixed || {}).forEach(function (k) { fixedWhere['f_' + k] = cfg.fixed[k]; });
        var where = $.extend({}, fixedWhere);
        var area = [cfg.formWidth || '760px', '86%'];

        var hasMoney = false;
        var cols = [{ type: 'checkbox', fixed: 'left' }];
        cfg.cols.forEach(function (c, i) {
          var col = { field: c.field, title: c.title, minWidth: 110, sort: !!c.sort };
          if (c.width) col.width = c.width;
          if (['select', 'user', 'relation'].indexOf(c.type) >= 0) {
            col.templet = function (d) { return esc(d[c.field + '__text']); };
          } else if (c.type === 'money') {
            col.align = 'right';
            col.totalRow = true;
            hasMoney = true;
            col.templet = function (d) { return fmtMoney(d[c.field]); };
          } else if (c.type === 'date') {
            col.templet = function (d) { return esc(String(d[c.field] || '').substr(0, 10)); };
          } else if (c.type === 'file') {
            col.templet = function (d) { return App.fileLinks(d[c.field + '__files']); };
          } else {
            col.templet = function (d) { return esc(d[c.field]); };
          }
          if (i === 0) {
            col.totalRowText = '合计';
            col.templet = (function (inner) {
              return function (d) { return '<a class="link" lay-event="view">' + (inner(d) || '#' + d.id) + '</a>'; };
            })(col.templet);
          }
          cols.push(col);
        });

        var actions = cfg.actions || [];
        var visible = function (a, d) {
          if (!a.when) return true;
          return Object.keys(a.when).every(function (f) {
            return a.when[f].map(String).indexOf(String(d[f])) >= 0;
          });
        };
        cols.push({
          title: '操作', fixed: 'right', minWidth: 150 + actions.length * 70,
          templet: function (d) {
            var h = '<a class="layui-btn layui-btn-xs layui-btn-primary" lay-event="view">详情</a>';
            if (cfg.perms.edit) h += '<a class="layui-btn layui-btn-xs" lay-event="edit">编辑</a>';
            actions.forEach(function (a, i) {
              if (visible(a, d)) {
                h += '<a class="layui-btn layui-btn-xs ' + (a.class || 'layui-btn-normal') + '" lay-event="act' + i + '">' + esc(a.text) + '</a>';
              }
            });
            if (cfg.perms.delete) h += '<a class="layui-btn layui-btn-xs layui-btn-danger" lay-event="del">删除</a>';
            return h;
          }
        });

        var bar = '<div>';
        if (cfg.perms.create) bar += '<button class="layui-btn layui-btn-sm" lay-event="add"><i class="layui-icon layui-icon-add-1"></i> 新增</button>';
        if (cfg.perms.delete) bar += '<button class="layui-btn layui-btn-sm layui-btn-danger" lay-event="batchDel"><i class="layui-icon layui-icon-delete"></i> 批量删除</button>';
        bar += '<button class="layui-btn layui-btn-sm layui-btn-primary" lay-event="export"><i class="layui-icon layui-icon-export"></i> 导出</button></div>';

        table.render({
          elem: '#tbl', id: 'tbl',
          url: cfg.base + '/list',
          where: where,
          toolbar: bar,
          defaultToolbar: ['filter'],
          page: true, limit: 20, limits: [20, 50, 100, 200],
          autoSort: false,
          totalRow: hasMoney,
          cols: [cols],
          text: { none: '暂无数据' }
        });

        var reload = function (resetPage) {
          table.reload('tbl', { where: where, page: resetPage ? { curr: 1 } : undefined });
        };

        form.render(null, 'search');
        App.renderDates(document.querySelector('.search-bar'));
        form.on('submit(do-search)', function (d) {
          where = $.extend({}, fixedWhere, d.field, { sort: where.sort, order: where.order });
          reload(true);
          return false;
        });
        $('.search-bar button[type=reset]').on('click', function () {
          setTimeout(function () { form.render(null, 'search'); where = $.extend({}, fixedWhere); reload(true); }, 0);
        });

        table.on('sort(tbl)', function (obj) {
          where.sort = obj.type ? obj.field : '';
          where.order = obj.type || '';
          reload(true);
        });

        var formUrl = function (id) {
          return cfg.base + '/form?' + $.param(id ? { id: id } : fixedWhere);
        };

        var del = function (ids) {
          layer.confirm('确定删除选中的 ' + ids.length + ' 条记录？', { icon: 3 }, function (i) {
            layer.close(i);
            App.post(cfg.base + '/delete', { ids: ids }, function (res) {
              layer.msg(res.msg, { icon: 1 });
              reload();
            });
          });
        };

        table.on('toolbar(tbl)', function (obj) {
          if (obj.event === 'add') {
            App.open(formUrl(0), '新增' + cfg.title, area, reload);
          } else if (obj.event === 'batchDel') {
            var ids = table.checkStatus('tbl').data.map(function (r) { return r.id; });
            if (!ids.length) return layer.msg('请先勾选记录');
            del(ids);
          } else if (obj.event === 'export') {
            win.location.href = cfg.base + '/export?' + $.param(where);
          }
        });

        table.on('tool(tbl)', function (obj) {
          var d = obj.data, ev = obj.event;
          if (ev === 'view') {
            App.open(cfg.base + '/view?id=' + d.id, cfg.title + '详情', ['1000px', '90%'], reload);
          } else if (ev === 'edit') {
            App.open(formUrl(d.id), '编辑' + cfg.title, area, reload);
          } else if (ev === 'del') {
            del([d.id]);
          } else if (ev.indexOf('act') === 0) {
            var a = actions[parseInt(ev.substr(3), 10)];
            var sep = a.url.indexOf('?') >= 0 ? '&' : '?';
            if (a.type === 'open') {
              App.open(a.url + sep + 'id=' + d.id, a.text, a.area || area, reload);
              return;
            }
            var go = function () {
              App.post(a.url, { id: d.id }, function (res) {
                layer.msg(res.msg, { icon: 1 });
                reload();
              });
            };
            if (a.confirm) {
              layer.confirm(a.confirm, { icon: 3 }, function (i) { layer.close(i); go(); });
            } else {
              go();
            }
          }
        });
      });
    },

    /** 附件链接列表 HTML */
    fileLinks: function (files) {
      return (files || []).map(function (f) {
        return '<a class="link" target="_blank" href="' + App.url('files/download?key=' + encodeURIComponent(f.key)) + '">' + esc(f.name) + '</a>';
      }).join('，');
    },

    /** 初始化表单中的附件字段（多文件上传、移除） */
    initFileFields: function (scope) {
      var $ = layui.$;
      $(scope).find('.file-field').each(function () {
        var $box = $(this), $input = $box.find('input[type=hidden]'), $list = $box.find('.file-list');
        if ($box.attr('data-readonly') === '1') return;
        var sync = function () {
          var keys = $list.find('li').map(function () { return $(this).attr('data-key'); }).get();
          $input.val(JSON.stringify(keys));
        };
        $list.on('click', '.file-del', function () { $(this).closest('li').remove(); sync(); });
        layui.upload.render({
          elem: $box.find('.file-upload-btn')[0],
          url: App.url('files/upload'),
          field: 'file',
          accept: 'file',
          exts: $box.attr('data-exts'),
          multiple: true,
          size: 20480,
          headers: { 'X-CSRF-Token': meta('csrf-token'), 'X-Requested-With': 'XMLHttpRequest' },
          before: function () { layui.layer.load(2); },
          allDone: function () { layui.layer.closeAll('loading'); },
          done: function (res) {
            if (res.code === 0) {
              var url = App.url('files/download?key=' + encodeURIComponent(res.data.key));
              $list.append('<li data-key="' + esc(res.data.key) + '"><i class="layui-icon layui-icon-file"></i> <a href="' + url + '" target="_blank">' +
                esc(res.data.name) + '</a> <i class="layui-icon layui-icon-close file-del" title="移除"></i></li>');
              sync();
            } else {
              layui.layer.msg(res.msg || '上传失败', { icon: 2 });
            }
          },
          error: function () { layui.layer.closeAll('loading'); layui.layer.msg('上传失败，请重试', { icon: 2 }); }
        });
      });
    },

    /* ------------------------------------------------------------ 通用表单页 */
    formPage: function () {
      layui.use(['form', 'layer', 'upload'], function () {
        var form = layui.form;
        form.render(null, 'edit-form');
        App.renderDates(document.querySelector('.form-page'));
        App.initFileFields(document.querySelector('.form-page'));
        form.on('submit(save)', function (d) {
          var action = d.form.getAttribute('data-action');
          App.post(action, d.field, function (res) {
            layui.layer.msg(res.msg || '保存成功', { icon: 1, time: 600 }, function () {
              App.closeSelf(true);
            });
          });
          return false;
        });
      });
    },

    /* ------------------------------------------------------------ 通用详情页 */
    viewPage: function () {
      layui.use(['element'], function () {
        layui.element.on('tab', function (data) {
          var frame = data.elem.find('.layui-tab-item').eq(data.index).find('iframe')[0];
          if (frame && !frame.getAttribute('src')) frame.setAttribute('src', frame.getAttribute('data-src'));
        });
        layui.element.render('tab');
      });
    }
  };

  win.App = App;

  // 全局初始化
  layui.use(['element', 'layer'], function () {
    var $ = layui.$;
    $.ajaxSetup({ headers: { 'X-CSRF-Token': meta('csrf-token'), 'X-Requested-With': 'XMLHttpRequest' } });

    $(document).on('click', '[data-open]', function () {
      var el = $(this);
      var area = (el.data('area') || '').split(',');
      App.open(el.data('open'), el.data('title'), area.length === 2 ? area : null, function () {
        if (el.data('reload')) win.location.reload();
      });
    });

    $('#side-toggle').on('click', function () {
      $('body').toggleClass('side-mini');
    });

    $('#logout-btn').on('click', function () {
      var url = $(this).data('url');
      layui.layer.confirm('确定退出登录？', { icon: 3 }, function () {
        App.post(url, {}, function () { win.location.href = App.url('login'); });
      });
    });
  });
})(window);
