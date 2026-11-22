<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $tree = $request->boolean('tree', true);

        if ($tree) {
            $staff = (bool) $request->user()?->can('categories.view');

            $categories = Category::with([
                'children' => fn ($q) => $q
                    ->when(!$staff, fn ($inner) => $inner->where('is_active', true))
                    ->orderBy('name'),
            ])
                ->whereNull('parent_id')
                ->when(!$staff, fn ($q) => $q->where('is_active', true))
                ->orderBy('name')
                ->get()
                ->map(fn (Category $c) => $this->transform($c, true));

            return ApiResponse::success($categories);
        }

        $categories = Category::with('parent')
            ->orderBy('name')
            ->paginate(min(50, (int) $request->input('per_page', 20)));

        return ApiResponse::success(
            $categories->getCollection()->map(fn (Category $c) => $this->transform($c))->values(),
            'Categories retrieved.',
            200,
            [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
            ]
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $validated = $this->sanitizeCategoryFields($validated);

        $category = Category::create([
            ...$validated,
            'slug' => $this->uniqueSlug($validated['name']),
        ]);

        return ApiResponse::success($this->transform($category->fresh('children')), 'Category created.', 201);
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => [
                'nullable',
                'exists:categories,id',
                Rule::notIn([$category->id]),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $validated = $this->sanitizeCategoryFields($validated);

        if ($validated['name'] !== $category->name) {
            $validated['slug'] = $this->uniqueSlug($validated['name'], $category->id);
        }

        $category->update($validated);

        return ApiResponse::success($this->transform($category->fresh('children')), 'Category updated.');
    }

    public function destroy(Category $category)
    {
        if ($category->children()->exists() || $category->products()->exists()) {
            return ApiResponse::error('Category has children or products and cannot be deleted.', 422);
        }

        $category->delete();

        return ApiResponse::success(null, 'Category deleted.');
    }

    private function sanitizeCategoryFields(array $validated): array
    {
        if (isset($validated['name'])) {
            $validated['name'] = $this->plainText($validated['name']);
        }
        if (array_key_exists('description', $validated) && is_string($validated['description'])) {
            $validated['description'] = $this->plainText($validated['description']) ?: null;
        }

        return $validated;
    }

    private function plainText(string $value): string
    {
        $value = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $value) ?? '';
        $value = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $value) ?? '';

        return trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (
            Category::where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    private function transform(Category $category, bool $withChildren = false): array
    {
        $data = [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'parent_id' => $category->parent_id,
            'is_active' => $category->is_active,
        ];

        if ($withChildren) {
            $data['children'] = $category->children->map(
                fn (Category $child) => $this->transform($child)
            )->values();
        }

        return $data;
    }
}
