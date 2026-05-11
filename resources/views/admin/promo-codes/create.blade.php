@extends('admin.layouts.app')

@section('title')
    <title>ALYANS DISTRIBUTIONS — Создание промокода</title>
@endsection

@section('content')
    <div class="py-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold text-slate-800 dark:text-navy-50">Создание промокода</h1>
            <a href="{{ route('promo-codes.index') }}" class="text-sm text-slate-500 hover:text-slate-700 transition">
                ← Назад к списку
            </a>
        </div>

        <form action="{{ route('promo-codes.store') }}" method="post"
              class="bg-white dark:bg-navy-700 rounded-2xl shadow-md p-6 space-y-5">
            @csrf

            @include('admin.promo-codes.partials.form')

            <div class="pt-4 flex justify-end">
                <button type="submit"
                        class="bg-purple-600 hover:bg-purple-700 text-white font-medium text-sm px-5 py-2.5 rounded-lg shadow-md transition">
                    Создать промокод
                </button>
            </div>
        </form>
    </div>
@endsection
