<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Category;
use App\Models\Product;
use App\Support\ProductFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()
            ->with(['category', 'images'])
            ->withAvg('approvedReviews as average_rating', 'rating')
            ->withCount('approvedReviews as reviews_count')
            ->when($request->boolean('active_only', !$request->user()?->can('products.view')), fn ($q) => $q->where('is_active', true))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . $request->string('search') . '%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('sku', 'like', $term)
                        ->orWhere('description', 'like', $term);
                });
            })
            ->when($request->filled('category'), function ($q) use ($request) {
                $category = Category::query()
                    ->where(function ($inner) use ($request) {
                        $inner->where('slug', $request->string('category'))
                            ->orWhere('id', $request->input('category'));
                    })
                    ->first();

                if ($category) {
                    $ids = collect([$category->id])
                        ->merge($category->children()->pluck('id'))
                        ->all();
                    $q->whereIn('category_id', $ids);
                }
            })
            ->when($request->filled('min_price'), fn ($q) => $q->where('price', '>=', (float) $request->input('min_price')))
            ->when($request->filled('max_price'), fn ($q) => $q->where('price', '<=', (float) $request->input('max_price')))
            ->when($request->filled('color'), fn ($q) => $q->where('color', $request->string('color')->toString()))
            ->when($request->filled('size'), fn ($q) => $q->where('size', $request->string('size')->toString()))
            ->when(
                $request->filled('dress_style') || $request->filled('style'),
                function ($q) use ($request) {
                    $style = $request->input('dress_style', $request->input('style'));
                    $q->where('dress_style', $style);
                }
            );

        $sort = $request->string('sort', 'newest')->toString();
        match ($sort) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'name' => $query->orderBy('name'),
            default => $query->orderByDesc('created_at'),
        };

        $perPage = min(48, max(1, (int) $request->input('per_page', 12)));
        $paginator = $query->paginate($perPage);

        return ApiResponse::success(
            $paginator->getCollection()->map(fn (Product $p) => $this->transform($p))->values(),
            'Products retrieved.',
            200,
            [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        );
    }

    public function show(string $slug)
    {
        $product = Product::with(['category', 'images'])
            ->withAvg('approvedReviews as average_rating', 'rating')
            ->withCount('approvedReviews as reviews_count')
            ->where(function ($q) use ($slug) {
                $q->where('slug', $slug)->orWhere('id', $slug);
            })
            ->firstOrFail();

        $related = Product::with('images')
            ->withAvg('approvedReviews as average_rating', 'rating')
            ->withCount('approvedReviews as reviews_count')
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('is_active', true)
            ->limit(4)
            ->get()
            ->map(fn (Product $p) => $this->transform($p));

        return ApiResponse::success([
            'product' => $this->transform($product, true),
            'related' => $related,
        ]);
    }

    public function adminShow(Product $product)
    {
        $product->load(['category', 'images']);

        return ApiResponse::success($this->transform($product, true));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedProduct($request);

        $product = Product::create([
            ...$validated,
            'slug' => $this->uniqueSlug($validated['name']),
        ]);

        $this->syncImages($product, $request);

        return ApiResponse::success(
            $this->transform($product->fresh(['category', 'images']), true),
            'Product created.',
            201
        );
    }

    public function update(Request $request, Product $product)
    {
        $validated = $this->validatedProduct($request, $product);

        if (isset($validated['name']) && $validated['name'] !== $product->name) {
            $validated['slug'] = $this->uniqueSlug($validated['name'], $product->id);
        }

        $product->update($validated);
        $this->syncImages($product, $request);

        return ApiResponse::success(
            $this->transform($product->fresh(['category', 'images']), true),
            'Product updated.'
        );
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return ApiResponse::success(null, 'Product deleted.');
    }

    private function validatedProduct(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'required',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->ignore($product?->id),
            ],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'color' => ['nullable', 'string', Rule::in(ProductFilters::colorNames())],
            'size' => ['nullable', 'string', Rule::in(ProductFilters::sizeNames())],
            'dress_style' => ['nullable', 'string', Rule::in(ProductFilters::dressStyleNames())],
            'is_active' => ['sometimes', 'boolean'],
            'images' => ['sometimes', 'array'],
            'images.*' => ['image', 'max:4096'],
            'image_urls' => ['sometimes', 'array'],
            'image_urls.*' => ['string', 'max:500'],
        ]);

        // Plain text only — mitigates stored XSS if content is ever rendered as HTML.
        if (array_key_exists('description', $data) && is_string($data['description'])) {
            $data['description'] = $this->plainText($data['description']) ?: null;
        }
        if (array_key_exists('name', $data) && is_string($data['name'])) {
            $data['name'] = $this->plainText($data['name']);
        }

        unset($data['images'], $data['image_urls']);

        return $data;
    }

    private function syncImages(Product $product, Request $request): void
    {
        $hasFiles = $request->hasFile('images');
        $hasUrls = $request->exists('image_urls');

        if (!$hasFiles && !$hasUrls) {
            return;
        }

        if ($hasUrls) {
            $product->images()->delete();
        }

        if ($hasFiles) {
            foreach ($request->file('images') as $index => $file) {
                $path = $file->store('products', 'public');
                $product->images()->create([
                    'image_path' => Storage::disk('public')->url($path),
                    'alt_text' => $product->name,
                    'is_primary' => $index === 0 && $product->images()->count() === 0,
                    'sort_order' => $product->images()->count() + $index,
                ]);
            }
        }

        if ($hasUrls) {
            $urls = array_values(array_filter(
                (array) $request->input('image_urls', []),
                fn ($url) => is_string($url) && trim($url) !== ''
            ));

            foreach ($urls as $index => $url) {
                $product->images()->create([
                    'image_path' => $url,
                    'alt_text' => $product->name,
                    'is_primary' => $index === 0,
                    'sort_order' => $index,
                ]);
            }
        }
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
            Product::where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    private function absoluteMediaUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return rtrim((string) config('app.url'), '/') . '/' . ltrim($path, '/');
    }

    private function transform(Product $product, bool $detailed = false): array
    {
        $images = $product->images
            ->sortBy('sort_order')
            ->values()
            ->map(fn ($img) => [
                'id' => $img->id,
                'url' => $this->absoluteMediaUrl($img->image_path),
                'alt' => $img->alt_text,
                'is_primary' => $img->is_primary,
            ]);

        $data = [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'price' => (float) $product->price,
            'compare_at_price' => $product->compare_at_price
                ? (float) $product->compare_at_price
                : null,
            'stock' => $product->stock,
            'in_stock' => $product->stock > 0,
            'is_active' => $product->is_active,
            'color' => $product->color,
            'size' => $product->size,
            'dress_style' => $product->dress_style,
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => $product->category->name,
                'slug' => $product->category->slug,
            ] : null,
            'thumbnail' => $images->firstWhere('is_primary')['url']
                ?? $images->first()['url']
                ?? null,
            'images' => $images,
            'average_rating' => $product->average_rating !== null
                ? round((float) $product->average_rating, 1)
                : null,
            'reviews_count' => (int) ($product->reviews_count ?? 0),
        ];

        if ($detailed) {
            $data['description'] = $product->description;
        }

        return $data;
    }
}
