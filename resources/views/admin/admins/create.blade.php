@extends('admin.layouts.app')

@section('title')
    <title>ALYANS DISTRIBUTIONS — Добавление администратора</title>
@endsection

@section('content')
    <div class="py-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold text-slate-800 dark:text-navy-50">
                Добавление администратора
            </h1>

            <a href="{{ route('admins.index') }}"
               class="text-sm text-slate-500 hover:text-slate-700 dark:text-navy-200 transition">
                ← Назад к списку
            </a>
        </div>

        <form action="{{ route('admins.store') }}" method="post"
              class="bg-white dark:bg-navy-700 rounded-2xl shadow-md dark:shadow-[0_0_10px_rgba(0,0,0,0.2)] p-6 space-y-5 transition">
            @csrf

            <h2 class="text-lg font-semibold text-slate-800  mb-4">
                Информация об администраторе
            </h2>

            <div class="grid sm:grid-cols-2 gap-5">
                <!-- login -->
                <div>
                    <label class="block text-sm font-medium text-slate-700  mb-1">Login</label>
                    <input type="text" name="login" value="{{ old('login') }}" required
                           class="w-full rounded-lg border border-slate-300 dark:border-navy-500 bg-slate-50 dark:bg-navy-800
                                  px-3 py-2 text-slate-800  text-sm focus:border-blue-500
                                  focus:ring-1 focus:ring-blue-500 outline-none transition">
                </div>

                <!-- Пароль -->
                <div>
                    <label class="block text-sm font-medium text-slate-700  mb-1">Пароль</label>
                    <input type="password" name="password" required
                           class="w-full rounded-lg border border-slate-300 dark:border-navy-500 bg-slate-50 dark:bg-navy-800
                                  px-3 py-2 text-slate-800  text-sm focus:border-blue-500
                                  focus:ring-1 focus:ring-blue-500 outline-none transition">
                </div>

                <!-- Роль -->
                <div>
                    <label class="block text-sm font-medium text-slate-700  mb-1">Роль</label>
                    <select name="role_id" required
                            class="w-full rounded-lg border border-slate-300 dark:border-navy-500 bg-slate-50 dark:bg-navy-800
                                   px-3 py-2 text-slate-800  text-sm focus:border-blue-500
                                   focus:ring-1 focus:ring-blue-500 outline-none transition">
                        <option value="">Выберите роль</option>
                        @foreach($roles as $id => $name)
                            <option value="{{ $id }}">
                                @switch($name)
                                    @case('admin')
                                        Администратор
                                        @break
                                    @case('manager')
                                        Менеджер
                                        @break
                                    @case('commodity_expert')
                                        Товаровед
                                        @break
                                    @default
                                        {{ ucfirst($name) }}
                                @endswitch
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="pt-4 flex justify-end">
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm px-5 py-2.5 rounded-lg shadow-md transition">
                    Сохранить администратора
                </button>
            </div>
        </form>
    </div>
@endsection
