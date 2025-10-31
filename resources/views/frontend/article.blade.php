{{-- Article Detail Page - Chi tiết bài viết --}}
@php
    // Get post from route parameter
    $slug = request()->route('slug');
    $post = \App\Models\Post::with(['category.parent', 'user'])
        ->where('slug', $slug)
        ->where('status', 'published')
        ->firstOrFail();

    // Increment view count (once per session)
    if (!session()->has('viewed_post_' . $post->id)) {
        $post->increment('views');
        session()->put('viewed_post_' . $post->id, true);
    }

    // Related posts
    $relatedPosts = \App\Models\Post::where('status', 'published')
        ->where('category_id', $post->category_id)
        ->where('id', '!=', $post->id)
        ->orderBy('created_at', 'desc')
        ->limit(4)
        ->get();
@endphp

<x-layouts.frontend
    :title="$post->title"
    :description="Str::limit(strip_tags($post->content), 160)"
    :ogImage="$post->banner ? Storage::url($post->banner) : null"
    :ogType="'article'">

    <article class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Main Content --}}
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
                    {{-- Breadcrumb --}}
                    <x-frontend.breadcrumb :items="[
                        ['label' => $post->category->parent ? $post->category->parent->name : $post->category->name, 'url' => route('category.show', $post->category->parent ? $post->category->parent->slug : $post->category->slug)],
                        ['label' => $post->title, 'url' => '#']
                    ]" />

                    {{-- Title --}}
                    <h1 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-6">
                        {{ $post->title }}
                    </h1>

                    {{-- Meta Info --}}
                    <div class="flex flex-wrap items-center gap-4 mb-6 pb-6 border-b border-gray-200 dark:border-gray-700">
                        {{-- Author --}}
                        <div class="flex items-center gap-2">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($post->user->name ?? 'Admin') }}&background=3B82F6&color=fff" 
                                 alt="Avatar" 
                                 class="h-10 w-10 rounded-full">
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $post->user->name ?? 'Admin' }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $post->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>

                        {{-- Category --}}
                        <a href="{{ route('category.show', $post->category->slug) }}" 
                           class="px-3 py-1.5 bg-blue-600 text-white text-sm font-semibold rounded-full hover:bg-blue-700 transition">
                            {{ $post->category->name }}
                        </a>

                        {{-- Views --}}
                        <div class="flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <span>{{ number_format($post->views) }} lượt xem</span>
                        </div>
                    </div>

                    {{-- Banner Image --}}
                    @if($post->banner)
                        <div class="mb-8 rounded-xl overflow-hidden">
                            <img src="{{ Storage::url($post->banner) }}" 
                                 alt="{{ $post->title }}" 
                                 class="w-full h-auto">
                        </div>
                    @endif

                    {{-- Content --}}
                    <div class="prose prose-lg dark:prose-invert max-w-none mb-8">
                        {!! $post->content !!}
                    </div>

                    {{-- Gallery --}}
                    @if($post->gallery && count($post->gallery) > 0)
                        <div class="mb-8">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Thư viện ảnh</h3>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                @foreach($post->gallery as $image)
                                    <div class="rounded-lg overflow-hidden">
                                        <img src="{{ Storage::url($image) }}" 
                                             alt="Gallery" 
                                             class="w-full h-48 object-cover hover:scale-110 transition-transform duration-300">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Share Buttons --}}
                    <div class="flex items-center gap-2 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Chia sẻ:</span>
                        <button class="p-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            Facebook
                        </button>
                        <button class="p-2 bg-blue-400 text-white rounded-lg hover:bg-blue-500 transition">
                            Twitter
                        </button>
                    </div>
                </div>

                {{-- Related Posts --}}
                @if($relatedPosts->isNotEmpty())
                    <div class="mt-8">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Bài viết liên quan</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @foreach($relatedPosts as $related)
                                <x-frontend.article-card-large :post="$related" />
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Sidebar --}}
            <div class="lg:col-span-1">
                @include('components.frontend.sidebar')
            </div>
        </div>
    </article>

    {{-- Schema.org JSON-LD --}}
    @php
        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $post->title,
            'image' => $post->banner ? Storage::url($post->banner) : '',
            'datePublished' => $post->created_at->toIso8601String(),
            'dateModified' => $post->updated_at->toIso8601String(),
            'author' => [
                '@type' => 'Person',
                'name' => $post->user->name ?? 'Admin'
            ],
        ];
    @endphp
    @push('scripts')
    <script type="application/ld+json">
    {!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}
    </script>
    @endpush
</x-layouts.frontend>


