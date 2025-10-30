    <?php

    use Livewire\Volt\Component;
    use Livewire\Attributes\Layout;
    use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
    use App\Models\Post;
    use App\Models\Category;
    use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

    new #[Layout('components.layouts.app')] #[Title('Quản lý Bài viết')]
    class extends Component
    {
    use WithPagination;

    // --- Search Properties ---
    #[Url(as: 'q', history: true)]
    public string $searchQuery = '';
    
    // --- Sorting Properties ---
    #[Url(as: 'sort', history: true)]
    public string $sortField = 'created_at';
    #[Url(as: 'dir', history: true)]
    public string $sortDirection = 'desc';

    // --- Pagination ---
    #[Url(as: 'per_page', history: true)]
    public int $perPage = 5;
    
    /**
     * Update số mục mỗi trang
     */
    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    // --- Filter Properties ---
    #[Url(as: 'status', history: true)]
    public string $filterStatus = 'all'; // all, draft, published, archived
    
    #[Url(as: 'category', history: true)]
    public string $filterCategory = 'all'; // all, category_id
    
    #[Url(as: 'banner', history: true)]
    public string $filterBanner = 'all'; // all, has_banner, no_banner
    
    #[Url(as: 'gallery', history: true)]
    public string $filterGallery = 'all'; // all, has_gallery, no_gallery
    
    #[Url(as: 'date', history: true)]
    public string $filterDate = 'all'; // all, today, last_7_days, last_30_days, older, specific
    
    #[Url(as: 'specific_date', history: true)]
    public string $specificDate = ''; // Ngày cụ thể được chọn

    public function updatedFilterStatus(): void { $this->resetPage(); }
    public function updatedFilterCategory(): void { $this->resetPage(); }
    public function updatedFilterBanner(): void { $this->resetPage(); }
    public function updatedFilterGallery(): void { $this->resetPage(); }
    public function updatedFilterDate(): void { 
        if ($this->filterDate !== 'specific') {
            $this->specificDate = '';
        }
        $this->resetPage(); 
    }
    public function updatedSpecificDate(): void { $this->resetPage(); }

    // --- Data Properties ---
        public Collection $categories;

        /**
     * Mount component
         */
        public function mount(): void
        {
        $this->categories = Category::orderBy('name', 'asc')->get();
        }

    /**
     * Tự động reset page khi search query thay đổi
     */
    public function updatedSearchQuery(): void
    {
        $this->resetPage();
    }

    /**
     * Xử lý sắp xếp
     */
    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
        }
    
    /**
     * Render component với paginated posts
     */
    public function with(): array
    {
        $query = Post::with(['category.parent', 'user']);

        // Filter theo trạng thái
        if ($this->filterStatus !== 'all') {
            $query->where('status', $this->filterStatus);
        }

        // Filter theo danh mục
        if ($this->filterCategory !== 'all') {
            $query->where('category_id', $this->filterCategory);
        }

        // Filter theo banner
        if ($this->filterBanner === 'has_banner') {
            $query->whereNotNull('banner');
        } elseif ($this->filterBanner === 'no_banner') {
            $query->whereNull('banner');
        }

        // Filter theo gallery
        if ($this->filterGallery === 'has_gallery') {
            $query->whereNotNull('gallery')
                  ->where('gallery', '!=', '[]')
                  ->where('gallery', '!=', 'null');
        } elseif ($this->filterGallery === 'no_gallery') {
            $query->where(function($q) {
                $q->whereNull('gallery')
                  ->orWhere('gallery', '[]')
                  ->orWhere('gallery', 'null');
            });
        }

        // Filter theo ngày tạo
        if ($this->filterDate === 'today') {
            $query->whereDate('created_at', today());
        } elseif ($this->filterDate === 'last_7_days') {
            $query->where('created_at', '>=', now()->subDays(7));
        } elseif ($this->filterDate === 'last_30_days') {
            $query->where('created_at', '>=', now()->subDays(30));
        } elseif ($this->filterDate === 'older') {
            $query->where('created_at', '<', now()->subDays(30));
        } elseif ($this->filterDate === 'specific' && !empty($this->specificDate)) {
            $query->whereDate('created_at', $this->specificDate);
        }

        // Apply search
        if (!empty(trim($this->searchQuery))) {
            $searchTerm = trim($this->searchQuery);
            $query->where(function($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                  ->orWhere('content', 'like', "%{$searchTerm}%");
            });
        }

        // Apply sorting
        $query->orderBy($this->sortField, $this->sortDirection);

        return [
            'posts' => $query->paginate($this->perPage)
        ];
    }

        /**
     * Xóa bài viết
         */
        public function delete(int $id): void
        {
            $post = Post::find($id);
            if ($post) {
                $post->delete();
                // Dispatch event để cập nhật dashboard
                $this->dispatch('post-deleted');
                $this->dispatch('toast-notification', type: 'success', message: 'Xóa bài viết thành công!');
                
                // Set flag for dashboard refresh
                $this->js("localStorage.setItem('dashboardNeedsRefresh', 'true'); window.dispatchEvent(new Event('post-deleted'));");
        } else {
            $this->dispatch('toast-notification', type: 'error', message: 'Không tìm thấy bài viết!');
        }
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

    /**
     * Xuất dữ liệu bài viết ra file Excel
     */
    public function exportToCSV()
    {
        $fileName = 'bai-viet-' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        $posts = Post::with(['category.parent', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];

        return new StreamedResponse(function() use ($posts) {
            $file = fopen('php://output', 'w');
            
            // BOM cho UTF-8 (để Excel hiển thị tiếng Việt đúng)
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Helper function để escape CSV field
            $escapeCsvField = function($field) {
                // Chuyển đổi sang string và escape double quotes
                $field = strval($field);
                // Nếu có dấu chấm phẩy, dấu ngoặc kép, hoặc xuống dòng -> bọc bằng dấu ngoặc kép
                if (str_contains($field, ';') || str_contains($field, '"') || str_contains($field, "\n")) {
                    $field = '"' . str_replace('"', '""', $field) . '"';
                }
                return $field;
            };
            
            // Header row
            $headerRow = ['ID', 'Tiêu đề', 'Danh Mục', 'Danh Mục Gốc', 'Tác giả', 'Trạng thái', 'Có Banner', 'Số ảnh Gallery', 'Ngày tạo', 'Ngày cập nhật'];
            fwrite($file, implode(';', array_map($escapeCsvField, $headerRow)) . "\n");
            
            // Data rows
            foreach ($posts as $post) {
                // Xác định danh mục
                $categoryName = 'N/A';
                $parentCategoryName = 'N/A';
                
                if ($post->category) {
                    if ($post->category->parent_id === null) {
                        // Nếu là danh mục gốc
                        $parentCategoryName = $post->category->name;
                    } else {
                        // Nếu là danh mục con
                        $categoryName = $post->category->name;
                        $parentCategoryName = $post->category->parent?->name ?? 'N/A';
                    }
                }
                
                // Đếm số ảnh gallery
                $galleryCount = 0;
                if ($post->gallery && is_array($post->gallery)) {
                    $galleryCount = count($post->gallery);
                }
                
                $row = [
                    $post->id,
                    $post->title,
                    $categoryName === 'N/A' ? '(Danh mục gốc)' : $categoryName,
                    $parentCategoryName,
                    $post->user?->name ?? 'N/A',
                    $this->getStatusLabel($post->status ?? 'draft'),
                    $post->banner ? 'Có' : 'Không',
                    $galleryCount,
                    $post->created_at->format('d/m/Y H:i'),
                    $post->updated_at->format('d/m/Y H:i'),
                ];
                fwrite($file, implode(';', array_map($escapeCsvField, $row)) . "\n");
            }
            
            fclose($file);
        }, 200, $headers);
    }
};
?>

{{-- Bắt đầu View --}}
<div class="p-6">

    {{-- Card duy nhất chứa tất cả --}}
    <div class="overflow-hidden rounded-2xl border-2 border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-800">
        
        {{-- Header với gradient --}}
        <div class="relative overflow-hidden bg-gradient-to-r from-blue-500 via-cyan-500 to-teal-500 px-8 py-6">
            <div class="relative z-10 flex items-center gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm shadow-lg">
                    <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                    </svg>
                    </div>
    <div>
                    <h1 class="text-2xl font-bold text-white drop-shadow-lg">Quản lý bài viết</h1>
                    <p class="text-sm text-white/90 mt-0.5">Quản lý tất cả bài viết trên hệ thống</p>
                </div>
            </div>
            <div class="absolute -right-10 -top-10 h-32 w-32 rounded-full bg-white/10 blur-3xl"></div>
            <div class="absolute -bottom-10 -left-10 h-32 w-32 rounded-full bg-white/10 blur-3xl"></div>
                            </div>

        {{-- Action Bar --}}
        <div class="bg-gradient-to-r from-white via-blue-50 to-cyan-50 p-5 dark:from-zinc-800 dark:via-blue-950 dark:to-cyan-950 flex flex-wrap items-center gap-4 border-b-2 border-gray-100 dark:border-gray-700">
            <!-- Nút thêm bài viết và xuất excel -->
            <div class="flex flex-shrink-0 items-center gap-3">
                <a 
                    href="{{ route('admin.posts.create') }}"
                    wire:navigate
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-500 to-cyan-600 px-5 py-3 text-sm font-semibold text-white shadow-lg hover:from-blue-600 hover:to-cyan-700 hover:shadow-xl transition-all duration-300 hover:scale-105 active:scale-95"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Thêm bài viết</span>
                </a>

                <button 
                    wire:click="exportToCSV"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-green-600 px-5 py-3 text-sm font-semibold text-white shadow-lg hover:from-emerald-600 hover:to-green-700 hover:shadow-xl transition-all duration-300 hover:scale-105 active:scale-95"
                    title="Xuất file CSV (mở được bằng Excel)"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>Xuất CSV</span>
                </button>
            </div>

            <!-- Thanh tìm kiếm với gradient border -->
            <div class="relative flex-1 min-w-[280px]">
                <div class="absolute inset-0 rounded-full bg-gradient-to-r from-blue-500 via-cyan-500 to-teal-500 opacity-20 blur-sm"></div>
                <input
                    type="search"
                    wire:model.live.debounce.300ms="searchQuery"
                    placeholder="Tìm kiếm bài viết..."
                    class="relative block w-full appearance-none rounded-full border-2 border-gray-200 bg-white py-3 pl-6 pr-16 shadow-md text-sm font-medium
                           focus:border-transparent focus:ring-4 focus:ring-cyan-200
                           dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder-gray-400
                           dark:focus:ring-cyan-900 transition-all duration-300"
                />

                <!-- Icon tìm kiếm gradient -->
                <button
                    type="button"
                    aria-label="Tìm kiếm"
                    class="absolute right-2 top-1/2 -translate-y-1/2 flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-r from-cyan-500 to-teal-600 text-white shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-110 active:scale-95"
                >
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
                    </div>

        {{-- Filter Bar --}}
        <div class="bg-white p-5 dark:bg-gray-800 border-b-2 border-gray-100 dark:border-gray-700">
            <div class="flex flex-wrap items-center gap-4">
                <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    <svg class="inline h-5 w-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    Bộ lọc:
                </div>

                <!-- Lọc theo trạng thái -->
                <div class="flex-1 min-w-[180px]">
                    <select wire:model.live="filterStatus" class="w-full rounded-lg border-2 border-gray-200 px-3 py-2 text-sm focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                        <option value="all">Tất cả trạng thái</option>
                        <option value="draft">Bản nháp</option>
                        <option value="published">Đã đăng</option>
                    </select>
                            </div>

                <!-- Lọc theo danh mục -->
                <div class="flex-1 min-w-[180px]">
                    <select wire:model.live="filterCategory" class="w-full rounded-lg border-2 border-gray-200 px-3 py-2 text-sm focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                        <option value="all">Tất cả danh mục</option>
                        @foreach($categories as $category)
                            @if($category->parent_id === null)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @foreach($categories->where('parent_id', $category->id) as $child)
                                    <option value="{{ $child->id }}">└─ {{ $child->name }}</option>
                                @endforeach
                            @endif
                                    @endforeach
                                </select>
                            </div>

                <!-- Lọc theo banner -->
                <div class="flex-1 min-w-[180px]">
                    <select wire:model.live="filterBanner" class="w-full rounded-lg border-2 border-gray-200 px-3 py-2 text-sm focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                        <option value="all">Tất cả banner</option>
                        <option value="has_banner">Có banner</option>
                        <option value="no_banner">Không có banner</option>
                    </select>
                </div>

                <!-- Lọc theo gallery -->
                <div class="flex-1 min-w-[180px]">
                    <select wire:model.live="filterGallery" class="w-full rounded-lg border-2 border-gray-200 px-3 py-2 text-sm focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                        <option value="all">Tất cả gallery</option>
                        <option value="has_gallery">Có gallery</option>
                        <option value="no_gallery">Không có gallery</option>
                    </select>
                            </div>

                <!-- Lọc theo ngày tạo -->
                <div class="flex-1 min-w-[180px]">
                    <select wire:model.live="filterDate" class="w-full rounded-lg border-2 border-gray-200 px-3 py-2 text-sm focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                        <option value="all">Tất cả ngày</option>
                        <option value="today">Hôm nay</option>
                        <option value="last_7_days">7 ngày qua</option>
                        <option value="last_30_days">30 ngày qua</option>
                        <option value="older"> Cũ hơn 30 ngày</option>
                        <option value="specific">Chọn ngày cụ thể</option>
                    </select>
                </div>

                <!-- Date picker khi chọn "Chọn ngày cụ thể" -->
                @if($filterDate === 'specific')
                    <div class="flex-1 min-w-[180px]">
                        <input 
                            type="date" 
                            wire:model.live="specificDate"
                            class="w-full rounded-lg border-2 border-cyan-500 px-3 py-2 text-sm focus:border-cyan-600 focus:ring-2 focus:ring-cyan-200 dark:border-cyan-600 dark:bg-gray-700 dark:text-gray-200 dark:focus:border-cyan-600"
                            placeholder="Chọn ngày"
                        />
                    </div>
                @endif

                <!-- Nút reset filter -->
                <button 
                    wire:click="$set('filterStatus', 'all'); $set('filterCategory', 'all'); $set('filterBanner', 'all'); $set('filterGallery', 'all'); $set('filterDate', 'all');"
                    class="rounded-lg bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 transition-colors"
                    title="Xóa tất cả bộ lọc"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
                </div>
            </div>

        {{-- Bảng --}}
                    <div class="overflow-x-auto">
            <table class="w-full table-auto">
                <thead class="bg-gradient-to-r from-blue-500 via-cyan-500 to-teal-500 text-white">
                    <tr>
                        {{-- Tiêu đề --}}
                        <th scope="col" class="px-6 py-4 text-left">
                            <button wire:click="sortBy('title')" class="group inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-white hover:text-yellow-200 transition-colors">
                                <span>Tiêu đề</span>
                                <span class="inline-flex flex-col -space-y-1">
                                    @if($sortField === 'title' && $sortDirection === 'asc')
                                        <svg class="h-3 w-3 text-yellow-300" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3.293l4.146 4.147a.5.5 0 0 0 .708-.708l-4.5-4.5a.5.5 0 0 0-.708 0l-4.5 4.5a.5.5 0 1 0 .708.708L8 3.293z"/></svg>
                                        <svg class="h-3 w-3 text-white/40" fill="currentColor" viewBox="0 0 16 16"><path d="M8 12.707l-4.146-4.147a.5.5 0 0 1 .708-.708L8 11.293l3.438-3.44a.5.5 0 0 1 .708.707l-4.5 4.5a.5.5 0 0 1-.708 0z"/></svg>
                                    @elseif($sortField === 'title' && $sortDirection === 'desc')
                                        <svg class="h-3 w-3 text-white/40" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3.293l4.146 4.147a.5.5 0 0 0 .708-.708l-4.5-4.5a.5.5 0 0 0-.708 0l-4.5 4.5a.5.5 0 1 0 .708.708L8 3.293z"/></svg>
                                        <svg class="h-3 w-3 text-yellow-300" fill="currentColor" viewBox="0 0 16 16"><path d="M8 12.707l-4.146-4.147a.5.5 0 0 1 .708-.708L8 11.293l3.438-3.44a.5.5 0 0 1 .708.707l-4.5 4.5a.5.5 0 0 1-.708 0z"/></svg>
                                    @else
                                        <svg class="h-3 w-3 text-white/40 group-hover:text-white/60" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3.293l4.146 4.147a.5.5 0 0 0 .708-.708l-4.5-4.5a.5.5 0 0 0-.708 0l-4.5 4.5a.5.5 0 1 0 .708.708L8 3.293z"/></svg>
                                        <svg class="h-3 w-3 text-white/40 group-hover:text-white/60" fill="currentColor" viewBox="0 0 16 16"><path d="M8 12.707l-4.146-4.147a.5.5 0 0 1 .708-.708L8 11.293l3.438-3.44a.5.5 0 0 1 .708.707l-4.5 4.5a.5.5 0 0 1-.708 0z"/></svg>
                                    @endif
                                </span>
                            </button>
                                        </th>
                        {{-- Danh mục con --}}
                        <th scope="col" class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-white whitespace-nowrap">Danh mục con</th>
                        {{-- Danh mục gốc --}}
                        <th scope="col" class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-white whitespace-nowrap">Danh mục gốc</th>
                        {{-- Tác giả --}}
                        <th scope="col" class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-white whitespace-nowrap">Tác giả</th>
                        {{-- Banner --}}
                        <th scope="col" class="px-4 py-4 text-center text-xs font-bold uppercase tracking-wider text-white whitespace-nowrap">Banner</th>
                        {{-- Gallery --}}
                        <th scope="col" class="px-4 py-4 text-center text-xs font-bold uppercase tracking-wider text-white whitespace-nowrap">Gallery</th>
                        {{-- Ngày đăng --}}
                        <th scope="col" class="px-4 py-4 text-left whitespace-nowrap">
                            <button wire:click="sortBy('created_at')" class="group inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-white hover:text-yellow-200 transition-colors">
                                <span>Ngày đăng</span>
                                <span class="inline-flex flex-col -space-y-1">
                                    @if($sortField === 'created_at' && $sortDirection === 'asc')
                                        <svg class="h-3 w-3 text-yellow-300" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3.293l4.146 4.147a.5.5 0 0 0 .708-.708l-4.5-4.5a.5.5 0 0 0-.708 0l-4.5 4.5a.5.5 0 1 0 .708.708L8 3.293z"/></svg>
                                        <svg class="h-3 w-3 text-white/40" fill="currentColor" viewBox="0 0 16 16"><path d="M8 12.707l-4.146-4.147a.5.5 0 0 1 .708-.708L8 11.293l3.438-3.44a.5.5 0 0 1 .708.707l-4.5 4.5a.5.5 0 0 1-.708 0z"/></svg>
                                    @elseif($sortField === 'created_at' && $sortDirection === 'desc')
                                        <svg class="h-3 w-3 text-white/40" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3.293l4.146 4.147a.5.5 0 0 0 .708-.708l-4.5-4.5a.5.5 0 0 0-.708 0l-4.5 4.5a.5.5 0 1 0 .708.708L8 3.293z"/></svg>
                                        <svg class="h-3 w-3 text-yellow-300" fill="currentColor" viewBox="0 0 16 16"><path d="M8 12.707l-4.146-4.147a.5.5 0 0 1 .708-.708L8 11.293l3.438-3.44a.5.5 0 0 1 .708.707l-4.5 4.5a.5.5 0 0 1-.708 0z"/></svg>
                                    @else
                                        <svg class="h-3 w-3 text-white/40 group-hover:text-white/60" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3.293l4.146 4.147a.5.5 0 0 0 .708-.708l-4.5-4.5a.5.5 0 0 0-.708 0l-4.5 4.5a.5.5 0 1 0 .708.708L8 3.293z"/></svg>
                                        <svg class="h-3 w-3 text-white/40 group-hover:text-white/60" fill="currentColor" viewBox="0 0 16 16"><path d="M8 12.707l-4.146-4.147a.5.5 0 0 1 .708-.708L8 11.293l3.438-3.44a.5.5 0 0 1 .708.707l-4.5 4.5a.5.5 0 0 1-.708 0z"/></svg>
                                    @endif
                                </span>
                            </button>
                        </th>
                        {{-- Trạng thái --}}
                        <th scope="col" class="px-4 py-4 text-center whitespace-nowrap">
                            <button wire:click="sortBy('status')" class="group inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-white hover:text-yellow-200 transition-colors">
                                <span>Trạng thái</span>
                                <span class="inline-flex flex-col -space-y-1">
                                    @if($sortField === 'status' && $sortDirection === 'asc')
                                        <svg class="h-3 w-3 text-yellow-300" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3.293l4.146 4.147a.5.5 0 0 0 .708-.708l-4.5-4.5a.5.5 0 0 0-.708 0l-4.5 4.5a.5.5 0 1 0 .708.708L8 3.293z"/></svg>
                                        <svg class="h-3 w-3 text-white/40" fill="currentColor" viewBox="0 0 16 16"><path d="M8 12.707l-4.146-4.147a.5.5 0 0 1 .708-.708L8 11.293l3.438-3.44a.5.5 0 0 1 .708.707l-4.5 4.5a.5.5 0 0 1-.708 0z"/></svg>
                                    @elseif($sortField === 'status' && $sortDirection === 'desc')
                                        <svg class="h-3 w-3 text-white/40" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3.293l4.146 4.147a.5.5 0 0 0 .708-.708l-4.5-4.5a.5.5 0 0 0-.708 0l-4.5 4.5a.5.5 0 1 0 .708.708L8 3.293z"/></svg>
                                        <svg class="h-3 w-3 text-yellow-300" fill="currentColor" viewBox="0 0 16 16"><path d="M8 12.707l-4.146-4.147a.5.5 0 0 1 .708-.708L8 11.293l3.438-3.44a.5.5 0 0 1 .708.707l-4.5 4.5a.5.5 0 0 1-.708 0z"/></svg>
                                    @else
                                        <svg class="h-3 w-3 text-white/40 group-hover:text-white/60" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3.293l4.146 4.147a.5.5 0 0 0 .708-.708l-4.5-4.5a.5.5 0 0 0-.708 0l-4.5 4.5a.5.5 0 1 0 .708.708L8 3.293z"/></svg>
                                        <svg class="h-3 w-3 text-white/40 group-hover:text-white/60" fill="currentColor" viewBox="0 0 16 16"><path d="M8 12.707l-4.146-4.147a.5.5 0 0 1 .708-.708L8 11.293l3.438-3.44a.5.5 0 0 1 .708.707l-4.5 4.5a.5.5 0 0 1-.708 0z"/></svg>
                                    @endif
                                </span>
                            </button>
                                        </th>
                        {{-- Hành động --}}
                        <th scope="col" class="px-6 py-4 text-center text-xs font-bold uppercase tracking-wider text-white whitespace-nowrap">Hành động</th>
                                    </tr>
                                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse ($posts as $post)
                        <tr wire:key="post-{{ $post->id }}" class="transition-all duration-200 hover:bg-gradient-to-r hover:from-blue-50 hover:via-cyan-50 hover:to-teal-50 dark:hover:from-blue-950/30 dark:hover:via-cyan-950/30 dark:hover:to-teal-950/30 hover:shadow-md">
                            {{-- Tiêu đề --}}
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                                <p class="font-semibold truncate max-w-xs">{{ $post->title }}</p>
                            </td>
                            {{-- Danh mục con --}}
                            <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700 dark:text-gray-300">
                                @if($post->category)
                                    @if($post->category->parent_id === null)
                                        {{-- Nếu post thuộc danh mục gốc, hiển thị ---- --}}
                                        <span class="text-gray-400">----</span>
                                    @else
                                        {{-- Nếu post thuộc danh mục con, hiển thị tên danh mục con --}}
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300">
                                            {{ $post->category->name }}
                                        </span>
                                    @endif
                                @else
                                    <span class="text-gray-400">N/A</span>
                                @endif
                            </td>
                            {{-- Danh mục gốc --}}
                            <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-500 dark:text-gray-400">
                                @if($post->category)
                                    @if($post->category->parent_id === null)
                                        {{-- Nếu post thuộc danh mục gốc, hiển thị tên gốc ở đây --}}
                                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ $post->category->name }}</span>
                                    @else
                                        {{-- Nếu post thuộc danh mục con, hiển thị tên cha --}}
                                        {{ $post->category->parent?->name ?? '—' }}
                                    @endif
                                @else
                                    <span class="text-gray-400">N/A</span>
                                @endif
                            </td>
                            {{-- Tác giả --}}
                            <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700 dark:text-gray-300">
                                <div class="flex items-center gap-2">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-cyan-600 text-white text-xs font-bold">
                                        {{ substr($post->user?->name ?? 'U', 0, 1) }}
                                    </div>
                                    <span>{{ $post->user?->name ?? 'N/A' }}</span>
                                </div>
                            </td>
                            {{-- Banner --}}
                            <td class="whitespace-nowrap px-4 py-4 text-center">
                                @if($post->banner)
                                    <img src="{{ asset('storage/' . $post->banner) }}" alt="Banner" class="h-12 w-20 object-cover rounded-lg shadow-sm mx-auto">
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            {{-- Gallery --}}
                            <td class="whitespace-nowrap px-4 py-4 text-center text-sm">
                                @if($post->gallery && is_array($post->gallery) && count($post->gallery) > 0)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                        {{ count($post->gallery) }} ảnh
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            {{-- Ngày đăng --}}
                            <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ $post->created_at->format('d/m/Y') }}
                            </td>
                            {{-- Trạng thái --}}
                            <td class="whitespace-nowrap px-4 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $this->getStatusBadge($post->status ?? 'draft') }}">
                                    {{ $this->getStatusLabel($post->status ?? 'draft') }}
                                </span>
                            </td>
                            {{-- Hành động --}}
                            <td class="whitespace-nowrap px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    {{-- Nút Xem --}}
                                    <a 
                                        href="{{ route('admin.posts.show', $post->id) }}"
                                        wire:navigate
                                        title="Xem"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-900/50 transition-colors"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                    {{-- Nút Sửa --}}
                                    <a 
                                        href="{{ route('admin.posts.edit', $post->id) }}"
                                        wire:navigate
                                        title="Sửa"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-yellow-100 text-yellow-600 hover:bg-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-400 dark:hover:bg-yellow-900/50 transition-colors"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    {{-- Nút Xóa --}}
                                    <button 
                                        wire:click="delete({{ $post->id }})"
                                        wire:confirm="Bạn có chắc chắn muốn xóa bài viết '{{ $post->title }}'?"
                                        title="Xóa"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-100 text-red-600 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400 dark:hover:bg-red-900/50 transition-colors"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                            <td colspan="9" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center justify-center space-y-3">
                                    @if(empty(trim($searchQuery)))
                                        <svg class="h-16 w-16 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        <p class="text-base font-semibold text-gray-500 dark:text-gray-400">Chưa có bài viết nào</p>
                                        <p class="text-sm text-gray-400 dark:text-gray-500">Bắt đầu bằng cách tạo bài viết đầu tiên</p>
                                    @else
                                        <svg class="h-16 w-16 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                        </svg>
                                        <p class="text-base font-semibold text-gray-500 dark:text-gray-400">Không tìm thấy kết quả phù hợp</p>
                                        <p class="text-sm text-gray-400 dark:text-gray-500">Thử tìm kiếm với từ khóa khác</p>
                                        <button 
                                            wire:click="$set('searchQuery', '')"
                                            class="mt-2 inline-flex items-center gap-2 rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 transition-colors"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                            Xóa tìm kiếm
                                        </button>
                                    @endif
                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
        
        {{-- Pagination --}}
        <div class="border-t border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
            <x-custom-pagination :paginator="$posts" :perPage="$perPage" />
                    </div>
    
        </div>
    </div>
