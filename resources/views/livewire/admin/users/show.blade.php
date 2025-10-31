<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\User;
use App\Models\Post;

new #[Layout('components.layouts.app')] #[Title('Chi tiết Tài khoản')]
class extends Component
{
    public User $user;
    
    public function mount(int $id): void
    {
        $this->user = User::with(['posts.category'])->findOrFail($id);
    }
    
    public function with(): array
    {
        return [
            'posts' => $this->user->posts()->latest()->paginate(10),
        ];
    }
};

?>

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Chi tiết Tài khoản</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Xem thông tin chi tiết người dùng
            </p>
        </div>
        
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.users.edit', $user->id) }}"
               class="inline-flex items-center gap-2 rounded-xl bg-amber-600 px-5 py-3 text-sm font-semibold text-white hover:bg-amber-700 transition">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Chỉnh sửa
            </a>
            
            <a href="{{ route('admin.users') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-gray-600 px-5 py-3 text-sm font-semibold text-white hover:bg-gray-700 transition">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Quay lại
            </a>
        </div>
    </div>
    
    {{-- User Info Card --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
        <div class="flex items-start gap-6">
            {{-- Avatar --}}
            <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=3B82F6&color=fff&size=128" 
                 alt="Avatar" 
                 class="h-32 w-32 rounded-full shadow-lg">
            
            {{-- Info --}}
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-4">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $user->name }}</h2>
                    @if($user->email_verified_at)
                        <span class="px-3 py-1 bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded-full text-sm font-semibold">
                            ✓ Đã xác thực
                        </span>
                    @else
                        <span class="px-3 py-1 bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400 rounded-full text-sm font-semibold">
                            Chưa xác thực
                        </span>
                    @endif
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <span class="text-gray-700 dark:text-gray-300">{{ $user->email }}</span>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span class="text-gray-700 dark:text-gray-300">Tạo: {{ $user->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span class="text-gray-700 dark:text-gray-300">Bài viết: <strong>{{ $user->posts()->count() }}</strong></span>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-gray-700 dark:text-gray-300">Cập nhật: {{ $user->updated_at->diffForHumans() }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- User's Posts --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Bài viết của {{ $user->name }}</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Tổng cộng {{ $posts->total() }} bài viết</p>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">
                            Tiêu đề
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">
                            Danh mục
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">
                            Trạng thái
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">
                            Ngày tạo
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">
                            Hành động
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($posts as $post)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            <td class="px-6 py-4">
                                <p class="font-semibold text-gray-900 dark:text-white line-clamp-1">{{ $post->title }}</p>
                            </td>
                            <td class="px-6 py-4">
                                @if($post->category)
                                    <span class="px-2 py-1 bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 rounded text-xs">
                                        {{ $post->category->name }}
                                    </span>
                                @else
                                    <span class="text-gray-400 text-sm">N/A</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold
                                    {{ $post->status === 'published' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400' }}">
                                    {{ $post->status === 'published' ? 'Đã đăng' : 'Bản nháp' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center text-sm text-gray-600 dark:text-gray-400">
                                {{ $post->created_at->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <a href="{{ route('admin.posts.show', $post->id) }}" 
                                   class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium">
                                    Xem
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                Chưa có bài viết nào
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="border-t border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
            <x-custom-pagination :paginator="$posts" :perPage="10" />
        </div>
    </div>
</div>
