{{-- Category Page - Danh sách bài viết theo category --}}
@php
    // Get category from route parameter
    $slug = request()->route('slug');
    $category = \App\Models\Category::where('slug', $slug)
        ->with('children')
        ->firstOrFail();

    // Get posts from this category and its children
    $categoryIds = [$category->id];
    if ($category->children->isNotEmpty()) {
        $categoryIds = array_merge($categoryIds, $category->children->pluck('id')->toArray());
    }

    $posts = \App\Models\Post::with(['category', 'user'])
        ->where('status', 'published')
        ->whereIn('category_id', $categoryIds)
        ->orderBy('created_at', 'desc')
        ->paginate(12);
@endphp

<x-layouts.frontend
    :title="$category->name . ' - ' . config('app.name')"
    :description="'Danh sách bài viết thuộc chuyên mục ' . $category->name">

    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Main Content --}}
            <div class="lg:col-span-2">
                {{-- Breadcrumb --}}
                <x-frontend.breadcrumb :items="[
                    ['label' => $category->parent ? $category->parent->name : $category->name, 'url' => $category->parent ? route('category.show', $category->parent->slug) : '#'],
                    ...$category->parent ? [['label' => $category->name, 'url' => '#']] : []
                ]" />

                {{-- Category Header --}}
                <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl p-8 mb-8 text-white">
                    <h1 class="text-3xl md:text-4xl font-bold mb-3">{{ $category->name }}</h1>
                    <p class="text-blue-100 mb-4">
                        Khám phá {{ $posts->total() }} bài viết trong chuyên mục này
                    </p>
                    
                    {{-- Child Categories --}}
                    @if($category->children->isNotEmpty())
                        <div class="flex flex-wrap gap-2 mt-4">
                            <a href="{{ route('category.show', $category->slug) }}" 
                               class="px-3 py-1.5 bg-white/20 hover:bg-white/30 rounded-full text-sm font-medium transition">
                                Tất cả
                            </a>
                            @foreach($category->children as $child)
                                <a href="{{ route('category.show', $child->slug) }}" 
                                   class="px-3 py-1.5 bg-white/20 hover:bg-white/30 rounded-full text-sm font-medium transition">
                                    {{ $child->name }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Posts Grid --}}
                @if($posts->isNotEmpty())
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        @foreach($posts as $post)
                            <x-frontend.article-card-large :post="$post" />
                        @endforeach
                    </div>

                    {{-- Pagination --}}
                    <div class="flex justify-center">
                        {{ $posts->links() }}
                    </div>
                @else
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-12 text-center">
                        <svg class="h-16 w-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Chưa có bài viết</h3>
                        <p class="text-gray-600 dark:text-gray-400">Chuyên mục này chưa có bài viết nào.</p>
                        <a href="{{ route('home') }}" 
                           class="inline-block mt-4 px-6 py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition">
                            Về trang chủ
                        </a>
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


