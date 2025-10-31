{{-- Article Card Small Component --}}
@props(['post'])

<article class="group flex gap-3 bg-white dark:bg-gray-800 rounded-lg p-3 hover:shadow-lg transition-all duration-300">
    {{-- Thumbnail --}}
    <a href="{{ route('article.show', $post->slug) }}" class="flex-shrink-0 w-24 h-24 rounded-lg overflow-hidden bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-700 dark:to-gray-600">
        @if($post->banner)
            <img src="{{ Storage::url($post->banner) }}" 
                 alt="{{ $post->title }}" 
                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                 loading="lazy">
        @else
            <div class="w-full h-full flex items-center justify-center">
                <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
        @endif
    </a>

    {{-- Content --}}
    <div class="flex-1 flex flex-col justify-between min-w-0">
        {{-- Title --}}
        <a href="{{ route('article.show', $post->slug) }}" 
           class="text-sm font-semibold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition line-clamp-2 mb-1">
            {{ $post->title }}
        </a>

        {{-- Meta --}}
        <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
            {{-- Category --}}
            @if($post->category)
                <span class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded">
                    {{ $post->category->name }}
                </span>
            @endif

            {{-- Date --}}
            <span>{{ $post->created_at->diffForHumans() }}</span>
        </div>
    </div>
</article>


