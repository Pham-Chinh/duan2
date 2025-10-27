<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Post;

new #[Layout('components.layouts.app')] #[Title('Chi tiết Bài viết')]
class extends Component
{
    public Post $post;

    /**
     * Mount component
     */
    public function mount(int $id): void
    {
        $this->post = Post::with(['category.parent', 'user'])->findOrFail($id);
    }

    /**
     * Quay lại danh sách
     */
    public function back(): void
    {
        $this->redirect(route('admin.posts'), navigate: true);
    }

    /**
     * Get status badge class
     */
    public function getStatusBadge(string $status): string
    {
        return match($status) {
            'draft' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
            'published' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
            'archived' => 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabel(string $status): string
    {
        return match($status) {
            'draft' => 'Nháp',
            'published' => 'Đã đăng',
            'archived' => 'Lưu trữ',
            default => 'Không rõ',
        };
    }
};
?>

{{-- View --}}
<div class="space-y-6 p-6">

    {{-- Header với gradient đẹp --}}
    <header class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500 p-8 shadow-xl">
        <div class="relative z-10">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <button
                        wire:click="back"
                        class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm shadow-lg hover:bg-white/30 transition-all"
                    >
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                    <div>
                        <h1 class="text-3xl font-bold text-white drop-shadow-lg">
                            Chi tiết Bài viết
                        </h1>
                        <p class="text-sm text-white/80 mt-1">Xem thông tin chi tiết bài viết</p>
                    </div>
                </div>
                <a
                    href="{{ route('admin.posts.edit', $post->id) }}"
                    wire:navigate
                    class="inline-flex items-center gap-2 rounded-xl bg-white/20 backdrop-blur-sm px-5 py-3 text-sm font-semibold text-white shadow-lg hover:bg-white/30 transition-all"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span>Chỉnh sửa</span>
                </a>
            </div>
        </div>
        {{-- Decorative elements --}}
        <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-white/10 blur-3xl"></div>
        <div class="absolute -bottom-10 -left-10 h-40 w-40 rounded-full bg-white/10 blur-3xl"></div>
    </header>

    {{-- Content Card --}}
    <div class="overflow-hidden rounded-2xl border-2 border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-800">
        
        {{-- Tiêu đề bài viết --}}
        <div class="border-b-2 border-gray-100 bg-gradient-to-r from-blue-50 to-indigo-50 px-8 py-6 dark:border-gray-700 dark:from-blue-900/30 dark:to-indigo-900/30">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white">
                {{ $post->title }}
            </h2>
            <div class="mt-4 flex flex-wrap items-center gap-4">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $this->getStatusBadge($post->status ?? 'draft') }}">
                    {{ $this->getStatusLabel($post->status ?? 'draft') }}
                </span>
                <span class="text-sm text-gray-600 dark:text-gray-400">
                    📅 {{ $post->created_at->format('d/m/Y H:i') }}
                </span>
                @if($post->created_at != $post->updated_at)
                    <span class="text-sm text-gray-500 dark:text-gray-500">
                        (Cập nhật: {{ $post->updated_at->format('d/m/Y H:i') }})
                    </span>
                @endif
            </div>
        </div>

        <div class="p-8 space-y-8">
            
            {{-- Thông tin cơ bản --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Tác giả --}}
                <div class="rounded-xl bg-gradient-to-br from-purple-50 to-pink-50 p-5 dark:from-purple-900/20 dark:to-pink-900/20 border border-purple-200 dark:border-purple-800">
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-purple-500 to-pink-600 text-white text-lg font-bold shadow-lg">
                            {{ substr($post->user?->name ?? 'U', 0, 1) }}
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Tác giả</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $post->user?->name ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                {{-- Danh mục --}}
                <div class="rounded-xl bg-gradient-to-br from-cyan-50 to-teal-50 p-5 dark:from-cyan-900/20 dark:to-teal-900/20 border border-cyan-200 dark:border-cyan-800">
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2">Danh mục</p>
                    <div class="space-y-2">
                        <p class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ $post->category?->name ?? 'N/A' }}
                        </p>
                        @if($post->category?->parent)
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                📁 Danh mục gốc: <span class="font-semibold">{{ $post->category->parent->name }}</span>
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Banner --}}
            @if($post->banner)
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">📸 Banner</h3>
                    <div class="rounded-xl overflow-hidden shadow-lg border-2 border-gray-200 dark:border-gray-700">
                        <img src="{{ asset('storage/' . $post->banner) }}" alt="Banner" class="w-full h-auto object-cover">
                    </div>
                </div>
            @endif

            {{-- Nội dung --}}
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">📝 Nội dung</h3>
                <div class="rounded-xl bg-gray-50 p-6 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700">
                    <div class="prose dark:prose-invert max-w-none">
                        <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap leading-relaxed">{{ $post->content }}</p>
                    </div>
                </div>
            </div>

            {{-- Gallery --}}
            @if($post->gallery && is_array($post->gallery) && count($post->gallery) > 0)
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">🖼️ Gallery ({{ count($post->gallery) }} ảnh)</h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        @foreach($post->gallery as $image)
                            <div class="rounded-xl overflow-hidden shadow-lg border-2 border-gray-200 dark:border-gray-700 hover:scale-105 transition-transform">
                                <img src="{{ asset('storage/' . $image) }}" alt="Gallery image" class="w-full h-48 object-cover">
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>

    </div>

</div>



