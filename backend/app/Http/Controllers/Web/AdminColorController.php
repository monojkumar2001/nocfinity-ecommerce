<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductColor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminColorController extends Controller
{
    public function index()
    {
        $colors = ProductColor::orderBy('sort_order')->orderBy('name')->paginate(30);

        return view('admin.colors.index', compact('colors'));
    }

    public function create()
    {
        return view('admin.colors.form', [
            'color' => new ProductColor(['is_active' => true, 'sort_order' => 0, 'hex' => '#000000']),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        ProductColor::create($this->validated($request));

        return redirect()->route('admin.colors.index')->with('success', 'Color created.');
    }

    public function edit(ProductColor $color)
    {
        return view('admin.colors.form', [
            'color' => $color,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, ProductColor $color)
    {
        $data = $this->validated($request, $color);
        $oldName = $color->name;
        $color->update($data);

        if ($oldName !== $data['name']) {
            Product::where('color', $oldName)->update(['color' => $data['name']]);
        }

        return redirect()->route('admin.colors.index')->with('success', 'Color updated.');
    }

    public function destroy(ProductColor $color)
    {
        if (Product::where('color', $color->name)->exists()) {
            return back()->with('error', 'Color is used by products. Reassign products first.');
        }

        $color->delete();

        return redirect()->route('admin.colors.index')->with('success', 'Color deleted.');
    }

    private function validated(Request $request, ?ProductColor $color = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('product_colors', 'name')->ignore($color?->id)],
            'hex' => ['nullable', 'string', 'max:7', 'regex:/^#?[0-9A-Fa-f]{6}$/'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $hex = $data['hex'] ?? null;
        if ($hex && !str_starts_with($hex, '#')) {
            $hex = '#' . $hex;
        }

        return [
            'name' => strip_tags($data['name']),
            'hex' => $hex ? strtoupper($hex) : null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
