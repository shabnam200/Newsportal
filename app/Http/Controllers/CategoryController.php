<?php

namespace App\Http\Controllers;

use App\Services\SubscriptionService;
use Inertia\Inertia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Category module of the portal dashboard: one `categories` table, up to 3 layers
 * (category > sub-category > sub-sub-category) stored with parent_id + level.
 * Always works on the logged-in portal's own database (the "tenant" connection that
 * the portal.auth middleware has already pointed at it).
 */
class CategoryController extends Controller
{
    private const MAX_LEVEL = 3;

    private function db()
    {
        return DB::connection('tenant');
    }

    /** The page: the normal dashboard shell with the category module in the content area. */
    public function index(Request $request, SubscriptionService $subs, PortalBillingController $billing)
    {
        return Inertia::render('Portal/ModuleHost', ['module' => 'categories'])
            ->rootView('dashboard')
            ->withViewData([...$billing->dashboardViewData($request, $subs), 'module' => 'categories']);
    }

    public function data(): JsonResponse
    {
        $rows = $this->db()->table('categories')
            ->orderBy('level')->orderBy('name')
            ->get(['id', 'parent_id', 'level', 'title', 'name', 'en_name', 'key_name', 'keywords', 'description', 'image_path', 'status'])
            ->map(function ($row) {
                $row->image_url = $row->image_path
                    ? url('/image/' . ltrim($row->image_path, '/\\')) . '?size=500'
                    : null;

                return $row;
            });

        return response()->json(['data' => $rows]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $db = $this->db();

        $parent = null;
        if ($data['parent_id'] !== null) {
            $parent = $db->table('categories')->where('id', $data['parent_id'])->first();
            if (!$parent) {
                return $this->invalid('parent_id', 'The selected parent does not exist.');
            }
        }

        $level = $parent ? $parent->level + 1 : 1;
        if ($level > self::MAX_LEVEL) {
            return $this->invalid('parent_id', 'Categories can only go ' . self::MAX_LEVEL . ' levels deep.');
        }

        if ($error = $this->keyTaken($data['key_name'], $data['parent_id'], null)) {
            return $this->invalid('key_name', $error);
        }

        $portalId = $this->portalId($request);
        $imagePath = $this->storeImage($request, $portalId);
        try {
            $id = $db->table('categories')->insertGetId($data + [
                'level' => $level,
                'creator' => 0,
                'image_path' => $imagePath,
            ]);
        } catch (Throwable $e) {
            $this->deleteImage($imagePath, $portalId);
            throw $e;
        }

        return response()->json(['message' => 'Category created.', 'id' => $id], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $db = $this->db();
        $row = $db->table('categories')->where('id', $id)->first();
        if (!$row) {
            return response()->json(['message' => 'Category not found.'], 404);
        }

        $data = $this->validated($request);
        $all = $db->table('categories')->get(['id', 'parent_id', 'level'])->keyBy('id');

        $newLevel = $row->level;
        $delta = 0;
        $subtree = [];

        if ($data['parent_id'] !== (is_null($row->parent_id) ? null : (int) $row->parent_id)) {
            $parent = null;
            if ($data['parent_id'] !== null) {
                $parent = $all->get($data['parent_id']);
                if (!$parent) {
                    return $this->invalid('parent_id', 'The selected parent does not exist.');
                }
            }

            $subtree = $this->subtree($all, $id); // id => depth below this category (self = 0)
            if (isset($subtree[$data['parent_id']])) {
                return $this->invalid('parent_id', 'A category cannot be moved inside itself.');
            }

            $newLevel = $parent ? $parent->level + 1 : 1;
            $height = max($subtree) + 1;
            if ($newLevel + $height - 1 > self::MAX_LEVEL) {
                return $this->invalid('parent_id', 'That would make the tree more than ' . self::MAX_LEVEL . ' levels deep.');
            }
            $delta = $newLevel - $row->level;
        }

        if ($error = $this->keyTaken($data['key_name'], $data['parent_id'], $id)) {
            return $this->invalid('key_name', $error);
        }

        $portalId = $this->portalId($request);
        $imagePath = $this->storeImage($request, $portalId);

        try {
            $db->transaction(function () use ($db, $id, $data, $newLevel, $delta, $subtree, $imagePath) {
                $updates = $data + ['level' => $newLevel];
                if ($imagePath !== null) {
                    $updates['image_path'] = $imagePath;
                }
                $db->table('categories')->where('id', $id)->update($updates);

                if ($delta !== 0) {
                    $children = array_diff(array_keys($subtree), [$id]);
                    if ($children) {
                        $db->table('categories')->whereIn('id', $children)->update(['level' => DB::raw('`level` + ' . (int) $delta)]);
                    }
                }
            });
        } catch (Throwable $e) {
            $this->deleteImage($imagePath, $portalId);
            throw $e;
        }

        if ($imagePath !== null) {
            $this->deleteImage($row->image_path, $portalId);
        }

        return response()->json(['message' => 'Category updated.']);
    }

    public function toggle(int $id): JsonResponse
    {
        $db = $this->db();
        $row = $db->table('categories')->where('id', $id)->first();
        if (!$row) {
            return response()->json(['message' => 'Category not found.'], 404);
        }

        $db->table('categories')->where('id', $id)->update(['status' => $row->status ? 0 : 1]);

        return response()->json(['message' => $row->status ? 'Category disabled.' : 'Category enabled.']);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $db = $this->db();

        $row = $db->table('categories')->where('id', $id)->first();
        if (!$row) {
            return response()->json(['message' => 'Category not found.'], 404);
        }

        if ($db->table('categories')->where('parent_id', $id)->exists()) {
            return response()->json(['message' => 'This category has sub-categories. Delete or move them first.'], 422);
        }

        $inUse = $db->table('news_common_info')->where('cat_id', $id)
            ->orWhere('cat_child_1', $id)->orWhere('cat_child_2', $id)->exists();
        if ($inUse) {
            return response()->json(['message' => 'This category is used by news and cannot be deleted. Disable it instead.'], 422);
        }

        $db->table('categories')->where('id', $id)->delete();
        $this->deleteImage($row->image_path, $this->portalId($request));

        return response()->json(['message' => 'Category deleted.']);
    }

    // ------------------------------------------------------------------

    private function validated(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'parent_id' => ['nullable', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:100'],
            'en_name' => ['nullable', 'string', 'max:100'],
            'key_name' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9_-]+$/'],
            'title' => ['nullable', 'string', 'max:100'],
            'keywords' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,gif,bmp', 'max:5120'],
            'status' => ['required', 'boolean'],
        ], [
            'name.required' => 'Please enter a name.',
            'key_name.regex' => 'The key may only contain letters, numbers, dashes and underscores.',
        ]);

        $validated = $validator->validate();

        $key = $validated['key_name'] ?? null;
        if ($key === null || $key === '') {
            $key = Str::slug($validated['en_name'] ?? '') ?: Str::slug($validated['name']);
        }
        if ($key === '') {
            // e.g. a Bangla-only name with no English name: slug() has nothing to build from
            $validator->errors()->add('key_name', 'Enter a key (letters, numbers, dashes) or an English name.');
            throw new ValidationException($validator);
        }

        return [
            'parent_id' => isset($validated['parent_id']) ? (int) $validated['parent_id'] : null,
            'name' => $validated['name'],
            'en_name' => $validated['en_name'] ?? null,
            'key_name' => $key,
            'title' => $validated['title'] ?? null,
            'keywords' => $validated['keywords'] ?? null,
            'description' => $validated['description'] ?? null,
            'status' => $request->boolean('status') ? 1 : 0,
        ];
    }

    /** Another category with the same key under the same parent? Returns the error text or null. */
    private function keyTaken(string $key, ?int $parentId, ?int $ignoreId): ?string
    {
        $q = $this->db()->table('categories')->where('key_name', $key);
        $parentId === null ? $q->whereNull('parent_id') : $q->where('parent_id', $parentId);
        if ($ignoreId !== null) {
            $q->where('id', '!=', $ignoreId);
        }

        return $q->exists() ? "The key \"{$key}\" is already used at this level." : null;
    }

    /** id => depth, for $rootId and everything below it (root has depth 0). */
    private function subtree($all, int $rootId): array
    {
        $byParent = [];
        foreach ($all as $r) {
            $byParent[$r->parent_id ?? 0][] = (int) $r->id;
        }

        $out = [$rootId => 0];
        $queue = [$rootId];
        while ($queue) {
            $id = array_shift($queue);
            foreach ($byParent[$id] ?? [] as $child) {
                $out[$child] = $out[$id] + 1;
                $queue[] = $child;
            }
        }

        return $out;
    }

    private function invalid(string $field, string $message): JsonResponse
    {
        return response()->json(['message' => $message, 'errors' => [$field => [$message]]], 422);
    }

    private function portalId(Request $request): int
    {
        return (int) $request->attributes->get('portal')->id;
    }

    private function storeImage(Request $request, int $portalId): ?string
    {
        $image = $request->file('image');
        if (!$image) {
            return null;
        }

        $directory = public_path("uploads/portal-categories/{$portalId}");
        File::ensureDirectoryExists($directory);

        $filename = $image->hashName();
        $image->move($directory, $filename);

        return "uploads/portal-categories/{$portalId}/{$filename}";
    }

    private function deleteImage(?string $path, int $portalId): void
    {
        $prefix = "uploads/portal-categories/{$portalId}/";
        if (!$path || !str_starts_with($path, $prefix)) {
            return;
        }

        $filename = substr($path, strlen($prefix));
        if ($filename === '' || basename($filename) !== $filename) {
            return;
        }

        File::delete(public_path($prefix . $filename));
    }
}
