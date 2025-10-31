{{-- Sidebar Component cho Frontend --}}
<aside class="space-y-8">
    {{-- Tin nổi bật (Top Views) --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="h-6 w-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/>
            </svg>
            Tin nổi bật
        </h3>

        @php
            $popularPosts = \App\Models\Post::where('status', 'published')
                ->orderBy('views', 'desc')
                ->limit(5)
                ->get();
        @endphp

        <div class="space-y-4">
            @forelse($popularPosts as $index => $post)
                <div class="flex gap-3 group">
                    <span class="flex-shrink-0 flex items-center justify-center w-8 h-8 rounded-full bg-gradient-to-r from-red-500 to-pink-500 text-white font-bold text-sm">
                        {{ $index + 1 }}
                    </span>
                    <div class="flex-1">
                        <a href="{{ route('article.show', $post->slug) }}" 
                           class="text-sm font-semibold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition line-clamp-2">
                            {{ $post->title }}
                        </a>
                        <div class="flex items-center gap-2 mt-1 text-xs text-gray-500 dark:text-gray-400">
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            {{ number_format($post->views ?? 0) }} lượt xem
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">Chưa có bài viết nổi bật</p>
            @endforelse
        </div>
    </div>

    {{-- Chủ đề nóng (Categories) --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="h-6 w-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
            </svg>
            Chủ đề nóng
        </h3>

        @php
            $hotCategories = \App\Models\Category::where('is_visible', true)
                ->withCount('posts')
                ->orderBy('posts_count', 'desc')
                ->limit(6)
                ->get();
        @endphp

        <div class="flex flex-wrap gap-2">
            @foreach($hotCategories as $cat)
                <a href="{{ route('category.show', $cat->slug) }}" 
                   class="inline-flex items-center gap-1 px-3 py-1.5 bg-gradient-to-r from-orange-100 to-red-100 dark:from-orange-900/30 dark:to-red-900/30 text-orange-700 dark:text-orange-300 text-sm font-medium rounded-full hover:from-orange-200 hover:to-red-200 dark:hover:from-orange-900/50 dark:hover:to-red-900/50 transition">
                    {{ $cat->name }}
                    <span class="text-xs bg-white dark:bg-gray-700 px-2 py-0.5 rounded-full">
                        {{ $cat->posts_count }}
                    </span>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Newsletter --}}
    <div class="bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
        <div class="text-center">
            <svg class="h-12 w-12 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            <h3 class="text-lg font-bold mb-2">Đăng ký nhận tin</h3>
            <p class="text-sm text-blue-100 mb-4">
                Cập nhật tin tức mới nhất qua email
            </p>
            <form action="#" method="POST" class="space-y-2">
                @csrf
                <input type="email" 
                       name="email" 
                       placeholder="Email của bạn" 
                       class="w-full rounded-lg px-4 py-2 text-gray-900 text-sm focus:ring-2 focus:ring-white">
                <button type="submit" 
                        class="w-full bg-white text-blue-600 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-blue-50 transition">
                    Đăng ký ngay
                </button>
            </form>
        </div>
    </div>

    {{-- Quảng cáo --}}
    <div class="bg-gray-200 dark:bg-gray-700 rounded-xl shadow-lg p-6 text-center">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Quảng cáo</p>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg h-64 overflow-hidden">
            <div x-data="{ 
                currentIndex: 0,
                images: [
                    'https://images.unsplash.com/photo-1567427017947-545c5f8d16ad?w=300&h=250&fit=crop',
                    'https://images.unsplash.com/photo-1551650975-87deedd944c3?w=300&h=250&fit=crop',
                    'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=300&h=250&fit=crop',
                    'https://images.unsplash.com/photo-1519183073328-330ccda8f863?w=300&h=250&fit=crop',
                    'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=300&h=250&fit=crop'
                ],
                init() {
                    setInterval(() => {
                        this.currentIndex = (this.currentIndex + 1) % this.images.length;
                    }, 3000);
                }
            }" class="relative h-full">
                {{-- Images --}}
                <template x-for="(image, index) in images" :key="index">
                    <div 
                        x-show="currentIndex === index"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="transition ease-in duration-300"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="absolute inset-0"
                    >
                        <img :src="image" alt="Advertisement" class="w-full h-full object-cover" />
                    </div>
                </template>
                
                {{-- Dots indicator --}}
                <div class="absolute bottom-2 left-0 right-0 flex justify-center gap-2">
                    <template x-for="(image, index) in images" :key="index">
                        <button 
                            @click="currentIndex = index"
                            class="w-2 h-2 rounded-full transition-all"
                            :class="currentIndex === index ? 'bg-white' : 'bg-white/50'"
                        ></button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</aside>


