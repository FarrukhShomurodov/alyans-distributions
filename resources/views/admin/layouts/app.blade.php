<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0"/>

    @yield('title')
    @yield('styles')

{{--    <link rel="icon" type="image/png" href="{{ asset('vendor/images/favicon.png') }}"/>--}}

    <!-- CSS Assets -->
    <link rel="stylesheet" href="{{ asset('vendor/css/app.css') }}"/>
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Javascript Assets -->
    <script src="{{ asset('vendor/js/app.js') }}" defer></script>

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


    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body x-data class="is-header-blur" x-bind="$store.global.documentBody">
<!-- App preloader-->
<div
    class="app-preloader fixed z-50 grid h-full w-full place-content-center bg-slate-50 dark:bg-navy-900">
    <div class="app-preloader-inner relative inline-block size-48"></div>
</div>

<!-- Page Wrapper -->
<div id="root" class="min-h-100vh flex grow bg-slate-50 dark:bg-navy-900" x-cloak>

    <!-- Уведомления -->
    <div class="fixed top-4 right-4 space-y-2 z-[10050]" id="flash-messages">
        @if ($errors->any())
            @foreach ($errors->all() as $error)
                <div class="flex items-center justify-between bg-red-500 text-white px-4 py-3 rounded-xl shadow-lg animate-slide-in max-w-md" role="alert">
                    <div class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 mr-2 flex-shrink-0">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856A2.062 2.062 0 0021 16.938V7.062A2.062 2.062 0 0018.938 5H5.062A2.062 2.062 0 003 7.062v9.876A2.062 2.062 0 005.062 19z"/>
                        </svg>
                        <span>{{ $error }}</span>
                    </div>
                    <button type="button" class="ml-3 text-white/80 hover:text-white focus:outline-none" onclick="this.parentElement.remove();">✕</button>
                </div>
            @endforeach
        @endif

        @if (session('error'))
            <div class="flex items-center justify-between bg-red-500 text-white px-4 py-3 rounded-xl shadow-lg animate-slide-in max-w-md" role="alert" data-auto-dismiss>
                <div class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 mr-2 flex-shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856A2.062 2.062 0 0021 16.938V7.062A2.062 2.062 0 0018.938 5H5.062A2.062 2.062 0 003 7.062v9.876A2.062 2.062 0 005.062 19z"/>
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
                <button type="button" class="ml-3 text-white/80 hover:text-white focus:outline-none" onclick="this.parentElement.remove();">✕</button>
            </div>
        @endif

        @if (session('success'))
            <div class="flex items-center justify-between bg-emerald-500 text-white px-4 py-3 rounded-xl shadow-lg animate-slide-in max-w-md" role="alert" data-auto-dismiss>
                <div class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 mr-2 flex-shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
                <button type="button" class="ml-3 text-white/80 hover:text-white focus:outline-none" onclick="this.parentElement.remove();">✕</button>
            </div>
        @endif
    </div>
    <script>
        // Auto-hide success/error toasts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('#flash-messages [data-auto-dismiss]').forEach(el => el.remove());
        }, 5000);
    </script>

    <!-- Sidebar -->
    @include('admin.layouts.sidebar')

    <!-- Mobile Searchbar -->
    @include('admin.layouts.mobile_sidebar')

    <!-- Right Sidebar -->
    @include('admin.layouts.right_sidebar')

    <!-- Main Content Wrapper -->
    <main class="main-content mail-app w-full px-[var(--margin-x)] pb-6 pl-[200px]">
        @yield('content')
    </main>
</div>

<div id="x-teleport-target"></div>

@yield('scripts')
<script>
    $(document).ready(function () {
        $('.select2').select2({
            allowClear: false,
            minimumResultsForSearch: 5
        });
    });

    window.addEventListener("DOMContentLoaded", () => Alpine.start());

    // === Глобальный поллинг счётчика непрочитанных чатов в сайдбаре (каждые 15с) ===
    (function () {
        @auth
        const supportLink = document.querySelector('a[href="{{ route('support.index') }}"]');
        if (!supportLink) return;

        let badge = supportLink.querySelector('span.bg-red-500');

        async function refreshBadge() {
            try {
                const res = await fetch("{{ route('support.unread-count') }}", {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin',
                });
                if (!res.ok) return;
                const data = await res.json();
                if (typeof data.count !== 'number') return;

                if (data.count > 0) {
                    if (!badge) {
                        badge = document.createElement('span');
                        badge.className = 'ml-auto inline-flex items-center justify-center w-5 h-5 rounded-full bg-red-500 text-white text-[10px] font-bold';
                        supportLink.appendChild(badge);
                    }
                    badge.textContent = data.count > 99 ? '99+' : data.count;
                    badge.style.display = '';
                } else if (badge) {
                    badge.style.display = 'none';
                }
            } catch (e) {}
        }

        setInterval(refreshBadge, 15000);
        @endauth
    })();
</script>
</body>
</html>
