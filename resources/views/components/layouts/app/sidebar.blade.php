<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        
        <!-- Smooth Dark Mode Transition -->
        <style>
            html {
                transition: background-color 0.3s ease, color 0.3s ease;
            }
            
            body {
                transition: background-color 0.3s ease, color 0.3s ease;
            }
            
            * {
                transition-property: background-color, border-color, color, fill, stroke;
                transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
                transition-duration: 200ms;
            }
        </style>
        
        <!-- Dark Mode Init Script (prevent flash) -->
        <script>
            // Run before page render to prevent flash
            if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        </script>
    </head>
    <body class="min-h-screen bg-gray-50 dark:bg-zinc-900">
        {{-- Sidebar với gradient xanh dương phù hợp dashboard --}}
        <flux:sidebar sticky stashable class="border-e border-gray-200/50 bg-gradient-to-b from-white via-blue-50/30 to-cyan-50/30 dark:border-zinc-700/50 dark:bg-gradient-to-b dark:from-zinc-900 dark:via-blue-950/20 dark:to-cyan-950/20 backdrop-blur-sm">
            <flux:sidebar.toggle class="lg:hidden text-gray-700 dark:text-white hover:bg-blue-100 dark:hover:bg-zinc-800 rounded-lg transition-all" icon="x-mark" />

            {{-- Logo --}}
            <div class="bg-gradient-to-r from-blue-500 to-cyan-500 rounded-2xl p-4 mb-6 shadow-lg">
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
                    <x-app-logo class="brightness-0 invert" />
                </a>
            </div>

            {{-- Navigation Menu --}}
            <flux:navlist variant="outline" class="space-y-1.5">
                <flux:navlist.group :heading="__('Menu')" class="grid gap-1.5 text-gray-500 dark:text-gray-400 font-bold uppercase text-[10px] tracking-wider mb-2">
                    <flux:navlist.item 
                        icon="home" 
                        :href="route('dashboard')" 
                        :current="request()->routeIs('dashboard')" 
                        wire:navigate
                        class="group relative text-gray-700 dark:text-gray-200 font-medium hover:text-blue-600 dark:hover:text-blue-400 transition-all duration-300 rounded-xl overflow-hidden {{ request()->routeIs('dashboard') ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white dark:text-white shadow-lg font-semibold' : 'hover:bg-blue-50 dark:hover:bg-blue-950/30' }}"
                    >
                        @if(!request()->routeIs('dashboard'))
                        <span class="absolute inset-0 bg-gradient-to-r from-blue-500/10 to-cyan-500/10 opacity-0 group-hover:opacity-100 transition-opacity"></span>
                        @endif
                        <span class="relative flex items-center gap-2">
                            <span class="flex-1">{{ __('Dashboard') }}</span>
                            @if(request()->routeIs('dashboard'))
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            @endif
                        </span>
                    </flux:navlist.item>
                    
                    <flux:navlist.item 
                        icon="book-open-text" 
                        :href="route('admin.categories')" 
                        :current="request()->routeIs('admin.categories')" 
                        wire:navigate
                        class="group relative text-gray-700 dark:text-gray-200 font-medium hover:text-emerald-600 dark:hover:text-emerald-400 transition-all duration-300 rounded-xl overflow-hidden {{ request()->routeIs('admin.categories') ? 'bg-gradient-to-r from-emerald-500 to-teal-500 text-white dark:text-white shadow-lg font-semibold' : 'hover:bg-emerald-50 dark:hover:bg-emerald-950/30' }}"
                    >
                        @if(!request()->routeIs('admin.categories'))
                        <span class="absolute inset-0 bg-gradient-to-r from-emerald-500/10 to-teal-500/10 opacity-0 group-hover:opacity-100 transition-opacity"></span>
                        @endif
                        <span class="relative flex items-center gap-2">
                            <span class="flex-1">{{ __('Quản lý Danh mục') }}</span>
                            @if(request()->routeIs('admin.categories'))
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            @endif
                        </span>
                    </flux:navlist.item>

                    @if(Route::has('admin.posts'))
                    <flux:navlist.item 
                        icon="layout-grid" 
                        :href="route('admin.posts')" 
                        :current="request()->routeIs('admin.posts*')" 
                        wire:navigate
                        class="group relative text-gray-700 dark:text-gray-200 font-medium hover:text-cyan-600 dark:hover:text-cyan-400 transition-all duration-300 rounded-xl overflow-hidden {{ request()->routeIs('admin.posts*') ? 'bg-gradient-to-r from-cyan-500 to-teal-500 text-white dark:text-white shadow-lg font-semibold' : 'hover:bg-cyan-50 dark:hover:bg-cyan-950/30' }}"
                    >
                        @if(!request()->routeIs('admin.posts*'))
                        <span class="absolute inset-0 bg-gradient-to-r from-cyan-500/10 to-teal-500/10 opacity-0 group-hover:opacity-100 transition-opacity"></span>
                        @endif
                        <span class="relative flex items-center gap-2">
                            <span class="flex-1">{{ __('Quản lý Bài viết') }}</span>
                            @if(request()->routeIs('admin.posts*'))
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            @endif
                        </span>
                    </flux:navlist.item>
                    @endif
                </flux:navlist.group>
            </flux:navlist>

            <flux:spacer />

            {{-- External links --}}
            <div class="bg-white/50 dark:bg-zinc-800/50 rounded-xl p-2.5 backdrop-blur-sm border border-gray-200/50 dark:border-zinc-700/50">
                <flux:navlist variant="outline" class="space-y-1">
                    <flux:navlist.item 
                        icon="folder-git-2" 
                        href="https://github.com/laravel/livewire-starter-kit" 
                        target="_blank"
                        class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50/50 dark:hover:bg-blue-950/30 transition-all duration-300 rounded-lg font-medium text-sm"
                    >
                        {{ __('Repository') }}
                    </flux:navlist.item>

                    <flux:navlist.item 
                        icon="book-open-text" 
                        href="https://laravel.com/docs/starter-kits#livewire" 
                        target="_blank"
                        class="text-gray-600 dark:text-gray-400 hover:text-cyan-600 dark:hover:text-cyan-400 hover:bg-cyan-50/50 dark:hover:bg-cyan-950/30 transition-all duration-300 rounded-lg font-medium text-sm"
                    >
                        {{ __('Documentation') }}
                    </flux:navlist.item>
                </flux:navlist>
            </div>

            {{-- Desktop User Menu --}}
            <div class="bg-gradient-to-r from-blue-500 to-cyan-500 rounded-2xl p-3 shadow-lg">
                <flux:dropdown class="hidden lg:block" position="bottom" align="start">
                    <flux:profile
                        :name="auth()->user()->name"
                        :initials="auth()->user()->initials()"
                        icon:trailing="chevrons-up-down"
                        data-test="sidebar-menu-button"
                        class="text-white hover:bg-white/20 transition-all duration-300 rounded-xl"
                    />

                    <flux:menu class="w-[260px] bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 shadow-2xl rounded-2xl">
                        <flux:menu.radio.group>
                            <div class="p-3 text-sm font-normal bg-gradient-to-r from-blue-500 to-cyan-500 rounded-xl m-2">
                                <div class="flex items-center gap-3 px-2 py-2 text-start text-sm">
                                    <span class="relative flex h-12 w-12 shrink-0 overflow-hidden rounded-xl shadow-lg ring-2 ring-white/50">
                                        <span class="flex h-full w-full items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm text-white font-bold text-lg">
                                            {{ auth()->user()->initials() }}
                                        </span>
                                    </span>

                                    <div class="grid flex-1 text-start text-sm leading-tight">
                                        <span class="truncate font-bold text-white">{{ auth()->user()->name }}</span>
                                        <span class="truncate text-xs text-white/80">{{ auth()->user()->email }}</span>
                                    </div>
                                </div>
                            </div>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <flux:menu.radio.group>
                            <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate class="text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-950 hover:text-blue-700 dark:hover:text-blue-400 transition-colors font-medium rounded-lg mx-2">
                                {{ __('Settings') }}
                            </flux:menu.item>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <form method="POST" action="{{ route('logout') }}" class="w-full px-2 pb-2">
                            @csrf
                            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full hover:bg-red-50 dark:hover:bg-red-950 text-red-600 dark:text-red-400 transition-colors font-medium rounded-lg" data-test="logout-button">
                                {{ __('Log Out') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            </div>
        </flux:sidebar>

        {{-- Mobile User Menu --}}
        <flux:header class="lg:hidden bg-gradient-to-r from-blue-500 to-cyan-500 border-b border-blue-600 dark:border-cyan-600 shadow-lg">
            <flux:sidebar.toggle class="lg:hidden text-white hover:bg-white/20 rounded-lg transition-all" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                    class="ring-2 ring-white/30 bg-white/20 backdrop-blur-sm text-white shadow-md"
                />

                <flux:menu class="bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 shadow-2xl rounded-2xl">
                    <flux:menu.radio.group>
                        <div class="p-3 text-sm font-normal bg-gradient-to-r from-blue-500 to-cyan-500 rounded-xl m-2">
                            <div class="flex items-center gap-3 px-2 py-2 text-start text-sm">
                                <span class="relative flex h-12 w-12 shrink-0 overflow-hidden rounded-xl shadow-lg ring-2 ring-white/50">
                                    <span class="flex h-full w-full items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm text-white font-bold">
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-bold text-white">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs text-white/80">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate class="text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-950 hover:text-blue-700 dark:hover:text-blue-400 rounded-lg mx-2">
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full px-2 pb-2">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full hover:bg-red-50 dark:hover:bg-red-950 text-red-600 dark:text-red-400 rounded-lg" data-test="logout-button">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        {{-- Toast Notification --}}
        <x-toast-notification />

        @fluxScripts
        @stack('scripts')
    </body>
</html>
