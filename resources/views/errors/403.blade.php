<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Доступ запрещен</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<div class="min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8">
        <div class="text-center">
            <!-- Иконка или логотип -->
            <div class="mx-auto w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
            </div>

            <!-- Заголовок -->
            <h1 class="text-2xl font-bold text-gray-900 mb-2">403 - Доступ запрещен</h1>

            <!-- Описание -->
            <p class="text-gray-600 mb-6">
                У вас недостаточно прав для доступа к этой странице.
            </p>

            <!-- Кнопки действий -->
            <div class="space-y-3">
                <!-- Кнопка выхода -->
                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-md transition duration-200">
                        Выйти из системы
                    </button>
                </form>

                <!-- Кнопка назад -->
                <button onclick="window.history.back()" class="w-full bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-md transition duration-200">
                    Назад
                </button>

                <!-- Ссылка на главную -->
                <a href="{{ url('/') }}" class="block w-full text-center text-blue-600 hover:text-blue-800 font-medium py-2">
                    На главную
                </a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
