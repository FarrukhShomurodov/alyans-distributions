@extends('admin.layouts.app')

@section('title')
    <title>ALYANS DISTRIBUTIONS — Атрибуты</title>
@endsection

@section('content')
    <div class="flex justify-between items-center py-6">
        <h1 class="text-2xl font-semibold text-slate-800 dark:text-navy-50">Атрибуты</h1>

        <a href="{{ route('attributes.create') }}"
           class="rounded-full bg-blue-600 px-6 py-2.5 text-white font-medium hover:bg-blue-700">
            Добавить атрибут
        </a>
    </div>

    <div class="bg-white dark:bg-navy-800 rounded-xl shadow-sm border border-slate-200 dark:border-navy-600 p-4">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-navy-600">
            <thead class="bg-slate-100 dark:bg-navy-700">
            <tr>
                <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600 dark:text-navy-100">ID</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600 dark:text-navy-100">Название</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600 dark:text-navy-100">Категория</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600 dark:text-navy-100">Тип</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600 dark:text-navy-100">Действия</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-navy-600">
            @foreach($attributes as $attribute)
                <tr>
                    <td class="px-4 py-3 text-sm text-slate-800 dark:text-navy-50">{{ $attribute->id }}</td>
                    <td class="px-4 py-3 text-sm text-slate-800 dark:text-navy-50">{{ $attribute->name }}</td>
                    <td class="px-4 py-3 text-sm text-slate-800 dark:text-navy-50">{{ $attribute->category?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm text-slate-800 dark:text-navy-50">{{ $attribute->type }}</td>
                    <td class="px-4 py-3 text-sm">
                        <a href="{{ route('attributes.edit', $attribute) }}" class="text-blue-600 hover:text-blue-800">Редактировать</a>
                        <form action="{{ route('attributes.destroy', $attribute) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800 ml-2">Удалить</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection