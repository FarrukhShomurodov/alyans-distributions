@foreach($children as $child)
    @if($child->childrenRecursive->count())
        <div class="cat-list__row cat-list__row--parent js-cat-toggle" data-cat="{{ $child->id }}">
            <span>{{ $child->name }}</span>
            <span class="cat-list__chevron"><i data-lucide="chevron-right"></i></span>
        </div>
        <div class="cat-list__children" id="cat-children-{{ $child->id }}" style="display:none">
            @include('webapp.partials.category-list-recursive', ['children' => $child->childrenRecursive])
        </div>
    @else
        <a href="{{ route('webapp.category.products', $child) }}" class="cat-list__row">
            <span>{{ $child->name }}</span>
        </a>
    @endif
@endforeach
