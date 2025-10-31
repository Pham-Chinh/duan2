{{-- Breadcrumb Component --}}
@props(['items' => []])

<nav class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 mb-6">
    {{-- Home --}}
    <a href="{{ route('home') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
        </svg>
    </a>

    @foreach($items as $index => $item)
        {{-- Separator --}}
        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>

        {{-- Item --}}
        @if($loop->last)
            <span class="text-gray-900 dark:text-white font-medium">
                {{ $item['label'] }}
            </span>
        @else
            <a href="{{ $item['url'] }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition">
                {{ $item['label'] }}
            </a>
        @endif
    @endforeach
</nav>


