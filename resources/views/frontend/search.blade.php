{{-- Search Page - Tìm kiếm bài viết --}}
@php
    $query = request()->query('q', '');
    $filterCategory = request()->query('category', 'all');
    $filterDate = request()->query('date', 'all');

    // Build search query
    $searchQuery = \App\Models\Post::with(['category', 'user'])
        ->where('status', 'published');

    // Keyword search
    if (!empty($query)) {
        $searchQuery->where(function($q) use ($query) {
            $q->where('title', 'like', '%'.$query.'%')
              ->orWhere('content', 'like', '%'.$query.'%');
        });
    }

    // Filter by category
    if ($filterCategory !== 'all') {
        $searchQuery->where('category_id', $filterCategory);
    }

    // Filter by date
    if ($filterDate === 'today') {
        $searchQuery->whereDate('created_at', today());
    } elseif ($filterDate === 'last_7_days') {
        $searchQuery->where('created_at', '>=', now()->subDays(7));
    } elseif ($filterDate === 'last_30_days') {
        $searchQuery->where('created_at', '>=', now()->subDays(30));
    }

    $results = $searchQuery->orderBy('created_at', 'desc')->paginate(10);
    $categories = \App\Models\Category::where('is_visible', true)->orderBy('name')->get();
@endphp

<x-layouts.frontend
    :title="'Tìm kiếm: ' . $query . ' - ' . config('app.name')"
    :description="'Kết quả tìm kiếm cho từ khóa: ' . $query">

    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Main Content --}}
            <div class="lg:col-span-2">
                {{-- Search Header --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-8">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                        Tìm kiếm bài viết
                    </h1>

                    {{-- Search Form --}}
                    <form action="{{ route('search') }}" method="GET" class="mb-6">
                        <div class="flex gap-2">
                            <input type="text" 
                                   name="q" 
                                   value="{{ $query }}"
                                   placeholder="Nhập từ khóa tìm kiếm..." 
                                   class="flex-1 rounded-lg border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-3 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-800"
                                   autofocus>
                            <button type="submit" 
                                    class="px-6 py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition flex items-center gap-2">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                Tìm
                            </button>
                        </div>

                        {{-- Filters --}}
                        <div class="flex flex-wrap gap-3 mt-4">
                            {{-- Category Filter --}}
                            <select name="category" 
                                    onchange="this.form.submit()"
                                    class="rounded-lg border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm focus:border-blue-500">
                                <option value="all">Tất cả chuyên mục</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ $filterCategory == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>

                            {{-- Date Filter --}}
                            <select name="date" 
                                    onchange="this.form.submit()"
                                    class="rounded-lg border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm focus:border-blue-500">
                                <option value="all" {{ $filterDate === 'all' ? 'selected' : '' }}>Mọi thời gian</option>
                                <option value="today" {{ $filterDate === 'today' ? 'selected' : '' }}>Hôm nay</option>
                                <option value="last_7_days" {{ $filterDate === 'last_7_days' ? 'selected' : '' }}>7 ngày qua</option>
                                <option value="last_30_days" {{ $filterDate === 'last_30_days' ? 'selected' : '' }}>30 ngày qua</option>
                            </select>

                            {{-- Clear Filters --}}
                            @if($query || $filterCategory !== 'all' || $filterDate !== 'all')
                                <a href="{{ route('search') }}" 
                                   class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                                    Xóa bộ lọc
                                </a>
                            @endif
                        </div>
                    </form>

                    {{-- Results Count --}}
                    <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        @if($query)
                            <span>Tìm thấy <strong>{{ $results->total() }}</strong> kết quả cho "<strong>{{ $query }}</strong>"</span>
                        @else
                            <span>Hiển thị <strong>{{ $results->total() }}</strong> bài viết</span>
                        @endif
                    </div>
                </div>

                {{-- Search Results --}}
                @if($results->isNotEmpty())
                    <div class="space-y-6 mb-8">
                        @foreach($results as $post)
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden hover:shadow-2xl transition">
                                <div class="md:flex gap-6 p-6">
                                    {{-- Thumbnail --}}
                                    <a href="{{ route('article.show', $post->slug) }}" 
                                       class="block flex-shrink-0 w-full md:w-48 h-48 rounded-lg overflow-hidden bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-700 dark:to-gray-600">
                                        @if($post->banner)
                                            <img src="{{ Storage::url($post->banner) }}" 
                                                 alt="{{ $post->title }}" 
                                                 class="w-full h-full object-cover hover:scale-110 transition-transform duration-500">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center">
                                                <svg class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                            </div>
                                        @endif
                                    </a>

                                    {{-- Content --}}
                                    <div class="flex-1 mt-4 md:mt-0">
                                        <a href="{{ route('article.show', $post->slug) }}" 
                                           class="block text-xl font-bold text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition mb-2">
                                            {{ $post->title }}
                                        </a>
                                        <p class="text-gray-600 dark:text-gray-400 text-sm line-clamp-2 mb-3">
                                            {{ Str::limit(strip_tags($post->content), 200) }}
                                        </p>
                                        <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                                            @if($post->category)
                                                <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded">
                                                    {{ $post->category->name }}
                                                </span>
                                            @endif
                                            <span>{{ $post->user->name ?? 'Admin' }}</span>
                                            <span>•</span>
                                            <span>{{ $post->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Pagination --}}
                    <div class="flex justify-center">
                        {{ $results->appends(request()->query())->links() }}
                    </div>
                @else
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-12 text-center">
                        <svg class="h-16 w-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Không tìm thấy kết quả</h3>
                        <p class="text-gray-600 dark:text-gray-400">Vui lòng thử lại với từ khóa khác</p>
                    </div>
                @endif
            </div>

            {{-- Sidebar --}}
            <div class="lg:col-span-1">
                @include('components.frontend.sidebar')
            </div>
        </div>
    </div>
</x-layouts.frontend>


