<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Rule;
use Livewire\WithFileUploads;
use App\Models\Post;
use App\Models\Category;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

new #[Layout('components.layouts.app')] #[Title('Chỉnh sửa Bài viết')]
class extends Component
{
    use WithFileUploads;

    public Post $post;

    // Form fields
    #[Rule('required|string|min:5|max:255')]
    public string $title = '';

    #[Rule('required|string')]
    public string $content = '';

    #[Rule('required|exists:categories,id')]
    public ?int $categoryId = null;

    #[Rule('nullable|image|max:2048')]
    public $newBanner = null;
    
    public $currentBanner = null;
    public array $newGallery = [];
    public array $currentGallery = [];

    // Data
    public $categories;

    /**
     * Mount component
     */
    public function mount(int $id): void
    {
        $this->post = Post::findOrFail($id);
        $this->title = $this->post->title;
        $this->content = $this->post->content ?? '';
        $this->categoryId = $this->post->category_id;
        $this->currentBanner = $this->post->banner;
        $this->currentGallery = $this->post->gallery ?? [];
        
        // Lấy tất cả danh mục đang hiển thị
        $allCategories = Category::where('is_visible', true)
            ->with('parent')
            ->orderBy('name', 'asc')
            ->get();
        
        // Lọc: nếu là danh mục con, kiểm tra cha có visible không
        $this->categories = $allCategories->filter(function ($category) {
            // Nếu là danh mục gốc và visible → OK
            if ($category->parent_id === null) {
                return true;
            }
            // Nếu là danh mục con → chỉ hiển thị nếu cha cũng visible
            return $category->parent && $category->parent->is_visible;
        });
    }

    /**
     * Xóa banner hiện tại
     */
    public function removeCurrentBanner(): void
    {
        $this->currentBanner = null;
    }

    /**
     * Xóa 1 ảnh trong gallery hiện tại
     */
    public function removeCurrentGalleryImage(int $index): void
    {
        unset($this->currentGallery[$index]);
        $this->currentGallery = array_values($this->currentGallery);
    }

    /**
     * Cập nhật bài viết với status tùy chỉnh
     */
    private function updatePost(string $status): void
    {
        // Validate basic fields
        $this->validate();

        try {
            // Xử lý banner
            $bannerPath = $this->currentBanner;
            
            // Nếu có upload banner mới
            if ($this->newBanner) {
                // Xóa banner cũ nếu có
                if ($this->post->banner) {
                    Storage::disk('public')->delete($this->post->banner);
                }
                $bannerPath = $this->newBanner->store('posts/banners', 'public');
            } 
            // Nếu xóa banner hiện tại
            elseif ($this->currentBanner === null && $this->post->banner) {
                Storage::disk('public')->delete($this->post->banner);
                $bannerPath = null;
            }

            // Xử lý gallery
            $galleryPaths = $this->currentGallery; // Giữ lại ảnh cũ

            // Thêm ảnh mới vào gallery
            if (!empty($this->newGallery)) {
                foreach ($this->newGallery as $image) {
                    if ($image) {
                        $galleryPaths[] = $image->store('posts/gallery', 'public');
                    }
                }
            }

            // Validate tổng số ảnh
            if (count($galleryPaths) > 5) {
                $this->addError('newGallery', 'Tổng số ảnh trong gallery không được quá 5.');
                return;
            }

            // Xóa các ảnh đã bị remove
            if ($this->post->gallery) {
                $removedImages = array_diff($this->post->gallery, $this->currentGallery);
                foreach ($removedImages as $image) {
                    Storage::disk('public')->delete($image);
                }
            }

            // Update post
            $this->post->update([
                'title' => $this->title,
                'content' => $this->content,
                'category_id' => $this->categoryId,
                'banner' => $bannerPath,
                'gallery' => !empty($galleryPaths) ? $galleryPaths : null,
                'status' => $status,
            ]);

            $statusLabel = match($status) {
                'draft' => 'Đã lưu bản nháp!',
                'published' => 'Đã đăng bài viết!',
                default => 'Đã cập nhật bài viết!'
            };

            // Dispatch event để cập nhật dashboard
            $this->dispatch('post-updated');
            $this->dispatch('toast-notification', type: 'success', message: $statusLabel);
            
            // Set flag for dashboard refresh
            $this->js("localStorage.setItem('dashboardNeedsRefresh', 'true'); window.dispatchEvent(new Event('post-updated'));");
            
            $this->redirect(route('admin.posts'), navigate: true);
        } catch (\Exception $e) {
            $this->dispatch('toast-notification', type: 'error', message: 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Lưu nháp
     */
    public function saveDraft(): void
    {
        // Nếu bài đang published → Tạo bản sao mới
        if ($this->post->status === 'published') {
            $this->createDraftCopy();
        } else {
            // Nếu đang draft/archived → Update bình thường
            $this->updatePost('draft');
        }
    }

    /**
     * Đăng bài (luôn update bài hiện tại)
     */
    public function publish(): void
    {
        $this->updatePost('published');
    }

    /**
     * Tạo bản sao nháp từ bài đã đăng
     */
    private function createDraftCopy(): void
    {
        // Validate basic fields
        $this->validate();

        try {
            // Upload banner mới nếu có
            $bannerPath = null;
            if ($this->newBanner) {
                $bannerPath = $this->newBanner->store('posts/banners', 'public');
            } else {
                // Copy banner cũ
                if ($this->currentBanner) {
                    $oldPath = $this->currentBanner;
                    $newPath = 'posts/banners/' . basename($oldPath);
                    Storage::disk('public')->copy($oldPath, $newPath);
                    $bannerPath = $newPath;
                }
            }

            // Upload gallery mới
            $galleryPaths = [];
            
            // Giữ lại ảnh cũ
            foreach ($this->currentGallery as $oldImage) {
                if (Storage::disk('public')->exists($oldImage)) {
                    $newPath = 'posts/gallery/' . basename($oldImage);
                    Storage::disk('public')->copy($oldImage, $newPath);
                    $galleryPaths[] = $newPath;
                }
            }
            
            // Thêm ảnh mới
            if (!empty($this->newGallery)) {
                foreach ($this->newGallery as $image) {
                    if ($image) {
                        $galleryPaths[] = $image->store('posts/gallery', 'public');
                    }
                }
            }

            // Tạo bản sao mới
            Post::create([
                'title' => $this->title . ' (Bản nháp)',
                'content' => $this->content,
                'category_id' => $this->categoryId,
                'user_id' => Auth::id(),
                'banner' => $bannerPath,
                'gallery' => !empty($galleryPaths) ? $galleryPaths : null,
                'status' => 'draft',
            ]);

            // Dispatch event để cập nhật dashboard
            $this->dispatch('post-created');
            $this->dispatch('toast-notification', 
                type: 'success', 
                message: 'Đã tạo bản sao nháp! Bài gốc vẫn được giữ nguyên.'
            );
            
            // Set flag for dashboard refresh
            $this->js("localStorage.setItem('dashboardNeedsRefresh', 'true'); window.dispatchEvent(new Event('post-created'));");
            
            $this->redirect(route('admin.posts'), navigate: true);
        } catch (\Exception $e) {
            $this->dispatch('toast-notification', type: 'error', message: 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Hủy và quay lại
     */
    public function cancel(): void
    {
        $this->redirect(route('admin.posts'), navigate: true);
    }
};
?>

{{-- View --}}
<div class="space-y-6 p-6">

    {{-- Header với gradient đẹp --}}
    <header class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-amber-500 via-orange-500 to-red-500 p-8 shadow-xl">
        <div class="relative z-10 flex flex-col space-y-4 md:flex-row md:items-center md:justify-between md:space-y-0">
            <div class="flex items-center gap-4">
                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/20 backdrop-blur-sm shadow-lg">
                    <svg class="h-10 w-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-white drop-shadow-lg">
                        Chỉnh sửa Bài viết
                    </h1>
                    <p class="text-sm text-white/80 mt-1">Cập nhật thông tin bài viết</p>
                </div>
            </div>
        </div>
        {{-- Decorative elements --}}
        <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-white/10 blur-3xl"></div>
        <div class="absolute -bottom-10 -left-10 h-40 w-40 rounded-full bg-white/10 blur-3xl"></div>
    </header>

    {{-- Form Card với gradient đẹp --}}
    <div class="overflow-hidden rounded-2xl border-2 border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-800">
        <div class="bg-gradient-to-r from-amber-50 to-orange-50 px-6 py-5 dark:from-amber-900/30 dark:to-orange-900/30">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Thông tin Bài viết</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Cập nhật thông tin bên dưới</p>
        </div>

        <form wire:submit="update" class="p-6 space-y-6">
            
            {{-- Tiêu đề --}}
            <div>
                <label for="title" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Tiêu đề <span class="text-red-500">*</span>
                </label>
                <input 
                    type="text" 
                    id="title" 
                    wire:model="title"
                    placeholder="Nhập tiêu đề bài viết..."
                    class="block w-full rounded-xl border-2 border-gray-200 px-4 py-3 shadow-sm transition-all focus:border-amber-500 focus:ring-4 focus:ring-amber-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:focus:border-amber-500 dark:focus:ring-amber-900"
                    required
                />
                @error('title') 
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> 
                @enderror
            </div>

            {{-- Nội dung --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Nội dung <span class="text-red-500">*</span>
                </label>
                <div wire:ignore>
                    <div id="content-edit"></div>
                </div>
                @error('content') 
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> 
                @enderror
            </div>

            {{-- Danh mục --}}
            <div x-data="{ 
                    open: false, 
                    expanded: {},
                    selectedId: @entangle('categoryId'),
                    selectedName: '{{ $categories->find($categoryId)?->name ?? '' }}',
                    selectCategory(id, name) {
                        this.selectedId = id;
                        this.selectedName = name;
                        this.open = false;
                    },
                    toggleExpand(catId) {
                        this.expanded[catId] = !this.expanded[catId];
                    }
                }" @click.away="open = false" class="relative">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Danh mục <span class="text-red-500">*</span>
                    </label>
                    
                    {{-- Custom Dropdown Button --}}
                    <button 
                        type="button"
                        @click="open = !open"
                        class="block w-full rounded-xl border-2 border-gray-200 px-4 py-3 shadow-sm transition-all focus:border-amber-500 focus:ring-4 focus:ring-amber-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-left flex items-center justify-between"
                    >
                        <span x-text="selectedName || '-- Chọn danh mục --'" class="truncate"></span>
                        <svg class="h-5 w-5 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    {{-- Dropdown Menu --}}
                    <div 
                        x-show="open"
                        x-transition
                        class="absolute z-50 mt-2 w-full rounded-xl border-2 border-gray-200 bg-white dark:bg-gray-700 dark:border-gray-600 shadow-xl max-h-80 overflow-y-auto"
                    >
                        @php
                            $rootCategories = $categories->whereNull('parent_id');
                        @endphp
                        
                        @foreach ($rootCategories as $parent)
                            @php
                                $children = $categories->where('parent_id', $parent->id);
                            @endphp
                            
                            {{-- Danh mục cha --}}
                            <div class="border-b border-gray-100 dark:border-gray-600 last:border-b-0">
                                <div class="flex items-center hover:bg-amber-50 dark:hover:bg-amber-900/20">
                                    {{-- Nút expand/collapse (nếu có con) --}}
                                    @if($children->isNotEmpty())
                                        <button 
                                            type="button"
                                            @click.stop="toggleExpand({{ $parent->id }})"
                                            class="px-3 py-3 hover:bg-amber-100 dark:hover:bg-amber-900/30"
                                        >
                                            <svg class="h-4 w-4 transition-transform" :class="expanded[{{ $parent->id }}] ? 'rotate-90' : ''" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                        </button>
                                    @else
                                        <div class="w-11"></div>
                                    @endif
                                    
                                    {{-- Tên danh mục cha --}}
                                    <button 
                                        type="button"
                                        @click="selectCategory({{ $parent->id }}, '{{ $parent->name }}')"
                                        class="flex-1 px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100"
                                        :class="selectedId === {{ $parent->id }} ? 'bg-amber-100 dark:bg-amber-900/40' : ''"
                                    >
                                        📂 {{ $parent->name }}
                                    </button>
                                </div>

                                {{-- Danh mục con --}}
                                @if($children->isNotEmpty())
                                    <div x-show="expanded[{{ $parent->id }}]" x-collapse class="bg-gray-50 dark:bg-gray-800">
                                        @foreach($children as $child)
                                            <button 
                                                type="button"
                                                @click="selectCategory({{ $child->id }}, '{{ $child->name }}')"
                                                class="w-full px-4 py-2.5 pl-12 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-amber-50 dark:hover:bg-amber-900/20 border-l-4 border-amber-300 dark:border-amber-600"
                                                :class="selectedId === {{ $child->id }} ? 'bg-amber-100 dark:bg-amber-900/40 font-semibold border-amber-500' : ''"
                                            >
                                                📄 {{ $child->name }}
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    @error('categoryId') 
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> 
                    @enderror
            </div>

            {{-- Banner --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Ảnh Banner <span class="text-xs font-normal text-gray-500">- Chỉ chọn 1 ảnh</span>
                </label>

                {{-- Banner hiện tại --}}
                @if($currentBanner && !$newBanner)
                    <div class="mb-4">
                        <p class="text-xs font-semibold text-gray-600 dark:text-gray-400 mb-2">Banner hiện tại:</p>
                        <div class="relative inline-block">
                            <img src="{{ asset('storage/' . $currentBanner) }}" alt="Banner hiện tại" class="h-40 w-full max-w-md rounded-xl object-cover shadow-lg border-2 border-gray-300 dark:border-gray-600" />
                            <button 
                                type="button" 
                                wire:click="removeCurrentBanner"
                                class="absolute -right-3 -top-3 flex h-8 w-8 items-center justify-center rounded-full bg-red-500 text-white hover:bg-red-600 shadow-xl transition-all hover:scale-110"
                                title="Xóa banner này"
                            >
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            💡 Click <strong>X</strong> để xóa banner này, sau đó chọn banner mới
                        </p>
                    </div>
                @endif

                {{-- Banner mới đã chọn --}}
                @if ($newBanner)
                    <div class="relative inline-block">
                        <p class="text-xs font-semibold text-amber-600 dark:text-amber-400 mb-2">Banner mới đã chọn:</p>
                        <img src="{{ $newBanner->temporaryUrl() }}" alt="Preview Banner Mới" class="h-40 w-full max-w-md rounded-xl object-cover shadow-lg border-2 border-amber-500" />
                        <button 
                            type="button" 
                            wire:click="$set('newBanner', null)"
                            class="absolute -right-3 -top-3 flex h-8 w-8 items-center justify-center rounded-full bg-red-500 text-white hover:bg-red-600 shadow-xl transition-all hover:scale-110"
                            title="Hủy chọn banner mới"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                        <div class="mt-2 text-xs text-amber-600 dark:text-amber-400 font-medium">
                            ✓ Banner mới - Click X để chọn lại
                        </div>
                    </div>
                @endif

                {{-- Nút chọn banner (chỉ hiện khi không có banner hiện tại hoặc đã xóa) --}}
                @if(!$currentBanner && !$newBanner)
                    <label class="flex cursor-pointer items-center justify-center gap-3 rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 px-6 py-8 hover:border-amber-500 hover:bg-amber-50 dark:border-gray-600 dark:bg-gray-700 dark:hover:border-amber-500 dark:hover:bg-amber-900/20 transition-all max-w-md">
                        <svg class="h-8 w-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <div class="text-center">
                            <span class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Chọn ảnh banner</span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400 mt-1">Chỉ chọn 1 ảnh</span>
                        </div>
                        <input type="file" wire:model="newBanner" accept="image/*" class="hidden" />
                    </label>
                @endif
                
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    <span class="font-semibold">Lưu ý:</span> Chỉ chọn 1 ảnh duy nhất. Định dạng: JPG, PNG. Tối đa 2MB.
                </p>
                @error('newBanner') 
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> 
                @enderror
            </div>

            {{-- Gallery --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Gallery (tối đa 5 ảnh)
                </label>

                {{-- Gallery hiện tại --}}
                @if(!empty($currentGallery))
                    <div class="mb-4">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Ảnh hiện tại ({{ count($currentGallery) }}):</p>
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                            @foreach($currentGallery as $index => $image)
                                <div class="relative">
                                    <img src="{{ asset('storage/' . $image) }}" alt="Gallery {{ $index + 1 }}" class="h-24 w-full rounded-lg object-cover shadow-md" />
                                    <button 
                                        type="button" 
                                        wire:click="removeCurrentGalleryImage({{ $index }})"
                                        class="absolute -right-2 -top-2 flex h-6 w-6 items-center justify-center rounded-full bg-red-500 text-white hover:bg-red-600 shadow-lg"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Upload ảnh mới --}}
                <label class="flex cursor-pointer items-center gap-2 rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 px-6 py-4 hover:border-amber-500 hover:bg-amber-50 dark:border-gray-600 dark:bg-gray-700 dark:hover:border-amber-500 dark:hover:bg-amber-900/20 transition-all">
                    <svg class="h-6 w-6 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Thêm ảnh mới vào gallery</span>
                    <input type="file" wire:model="newGallery" accept="image/*" multiple class="hidden" />
                </label>
                @if (!empty($newGallery))
                    <div class="mt-4 grid grid-cols-2 md:grid-cols-5 gap-4">
                        @foreach ($newGallery as $index => $image)
                            <div class="relative">
                                <img src="{{ $image->temporaryUrl() }}" alt="New {{ $index + 1 }}" class="h-24 w-full rounded-lg object-cover shadow-md" />
                                <button 
                                    type="button" 
                                    wire:click="$set('newGallery.{{ $index }}', null)"
                                    class="absolute -right-2 -top-2 flex h-6 w-6 items-center justify-center rounded-full bg-red-500 text-white hover:bg-red-600 shadow-lg"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Định dạng: JPG, PNG. Mỗi ảnh tối đa 2MB. Tổng cộng không quá 5 ảnh.</p>
                @error('newGallery') 
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> 
                @enderror
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center justify-between gap-4 pt-6 border-t-2 border-gray-100 dark:border-gray-700">
                <button 
                    type="button"
                    wire:click="cancel"
                    class="inline-flex items-center gap-2 rounded-xl border-2 border-gray-300 bg-white px-6 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 shadow-md hover:shadow-lg transition-all duration-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    <span>Hủy</span>
                </button>

                <div class="flex items-center gap-3">
                    {{-- Nút Lưu nháp --}}
                    <button 
                        type="button"
                        wire:click="saveDraft"
                        class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-gray-500 to-gray-600 px-6 py-3 text-sm font-semibold text-white shadow-lg hover:from-gray-600 hover:to-gray-700 hover:shadow-xl transition-all duration-300 hover:scale-105 active:scale-95"
                        title="{{ $post->status === 'published' ? 'Tạo bản sao nháp (bài gốc giữ nguyên)' : 'Lưu thành nháp' }}"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>{{ $post->status === 'published' ? 'Sao chép sang nháp' : 'Lưu nháp' }}</span>
                    </button>

                    {{-- Nút Đăng bài --}}
                    <button 
                        type="button"
                        wire:click="publish"
                        class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-amber-500 to-orange-600 px-6 py-3 text-sm font-semibold text-white shadow-lg hover:from-amber-600 hover:to-orange-700 hover:shadow-xl transition-all duration-300 hover:scale-105 active:scale-95"
                        title="{{ $post->status === 'published' ? 'Cập nhật bài viết' : 'Đăng bài lên' }}"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <span>{{ $post->status === 'published' ? 'Cập nhật' : 'Đăng bài' }}</span>
                    </button>
                </div>
            </div>

        </form>
    </div>

</div>

@push('scripts')
<!-- Quill.js CSS -->
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<!-- Quill.js JS -->
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>

<script>
    let quillEditInstance = null;

    function initQuillEditorEdit() {
        const editorContainer = document.getElementById('content-edit');
        if (!editorContainer) return;

        // Destroy editor cũ nếu tồn tại
        if (quillEditInstance) {
            quillEditInstance = null;
        }

        // Xóa tất cả toolbar cũ
        const existingToolbars = editorContainer.parentElement.querySelectorAll('.ql-toolbar');
        existingToolbars.forEach(toolbar => toolbar.remove());

        // Xóa container cũ
        const existingContainers = editorContainer.parentElement.querySelectorAll('.ql-container');
        existingContainers.forEach(container => {
            if (container !== editorContainer && !container.classList.contains('ql-snow')) {
                container.remove();
            }
        });

        // Reset content div
        editorContainer.className = '';
        editorContainer.innerHTML = '';
        
        // Khởi tạo Quill editor mới
        quillEditInstance = new Quill('#content-edit', {
            theme: 'snow',
            placeholder: 'Viết nội dung bài viết của bạn...',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                    [{ 'font': [] }],
                    [{ 'size': ['small', false, 'large', 'huge'] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'script': 'sub'}, { 'script': 'super' }],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'indent': '-1'}, { 'indent': '+1' }],
                    [{ 'align': [] }],
                    ['blockquote', 'code-block'],
                    ['link', 'image', 'video'],
                    ['clean']
                ]
            }
        });

        // Load nội dung hiện tại
        const currentContent = @js($content ?? '');
        if (currentContent) {
            quillEditInstance.root.innerHTML = currentContent;
        }

        // Sync với Livewire
        quillEditInstance.on('text-change', function() {
            let content = quillEditInstance.root.innerHTML;
            @this.set('content', content);
        });
    }

    document.addEventListener('livewire:navigated', () => {
        setTimeout(initQuillEditorEdit, 100);
    });

    // Cleanup khi rời trang
    document.addEventListener('livewire:navigating', () => {
        if (quillEditInstance) {
            quillEditInstance = null;
        }
    });
</script>

<style>
    .ql-container {
        min-height: 400px;
        font-size: 14px;
    }
    .ql-editor {
        min-height: 400px;
    }
</style>
@endpush
