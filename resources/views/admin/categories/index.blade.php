@extends('admin.layouts.app')

@section('title')
    <title>ALYANS DISTRIBUTIONS — Категории</title>
@endsection

@section('content')
    <div class="flex justify-between items-center py-6">
        <h1 class="text-2xl font-semibold text-slate-800 dark:text-navy-50">Категории товаров</h1>

        <button onclick="window.location.href='{{ route('categories.create') }}'"
                class="rounded-full bg-blue-600 px-6 py-2.5 text-white font-medium hover:bg-blue-700 focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 transition">
            Добавить категорию
        </button>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-navy-800 rounded-xl shadow-sm border border-slate-200 dark:border-navy-600 p-4">
            <div class="text-sm text-slate-500">Всего категорий</div>
            <div class="text-2xl font-semibold text-slate-800 mt-1">{{ $stats['total'] }}</div>
        </div>

        <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-xl shadow-sm border border-emerald-200 dark:border-emerald-700 p-4">
            <div class="text-sm text-emerald-600">Активные</div>
            <div class="text-2xl font-semibold text-emerald-700 mt-1">{{ $stats['active'] }}</div>
        </div>

        <div class="bg-rose-50 dark:bg-rose-900/20 rounded-xl shadow-sm border dark:border-rose-400 p-4">
            <div class="text-sm text-rose-600 dark:text-rose-400">Неактивные</div>
            <div class="text-2xl font-semibold text-rose-700 mt-1">{{ $stats['inactive'] }}</div>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-navy-600 shadow-sm bg-white dark:bg-navy-800">
        <div class="p-4 border-b border-slate-200 dark:border-navy-600 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <button onclick="expandAll()" class="text-xs px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-navy-700 text-slate-600 dark:text-navy-200 hover:bg-slate-200 transition">
                    Развернуть все
                </button>
                <button onclick="collapseAll()" class="text-xs px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-navy-700 text-slate-600 dark:text-navy-200 hover:bg-slate-200 transition">
                    Свернуть все
                </button>
            </div>
            <div class="text-sm text-slate-500">
                <i class="fas fa-sitemap mr-1"></i> Древовидная структура
            </div>
        </div>

        <div class="divide-y divide-slate-100 dark:divide-navy-700" id="categories-tree">
            @foreach($categoriesTree as $category)
                @include('admin.categories.partials.category-tree-row', ['category' => $category, 'level' => 0])
            @endforeach
        </div>

        @if($categoriesTree->isEmpty())
            <div class="p-8 text-center text-slate-400">
                <i class="fas fa-folder-open text-4xl mb-3"></i>
                <p>Категории не найдены</p>
                <a href="{{ route('categories.create') }}" class="text-blue-600 hover:underline text-sm mt-2 inline-block">Добавить первую категорию</a>
            </div>
        @endif
    </div>
@endsection

@section('scripts')
<script>
    function toggleChildren(id) {
        const children = document.getElementById('children-' + id);
        const icon = document.getElementById('toggle-icon-' + id);
        if (children) {
            children.classList.toggle('hidden');
            if (icon) {
                icon.classList.toggle('rotate-90');
            }
        }
    }

    function expandAll() {
        document.querySelectorAll('[id^="children-"]').forEach(el => el.classList.remove('hidden'));
        document.querySelectorAll('[id^="toggle-icon-"]').forEach(el => el.classList.add('rotate-90'));
    }

    function collapseAll() {
        document.querySelectorAll('[id^="children-"]').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('[id^="toggle-icon-"]').forEach(el => el.classList.remove('rotate-90'));
    }
</script>
@endsection
