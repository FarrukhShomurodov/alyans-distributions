@extends('admin.layouts.app')

@section('title')
    <title>ALYANS DISTRIBUTIONS — Новый шаблон</title>
@endsection

@section('content')
    <div class="py-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold text-slate-800">Новый шаблон ответа</h1>
            <a href="{{ route('chat-templates.index') }}" class="text-sm text-slate-500 hover:text-slate-800">← Назад</a>
        </div>

        <form action="{{ route('chat-templates.store') }}" method="POST"
              class="bg-white dark:bg-navy-800 rounded-2xl shadow-sm p-6 space-y-5">
            @csrf
            @include('admin.chat-templates._form', ['submitLabel' => 'Создать'])
        </form>
    </div>
@endsection
