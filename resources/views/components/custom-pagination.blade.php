@props(['paginator', 'perPage'])

<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    {{-- Tổng số bản ghi --}}
    <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
        Tổng số: <span class="font-bold text-gray-900 dark:text-white">{{ $paginator->total() }}</span>
    </div>

    @if ($paginator->hasPages())
        <div class="flex items-center gap-3">
            {{-- Previous Button --}}
            @if ($paginator->onFirstPage())
                <button disabled class="inline-flex h-10 w-10 items-center justify-center rounded-lg border-2 border-gray-200 bg-gray-50 text-gray-400 cursor-not-allowed dark:border-gray-700 dark:bg-gray-800">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
            @else
                <button wire:click="previousPage" wire:loading.attr="disabled" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border-2 border-gray-300 bg-white text-gray-600 transition-all hover:border-cyan-500 hover:bg-cyan-50 hover:text-cyan-600 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-cyan-500 dark:hover:bg-cyan-950/30">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
            @endif

            {{-- Page Numbers --}}
            @foreach ($paginator->getUrlRange(1, $paginator->lastPage()) as $page => $url)
                @if ($page == $paginator->currentPage())
                    <button class="inline-flex h-10 min-w-[2.5rem] items-center justify-center rounded-lg border-2 border-cyan-500 bg-cyan-500 px-3 text-sm font-bold text-white shadow-md">
                        {{ $page }}
                    </button>
                @else
                    <button wire:click="setPage({{ $page }})" class="inline-flex h-10 min-w-[2.5rem] items-center justify-center rounded-lg border-2 border-gray-300 bg-white px-3 text-sm font-semibold text-gray-700 transition-all hover:border-cyan-500 hover:bg-cyan-50 hover:text-cyan-600 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-cyan-500 dark:hover:bg-cyan-950/30">
                        {{ $page }}
                    </button>
                @endif
            @endforeach

            {{-- Next Button --}}
            @if ($paginator->hasMorePages())
                <button wire:click="nextPage" wire:loading.attr="disabled" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border-2 border-gray-300 bg-white text-gray-600 transition-all hover:border-cyan-500 hover:bg-cyan-50 hover:text-cyan-600 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-cyan-500 dark:hover:bg-cyan-950/30">
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

