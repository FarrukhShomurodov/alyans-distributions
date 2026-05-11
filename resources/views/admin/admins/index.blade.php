@extends('admin.layouts.app')

@section('title')
    <title>ALYANS DISTRIBUTIONS — Администраторы</title>
@endsection

@section('content')
    <div class="flex justify-between items-center py-6">
        <h1 class="text-2xl font-semibold text-slate-800 dark:text-navy-50">Администраторы</h1>

        <button onclick="window.location.href='{{ route('admins.create') }}'"
                class="rounded-full bg-blue-600 px-6 py-2.5 text-white font-medium hover:bg-blue-700 focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 transition">
            Добавить администратора
        </button>
    </div>

    <!-- Статистика -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-navy-800 rounded-xl shadow-sm border border-slate-200 dark:border-navy-600 p-4">
            <div class="text-sm text-slate-500 dark:text-slate-800">Всего администраторов</div>
            <div class="text-2xl font-semibold text-slate-800  mt-1">
                {{ $admins->total() }}
            </div>
        </div>

        <div
            class="bg-emerald-50 dark:bg-emerald-900/20 rounded-xl shadow-sm border border-emerald-200 dark:border-emerald-700 p-4">
            <div class="text-sm text-emerald-600 ">Роль — Admin</div>
            <div class="text-2xl font-semibold text-emerald-700  mt-1">
                {{ \App\Models\Admin::role('admin')->count() }}
            </div>
        </div>

        <div
            class="bg-amber-50 dark:bg-amber-900/20 rounded-xl shadow-sm border border-amber-200 dark:border-amber-700 p-4">
            <div class="text-sm text-amber-600 dark:text-amber-500">Роль — Manager</div>
            <div class="text-2xl font-semibold text-amber-700 dark:text-amber-500 mt-1">
                {{ \App\Models\Admin::role('manager')->count() }}
            </div>
        </div>

        <div
            class="bg-blue-50 dark:bg-blue-900/20 rounded-xl shadow-sm border border-blue-200 dark:border-blue-700 p-4">
            <div class="text-sm text-blue-600 dark:text-blue-500">Роль — Товаровед</div>
            <div class="text-2xl font-semibold text-blue-700 dark:text-blue-500 mt-1">
                {{ \App\Models\Admin::role('commodity_expert')->count() }}
            </div>
        </div>
    </div>

    <!-- Таблица -->
    <div
        class="overflow-hidden rounded-xl border border-slate-200 dark:border-navy-600 shadow-sm bg-white dark:bg-navy-800">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-navy-600">
            <thead class="bg-slate-100 dark:bg-navy-700">
            <tr>
                <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600 dark:text-navy-100">ID</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600 dark:text-navy-100">Login</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600 dark:text-navy-100">Роль</th>
                <th class="px-4 py-3 text-center text-sm font-semibold text-slate-600 dark:text-navy-100">Действия</th>
            </tr>
            </thead>

            <tbody class="divide-y divide-slate-200 dark:divide-navy-600 text-slate-700 dark:text-navy-100">
            @foreach($admins as $admin)
                <tr class="hover:bg-slate-50 dark:hover:bg-navy-700 transition">
                    <td class="px-4 py-3 text-sm font-medium">{{ $admin->id }}</td>

                    <td class="px-4 py-3 text-sm font-semibold text-slate-800 ">
                        {{ $admin->login }}
                    </td>

                    <td class="px-4 py-3 text-sm">
                        @if($admin->roles->isNotEmpty())
                            <span
                                class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full
                                {{ $admin->roles->first()->name === 'admin'
                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-800 dark:text-emerald-200'
                                    : 'bg-amber-100 text-amber-500 dark:bg-amber-800 ' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                     stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M5 13l4 4L19 7"/>
                                </svg>
                                {{ ucfirst($admin->roles->first()->name) }}
                            </span>
                        @else
                            <span class="text-xs text-slate-400">—</span>
                        @endif
                    </td>

                    <td class="px-4 py-3 text-center">
                        <div class="flex justify-center space-x-2">
                            <!-- Редактировать -->
                            <button title="Редактировать"
                                    onclick="window.location.href='{{ route('admins.edit', $admin->id) }}'"
                                    class="p-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                     stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                            </button>

                            <!-- Удалить -->
                            <form action="{{ route('admins.destroy', $admin->id) }}" method="POST"
                                  onsubmit="return confirm('Удалить администратора?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="Удалить"
                                        class="p-2 rounded-lg bg-red-600 hover:bg-red-700 text-white transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                         stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-7 0h8"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        <x-pagination :paginator="$admins"/>
    </div>
@endsection
