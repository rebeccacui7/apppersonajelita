<?php
declare(strict_types=1);

namespace App\Core;

/**
 * 通用 CRUD 控制器
 *
 * 子类只需声明表、权限前缀与字段定义 fields()，即可获得：
 * 列表(搜索/筛选/分页/排序/合计) + 新增/编辑表单 + 详情(含子表) + 软删除 + CSV 导出 + 数据权限 + 操作日志。
 * 业务规则通过 beforeSave / afterSave / beforeDelete / afterDelete 钩子扩展。
 *
 * 字段定义：
 *  'key' => [
 *     'label'    => '名称',
 *     'type'     => text|textarea|number|money|date|datetime|select|user|relation|password|checkbox,
 *     'options'  => [值 => 文本]            // select / checkbox
 *     'table'    => 'crm_customer',          // relation 关联表
 *     'display'  => 'name',                  // relation 显示列
 *     'required' => bool, 'list' => bool, 'form' => bool, 'readonly' => bool,
 *     'search'   => bool,  // 参与关键字模糊搜索
 *     'filter'   => bool,  // 列表顶部下拉筛选
 *     'virtual'  => bool,  // 非数据库列（自行在钩子里处理）
 *     'default'  => mixed, 'width' => int, 'sort' => bool, 'tips' => string,
 *  ]
 */
abstract class ResourceController extends Controller
{
    protected string $table = '';
    protected string $perm = '';
    protected string $title = '';
    protected string $path = '';
    /** 数据范围依据的负责人列；null 表示该表不做数据范围过滤 */
    protected ?string $ownerField = 'owner_id';
    protected string $nameField = 'name';
    protected bool $softDelete = true;
    protected bool $hasCreatedBy = true;
    protected bool $readonly = false;
    protected array $hidden = [];
    protected string $defaultSort = 'id';
    /** 列表日期区间筛选所依据的字段 */
    protected ?string $dateField = null;
    protected string $formWidth = '760px';

    private ?array $fieldCache = null;

    abstract protected function fields(): array;

    /* ------------------------------------------------------------------ 页面 */

    public function index(): void
    {
        $embed = Request::get('embed') === '1';
        $fixed = $this->fixedFilters();
        $fields = $this->getFields();

        $cols = [];
        foreach ($fields as $k => $f) {
            if ($f['list'] && !isset($fixed[$k])) {
                $cols[] = ['field' => $k, 'title' => $f['label'], 'type' => $f['type'], 'width' => $f['width'], 'sort' => $f['sort']];
            }
        }
        $filters = [];
        foreach ($fields as $k => $f) {
            if ($f['filter'] && !isset($fixed[$k])) {
                $filters[] = ['field' => $k, 'label' => $f['label'], 'options' => $this->options($k)];
            }
        }
        $actions = array_values(array_filter($this->rowActions(), fn($a) => empty($a['perm']) || Auth::can($a['perm'])));
        foreach ($actions as &$a) {
            $a['url'] = url($a['url']);
        }
        unset($a);

        $page = [
            'title'    => $this->title,
            'base'     => url($this->path),
            'cols'     => $cols,
            'filters'  => $filters,
            'keyword'  => (bool)array_filter($fields, fn($f) => $f['search']),
            'dateField'=> $this->dateField ? ($fields[$this->dateField]['label'] ?? '日期') : null,
            'fixed'    => $fixed,
            'actions'  => $actions,
            'formWidth'=> $this->formWidth,
            'perms'    => [
                'edit'   => !$this->readonly && Auth::can($this->perm . '.edit'),
                'delete' => !$this->readonly && Auth::can($this->perm . '.delete'),
            ],
        ];
        $this->render('common/resource_index', ['page' => $page, 'title' => $this->title], $embed ? 'layout/blank' : 'layout/main');
    }

    public function list(): void
    {
        [$where, $params] = $this->buildWhere();
        $page = max(1, Request::int('page', 1));
        $limit = min(200, max(1, Request::int('limit', 20)));

        $count = (int)DB::value("SELECT COUNT(*) FROM `{$this->table}` t WHERE $where", $params);
        $rows = DB::all(
            "SELECT {$this->selectSql()} FROM `{$this->table}` t WHERE $where ORDER BY {$this->orderSql()} LIMIT $limit OFFSET " . (($page - 1) * $limit),
            $params
        );

        // 金额字段合计
        $totals = [];
        $sums = [];
        foreach ($this->getFields() as $k => $f) {
            if ($f['type'] === 'money' && $f['list'] && !$f['virtual']) {
                $sums[] = "SUM(t.`$k`) AS `$k`";
            }
        }
        if ($sums && $count > 0) {
            $totals = DB::row("SELECT " . implode(',', $sums) . " FROM `{$this->table}` t WHERE $where", $params) ?? [];
            $totals = array_map(fn($v) => money($v), $totals);
        }

        Response::json([
            'code' => 0, 'msg' => '', 'count' => $count,
            'data' => array_map(fn($r) => $this->decorate($r), $rows),
            'totalRow' => $totals,
        ]);
    }

    public function export(): void
    {
        [$where, $params] = $this->buildWhere();
        $rows = DB::all("SELECT {$this->selectSql()} FROM `{$this->table}` t WHERE $where ORDER BY {$this->orderSql()} LIMIT 10000", $params);
        $fields = array_filter($this->getFields(), fn($f) => !$f['virtual'] && $f['type'] !== 'password');

        $header = ['ID'];
        foreach ($fields as $f) {
            $header[] = $f['label'];
        }
        $header[] = '创建时间';

        $out = [];
        foreach ($rows as $r) {
            $r = $this->decorate($r);
            $line = [$r['id']];
            foreach ($fields as $k => $f) {
                $line[] = $r[$k . '__text'] ?? $r[$k] ?? '';
            }
            $line[] = $r['created_at'] ?? '';
            $out[] = $line;
        }
        Logger::log($this->perm, 'export', null, '导出' . count($out) . '条');
        Response::csv($this->title . '_' . date('YmdHis') . '.csv', $header, $out);
    }

    public function form(): void
    {
        if ($this->readonly) {
            throw new BizException('该数据为只读');
        }
        $id = Request::int('id');
        if ($id) {
            $row = $this->findScoped($id);
            if (!$row) {
                throw new BizException('记录不存在或无权操作');
            }
        } else {
            $row = [];
            foreach ($this->getFields() as $k => $f) {
                $v = Request::get($k, Request::get('f_' . $k));
                $row[$k] = $v ?? $f['default'];
            }
        }
        $row = $this->formRow($row);

        $fields = [];
        foreach ($this->getFields() as $k => $f) {
            if ($f['form']) {
                $f['options'] = $this->options($k);
                $fields[$k] = $f;
            }
        }
        $this->render('common/resource_form', [
            'title'  => ($id ? '编辑' : '新增') . $this->title,
            'id'     => $id,
            'row'    => $row,
            'fields' => $fields,
            'action' => url($this->path . '/save'),
        ], 'layout/blank');
    }

    public function view(): void
    {
        $id = Request::int('id');
        $row = $id ? $this->findScoped($id, true) : null;
        if (!$row) {
            throw new BizException('记录不存在或无权查看');
        }
        $row = $this->decorate($row);

        $children = [];
        foreach ($this->children() as $c) {
            if (empty($c['perm']) || Auth::can($c['perm'])) {
                $c['url'] = url($c['path'], ['embed' => 1, 'f_' . $c['fk'] => $id]);
                $children[] = $c;
            }
        }
        $this->render('common/resource_view', [
            'title'    => $this->title . '详情',
            'heading'  => $row[$this->nameField] ?? ('#' . $id),
            'row'      => $row,
            'fields'   => array_filter($this->getFields(), fn($f) => $f['type'] !== 'password'),
            'children' => $children,
            'editUrl'  => !$this->readonly && Auth::can($this->perm . '.edit') ? url($this->path . '/form', ['id' => $id]) : null,
        ], 'layout/blank');
    }

    /* ------------------------------------------------------------------ 写操作 */

    public function save(): void
    {
        if ($this->readonly) {
            throw new BizException('该数据为只读');
        }
        $id = Request::int('id');
        $old = null;
        if ($id) {
            $old = $this->findScoped($id);
            if (!$old) {
                throw new BizException('记录不存在或无权操作');
            }
        }

        $data = [];
        foreach ($this->getFields() as $k => $f) {
            if (!$f['form'] || $f['virtual'] || $f['readonly'] || $f['type'] === 'password') {
                continue;
            }
            $v = $this->cast($k, $f, $_POST[$k] ?? null);
            if ($f['required'] && ($v === null || $v === '')) {
                throw new BizException($f['label'] . '不能为空');
            }
            $data[$k] = $v;
        }
        $data = $this->beforeSave($data, $old);

        $id = DB::transaction(function () use ($data, $old) {
            if ($old) {
                $id = (int)$old['id'];
                DB::update($this->table, $data, $id);
            } else {
                if ($this->ownerField && empty($data[$this->ownerField])) {
                    $data[$this->ownerField] = Auth::id();
                }
                if ($this->hasCreatedBy) {
                    $data['created_by'] = Auth::id();
                }
                $id = DB::insert($this->table, $data);
            }
            $this->afterSave($id, $data, $old);
            return $id;
        });

        Logger::log($this->perm, $old ? 'update' : 'create', $id, json_encode($this->logPayload($data), JSON_UNESCAPED_UNICODE));
        $this->success('保存成功', ['id' => $id]);
    }

    public function delete(): void
    {
        if ($this->readonly) {
            throw new BizException('该数据为只读');
        }
        $ids = Request::ids();
        if (!$ids) {
            throw new BizException('请选择要删除的记录');
        }
        $n = DB::transaction(function () use ($ids) {
            $n = 0;
            foreach ($ids as $id) {
                $row = $this->findScoped($id);
                if (!$row) {
                    continue;
                }
                $this->beforeDelete($row);
                if ($this->softDelete) {
                    DB::query("UPDATE `{$this->table}` SET deleted_at = NOW() WHERE id = ?", [$id]);
                } else {
                    DB::query("DELETE FROM `{$this->table}` WHERE id = ?", [$id]);
                }
                $this->afterDelete($row);
                Logger::log($this->perm, 'delete', $id, (string)($row[$this->nameField] ?? ''));
                $n++;
            }
            return $n;
        });
        $this->success("已删除 {$n} 条记录");
    }

    /* ------------------------------------------------------------------ 钩子（子类按需覆盖） */

    protected function beforeSave(array $data, ?array $old): array
    {
        return $data;
    }

    protected function afterSave(int $id, array $data, ?array $old): void
    {
    }

    protected function beforeDelete(array $row): void
    {
    }

    protected function afterDelete(array $row): void
    {
    }

    /** 编辑表单回显前加工 */
    protected function formRow(array $row): array
    {
        return $row;
    }

    /** 列表行操作按钮：[['text','url','perm','type'=>ajax|open,'confirm','when'=>['field'=>[值...]]]] */
    protected function rowActions(): array
    {
        return [];
    }

    /** 详情页子表：[['title','path','fk','perm']] */
    protected function children(): array
    {
        return [];
    }

    /** 数据范围过滤（默认按 ownerField），可覆盖实现间接关联过滤 */
    protected function scopeWhere(): array
    {
        return $this->ownerField ? Auth::scopeSql("t.`{$this->ownerField}`") : ['', []];
    }

    /* ------------------------------------------------------------------ 内部工具 */

    protected function getFields(): array
    {
        if ($this->fieldCache === null) {
            $this->fieldCache = [];
            foreach ($this->fields() as $k => $f) {
                $type = $f['type'] ?? 'text';
                $f += [
                    'label' => $k, 'type' => $type, 'required' => false,
                    'list' => !in_array($type, ['textarea', 'password', 'checkbox'], true),
                    'form' => true, 'readonly' => false, 'search' => false, 'filter' => false,
                    'virtual' => false, 'default' => null, 'width' => null, 'sort' => false,
                    'options' => [], 'tips' => '',
                ];
                if ($type === 'user') {
                    $f += ['table' => 'sys_user', 'display' => 'realname'];
                }
                $this->fieldCache[$k] = $f;
            }
        }
        return $this->fieldCache;
    }

    /** 下拉选项，返回 [值 => 文本] */
    protected function options(string $key): array
    {
        $f = $this->getFields()[$key];
        if ($f['type'] === 'user') {
            return self::userOptions();
        }
        if ($f['type'] === 'relation') {
            $rows = DB::all("SELECT id, `{$f['display']}` AS label FROM `{$f['table']}` WHERE deleted_at IS NULL ORDER BY id DESC LIMIT 1000");
            return array_column($rows, 'label', 'id');
        }
        return $f['options'];
    }

    protected static function userOptions(): array
    {
        static $cache = null;
        if ($cache === null) {
            $rows = DB::all('SELECT id, realname FROM sys_user WHERE deleted_at IS NULL AND status = 1 ORDER BY id');
            $cache = array_column($rows, 'realname', 'id');
        }
        return $cache;
    }

    protected function cast(string $key, array $f, mixed $raw): mixed
    {
        if (is_array($raw)) {
            return null;
        }
        $raw = $raw === null ? '' : trim((string)$raw);
        switch ($f['type']) {
            case 'number':
                return $raw === '' ? null : (is_numeric($raw) ? $raw + 0 : throw new BizException($f['label'] . '必须是数字'));
            case 'money':
                return $raw === '' ? null : (is_numeric($raw) ? round((float)$raw, 2) : throw new BizException($f['label'] . '必须是金额'));
            case 'date':
                if ($raw === '') return null;
                $d = \DateTime::createFromFormat('!Y-m-d', $raw);
                return $d ? $d->format('Y-m-d') : throw new BizException($f['label'] . '日期格式错误');
            case 'datetime':
                if ($raw === '') return null;
                $ts = strtotime($raw);
                return $ts ? date('Y-m-d H:i:s', $ts) : throw new BizException($f['label'] . '时间格式错误');
            case 'user':
            case 'relation':
                return ctype_digit($raw) && (int)$raw > 0 ? (int)$raw : null;
            case 'select':
                if ($raw === '') return null;
                if (!array_key_exists($raw, $this->options($key))) {
                    throw new BizException($f['label'] . '选项无效');
                }
                return $raw;
            case 'textarea':
                return mb_substr($raw, 0, 5000);
            default:
                return mb_substr($raw, 0, 255);
        }
    }

    /** 通过 GET f_字段=值 传入的固定筛选（用于详情页子表） */
    protected function fixedFilters(): array
    {
        $out = [];
        foreach ($this->getFields() as $k => $f) {
            $v = $_GET['f_' . $k] ?? null;
            if (!$f['virtual'] && is_string($v) && $v !== '') {
                $out[$k] = $v;
            }
        }
        return $out;
    }

    protected function buildWhere(): array
    {
        $where = [];
        $params = [];
        if ($this->softDelete) {
            $where[] = 't.deleted_at IS NULL';
        }
        [$sql, $p] = $this->scopeWhere();
        if ($sql !== '') {
            $where[] = $sql;
            array_push($params, ...$p);
        }

        $kw = Request::str('keyword');
        if ($kw !== '') {
            $or = [];
            foreach ($this->getFields() as $k => $f) {
                if ($f['search'] && !$f['virtual']) {
                    $or[] = "t.`$k` LIKE ?";
                    $params[] = '%' . addcslashes($kw, '%_\\') . '%';
                }
            }
            if ($or) {
                $where[] = '(' . implode(' OR ', $or) . ')';
            }
        }

        foreach ($this->getFields() as $k => $f) {
            $v = $_GET['f_' . $k] ?? null;
            if (!$f['virtual'] && is_string($v) && $v !== '') {
                $where[] = "t.`$k` = ?";
                $params[] = $v;
            }
        }

        if ($this->dateField) {
            $from = Request::str('date_from');
            $to = Request::str('date_to');
            if ($from !== '' && strtotime($from)) {
                $where[] = "t.`{$this->dateField}` >= ?";
                $params[] = date('Y-m-d 00:00:00', strtotime($from));
            }
            if ($to !== '' && strtotime($to)) {
                $where[] = "t.`{$this->dateField}` <= ?";
                $params[] = date('Y-m-d 23:59:59', strtotime($to));
            }
        }

        return [$where ? implode(' AND ', $where) : '1=1', $params];
    }

    protected function selectSql(): string
    {
        $cols = ['t.*'];
        foreach ($this->getFields() as $k => $f) {
            if ($f['virtual']) {
                continue;
            }
            if (in_array($f['type'], ['user', 'relation'], true)) {
                $cols[] = "(SELECT x.`{$f['display']}` FROM `{$f['table']}` x WHERE x.id = t.`$k`) AS `{$k}__text`";
            }
        }
        if ($this->hasCreatedBy) {
            $cols[] = '(SELECT x.realname FROM sys_user x WHERE x.id = t.created_by) AS created_by__text';
        }
        return implode(', ', $cols);
    }

    protected function orderSql(): string
    {
        $sort = Request::str('sort');
        $order = strtolower(Request::str('order')) === 'asc' ? 'ASC' : 'DESC';
        $fields = $this->getFields();
        if ($sort !== '' && isset($fields[$sort]) && $fields[$sort]['sort'] && !$fields[$sort]['virtual']) {
            return "t.`$sort` $order, t.id DESC";
        }
        return "t.`{$this->defaultSort}` DESC" . ($this->defaultSort !== 'id' ? ', t.id DESC' : '');
    }

    /** 按数据范围读取单条记录 */
    protected function findScoped(int $id, bool $withText = false): ?array
    {
        $where = ['t.id = ?'];
        $params = [$id];
        if ($this->softDelete) {
            $where[] = 't.deleted_at IS NULL';
        }
        [$sql, $p] = $this->scopeWhere();
        if ($sql !== '') {
            $where[] = $sql;
            array_push($params, ...$p);
        }
        $select = $withText ? $this->selectSql() : 't.*';
        return DB::row("SELECT $select FROM `{$this->table}` t WHERE " . implode(' AND ', $where), $params);
    }

    /** 给行数据补充 __text 显示值，并去除敏感字段 */
    protected function decorate(array $row): array
    {
        foreach ($this->getFields() as $k => $f) {
            if ($f['type'] === 'select' && array_key_exists($k, $row)) {
                $row[$k . '__text'] = $f['options'][(string)$row[$k]] ?? '';
            }
        }
        foreach (array_merge($this->hidden, ['password']) as $h) {
            unset($row[$h]);
        }
        return $row;
    }

    protected function logPayload(array $data): array
    {
        foreach (array_merge($this->hidden, ['password']) as $h) {
            unset($data[$h]);
        }
        return $data;
    }

    /** 生成业务编号：前缀 + 日期 + 当日序号，如 PRJ20261002001 */
    protected function serial(string $prefix, string $column = 'code'): string
    {
        $base = $prefix . date('Ymd');
        $last = DB::value("SELECT `$column` FROM `{$this->table}` WHERE `$column` LIKE ? ORDER BY `$column` DESC LIMIT 1", [$base . '%']);
        $seq = $last ? (int)substr((string)$last, strlen($base)) + 1 : 1;
        return $base . str_pad((string)$seq, 3, '0', STR_PAD_LEFT);
    }
}
