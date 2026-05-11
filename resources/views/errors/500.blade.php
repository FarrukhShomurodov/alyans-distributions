<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0"/>

    <title>HRM - 500</title>

    <link rel="icon" type="image/png" href="{{ asset('public/vendor/images/favicon.png') }}"/>

    <!-- CSS Assets -->
    <link rel="stylesheet" href="{{ asset('public/vendor/css/app.css') }}"/>
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Javascript Assets -->
    <script src="{{ asset('public/vendor/js/app.js') }}" defer></script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
        rel="stylesheet"/>
    <script>
        localStorage.getItem("_x_darkMode_on") === "true" &&
        document.documentElement.classList.add("dark");
    </script>

    {{--  select2  --}}
    <script src="https://code.jquery.com/jquery-3.7.1.js"
            integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>
<body>

<div class="flex justify-center items-center min-h-screen w-full">
    <div class="max-w-md p-6 text-center">
        <div class="w-full">
            <img
                class="w-full"
                src="{{ asset('public/vendor/images/illustrations/error-500.svg') }}"
                alt="image"
            />
        </div>
        <p class="pt-4 text-7xl font-bold text-primary dark:text-accent">
            500
        </p>
        <p
            class="pt-4 text-xl font-semibold text-slate-800 dark:text-navy-50"
        >
            Ошибка сервера
        </p>
        <p class="pt-2 text-slate-500 dark:text-navy-200">
            Сервер был заброшен на некоторое время. Пожалуйста, будьте терпеливы или попробуйте еще раз позже.
        </p>
    </div>
</div>

</body>
</html>
