@extends('admin.layouts.app')

@section('title')
    <title>ALYANS DISTRIBUTIONS — Отпуска</title>
@endsection

@section('content')
    <div class="flex justify-between items-center space-x-1 py-5">
        <h1 class="text-xl font-medium text-slate-800 dark:text-navy-50 lg:text-2xl">Отпуска сотрудников</h1>

        <button onclick="window.location.href='{{ route('vacations.create') }}'"
                class="btn btn-sm min-w-[7rem] h-[45px] rounded-full bg-blue-600 font-medium text-white hover:bg-blue-800 focus:bg-blue-700">
            Создать
        </button>
    </div>

    <div class="card">
        <!-- Заголовки -->
        <div class="flex items-center justify-between px-4 py-3 border-b text-sm text-slate-700 dark:text-navy-100">
            <div class="flex items-center space-x-10">
                <div class="w-16 font-medium text-[16px]">ID</div>
                <div class="w-56 font-medium text-[16px]">Сотрудник</div>
                <div class="w-40 font-medium text-[16px]">Тип отпуска</div>
                <div class="w-40 font-medium text-[16px]">Период</div>
                <div class="w-32 font-medium text-[16px]">Статус</div>
            </div>
            <div class="w-32 flex justify-center font-medium text-[16px]">Действия</div>
        </div>

        @forelse($vacations as $vacation)
            <div
                class="flex items-center justify-between px-4 py-3 border-b text-sm text-slate-700 dark:text-navy-100 hover:bg-slate-100 dark:hover:bg-navy-600">
                <div class="flex items-center space-x-10">
                    <div class="w-16 font-medium">#{{ $vacation->id }}</div>

                    <div class="w-56 font-semibold text-blue-600">
                        {{ $vacation->employee->full_name ?? '—' }}
                    </div>

                    <div class="w-40 capitalize">
                        @switch($vacation->type)
                            @case('annual') Ежегодный @break
                            @case('unpaid') Без содержания @break
                            @case('sick') Больничный @break
                            @case('maternity') Декретный @break
                        @endswitch
                    </div>

                    <div class="w-40">
                        {{ $vacation->start_date->format('d.m.Y') }} —
                        {{ $vacation->end_date->format('d.m.Y') }}
                    </div>

                    <div class="w-32">
                        @if($vacation->status === 'approved')
                            <span class="text-green-600 font-semibold">Одобрен</span>
                        @elseif($vacation->status === 'rejected')
                            <span class="text-red-600 font-semibold">Отклонён</span>
                        @else
                            <span class="text-yellow-600 font-semibold">Ожидает</span>
                        @endif
                    </div>
                </div>

                <div class="w-32 flex justify-end space-x-3">
                    <!-- Одобрить -->
                    @if($vacation->status === 'pending')
                        <form action="{{ route('vacations.approve', $vacation) }}" method="post">
                            @csrf
                            <button type="submit" title="Одобрить"
                                    class="bg-green-600 hover:bg-green-700 text-white p-2 rounded">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                     viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M5 13l4 4L19 7"/>
                                </svg>
                            </button>
                        </form>

                        <form action="{{ route('vacations.reject', $vacation) }}" method="post">
                            @csrf
                            <button type="submit" title="Отклонить"
                                    class="bg-red-600 hover:bg-red-700 text-white p-2 rounded">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                     viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </form>
                    @endif

                    <!-- Редактировать -->
                    <button title="Редактировать"
                            onclick="window.location.href='{{ route('vacations.edit', $vacation) }}'"
                            class="bg-blue-600 hover:bg-blue-700 text-white p-2 rounded">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                    </button>
                </div>
            </div>
        @empty
            <div class="text-center text-slate-500 py-6">Нет данных</div>
        @endforelse
    </div>

    <x-pagination :paginator="$vacations"/>
@endsection
