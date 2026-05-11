@extends('admin.layouts.app')

@section('title')
    <title>ALYANS DISTRIBUTIONS — Редактирование администратора</title>
@endsection

@section('content')
    <div class="py-8">
        <!-- Заголовок -->
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold text-slate-800 dark:text-navy-50">
                Редактирование администратора
            </h1>

            <a href="{{ route('admins.index') }}"
               class="text-sm text-slate-500 hover:text-slate-700 dark:text-navy-200 transition">
                ← Назад к списку
            </a>
        </div>

        <!-- Форма -->
        <form action="{{ route('admins.update', $admin->id) }}" method="post"
              class="bg-white dark:bg-navy-700 rounded-2xl shadow-md dark:shadow-[0_0_10px_rgba(0,0,0,0.2)] p-6 space-y-5 transition">
            @csrf
            @method('PUT')

            <h2 class="text-lg font-semibold text-slate-800  mb-4">
                Информация об администраторе
            </h2>

            <div class="grid sm:grid-cols-2 gap-5">
                <!-- login -->
                <div>
                    <label class="block text-sm font-medium text-slate-700  mb-1">
                        login
                    </label>
                    <input type="text" name="login" value="{{ old('login', $admin->login) }}" required
                           class="w-full rounded-lg border border-slate-700 dark:border-navy-500 bg-slate-50 dark:bg-navy-800
                                  px-3 py-2 text-slate-800  text-sm focus:border-blue-500
                                  focus:ring-1 focus:ring-blue-500 outline-none transition">
                </div>

                <!-- Пароль -->
                <div>
                    <label class="block text-sm font-medium text-slate-700  mb-1">
                        Новый пароль <span class="text-xs text-slate-400">(оставьте пустым, чтобы не менять)</span>
                    </label>
                    <input type="password" name="password"
                           class="w-full rounded-lg border border-slate-700 dark:border-navy-500 bg-slate-50 dark:bg-navy-800
                                  px-3 py-2 text-slate-800  text-sm focus:border-blue-500
                                  focus:ring-1 focus:ring-blue-500 outline-none transition">
                </div>

                <!-- Роль -->
                <div>
                    <label class="block text-sm font-medium text-slate-700  mb-1">
                        Роль
                    </label>
                    <select name="role_id" required
                            class="w-full rounded-lg border border-slate-700 dark:border-navy-500 bg-slate-50 dark:bg-navy-800
                                   px-3 py-2 text-slate-800  text-sm focus:border-blue-500
                                   focus:ring-1 focus:ring-blue-500 outline-none transition">
                        @foreach($roles as $id => $name)
                            <option value="{{ $id }}" @selected($admin->roles->contains('name', $name))>
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

                <!-- ID -->
                <div>
                    <label class="block text-sm font-medium text-slate-700  mb-1">
                        ID администратора
                    </label>
                    <input type="text" value="{{ $admin->id }}" readonly
                           class="w-full rounded-lg border border-slate-200 dark:border-navy-600 bg-slate-100 dark:bg-navy-800
                                  px-3 py-2 text-slate-500 dark:text-slate-400 text-sm cursor-not-allowed">
                </div>
            </div>

            <div class="pt-4 flex justify-end">
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm px-5 py-2.5 rounded-lg shadow-md transition">
                    Сохранить изменения
                </button>
            </div>
        </form>
    </div>
@endsection
