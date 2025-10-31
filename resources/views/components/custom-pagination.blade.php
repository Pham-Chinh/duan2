@props(['paginator', 'perPage'])

<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    {{-- Tổng số bản ghi --}}
    <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
        Tổng số: <span class="font-bold text-gray-900 dark:text-white">{{ $paginator->total() }}</span>
    </div>

    @if ($paginator->hasPages())
        <div class="flex items-center gap-2">
            {{-- First Page Button --}}
            @if ($paginator->onFirstPage())
                <button disabled class="inline-flex h-10 w-10 items-center justify-center rounded-lg border-2 border-gray-200 bg-gray-50 text-gray-400 cursor-not-allowed dark:border-gray-700 dark:bg-gray-800">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                    </svg>
                </button>
            @else
                <button wire:click="gotoPage(1)" wire:loading.attr="disabled" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border-2 border-gray-300 bg-white text-gray-600 transition-all hover:border-cyan-500 hover:bg-cyan-50 hover:text-cyan-600 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-cyan-500 dark:hover:bg-cyan-950/30" title="Trang đầu">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                    </svg>
                </button>
            @endif

            {{-- Previous Button --}}
            @if ($paginator->onFirstPage())
                <button disabled class="inline-flex h-10 w-10 items-center justify-center rounded-lg border-2 border-gray-200 bg-gray-50 text-gray-400 cursor-not-allowed dark:border-gray-700 dark:bg-gray-800">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
            @else
                <button wire:click="previousPage" wire:loading.attr="disabled" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border-2 border-gray-300 bg-white text-gray-600 transition-all hover:border-cyan-500 hover:bg-cyan-50 hover:text-cyan-600 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-cyan-500 dark:hover:bg-cyan-950/30" title="Trang trước">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
            @endif

            {{-- Page Numbers (Smart pagination) --}}
            @php
                $currentPage = $paginator->currentPage();
                $lastPage = $paginator->lastPage();
                
                // Logic hiển thị số trang thông minh
                $showPages = [];
                
                if ($lastPage <= 7) {
                    // Nếu ít hơn 7 trang, hiển thị tất cả
                    $showPages = range(1, $lastPage);
                } else {
                    // Luôn hiển thị trang 1
                    $showPages[] = 1;
                    
                    if ($currentPage > 3) {
                        $showPages[] = '...';
                    }
                    
                    // Hiển thị các trang xung quanh trang hiện tại
                    $start = max(2, $currentPage - 1);
                    $end = min($lastPage - 1, $currentPage + 1);
                    
                    for ($i = $start; $i <= $end; $i++) {
                        $showPages[] = $i;
                    }
                    
                    if ($currentPage < $lastPage - 2) {
                        $showPages[] = '...';
                    }
                    
                    // Luôn hiển thị trang cuối
                    if (!in_array($lastPage, $showPages)) {
                        $showPages[] = $lastPage;
                    }
                }
            @endphp

            @foreach ($showPages as $page)
                @if ($page === '...')
                    <span class="inline-flex h-10 min-w-[2.5rem] items-center justify-center px-3 text-sm font-semibold text-gray-400 dark:text-gray-500">
                        ...
                    </span>
                @elseif ($page == $currentPage)
                    <button class="inline-flex h-10 min-w-[2.5rem] items-center justify-center rounded-lg border-2 border-cyan-500 bg-cyan-500 px-3 text-sm font-bold text-white shadow-md">
                        {{ $page }}
                    </button>
                @else
                    <button wire:click="gotoPage({{ $page }})" class="inline-flex h-10 min-w-[2.5rem] items-center justify-center rounded-lg border-2 border-gray-300 bg-white px-3 text-sm font-semibold text-gray-700 transition-all hover:border-cyan-500 hover:bg-cyan-50 hover:text-cyan-600 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-cyan-500 dark:hover:bg-cyan-950/30">
                        {{ $page }}
                    </button>
                @endif
            @endforeach

            {{-- Next Button --}}
            @if ($paginator->hasMorePages())
                <button wire:click="nextPage" wire:loading.attr="disabled" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border-2 border-gray-300 bg-white text-gray-600 transition-all hover:border-cyan-500 hover:bg-cyan-50 hover:text-cyan-600 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-cyan-500 dark:hover:bg-cyan-950/30" title="Trang sau">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            @else
                <button disabled class="inline-flex h-10 w-10 items-center justify-center rounded-lg border-2 border-gray-200 bg-gray-50 text-gray-400 cursor-not-allowed dark:border-gray-700 dark:bg-gray-800">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            @endif

            {{-- Last Page Button --}}
            @if ($paginator->hasMorePages())
                <button wire:click="gotoPage({{ $paginator->lastPage() }})" wire:loading.attr="disabled" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border-2 border-gray-300 bg-white text-gray-600 transition-all hover:border-cyan-500 hover:bg-cyan-50 hover:text-cyan-600 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-cyan-500 dark:hover:bg-cyan-950/30" title="Trang cuối">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
                    </svg>
                </button>
            @else
                <button disabled class="inline-flex h-10 w-10 items-center justify-center rounded-lg border-2 border-gray-200 bg-gray-50 text-gray-400 cursor-not-allowed dark:border-gray-700 dark:bg-gray-800">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
                    </svg>
                </button>
            @endif
        </div>
    @else
        {{-- Khi chỉ có 1 trang, hiển thị placeholder --}}
        <div class="text-sm text-gray-500 dark:text-gray-400">
            Trang 1 / 1
        </div>
    @endif

    {{-- Page Size Selector - Luôn hiển thị --}}
    <div class="flex items-center gap-2">
        <select 
            wire:model.live="perPage" 
            class="rounded-lg border-2 border-gray-300 bg-white py-2 pl-3 pr-10 text-sm font-medium text-gray-700 transition-all focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-cyan-500"
        >
            <option value="5">5 / trang</option>
            <option value="10">10 / trang</option>
            <option value="15">15 / trang</option>
            <option value="25">25 / trang</option>
            <option value="50">50 / trang</option>
        </select>
    </div>
</div>

