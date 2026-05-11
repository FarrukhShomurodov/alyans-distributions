@extends('admin.layouts.app')

@section('title')
    <title>ALYANS DISTRIBUTIONS — Редактирование отпуска</title>
@endsection

@section('content')
    <div class="flex items-center space-x-4 py-5 lg:py-6">
        <h2 class="text-xl font-medium text-slate-800 dark:text-navy-50 lg:text-2xl">
            Редактирование отпуска
        </h2>
    </div>

    <!-- Ошибки -->
    <div class="alert-container position-fixed top-0 end-0 p-3" style="z-index: 10050;">
        @if ($errors->any())
            @foreach ($errors->all() as $error)
                <div class="alert alert-solid-danger alert-dismissible d-flex align-items-center" role="alert">
                    <i class="bx bx-error-circle fs-4 me-2"></i>
                    {{ $error }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endforeach
        @endif
    </div>

    <div class="grid grid-cols-12 gap-4 sm:gap-5 lg:gap-6">
        <div class="col-span-12 lg:col-span-12">
            <form action="{{ route('vacations.update', $vacation->id) }}" method="post" id="vacation-form">
                @csrf
                @method('PUT')

                <div class="card">
                    <div class="p-4 sm:p-5">
                        <h2 class="text-xl font-semibold mb-6 text-gray-800">Редактирование отпуска сотрудника</h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-base text-gray-800">

                            <!-- Сотрудник -->
                            <div class="md:col-span-2">
                                <label for="employee_id" class="block mb-2 font-medium">Сотрудник</label>
                                <select name="employee_id" id="employee_id"
                                        required
                                        class="w-full border rounded-full px-5 py-3">
                                    <option value="">Выберите сотрудника</option>
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->id }}"
                                            @selected(old('employee_id', $vacation->employee_id) == $employee->id)>
                                            {{ $employee->full_name ?? ($employee->last_name . ' ' . $employee->first_name) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Дата начала -->
                            <div>
                                <label for="start_date" class="block mb-2 font-medium">Дата начала</label>
                                <input type="date" name="start_date" id="start_date"
                                       value="{{ old('start_date', $vacation->start_date->format('Y-m-d')) }}"
                                       required
                                       class="w-full border rounded-full px-5 py-3">
                            </div>

                            <!-- Дата окончания -->
                            <div>
                                <label for="end_date" class="block mb-2 font-medium">Дата окончания</label>
                                <input type="date" name="end_date" id="end_date"
                                       value="{{ old('end_date', $vacation->end_date->format('Y-m-d')) }}"
                                       required
                                       class="w-full border rounded-full px-5 py-3">
                            </div>

                            <!-- Тип отпуска -->
                            <div class="md:col-span-2">
                                <label for="type" class="block mb-2 font-medium">Тип отпуска</label>
                                <select name="type" id="type"
                                        required
                                        class="w-full border rounded-full px-5 py-3">
                                    <option value="">Выберите тип отпуска</option>
                                    <option value="annual" @selected(old('type', $vacation->type) === 'annual')>Ежегодный оплачиваемый</option>
                                    <option value="unpaid" @selected(old('type', $vacation->type) === 'unpaid')>Без сохранения зарплаты</option>
                                    <option value="sick" @selected(old('type', $vacation->type) === 'sick')>Больничный</option>
                                    <option value="maternity" @selected(old('type', $vacation->type) === 'maternity')>Декретный</option>
                                </select>
                            </div>

                            <!-- Комментарий -->
                            <div class="md:col-span-2">
                                <label for="comment" class="block mb-2 font-medium">Комментарий (необязательно)</label>
                                <textarea name="comment" id="comment" rows="3"
                                          class="w-full border rounded-xl px-5 py-3 resize-none"
                                          placeholder="Например: перенос по семейным обстоятельствам">{{ old('comment', $vacation->reason) }}</textarea>
                            </div>

                            <!-- Текущий статус -->
                            <div class="md:col-span-2">
                                <label for="status" class="block mb-2 font-medium">Статус</label>
                                <select name="status" id="status"
                                        class="w-full border rounded-full px-5 py-3">
                                    <option value="pending" @selected(old('status', $vacation->status) === 'pending')>Ожидает подтверждения</option>
                                    <option value="approved" @selected(old('status', $vacation->status) === 'approved')>Одобрен</option>
                                    <option value="rejected" @selected(old('status', $vacation->status) === 'rejected')>Отклонён</option>
                                </select>
                            </div>

                            <!-- Причина отклонения -->
                            <div id="rejection-block" class="md:col-span-2" style="display: none;">
                                <label for="rejection_reason" class="block mb-2 font-medium">Причина отклонения</label>
                                <textarea name="rejection_reason" id="rejection_reason" rows="2"
                                          class="w-full border rounded-xl px-5 py-3 resize-none"
                                          placeholder="Укажите причину отклонения">{{ old('rejection_reason', $vacation->rejection_reason) }}</textarea>
                            </div>

                            <!-- Предварительный расчёт -->
                            <div class="md:col-span-2">
                                <label class="block mb-2 font-medium">Количество оплачиваемых дней</label>
                                <input type="text" id="days_preview"
                                       class="w-full border rounded-full px-5 py-3 bg-gray-100"
                                       readonly
                                       value="{{ $vacation->days_count }} дн.">
                            </div>

                        </div>

                        <div class="w-full mt-6 flex justify-end">
                            <button type="submit"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                Сохранить изменения
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const startInput = document.getElementById('start_date');
            const endInput = document.getElementById('end_date');
            const daysField = document.getElementById('days_preview');
            const statusSelect = document.getElementById('status');
            const rejectionBlock = document.getElementById('rejection-block');

            function toggleRejection() {
                rejectionBlock.style.display = statusSelect.value === 'rejected' ? 'block' : 'none';
            }

            async function updateDays() {
                const start = startInput.value;
                const end = endInput.value;
                if (!start || !end) return;

                try {
                    const response = await fetch(`{{ route('vacations.calculateDays') }}?start=${start}&end=${end}`);
                    const data = await response.json();
                    daysField.value = data.days ? `${data.days} дн.` : 'Ошибка расчёта';
                } catch (e) {
                    daysField.value = 'Ошибка';
                }
            }

            startInput.addEventListener('change', updateDays);
            endInput.addEventListener('change', updateDays);
            statusSelect.addEventListener('change', toggleRejection);

            toggleRejection();
        });
    </script>
@endsection
