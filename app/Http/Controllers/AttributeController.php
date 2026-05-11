<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AttributeController
{
    public function index(): JsonResponse
    {
        $attributes = Attribute::all();
        return response()->json($attributes);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'string|in:string,number',
            'category_id' => 'required|integer|exists:categories,id',
        ]);

        $attribute = Attribute::create($request->only(['name', 'type', 'category_id']));
        return response()->json($attribute, 201);
    }

    public function show(Attribute $attribute): JsonResponse
    {
        return response()->json($attribute);
    }

    public function update(Request $request, Attribute $attribute): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'string|in:string,number',
            'category_id' => 'required|integer|exists:categories,id',
        ]);

        $attribute->update($request->only(['name', 'type', 'category_id']));
        return response()->json($attribute);
    }

    public function destroy(Attribute $attribute): JsonResponse
    {
        $attribute->delete();
        return response()->json(['message' => 'Attribute deleted']);
    }
}