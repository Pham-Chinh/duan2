<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ darkMode: false }" x-init="
    darkMode = localStorage.getItem('darkMode') === 'true' || (!('darkMode' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches);
    $watch('darkMode', value => localStorage.setItem('darkMode', value));
" :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Dynamic Meta Tags --}}
    <title>{{ $title ?? config('app.name', 'Tin Tức') }}</title>
    <meta name="description" content="{{ $description ?? 'Website tin tức cập nhật mới nhất' }}">
    <meta name="keywords" content="{{ $keywords ?? 'tin tức, bài viết, news' }}">

    {{-- Open Graph Meta Tags --}}
    <meta property="og:title" content="{{ $title ?? config('app.name') }}">
    <meta property="og:description" content="{{ $description ?? 'Website tin tức cập nhật mới nhất' }}">
    <meta property="og:image" content="{{ $ogImage ?? asset('images/default-og.jpg') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="{{ $ogType ?? 'website' }}">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title ?? config('app.name') }}">
    <meta name="twitter:description" content="{{ $description ?? 'Website tin tức cập nhật mới nhất' }}">
    <meta name="twitter:image" content="{{ $ogImage ?? asset('images/default-og.jpg') }}">

    {{-- Favicon --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.ico') }}">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Styles --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    {{-- Additional Styles --}}
    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }
    </style>

    {{-- Stack for additional head content --}}
    @stack('styles')
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 antialiased">
    {{-- Header --}}
    @include('components.frontend.header')

    {{-- Main Content --}}
    <main class="min-h-screen">
        {{ $slot }}
    </main>

    {{-- Footer --}}
    @include('components.frontend.footer')

    {{-- Scripts --}}
    @livewireScripts
    @stack('scripts')

    {{-- Toast Notifications (nếu cần) --}}
    <div x-data="{ show: false, message: '', type: 'success' }"
         @toast-notification.window="
            show = true;
            message = $event.detail.message;
            type = $event.detail.type || 'success';
            setTimeout(() => show = false, 3000);
         "
         x-show="show"
         x-transition
         class="fixed top-4 right-4 z-50 max-w-sm">
        <div class="rounded-lg shadow-lg p-4"
             :class="{
                'bg-green-500 text-white': type === 'success',
                'bg-red-500 text-white': type === 'error',
                'bg-blue-500 text-white': type === 'info'
             }">
            <p x-text="message"></p>
        </div>
    </div>
</body>
</html>


