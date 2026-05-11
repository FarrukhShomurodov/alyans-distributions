<div class="sidebar print:hidden">
    <div class="main-sidebar w-[250px] h-full">
        <div
            class="flex h-full w-full flex-col items-center overflow-y-auto border-r border-slate-150 bg-white dark:border-navy-700 dark:bg-navy-800">

            <div class="w-full flex justify-center py-6 border-b border-slate-200 dark:border-navy-700">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                    <span class="text-xl font-bold text-slate-800 dark:text-navy-50">ALYANS DISTRIBUTIONS</span>
                </a>
            </div>


            <div class="w-full mt-4 px-4 space-y-1">
                <a href="{{ route('support.index') }}"
                    class="flex items-center space-x-3 p-2 rounded-md font-semibold
                        {{ request()->routeIs('support.index') ? 'bg-blue-50 text-blue-600 dark:bg-navy-600' : 'text-gray-800 dark:text-navy-100 hover:bg-gray-100 dark:hover:bg-navy-700' }}">
                    <i class="fas fa-headset"></i>
                    <span>Поддержка</span>

                    @if(($newChatsCount ?? 0) > 0)
                        <span class="ml-auto inline-flex items-center justify-center w-5 h-5 rounded-full bg-red-500 text-white text-[10px] font-bold">
                            {{ $newChatsCount > 99 ? '99+' : $newChatsCount }}
                        </span>
                    @endif
                </a>

                <a href="{{ route('chat-templates.index') }}"
                    class="flex items-center space-x-3 p-2 rounded-md text-sm
                        {{ request()->routeIs('chat-templates.*') ? 'bg-blue-50 text-blue-600 dark:bg-navy-600' : 'text-gray-700 dark:text-navy-200 hover:bg-gray-100 dark:hover:bg-navy-700' }}">
                    <i class="fas fa-comment-dots"></i>
                    <span>Шаблоны ответов</span>
                </a>
            </div>

            <div class="w-full flex-1 px-4 mt-4 space-y-2">

                <a href="{{ route('dashboard') }}"
                   class="flex items-center space-x-3 p-2 rounded-md
                          {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-600 dark:bg-navy-600' : 'text-gray-800 dark:text-navy-100 hover:bg-gray-100 dark:hover:bg-navy-700' }}">
                    <i class="fas fa-home-alt"></i>
                    <span>Главная</span>
                </a>

                <!-- Manager Routes -->
                @if(auth()->user()->hasRole(['manager', 'admin']))
                    <a href="{{ route('orders.index') }}"
                       class="flex items-center space-x-3 p-2 rounded-md
                              {{ request()->routeIs('orders.index') ? 'bg-blue-50 text-blue-600 dark:bg-navy-600' : 'text-gray-800 dark:text-navy-100 hover:bg-gray-100 dark:hover:bg-navy-700' }}">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Заказы</span>
                    </a>

                    <a href="{{ route('bot.users.index') }}"
                       class="flex items-center space-x-3 p-2 rounded-md
                              {{ request()->routeIs('bot.users.index') ? 'bg-blue-50 text-blue-600 dark:bg-navy-600' : 'text-gray-800 dark:text-navy-100 hover:bg-gray-100 dark:hover:bg-navy-700' }}">
                        <i class="fas fa-user"></i>
                        <span>Пользователи бота</span>
                    </a>

                    <a href="{{ route('broadcasts.index') }}"
                       class="flex items-center space-x-3 p-2 rounded-md
                              {{ request()->routeIs('broadcasts.*') ? 'bg-blue-50 text-blue-600 dark:bg-navy-600' : 'text-gray-800 dark:text-navy-100 hover:bg-gray-100 dark:hover:bg-navy-700' }}">
                        <i class="fas fa-paper-plane"></i>
                        <span>Рассылки</span>
                    </a>
                @endif

                <!-- Commodity Expert & Admin Routes -->
                @if(auth()->user()->hasRole(['commodity_expert', 'admin']))
                    <a href="{{ route('categories.index') }}"
                       class="flex items-center space-x-3 p-2 rounded-md
                              {{ request()->routeIs('categories.index') ? 'bg-blue-50 text-blue-600 dark:bg-navy-600' : 'text-gray-800 dark:text-navy-100 hover:bg-gray-100 dark:hover:bg-navy-700' }}">
                        <i class="fas fa-layer-group"></i>
                        <span>Категории</span>
                    </a>

                    <a href="{{ route('attributes.index') }}"
                       class="flex items-center space-x-3 p-2 rounded-md
                              {{ request()->routeIs('attributes.*') ? 'bg-blue-50 text-blue-600 dark:bg-navy-600' : 'text-gray-800 dark:text-navy-100 hover:bg-gray-100 dark:hover:bg-navy-700' }}">
                        <i class="fas fa-tags"></i>
                        <span>Атрибуты</span>
                    </a>

                    <a href="{{ route('products.index') }}"
                       class="flex items-center space-x-3 p-2 rounded-md
                              {{ request()->routeIs('products.index') ? 'bg-blue-50 text-blue-600 dark:bg-navy-600' : 'text-gray-800 dark:text-navy-100 hover:bg-gray-100 dark:hover:bg-navy-700' }}">
                        <i class="fas fa-box-open"></i>
                        <span>Продукты</span>
                    </a>

                    <a href="{{ route('stocks.index') }}"
                       class="flex items-center space-x-3 p-2 rounded-md
                              {{ request()->routeIs('stocks.index') ? 'bg-blue-50 text-blue-600 dark:bg-navy-600' : 'text-gray-800 dark:text-navy-100 hover:bg-gray-100 dark:hover:bg-navy-700' }}">
                        <i class="fas fa-warehouse"></i>
                        <span>Остатки</span>
                    </a>
                @endif

                <!-- Admin Only Routes -->
                @if(auth()->user()->hasRole('admin'))
                    <a href="{{ route('admins.index') }}"
                       class="flex items-center space-x-3 p-2 rounded-md
                              {{ request()->routeIs('admins.index') ? 'bg-blue-50 text-blue-600 dark:bg-navy-600' : 'text-gray-800 dark:text-navy-100 hover:bg-gray-100 dark:hover:bg-navy-700' }}">
                        <i class="fas fa-user-cog"></i>
                        <span>Пользователи панели</span>
                    </a>

                    <a href="{{ route('promotions.index') }}"
                       class="flex items-center space-x-3 p-2 rounded-md
                              {{ request()->routeIs('promotions.*') ? 'bg-blue-50 text-blue-600 dark:bg-navy-600' : 'text-gray-800 dark:text-navy-100 hover:bg-gray-100 dark:hover:bg-navy-700' }}">
                        <i class="fas fa-percent"></i>
                        <span>Акции и скидки</span>
                    </a>

                    <a href="{{ route('promo-codes.index') }}"
                       class="flex items-center space-x-3 p-2 rounded-md
                              {{ request()->routeIs('promo-codes.*') ? 'bg-blue-50 text-blue-600 dark:bg-navy-600' : 'text-gray-800 dark:text-navy-100 hover:bg-gray-100 dark:hover:bg-navy-700' }}">
                        <i class="fas fa-ticket-alt"></i>
                        <span>Промокоды</span>
                    </a>

                    <a href="{{ route('slider-products.index') }}"
                       class="flex items-center space-x-3 p-2 rounded-md
                              {{ request()->routeIs('slider-products.*') ? 'bg-blue-50 text-blue-600 dark:bg-navy-600' : 'text-gray-800 dark:text-navy-100 hover:bg-gray-100 dark:hover:bg-navy-700' }}">
                        <i class="fas fa-random"></i>
                        <span>Слайдер товаров</span>
                    </a>

                    <a href="{{ route('landing.carousel.index') }}"
                       class="flex items-center space-x-3 p-2 rounded-md
                              {{ request()->routeIs('landing.carousel.*') ? 'bg-blue-50 text-blue-600 dark:bg-navy-600' : 'text-gray-800 dark:text-navy-100 hover:bg-gray-100 dark:hover:bg-navy-700' }}">
                        <i class="fas fa-images"></i>
                        <span>Карусель</span>
                    </a>
                @endif

            </div>

            <div class="w-full p-4 border-t border-gray-200 dark:border-navy-700">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center space-x-3 p-2 rounded-md text-red-600 hover:bg-red-50 dark:hover:bg-navy-700">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Выход</span>
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>
