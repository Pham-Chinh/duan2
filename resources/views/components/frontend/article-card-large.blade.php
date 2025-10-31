{{-- Article Card Large Component --}}
@props(['post'])

<article class="group bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden hover:shadow-2xl transition-all duration-300 hover:-translate-y-1">
    {{-- Thumbnail --}}
    <a href="{{ route('article.show', $post->slug) }}" class="block relative overflow-hidden aspect-video bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-700 dark:to-gray-600">
        @if($post->banner)
            <img src="{{ Storage::url($post->banner) }}" 
                 alt="{{ $post->title }}" 
                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                 loading="lazy">
        @else
            <div class="w-full h-full flex items-center justify-center">
                <svg class="h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
        @endif
        
        {{-- Category Badge --}}
        @if($post->category)
            <span class="absolute top-3 left-3 px-3 py-1 bg-blue-600 text-white text-xs font-semibold rounded-full shadow-lg">
                {{ $post->category->name }}
            </span>
        @endif
    </a>

    {{-- Content --}}
    <div class="p-6">
        {{-- Title --}}
        <a href="{{ route('article.show', $post->slug) }}" 
           class="block text-xl font-bold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition mb-3 line-clamp-2">
            {{ $post->title }}
        </a>

        {{-- Excerpt --}}
        <p class="text-gray-600 dark:text-gray-400 text-sm line-clamp-3 mb-4">
            {{ Str::limit(strip_tags($post->content), 150) }}
        </p>

        {{-- Meta --}}
        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
            <div class="flex items-center gap-4">
                {{-- Author --}}
                @if($post->user)
                    <div class="flex items-center gap-1">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span>{{ $post->user->name }}</span>
                    </div>
                @endif

                {{-- Date --}}
                <div class="flex items-center gap-1">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span>{{ $post->created_at->diffForHumans() }}</span>
                </div>
            </div>

            {{-- Views --}}
            <div class="flex items-center gap-1">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                <span>{{ number_format($post->views ?? 0) }}</span>
            </div>
        </div>
    </div>
</article>


