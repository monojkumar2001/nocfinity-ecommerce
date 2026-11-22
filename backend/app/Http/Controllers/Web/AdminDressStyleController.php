<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DressStyle;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminDressStyleController extends Controller
{
    public function index()
    {
        $styles = DressStyle::orderBy('sort_order')->orderBy('name')->paginate(30);

        return view('admin.dress-styles.index', compact('styles'));
    }

    public function create()
    {
        return view('admin.dress-styles.form', [
            'style' => new DressStyle(['is_active' => true, 'sort_order' => 0]),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        DressStyle::create($data);

        return redirect()->route('admin.dress-styles.index')->with('success', 'Dress style created.');
    }

    public function edit(DressStyle $dress_style)
    {
        return view('admin.dress-styles.form', [
            'style' => $dress_style,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, DressStyle $dress_style)
    {
        $data = $this->validated($request, $dress_style);
        $oldName = $dress_style->name;
        $dress_style->update($data);

        if ($oldName !== $data['name']) {
            Product::where('dress_style', $oldName)->update(['dress_style' => $data['name']]);
        }

        return redirect()->route('admin.dress-styles.index')->with('success', 'Dress style updated.');
    }

    public function destroy(DressStyle $dress_style)
    {
        if (Product::where('dress_style', $dress_style->name)->exists()) {
            return back()->with('error', 'Dress style is used by products. Reassign products first.');
        }

        $dress_style->delete();

        return redirect()->route('admin.dress-styles.index')->with('success', 'Dress style deleted.');
    }

    private function validated(Request $request, ?DressStyle $style = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('dress_styles', 'name')->ignore($style?->id)],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $name = strip_tags($data['name']);

        return [
            'name' => $name,
            'slug' => Str::slug($name) ?: 'style',
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
