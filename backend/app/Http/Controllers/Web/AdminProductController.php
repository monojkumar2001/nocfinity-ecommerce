<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Support\ProductFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminProductController extends Controller
{
    public function index(Request $request)
    {
        $q = Product::with(['category', 'images'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%' . $request->string('search') . '%';
                $query->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('sku', 'like', $term);
                });
            })
            ->orderByDesc('id');

        $products = $q->paginate(12)->withQueryString();

        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        return view('admin.products.form', [
            'product' => new Product(['is_active' => true, 'stock' => 0]),
            'categories' => $this->categoryOptions(),
            'colors' => ProductFilters::colorNames(),
            'sizes' => ProductFilters::sizeNames(),
            'dressStyles' => ProductFilters::dressStyleNames(),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name']);

        $product = Product::create($data);
        $this->syncImages($product, $request);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product created successfully.');
    }

    public function edit(Product $product)
    {
        $product->load('images');

        return view('admin.products.form', [
            'product' => $product,
            'categories' => $this->categoryOptions(),
            'colors' => ProductFilters::colorNames(),
            'sizes' => ProductFilters::sizeNames(),
            'dressStyles' => ProductFilters::dressStyleNames(),
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validated($request, $product);

        if ($data['name'] !== $product->name) {
            $data['slug'] = $this->uniqueSlug($data['name'], $product->id);
        }

        $product->update($data);
        $this->syncImages($product, $request);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        $product->images()->delete();
        $product->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product deleted.');
    }

    private function validated(Request $request, ?Product $product = null): array
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
            'image_url' => ['nullable', 'url', 'max:500'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['description'] = isset($data['description'])
            ? strip_tags($data['description'])
            : null;
        $data['name'] = strip_tags($data['name']);
        $data['color'] = $data['color'] ?? null;
        $data['size'] = $data['size'] ?? null;
        $data['dress_style'] = $data['dress_style'] ?? null;

        unset($data['images'], $data['image_url']);

        return $data;
    }

    private function syncImages(Product $product, Request $request): void
    {
        if ($request->filled('image_url')) {
            $product->images()->delete();
            $product->images()->create([
                'image_path' => $request->string('image_url')->toString(),
                'alt_text' => $product->name,
                'is_primary' => true,
                'sort_order' => 0,
            ]);
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $file) {
                $path = $file->store('products', 'public');
                $url = Storage::disk('public')->url($path);
                if (!str_starts_with($url, 'http')) {
                    $url = rtrim(config('app.url'), '/') . $url;
                }

                $product->images()->create([
                    'image_path' => $url,
                    'alt_text' => $product->name,
                    'is_primary' => $product->images()->count() === 0 && $index === 0,
                    'sort_order' => $product->images()->count() + $index,
                ]);
            }
        }
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'product';
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

    private function categoryOptions()
    {
        return Category::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'parent_id']);
    }
}
