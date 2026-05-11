<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use Illuminate\Http\Request;

class AttributeController
{
    public function index()
    {
        $attributes = Attribute::query()
            ->with('category:id,name')
            ->orderBy('id')
            ->get();

        return view('admin.attributes.index', compact('attributes'));
    }

    public function create()
    {
        $categories = Category::query()->select(['id', 'name'])->orderBy('name')->get();

        return view('admin.attributes.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:string,number',
            'category_id' => 'required|integer|exists:categories,id',
        ]);

        $attribute = Attribute::create($request->only(['name', 'type', 'category_id']));

//        return redirect()->route('attributes.index')->with('success', 'Attribute created successfully.');
        return redirect()->route('attributes.edit', $attribute->id);
    }

    public function show(Attribute $attribute)
    {
        return view('admin.attributes.show', compact('attribute'));
    }

    public function edit(Attribute $attribute)
    {
        $attribute->load('values');
        $categories = Category::query()->select(['id', 'name'])->orderBy('name')->get();

        return view('admin.attributes.edit', compact('attribute', 'categories'));
    }

    public function update(Request $request, Attribute $attribute)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:string,number',
            'category_id' => 'required|integer|exists:categories,id',
        ]);

        $attribute->update($request->only(['name', 'type', 'category_id']));

        return redirect()->route('attributes.index')->with('success', 'Attribute updated successfully.');
    }

    public function destroy(Attribute $attribute)
    {
        $attribute->delete();

        return redirect()->route('attributes.index')->with('success', 'Attribute deleted successfully.');
    }

    public function storeValue(Request $request, Attribute $attribute)
    {
        $rules = [
            'value' => ['required', 'string', 'max:255'],
        ];

        if ($attribute->type === 'number') {
            $rules['value'][] = 'numeric';
        }

        $request->validate($rules);

        $attribute->values()->firstOrCreate([
            'value' => (string)$request->input('value'),
        ]);

        return redirect()->route('attributes.edit', $attribute)->with('success', 'Attribute value added successfully.');
    }

    public function destroyValue(Attribute $attribute, AttributeValue $value)
    {
        if ($value->attribute_id !== $attribute->id) {
            abort(404);
        }

        $value->delete();

        return redirect()->route('attributes.edit', $attribute)->with('success', 'Attribute value deleted successfully.');
    }
}
