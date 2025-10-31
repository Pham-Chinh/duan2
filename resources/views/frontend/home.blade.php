{{-- Home Page - Trang chủ --}}
<x-layouts.frontend>
    {{-- Hero Section --}}
    <section class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Featured Post (2/3 width) --}}
            @php
                $featured = \App\Models\Post::where('status', 'published')
                    ->orderBy('views', 'desc')
                    ->first();
            @endphp

            @if($featured)
                <div class="lg:col-span-2">
                    <article class="group relative h-[500px] rounded-2xl overflow-hidden shadow-2xl">
                        <a href="{{ route('article.show', $featured->slug) }}" class="block h-full">
                            @if($featured->banner)
                                <img src="{{ Storage::url($featured->banner) }}" 
                                     alt="{{ $featured->title }}" 
                                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                            @else
                                <div class="w-full h-full bg-gradient-to-br from-blue-500 to-purple-600"></div>
                            @endif
                            
                            {{-- Overlay Gradient --}}
                            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/50 to-transparent"></div>
                            
                            {{-- Content --}}
                            <div class="absolute bottom-0 left-0 right-0 p-8 text-white">
                                @if($featured->category)
                                    <span class="inline-block px-4 py-1.5 bg-red-600 text-sm font-semibold rounded-full mb-3">
                                        {{ $featured->category->name }}
                                    </span>
                                @endif
                                <h2 class="text-3xl md:text-4xl font-bold mb-3 line-clamp-2 group-hover:text-blue-400 transition">
                                    {{ $featured->title }}
                                </h2>
                                <p class="text-gray-200 text-lg line-clamp-2 mb-4">
                                    {{ Str::limit(strip_tags($featured->content), 200) }}
                                </p>
                                <div class="flex items-center gap-4 text-sm">
                                    <span>{{ $featured->user->name ?? 'Admin' }}</span>
                                    <span>•</span>
                                    <span>{{ $featured->created_at->diffForHumans() }}</span>
                                    <span>•</span>
                                    <span>{{ number_format($featured->views ?? 0) }} lượt xem</span>
                                </div>
                            </div>
                        </a>
                    </article>
                </div>
            @endif

            {{-- Sidebar Posts (1/3 width) --}}
            <div class="space-y-4">
                @php
                    $sidebarPosts = \App\Models\Post::where('status', 'published')
                        ->where('id', '!=', $featured?->id)
                        ->orderBy('created_at', 'desc')
                        ->limit(4)
                        ->get();
                @endphp

                @foreach($sidebarPosts as $post)
                    <x-frontend.article-card-small :post="$post" />
                @endforeach
            </div>
        </div>
    </section>

    {{-- Latest Posts Section --}}
    <section class="container mx-auto px-4 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Main Content --}}
            <div class="lg:col-span-2">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                    <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Tin mới nhất
                </h2>

                @php
                    $latestPosts = \App\Models\Post::where('status', 'published')
                        ->orderBy('created_at', 'desc')
                        ->paginate(9);
                @endphp

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach($latestPosts as $post)
                        <x-frontend.article-card-large :post="$post" />
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div class="mt-8">
                    {{ $latestPosts->links() }}
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="lg:col-span-1">
                @include('components.frontend.sidebar')
            </div>
        </div>
    </section>

    {{-- Category Sections --}}
    @php
        $categories = \App\Models\Category::whereNull('parent_id')
            ->where('is_visible', true)
            ->withCount('posts')
            ->having('posts_count', '>', 0)
            ->orderBy('posts_count', 'desc')
            ->limit(3)
            ->get();
    @endphp

    @foreach($categories as $category)
        <section class="container mx-auto px-4 py-8 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $category->name }}
                </h2>
                <a href="{{ route('category.show', $category->slug) }}" 
                   class="text-sm font-semibold text-blue-600 hover:text-blue-700 flex items-center gap-1">
                    Xem tất cả
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>

            @php
                $categoryPosts = \App\Models\Post::where('status', 'published')
                    ->where('category_id', $category->id)
                    ->orderBy('created_at', 'desc')
                    ->limit(4)
                    ->get();
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($categoryPosts as $post)
                    <x-frontend.article-card-large :post="$post" />
                @endforeach
            </div>
        </section>
    @endforeach
</x-layouts.frontend>


