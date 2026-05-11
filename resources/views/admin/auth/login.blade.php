<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0"/>

    <title>ALYANS DISTRIBUTIONS - Вход</title>
{{--    <link rel="icon" type="image/png" href="{{ asset('vendor/images/favicon.png') }}"/>--}}

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
          rel="stylesheet"/>

    <style>
        @keyframes slide-in {
            from {
                transform: translateX(20px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes fade-out {
            to {
                opacity: 0;
                transform: translateX(20px);
            }
        }

        .animate-slide-in {
            animation: slide-in 0.3s ease-out;
        }

        .animate-fade-out {
            animation: fade-out 0.3s ease-in forwards;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center bg-slate-50 dark:bg-navy-900 font-[Inter]">

<!-- 🔴 Уведомления ошибок -->
@if ($errors->any())
    <div class="fixed top-4 right-4 space-y-2 z-[10050]">
        @foreach ($errors->all() as $error)
            <div
                class="flex items-center justify-between bg-red-500 text-white px-4 py-3 rounded-xl shadow-lg animate-slide-in"
                role="alert">
                <div class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         fill="none" viewBox="0 0 24 24"
                         stroke-width="2" stroke="currentColor"
                         class="w-5 h-5 mr-2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 9v2m0 4h.01m-6.938 4h13.856A2.062 2.062 0 0021 16.938V7.062A2.062 2.062 0 0018.938 5H5.062A2.062 2.062 0 003 7.062v9.876A2.062 2.062 0 005.062 19z"/>
                    </svg>
                    <span>{{ $error }}</span>
                </div>

                <button type="button"
                        class="ml-3 text-white/80 hover:text-white focus:outline-none"
                        onclick="this.parentElement.classList.add('animate-fade-out'); setTimeout(() => this.parentElement.remove(), 300);">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         fill="none" viewBox="0 0 24 24"
                         stroke-width="2" stroke="currentColor"
                         class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        @endforeach
    </div>
@endif

<!-- 🧩 Контейнер страницы -->
<main class="w-full max-w-md mx-auto bg-white dark:bg-navy-800 rounded-2xl shadow-xl p-8">
    <div class="text-center mb-6">
        {{--        <img class="mx-auto w-16" src="{{ asset('vendor/images/app-logo.svg') }}" alt="logo"/>--}}
        <h2 class="mt-4 text-2xl font-semibold text-slate-700 dark:text-navy-100">Добро пожаловать</h2>
        <p class="text-slate-400 dark:text-navy-300">Введите свои данные, чтобы начать</p>
    </div>

    <form action="{{ route('dashboard.login') }}" method="post" class="space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-medium text-slate-600 dark:text-navy-200 mb-1">
                Имя пользователя
            </label>
            <input
                class="w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition dark:border-navy-450 dark:focus:border-accent"
                placeholder="Введите имя пользователя"
                name="login"
                type="text"
                required
            />
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-600 dark:text-navy-200 mb-1">
                Пароль
            </label>
            <input
                class="w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition dark:border-navy-450 dark:focus:border-accent"
                placeholder="Введите пароль"
                name="password"
                type="password"
                required
            />
        </div>

        <div class="flex items-center justify-between">
            <label class="inline-flex items-center space-x-2">
                <input type="checkbox"
                       class="rounded border-slate-400 text-blue-600 focus:ring-blue-500 dark:text-accent"/>
                <span class="text-sm text-slate-600 dark:text-navy-200">Запомнить</span>
            </label>
        </div>

        <button
            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-lg shadow-sm transition focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 dark:bg-accent dark:hover:bg-accent-focus">
            Войти
        </button>
    </form>
</main>

<!-- ⏳ Автозакрытие ошибок -->
<script>
    setTimeout(() => {
        document.querySelectorAll('[role="alert"]').forEach(el => {
            el.classList.add('animate-fade-out');
            setTimeout(() => el.remove(), 300);
        });
    }, 4000);
</script>

</body>
</html>
