<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductSize;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminSizeController extends Controller
{
    public function index()
    {
        $sizes = ProductSize::orderBy('sort_order')->orderBy('name')->paginate(30);

        return view('admin.sizes.index', compact('sizes'));
    }

    public function create()
    {
        return view('admin.sizes.form', [
            'size' => new ProductSize(['is_active' => true, 'sort_order' => 0]),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        ProductSize::create($this->validated($request));

        return redirect()->route('admin.sizes.index')->with('success', 'Size created.');
    }

    public function edit(ProductSize $size)
    {
        return view('admin.sizes.form', [
            'size' => $size,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, ProductSize $size)
    {
        $data = $this->validated($request, $size);
        $oldName = $size->name;
        $size->update($data);

        if ($oldName !== $data['name']) {
            Product::where('size', $oldName)->update(['size' => $data['name']]);
        }

        return redirect()->route('admin.sizes.index')->with('success', 'Size updated.');
    }

    public function destroy(ProductSize $size)
    {
        if (Product::where('size', $size->name)->exists()) {
            return back()->with('error', 'Size is used by products. Reassign products first.');
        }

        $size->delete();

        return redirect()->route('admin.sizes.index')->with('success', 'Size deleted.');
    }

    private function validated(Request $request, ?ProductSize $size = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('product_sizes', 'name')->ignore($size?->id)],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        return [
            'name' => strip_tags($data['name']),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
