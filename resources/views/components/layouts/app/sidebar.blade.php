<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        {{-- Sidebar với màu sáng dễ nhìn --}}
        <flux:sidebar sticky stashable class="border-e border-zinc-200 bg-gradient-to-b from-blue-50 via-indigo-50 to-purple-50 dark:border-zinc-700 dark:bg-gradient-to-b dark:from-zinc-900 dark:via-indigo-950 dark:to-purple-950 shadow-lg">
            <flux:sidebar.toggle class="lg:hidden text-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-zinc-700" icon="x-mark" />

            {{-- Logo với border đẹp --}}
            <div class="bg-white dark:bg-zinc-800 rounded-xl p-3 mb-4 shadow-md border-2 border-indigo-200 dark:border-indigo-800">
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
                    <x-app-logo />
                </a>
            </div>

            {{-- Navigation với text đậm dễ nhìn --}}
            <flux:navlist variant="outline" class="space-y-2">
                <flux:navlist.group :heading="__('Platform')" class="grid gap-1 text-gray-500 dark:text-gray-400 font-semibold uppercase text-xs">
                    <flux:navlist.item 
                        icon="home" 
                        :href="route('dashboard')" 
                        :current="request()->routeIs('dashboard')" 
                        wire:navigate
                        class="text-gray-800 dark:text-gray-100 font-medium hover:bg-indigo-100 dark:hover:bg-indigo-900 transition-all duration-300 hover:translate-x-1 rounded-lg {{ request()->routeIs('dashboard') ? 'bg-indigo-200 dark:bg-indigo-800 text-indigo-900 dark:text-white shadow-md font-semibold' : '' }}"
                    >
                        {{ __('Dashboard') }}
                    </flux:navlist.item>
                    
                    <flux:navlist.item 
                        icon="book-open-text" 
                        :href="route('admin.categories')" 
                        :current="request()->routeIs('admin.categories')" 
                        wire:navigate
                        class="text-gray-800 dark:text-gray-100 font-medium hover:bg-purple-100 dark:hover:bg-purple-900 transition-all duration-300 hover:translate-x-1 rounded-lg {{ request()->routeIs('admin.categories') ? 'bg-purple-200 dark:bg-purple-800 text-purple-900 dark:text-white shadow-md font-semibold' : '' }}"
                    >
                        {{ __('Quản lý Danh mục') }}
                    </flux:navlist.item>

                    @if(Route::has('admin.posts'))
                    <flux:navlist.item 
                        icon="layout-grid" 
                        :href="route('admin.posts')" 
                        :current="request()->routeIs('admin.posts')" 
                        wire:navigate
                        class="text-gray-800 dark:text-gray-100 font-medium hover:bg-pink-100 dark:hover:bg-pink-900 transition-all duration-300 hover:translate-x-1 rounded-lg {{ request()->routeIs('admin.posts') ? 'bg-pink-200 dark:bg-pink-800 text-pink-900 dark:text-white shadow-md font-semibold' : '' }}"
                    >
                        {{ __('Quản lý Bài viết') }}
                    </flux:navlist.item>
                    @endif
                </flux:navlist.group>
            </flux:navlist>

            <flux:spacer />

            {{-- External links --}}
            <div class="bg-white dark:bg-zinc-800 rounded-xl p-2 shadow-sm border border-gray-200 dark:border-zinc-700">
                <flux:navlist variant="outline" class="space-y-1">
                    <flux:navlist.item 
                        icon="folder-git-2" 
                        href="https://github.com/laravel/livewire-starter-kit" 
                        target="_blank"
                        class="text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-gray-100 dark:hover:bg-zinc-700 transition-all duration-300 rounded-lg font-medium"
                    >
                        {{ __('Repository') }}
                    </flux:navlist.item>

                    <flux:navlist.item 
                        icon="book-open-text" 
                        href="https://laravel.com/docs/starter-kits#livewire" 
                        target="_blank"
                        class="text-gray-700 dark:text-gray-300 hover:text-purple-600 dark:hover:text-purple-400 hover:bg-gray-100 dark:hover:bg-zinc-700 transition-all duration-300 rounded-lg font-medium"
                    >
                        {{ __('Documentation') }}
                    </flux:navlist.item>
                </flux:navlist>
            </div>

            {{-- Desktop User Menu với border màu --}}
            <div class="bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-950 dark:to-purple-950 rounded-xl p-3 border-2 border-indigo-300 dark:border-indigo-700 shadow-lg">
                <flux:dropdown class="hidden lg:block" position="bottom" align="start">
                    <flux:profile
                        :name="auth()->user()->name"
                        :initials="auth()->user()->initials()"
                        icon:trailing="chevrons-up-down"
                        data-test="sidebar-menu-button"
                        class="text-gray-900 dark:text-white hover:bg-white/50 dark:hover:bg-zinc-800/50 transition-all duration-300 rounded-lg"
                    />

                    <flux:menu class="w-[240px] bg-white dark:bg-zinc-800 border-2 border-indigo-200 dark:border-indigo-800 shadow-2xl">
                        <flux:menu.radio.group>
                            <div class="p-2 text-sm font-normal bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-950 dark:to-purple-950 rounded-lg m-1">
                                <div class="flex items-center gap-3 px-2 py-2 text-start text-sm">
                                    <span class="relative flex h-10 w-10 shrink-0 overflow-hidden rounded-xl shadow-md ring-2 ring-indigo-300 dark:ring-indigo-700">
                                        <span class="flex h-full w-full items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white font-bold text-lg">
                                            {{ auth()->user()->initials() }}
                                        </span>
                                    </span>

                                    <div class="grid flex-1 text-start text-sm leading-tight">
                                        <span class="truncate font-bold text-gray-900 dark:text-white">{{ auth()->user()->name }}</span>
                                        <span class="truncate text-xs text-gray-600 dark:text-gray-400">{{ auth()->user()->email }}</span>
                                    </div>
                                </div>
                            </div>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <flux:menu.radio.group>
                            <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate class="text-gray-700 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-indigo-950 hover:text-indigo-700 dark:hover:text-indigo-400 transition-colors font-medium">
                                {{ __('Settings') }}
                            </flux:menu.item>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full hover:bg-red-50 dark:hover:bg-red-950 text-red-600 dark:text-red-400 transition-colors font-medium" data-test="logout-button">
                                {{ __('Log Out') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            </div>
        </flux:sidebar>

        {{-- Mobile User Menu --}}
        <flux:header class="lg:hidden bg-gradient-to-r from-blue-50 to-purple-50 dark:from-zinc-900 dark:to-purple-950 border-b-2 border-indigo-200 dark:border-indigo-800">
            <flux:sidebar.toggle class="lg:hidden text-gray-700 dark:text-white" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                    class="ring-2 ring-indigo-300 dark:ring-indigo-700 bg-gradient-to-br from-indigo-500 to-purple-600 text-white shadow-md"
                />

                <flux:menu class="bg-white dark:bg-zinc-800 border-2 border-indigo-200 dark:border-indigo-800">
                    <flux:menu.radio.group>
                        <div class="p-2 text-sm font-normal bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-950 dark:to-purple-950 rounded-lg m-1">
                            <div class="flex items-center gap-3 px-2 py-2 text-start text-sm">
                                <span class="relative flex h-10 w-10 shrink-0 overflow-hidden rounded-xl shadow-md ring-2 ring-indigo-300 dark:ring-indigo-700">
                                    <span class="flex h-full w-full items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white font-bold">
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-bold text-gray-900 dark:text-white">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs text-gray-600 dark:text-gray-400">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate class="text-gray-700 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-indigo-950 hover:text-indigo-700 dark:hover:text-indigo-400">
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full hover:bg-red-50 dark:hover:bg-red-950 text-red-600 dark:text-red-400" data-test="logout-button">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
