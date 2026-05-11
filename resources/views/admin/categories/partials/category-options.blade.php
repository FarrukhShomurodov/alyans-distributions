@php($level = $level ?? 0)
@php($excludedCategoryIds = $excludedCategoryIds ?? collect())

@foreach($categories as $category)
    @continue($excludedCategoryIds->contains($category->id))
    <option value="{{ $category->id }}"
            @selected(old('parent_id', $selectedParentId ?? null) == $category->id)>
        {{ str_repeat('— ', $level) }}{{ $category->name }}
    </option>

    @if(($category->childrenRecursive ?? collect())->isNotEmpty())
        @include('admin.categories.partials.category-options', [
            'categories' => $category->childrenRecursive,
            'level' => $level + 1,
            'selectedParentId' => $selectedParentId ?? null,
            'excludedCategoryIds' => $excludedCategoryIds
        ])
    @endif
@endforeach
