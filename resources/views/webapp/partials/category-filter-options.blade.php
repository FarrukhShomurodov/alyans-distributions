@php($level = $level ?? 0)

@foreach($categories as $category)
    <label class="filters__category" style="margin-left: {{ $level * 14 }}px;">
        <input type="radio"
               name="category_id"
               value="{{ $category->id }}"
               @checked((int) $selectedCategoryId === (int) $category->id)>
        <span>{{ $category->name }}</span>
    </label>

    @if(($category->childrenRecursive ?? collect())->isNotEmpty())
        @include('webapp.partials.category-filter-options', [
            'categories' => $category->childrenRecursive,
            'level' => $level + 1,
            'selectedCategoryId' => $selectedCategoryId
        ])
    @endif
@endforeach
