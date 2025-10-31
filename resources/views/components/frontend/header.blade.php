{{-- Header Component cho Frontend --}}
<header class="sticky top-0 z-40 bg-white dark:bg-gray-800 shadow-md">
    <div class="container mx-auto px-4">
        {{-- Top Bar --}}
        <div class="flex items-center justify-between py-4">
            {{-- Logo --}}
            <a href="{{ route('home') }}" class="flex items-center gap-2">
                <img src="{{ asset('favicon.svg') }}" alt="Logo" class="h-10 w-10">
                <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                    {{ config('app.name', 'Tin Tức') }}
                </span>
            </a>

            {{-- Desktop Navigation --}}
            <nav class="hidden md:flex items-center gap-6">
                {{-- Menu chính --}}
                <a href="{{ route('home') }}" 
                   class="text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition">
                    Trang chủ
                </a>

                {{-- Categories Dropdown --}}
                @php
                    $categories = \App\Models\Category::whereNull('parent_id')
                        ->where('is_visible', true)
                        ->with('children')
                        ->orderBy('name')
                        ->get();
                @endphp

                @foreach($categories as $category)
                    @if($category->children->isNotEmpty())
                        {{-- Category có con: dropdown --}}
                        <div x-data="{ open: false }" @click.away="open = false" class="relative">
                            <button @click="open = !open" 
                                    class="flex items-center gap-1 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition">
                                {{ $category->name }}
                                <svg class="h-4 w-4" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="open" 
                                 x-transition
                                 class="absolute top-full left-0 mt-2 w-48 bg-white dark:bg-gray-700 rounded-lg shadow-lg py-2">
                                <a href="{{ route('category.show', $category->slug) }}" 
                                   class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600">
                                    Tất cả {{ $category->name }}
                                </a>
                                @foreach($category->children as $child)
                                    <a href="{{ route('category.show', $child->slug) }}" 
                                       class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600">
                                        {{ $child->name }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        {{-- Category không có con: link thường --}}
                        <a href="{{ route('category.show', $category->slug) }}" 
                           class="text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition">
                            {{ $category->name }}
                        </a>
                    @endif
                @endforeach
            </nav>

            {{-- Right Side: Search + Dark Mode + Login --}}
            <div class="flex items-center gap-4">
                {{-- Search Icon --}}
                <button @click="$dispatch('open-search-modal')" 
                        class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>

                {{-- Dark Mode Toggle --}}
                <button @click="darkMode = !darkMode" 
                        class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400">
                    <svg x-show="!darkMode" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                    <svg x-show="darkMode" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </button>

                {{-- Login / User Avatar --}}
                @auth
                    <div x-data="{ userDropdownOpen: false }" 
                         @mouseenter="userDropdownOpen = true" 
                         @mouseleave="userDropdownOpen = false"
                         class="relative">
                        {{-- User Avatar Button --}}
                        <button class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&background=3B82F6&color=fff" 
                                 alt="Avatar" 
                                 class="h-8 w-8 rounded-full ring-2 ring-transparent hover:ring-blue-400 transition">
                            <span class="hidden md:inline">{{ auth()->user()->name }}</span>
                            <svg class="h-4 w-4 transition-transform" :class="userDropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        {{-- Dropdown Menu --}}
                        <div x-show="userDropdownOpen" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-56 origin-top-right rounded-lg bg-white dark:bg-gray-800 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-50"
                             style="display: none;">
                            
                            {{-- User Info Header --}}
                            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                <div class="flex items-center gap-3">
                                    <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&background=3B82F6&color=fff" 
                                         alt="Avatar" 
                                         class="h-10 w-10 rounded-full">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                            {{ auth()->user()->name }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                            {{ auth()->user()->email }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {{-- Menu Items --}}
                            <div class="py-2">
                                {{-- Profile (tạm thời disabled) --}}
                                <a href="#" 
                                   class="flex items-center gap-3 px-4 py-2 text-sm text-gray-400 dark:text-gray-500 cursor-not-allowed"
                                   title="Chức năng đang phát triển">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    <span>Hồ sơ cá nhân</span>
                                    <span class="ml-auto text-xs bg-gray-200 dark:bg-gray-700 px-2 py-0.5 rounded">Soon</span>
                                </a>

                                @if(auth()->user()->canAccessDashboard())
                                    {{-- Dashboard Link (Admin & Editor) --}}
                                    <a href="{{ route('dashboard') }}" 
                                       class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                        </svg>
                                        <span>Dashboard</span>
                                    </a>
                                @endif
                            </div>

                            {{-- Divider --}}
                            <div class="border-t border-gray-200 dark:border-gray-700"></div>

                            {{-- Logout --}}
                            <div class="py-2">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" 
                                            class="flex w-full items-center gap-3 px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                        </svg>
                                        <span>Đăng xuất</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" 
                       class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">
                        Đăng nhập
                    </a>
                @endauth

                {{-- Mobile Menu Toggle --}}
                <button @click="$dispatch('toggle-mobile-menu')" 
                        class="md:hidden text-gray-600 dark:text-gray-400">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Search Bar (full width khi click search icon) --}}
    <div x-data="{ searchOpen: false }" 
         @open-search-modal.window="searchOpen = true"
         x-show="searchOpen"
         x-transition
         class="border-t border-gray-200 dark:border-gray-700 py-4">
        <div class="container mx-auto px-4">
            <form action="{{ route('search') }}" method="GET" class="flex gap-2">
                <input type="text" 
                       name="q" 
                       placeholder="Tìm kiếm bài viết..." 
                       class="flex-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-800"
                       x-ref="searchInput">
                <button type="submit" 
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Tìm
                </button>
                <button type="button" 
                        @click="searchOpen = false"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                    Đóng
                </button>
            </form>
        </div>
    </div>

    {{-- Mobile Menu (slide-in từ trái) --}}
    <div x-data="{ mobileMenuOpen: false }" 
         @toggle-mobile-menu.window="mobileMenuOpen = !mobileMenuOpen"
         x-show="mobileMenuOpen"
         @click.away="mobileMenuOpen = false"
         class="md:hidden">
        <div x-show="mobileMenuOpen" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             @click="mobileMenuOpen = false"
             class="fixed inset-0 bg-black bg-opacity-50 z-40"></div>
        
        <div x-show="mobileMenuOpen"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             class="fixed top-0 left-0 h-full w-64 bg-white dark:bg-gray-800 shadow-lg z-50 overflow-y-auto">
            
            <div class="p-4">
                <div class="flex justify-between items-center mb-6">
                    <span class="text-xl font-bold text-blue-600 dark:text-blue-400">Menu</span>
                    <button @click="mobileMenuOpen = false" class="text-gray-600 dark:text-gray-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- User Info (Mobile) --}}
                @auth
                    <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <div class="flex items-center gap-3 mb-3">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&background=3B82F6&color=fff" 
                                 alt="Avatar" 
                                 class="h-12 w-12 rounded-full ring-2 ring-blue-400">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                    {{ auth()->user()->name }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                    {{ auth()->user()->email }}
                                </p>
                            </div>
                        </div>
                        
                        <div class="space-y-2">
                            {{-- Profile (disabled) --}}
                            <a href="#" 
                               class="flex items-center gap-2 text-sm text-gray-400 dark:text-gray-500 cursor-not-allowed">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                Hồ sơ cá nhân
                                <span class="ml-auto text-xs bg-gray-200 dark:bg-gray-700 px-2 py-0.5 rounded">Soon</span>
                            </a>

                            @if(auth()->user()->canAccessDashboard())
                                {{-- Dashboard Link (Admin & Editor) --}}
                                <a href="{{ route('dashboard') }}" 
                                   class="flex items-center gap-2 text-sm text-blue-600 dark:text-blue-400 hover:underline">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                    Dashboard
                                </a>
                            @endif

                            {{-- Logout --}}
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" 
                                        class="flex w-full items-center gap-2 text-sm text-red-600 dark:text-red-400 hover:underline">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                    </svg>
                                    Đăng xuất
                                </button>
                            </form>
                        </div>
                    </div>
                @endauth
                
                <nav class="space-y-2">
                    <a href="{{ route('home') }}" class="block py-2 text-gray-700 dark:text-gray-300 hover:text-blue-600">
                        Trang chủ
                    </a>
                    @foreach($categories as $category)
                        <div>
                            <a href="{{ route('category.show', $category->slug) }}" 
                               class="block py-2 font-semibold text-gray-700 dark:text-gray-300">
                                {{ $category->name }}
                            </a>
                            @if($category->children->isNotEmpty())
                                <div class="ml-4 space-y-1">
                                    @foreach($category->children as $child)
                                        <a href="{{ route('category.show', $child->slug) }}" 
                                           class="block py-1 text-sm text-gray-600 dark:text-gray-400">
                                            {{ $child->name }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </nav>
            </div>
        </div>
    </div>
</header>

