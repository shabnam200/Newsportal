<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Read-only access to the tenant database that belongs to the token (see AuthenticatePortalApiToken).
 * Table and column names are only ever taken from the live database catalogue, never from raw input,
 * and every value is bound as a parameter.
 */
class TenantDataController extends Controller
{
    /** GET /api/v1 */
    public function index(Request $request): JsonResponse
    {
        $portal = $request->attributes->get('portal');

        return $this->json([
            'portal' => ['id' => $portal->id, 'name' => $portal->portal_name],
            'tables_available' => count($this->tables()),
            'endpoints' => [
                'GET /api/v1/tables' => 'all tables you can read',
                'GET /api/v1/tables/{table}' => 'rows of one table (page, per_page, order_by, direction, fields, filter[column]=value)',
                'GET /api/v1/tables/{table}/{id}' => 'one row by primary key',
            ],
        ]);
    }

    /**
     * GET /api/get_categories/{token}
     * The portal's categories as a tree (level 1 > children > children). Options:
     *   ?flat=1    plain list instead of a tree
     *   ?status=1  only active categories (0 = only inactive)
     */
    public function categories(Request $request): JsonResponse
    {
        $query = DB::connection('tenant')->table('categories')->orderBy('level')->orderBy('name');

        if (in_array($request->query('status'), ['0', '1', 0, 1], true)) {
            $query->where('status', (int) $request->query('status'));
        }

        $rows = $query->get(['id', 'parent_id', 'level', 'title', 'name', 'en_name', 'key_name', 'keywords', 'description', 'image_path', 'status'])
            ->map(function ($row) {
                $row->image_url = $row->image_path
                    ? url('/image/' . ltrim($row->image_path, '/\\')) . '?size=500'
                    : null;

                return $row;
            })->all();

        $total = count($rows);

        if (!$request->boolean('flat')) {
            $byParent = [];
            foreach ($rows as $row) {
                $byParent[$row->parent_id ?? 0][] = $row;
            }
            $attach = function (array $nodes) use (&$attach, &$byParent): array {
                foreach ($nodes as $node) {
                    $node->children = $attach($byParent[$node->id] ?? []);
                }

                return $nodes;
            };
            // With ?status filter a child can survive while its parent is filtered out: show it at the top.
            $ids = array_flip(array_map(fn ($r) => $r->id, $rows));
            $roots = array_values(array_filter($rows, fn ($r) => $r->parent_id === null || !isset($ids[$r->parent_id])));
            $rows = $attach($roots);
        }

        return $this->json(['data' => $rows, 'meta' => ['total' => $total, 'format' => $request->boolean('flat') ? 'flat' : 'tree']]);
    }

    /**
     * GET /api/get_all_data/{token}
     * The first rows of EVERY readable table in one response (newest first).
     *   ?limit=100          rows per table (1-1000, default 100)
     *   ?tables=a,b,c       only these tables
     * For the complete data of one big table, page through get_table/{table}/{token}.
     */
    public function everything(Request $request): JsonResponse
    {
        $allowed = $this->tables();
        $only = $allowed;

        if (is_string($list = $request->query('tables')) && $list !== '') {
            $only = array_values(array_unique(array_filter(array_map('trim', explode(',', $list)))));
            $unknown = array_diff($only, $allowed);
            if ($unknown) {
                return $this->error('invalid_tables', 'Unknown table(s): ' . implode(', ', $unknown), 422);
            }
        }

        $limit = max(1, min((int) $request->query('limit', 100), 1000));
        $db = DB::connection('tenant');
        $data = [];

        foreach ($only as $table) {
            $columns = $this->columns($table);
            $primary = null;
            foreach ($columns as $c) {
                if ($c->col_key === 'PRI') {
                    $primary = $c->name;
                    break;
                }
            }

            $query = $db->table($table)->select(array_map(fn ($c) => $c->name, $columns));
            if ($primary !== null) {
                $query->orderBy($primary, 'desc');
            }
            $rows = $query->limit($limit)->get();

            $data[$table] = ['total' => $db->table($table)->count(), 'returned' => $rows->count(), 'rows' => $rows];
        }

        return $this->json(['data' => $data, 'meta' => ['tables' => count($data), 'limit_per_table' => $limit]]);
    }

    /** GET /api/v1/tables */
    public function tableList(): JsonResponse
    {
        $rows = DB::connection('tenant')->select(
            "SELECT TABLE_NAME AS name, TABLE_ROWS AS rows_estimate FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME"
        );

        $estimates = [];
        foreach ($rows as $r) {
            $estimates[$r->name] = (int) $r->rows_estimate;
        }

        $data = [];
        foreach ($this->tables() as $name) {
            $data[] = [
                'table' => $name,
                'rows_estimate' => $estimates[$name] ?? null,
                'columns' => array_map(fn ($c) => ['name' => $c->name, 'type' => $c->type, 'primary' => $c->col_key === 'PRI'],
                    $this->columns($name)),
            ];
        }

        return $this->json(['data' => $data]);
    }

    /** GET /api/v1/tables/{table} */
    public function rows(Request $request, string $table): JsonResponse
    {
        if (!in_array($table, $this->tables(), true)) {
            return $this->notFound("Table \"{$table}\" was not found.");
        }

        $columns = $this->columns($table);
        $names = array_map(fn ($c) => $c->name, $columns);

        $query = DB::connection('tenant')->table($table);

        $filters = $request->query('filter', []);
        if (is_array($filters)) {
            foreach ($filters as $column => $value) {
                if (!in_array($column, $names, true) || !is_scalar($value)) {
                    return $this->error('invalid_filter', "Cannot filter by \"{$column}\".", 422);
                }
                $query->where($column, '=', (string) $value);
            }
        }

        $select = $names;
        if (is_string($fields = $request->query('fields')) && $fields !== '') {
            $wanted = array_values(array_unique(array_filter(array_map('trim', explode(',', $fields)))));
            $unknown = array_diff($wanted, $names);
            if ($unknown) {
                return $this->error('invalid_fields', 'Unknown field(s): ' . implode(', ', $unknown), 422);
            }
            $select = $wanted;
        }

        $primary = null;
        foreach ($columns as $c) {
            if ($c->col_key === 'PRI') {
                $primary = $c->name;
                break;
            }
        }
        $orderBy = $request->query('order_by', $primary);
        if ($orderBy !== null && !in_array($orderBy, $names, true)) {
            return $this->error('invalid_order_by', "Cannot sort by \"{$orderBy}\".", 422);
        }
        $direction = strtolower((string) $request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        $perPage = (int) $request->query('per_page', config('portal_api.default_per_page', 50));
        $perPage = max(1, min($perPage, (int) config('portal_api.max_per_page', 200)));
        $page = max(1, (int) $request->query('page', 1));

        $total = (clone $query)->count();

        if ($orderBy !== null) {
            $query->orderBy($orderBy, $direction);
        }

        $data = $query->select($select)->offset(($page - 1) * $perPage)->limit($perPage)->get();

        return $this->json([
            'data' => $data,
            'meta' => [
                'table' => $table,
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => max(1, (int) ceil($total / $perPage)),
            ],
        ]);
    }

    /** GET /api/v1/tables/{table}/{id} */
    public function find(string $table, string $id): JsonResponse
    {
        if (!in_array($table, $this->tables(), true)) {
            return $this->notFound("Table \"{$table}\" was not found.");
        }

        $columns = $this->columns($table);
        $primary = array_values(array_filter($columns, fn ($c) => $c->col_key === 'PRI'));

        if (count($primary) !== 1) {
            return $this->error('no_single_primary_key', 'This table has no single-column primary key; use the list endpoint with filter[column]=value.', 422);
        }

        $row = DB::connection('tenant')->table($table)
            ->select(array_map(fn ($c) => $c->name, $columns))
            ->where($primary[0]->name, '=', $id)
            ->first();

        return $row ? $this->json(['data' => $row]) : $this->notFound('Row not found.');
    }

    /** Names of the tables this token may read (real tables of the tenant DB minus the hidden ones). */
    private function tables(): array
    {
        $hidden = array_map('strtolower', config('portal_api.hidden_tables', []));

        $rows = DB::connection('tenant')->select(
            "SELECT TABLE_NAME AS name FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME"
        );

        return array_values(array_filter(
            array_map(fn ($r) => $r->name, $rows),
            fn ($name) => !in_array(strtolower($name), $hidden, true)
        ));
    }

    /** Readable columns of one (already validated) table, hidden columns removed. */
    private function columns(string $table): array
    {
        $hidden = array_map('strtolower', config('portal_api.hidden_columns', []));

        $rows = DB::connection('tenant')->select(
            "SELECT COLUMN_NAME AS name, DATA_TYPE AS type, COLUMN_KEY AS col_key FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION",
            [$table]
        );

        return array_values(array_filter($rows, fn ($c) => !in_array(strtolower($c->name), $hidden, true)));
    }

    private function json(array $payload, int $status = 200): JsonResponse
    {
        // Bangla text stays readable; any stray invalid UTF-8 (old latin1 tables) cannot break the response.
        return response()->json($payload, $status, [], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }

    private function error(string $code, string $message, int $status): JsonResponse
    {
        return $this->json(['error' => $code, 'message' => $message], $status);
    }

    private function notFound(string $message): JsonResponse
    {
        return $this->error('not_found', $message, 404);
    }
}
