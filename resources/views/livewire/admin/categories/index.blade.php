<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\WithPagination;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Validation\Rule as ValidationRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Attributes\Title;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Excel as ExcelWriter;

new #[Layout('components.layouts.app')] #[Title('Quản lý Danh mục')]
class extends Component
{
    use WithPagination;
    // --- Form Properties ---
    #[Rule('required|string|min:3|max:255')]
    public string $name = '';
    #[Rule('nullable|exists:categories,id')]
    public ?int $parentId = null;
    #[Rule('boolean')] // Giữ lại thuộc tính này dù không hiển thị, có thể dùng sau
    public bool $isVisible = true;

    // --- State Management ---
    public ?Category $editingCategory = null;
    public bool $showAddEditModal = false;
    public bool $isAddingChild = false;
    #[Url(as: 'q', history: true)]
    public string $searchQuery = '';

    // --- Sorting Properties ---
    #[Url(as: 'sort', history: true)]
    public string $sortField = 'name';
    #[Url(as: 'dir', history: true)]
    public string $sortDirection = 'asc';

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
    #[Url(as: 'type', history: true)]
    public string $filterType = 'all'; // all, root, child
    #[Url(as: 'visible', history: true)]
    public string $filterVisible = 'all'; // all, visible, hidden
    #[Url(as: 'posts', history: true)]
    public string $filterPosts = 'all'; // all, has_posts, no_posts
    #[Url(as: 'date', history: true)]
    public string $filterDate = 'all'; // all, today, last_7_days, last_30_days, older, specific
    
    #[Url(as: 'specific_date', history: true)]
    public string $specificDate = ''; // Ngày cụ thể được chọn

    // --- Post Viewing Modal ---
    public ?Category $viewingCategory = null;
    public ?EloquentCollection $postsForModal = null;


    // --- Computed Properties ---

    /**
     * Lấy danh sách categories đã lọc và paginate
     * SỬ DỤNG QUERY BUILDER để tối ưu performance
     */
    public function filteredCategories()
    {
        $query = Category::with(['parent'])->withCount('posts');
        
        // Lọc theo loại (gốc/con)
        if ($this->filterType === 'root') {
            $query->whereNull('parent_id');
        } elseif ($this->filterType === 'child') {
            $query->whereNotNull('parent_id');
        }
        
        // Lọc theo trạng thái hiển thị
        if ($this->filterVisible === 'visible') {
            $query->where('is_visible', true);
        } elseif ($this->filterVisible === 'hidden') {
            $query->where('is_visible', false);
        }
        
        // Lọc theo bài viết (sử dụng has/doesntHave relationship)
        if ($this->filterPosts === 'has_posts') {
            $query->has('posts');
        } elseif ($this->filterPosts === 'no_posts') {
            $query->doesntHave('posts');
        }
        
        // Lọc theo search (sử dụng LIKE trong SQL)
        if (!empty(trim($this->searchQuery))) {
            $searchTerm = trim($this->searchQuery);
            $query->where('name', 'like', '%' . $searchTerm . '%');
        }

        // Lọc theo ngày tạo
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
        
        // Áp dụng sorting trực tiếp bằng SQL
        $this->applySortingToQuery($query);
        
        // Paginate - Laravel tự động xử lý
        return $query->paginate($this->perPage);
    }
    
    /**
     * Áp dụng sorting trực tiếp vào query builder
     */
    private function applySortingToQuery($query): void
    {
        $direction = $this->sortDirection;
        
        switch ($this->sortField) {
            case 'name':
                $query->orderBy('name', $direction);
                break;
            case 'parent':
                // Join với parent để sort theo tên parent
                $query->leftJoin('categories as parent_cat', 'categories.parent_id', '=', 'parent_cat.id')
                      ->orderBy('parent_cat.name', $direction)
                      ->select('categories.*'); // Chỉ select columns từ categories
                break;
            case 'posts_count':
                $query->orderBy('posts_count', $direction);
                break;
            case 'created_at':
                $query->orderBy('created_at', $direction);
                break;
            default:
                $query->orderBy('name', 'asc');
        }
    }
    
    /**
     * Xử lý sắp xếp khi click vào cột
     */
    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            // Đổi hướng nếu click vào cột đang sort
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            // Sort mới theo cột khác
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        
        // Livewire tự động re-render với sort mới
    }

    /**
     * Tự động reset về trang 1 khi search query thay đổi
     */
    public function updatedSearchQuery(): void
    {
        $this->resetPage();
    }

    /**
     * Reset về trang 1 khi filter thay đổi
     */
    public function updatedFilterType(): void
    {
        $this->resetPage();
    }

    public function updatedFilterVisible(): void
    {
        $this->resetPage();
    }

    public function updatedFilterPosts(): void
    {
        $this->resetPage();
    }

    public function updatedFilterDate(): void
    {
        if ($this->filterDate !== 'specific') {
            $this->specificDate = '';
        }
        $this->resetPage();
    }

    public function updatedSpecificDate(): void
    {
        $this->resetPage();
    }

    /**
     * Toggle trạng thái hiển thị với Transaction
     */
    public function toggleVisibility(int $id): void
    {
        try {
            $category = Category::with('parent')->findOrFail($id);
            
            DB::transaction(function () use ($category) {
                $newVisibility = !$category->is_visible;
                $category->update(['is_visible' => $newVisibility]);
                
                // Nếu đang hiển thị danh mục con và danh mục gốc đang bị ẩn
                // → Tự động hiển thị danh mục gốc luôn
                if ($newVisibility && $category->parent_id && $category->parent && !$category->parent->is_visible) {
                    $category->parent->update(['is_visible' => true]);
                    $this->dispatch('toast-notification', 
                        type: 'success', 
                        message: 'Đã hiển thị danh mục con và danh mục gốc!'
                    );
                } else {
                    $this->dispatch('toast-notification', 
                        type: 'success', 
                        message: $newVisibility ? 'Đã hiển thị danh mục!' : 'Đã ẩn danh mục!'
                    );
                }
            });
        } catch (\Exception $e) {
            $this->dispatch('toast-notification', 
                type: 'error', 
                message: 'Lỗi: ' . $e->getMessage()
            );
        }
    }

    /**
     * Xuất dữ liệu ra file excel (mở được bằng Excel)
     */
    public function exportToCSV()
    {
        $fileName = 'danh-muc-' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        $categories = Category::with(['parent'])
            ->withCount('posts')
            ->orderBy('parent_id', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];

        return new StreamedResponse(function() use ($categories) {
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
            $headers = ['ID', 'Tên Danh Mục', 'Danh Mục Gốc', 'Slug', 'Số Bài Viết', 'Hiển Thị', 'Ngày Tạo', 'Ngày Cập Nhật'];
            fwrite($file, implode(';', array_map($escapeCsvField, $headers)) . "\n");
            
            // Data rows
            foreach ($categories as $category) {
                $row = [
                    $category->id,
                    $category->name,
                    $category->parent ? $category->parent->name : '(Danh mục gốc)',
                    $category->slug,
                    $category->posts_count ?? 0,
                    $category->is_visible ? 'Có' : 'Không',
                    $category->created_at->format('d/m/Y H:i'),
                    $category->updated_at->format('d/m/Y H:i'),
                ];
                fwrite($file, implode(';', array_map($escapeCsvField, $row)) . "\n");
            }
            
            fclose($file);
        }, 200, $headers);
    }

    /**
     * Lấy danh sách options cho dropdown - CHỈ DANH MỤC GỐC
     * Có caching để tối ưu performance
     */
    public function categoryOptions(): BaseCollection
    {
        // Cache key dựa theo editing category ID để đảm bảo exclude đúng
        $cacheKey = 'category_options_' . ($this->editingCategory?->id ?? 'new');
        
        return Cache::remember($cacheKey, 3600, function () {
            $query = Category::whereNull('parent_id')
                ->orderBy('name', 'asc')
                ->select('id', 'name');
            
            // Bỏ qua nếu đang edit chính nó
            if ($this->editingCategory) {
                $query->where('id', '!=', $this->editingCategory->id);
            }
            
            return $query->get()->map(function ($cat) {
                $obj = new \stdClass();
                $obj->id = $cat->id;
                $obj->display_name = $cat->name;
                return $obj;
            });
        });
    }


    // --- Hooks ---
    public function updatedParentId($value): void { $this->resetErrorBag('parentId'); }

    // --- Lifecycle Methods ---
    public function mount(): void 
    { 
        // Không cần load categories nữa vì dùng Query Builder
    }

    // --- Core Logic Methods ---

    /**
     * Reset form về trạng thái ban đầu
     */
    public function resetForm(): void
    {
        $this->reset(['name', 'parentId', 'isVisible', 'editingCategory', 'isAddingChild']);
        $this->resetErrorBag();
    }
    
    /**
     * Mở modal thêm danh mục gốc
     */
    public function openAddRootModal(): void
    {
        $this->resetForm(); 
        $this->isAddingChild = false; 
        $this->parentId = null;
        $this->showAddEditModal = true;
    }
    
    /**
     * Mở modal thêm danh mục con
     */
    public function openAddChildModal(): void
    {
        // Kiểm tra có danh mục gốc chưa
        if (Category::whereNull('parent_id')->count() === 0) {
            $this->dispatch('toast-notification', type: 'error', message: 'Cần tạo danh mục gốc trước.');
            return;
        }
        
        $this->resetForm();
        $this->isAddingChild = true;
        $this->parentId = null; 
        $this->showAddEditModal = true;
    }
    
    /**
     * Mở modal sửa danh mục
     */
    public function edit(int $id): void
    {
        $category = Category::find($id);
        if ($category) {
            $this->resetErrorBag();
            $this->editingCategory = $category;
            $this->name = $category->name;
            $this->parentId = $category->parent_id;
            $this->isVisible = $category->is_visible;
            $this->isAddingChild = $category->parent_id !== null;
            $this->showAddEditModal = true;
        }
    }
    
    /**
     * Đóng modal thêm/sửa
     */
    public function closeAddEditModal(): void
    {
        $this->showAddEditModal = false;
        $this->resetForm();
    }
    
    /**
     * Validate slug cho category
     */
    protected function validateSlug(): array
    {
        $slug = Str::slug($this->name);
        $query = ValidationRule::unique('categories', 'slug');
        
        if ($this->editingCategory) {
            $query->ignore($this->editingCategory->id);
        }
        
        $validator = Validator::make(['slug' => $slug], ['slug' => [$query]]);
        
        if ($validator->fails()) {
            $this->addError('name', 'Tên đã trùng lặp (slug).');
            return [];
        }
        
        return ['slug' => $slug];
    }
    
    /**
     * Lưu category (create hoặc update)
     */
    public function save(): void
    {
        // Validation rules
        $rules = [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'isVisible' => ['boolean'],
            'parentId' => ['nullable', 'exists:categories,id'],
        ];
        
        // Parent ID bắt buộc khi thêm con
        if ($this->isAddingChild && !$this->editingCategory) {
            $rules['parentId'][] = 'required';
        }
        
        $validator = Validator::make(
            ['name' => $this->name, 'isVisible' => $this->isVisible, 'parentId' => $this->parentId],
            $rules,
            ['parentId.required' => 'Vui lòng chọn danh mục cha.']
        );
        
        if ($validator->fails()) {
            $this->setErrorBag($validator->errors());
            return;
        }
        
        // Kiểm tra chỉ cho phép 2 cấp (cha-con)
        if ($this->parentId) {
            $selectedParent = Category::find($this->parentId);
            if ($selectedParent && $selectedParent->parent_id !== null) {
                $this->addError('parentId', 'Chỉ được tạo danh mục con (2 cấp). Không thể tạo cháu (3 cấp).');
                return;
            }
        }
        
        // Validate slug
        $validatedSlug = $this->validateSlug();
        if (empty($validatedSlug)) return;
        
        // Kiểm tra không thể chọn chính nó làm cha
        if ($this->editingCategory && $this->parentId == $this->editingCategory->id) {
            $this->addError('parentId', 'Không thể chọn chính nó làm danh mục cha.');
            return;
        }
        
        // Prepare data
        $data = [
            'name' => $this->name,
            'slug' => $validatedSlug['slug'],
            'parent_id' => $this->parentId,
            'is_visible' => $this->isVisible
        ];
        
        try {
            if ($this->editingCategory) {
                // Update
                $categoryToUpdate = Category::findOrFail($this->editingCategory->id);
                $categoryToUpdate->update($data);
                $this->dispatch('toast-notification', type: 'success', message: 'Cập nhật danh mục thành công!');
            } else {
                // Create
                Category::create($data);
                $this->dispatch('toast-notification', type: 'success', message: 'Tạo danh mục mới thành công!');
            }
            
            // Clear cache vì có thay đổi
            Cache::flush(); // Hoặc chỉ xóa cache liên quan: Cache::forget('category_options_*')
            
            $this->closeAddEditModal();
        } catch (\Exception $e) {
            $this->dispatch('toast-notification', type: 'error', message: 'Lỗi: ' . $e->getMessage());
        }
    }
    /**
     * Xóa category với error handling tốt
     */
    public function delete(int $id): void
    {
        try {
            $category = Category::with('children')->withCount('posts')->findOrFail($id);
            
            // Kiểm tra có danh mục con không
            if ($category->children->isNotEmpty()) {
                throw new \Exception('Không thể xóa danh mục có danh mục con!');
            }
            
            // Kiểm tra có bài viết không
            if ($category->posts_count > 0) {
                throw new \Exception('Không thể xóa danh mục có bài viết!');
            }
            
            // Xóa trong transaction
            DB::transaction(function () use ($category) {
                $category->delete();
            });
            
            // Clear cache vì có thay đổi
            Cache::flush();
            
            $this->dispatch('toast-notification', type: 'success', message: 'Xóa danh mục thành công!');
            
            // Nếu đang edit category này thì đóng modal
            if ($this->editingCategory && $this->editingCategory->id === $id) {
                $this->closeAddEditModal();
            }
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $this->dispatch('toast-notification', type: 'error', message: 'Không tìm thấy danh mục!');
        } catch (\Exception $e) {
            $this->dispatch('toast-notification', type: 'error', message: $e->getMessage());
        }
    }
    
    /**
     * Mở modal xem bài viết trong category
     */
    public function openPostModal(int $categoryId): void
    {
        $category = Category::withCount('posts')->find($categoryId);
        if ($category) {
            $this->viewingCategory = $category;
            $this->postsForModal = Post::where('category_id', $categoryId)
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->get();
        }
    }
    
    /**
     * Đóng modal xem bài viết
     */
    public function closePostModal(): void 
    { 
        $this->reset(['viewingCategory', 'postsForModal']);
    }

     // Truyền computed properties vào view (SỬA LẠI: dùng filtered)
     public function with(): array
     {
         return [
             'categoryOptions' => $this->categoryOptions(),
             // SỬA LẠI: Truyền danh sách đã lọc
             'displayCategories' => $this->filteredCategories(),
         ];
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
                    <flux:icon.folder class="size-8 text-white" />
                </div>
                <h1 class="text-2xl font-bold text-white drop-shadow-lg">Quản lý Danh mục</h1>
            </div>
            <div class="absolute -right-10 -top-10 h-32 w-32 rounded-full bg-white/10 blur-3xl"></div>
            <div class="absolute -bottom-10 -left-10 h-32 w-32 rounded-full bg-white/10 blur-3xl"></div>
        </div>

        {{-- Action Bar --}}
        <div class="bg-gradient-to-r from-white via-blue-50 to-cyan-50 p-5 dark:from-zinc-800 dark:via-blue-950 dark:to-cyan-950 flex flex-wrap items-center gap-4 border-b-2 border-gray-100 dark:border-gray-700">
        <!-- Nhóm nút với gradient -->
        <div class="flex flex-shrink-0 items-center gap-3">
            <button 
                wire:click="openAddRootModal"
                class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-500 to-cyan-600 px-5 py-3 text-sm font-semibold text-white shadow-lg hover:from-blue-600 hover:to-cyan-700 hover:shadow-xl transition-all duration-300 hover:scale-105 active:scale-95"
            >
                <flux:icon.folder class="size-5" />
                <span>Thêm Mục Gốc</span>
            </button>

            <button 
                wire:click="openAddChildModal"
                class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-cyan-500 to-teal-600 px-5 py-3 text-sm font-semibold text-white shadow-lg hover:from-cyan-600 hover:to-teal-700 hover:shadow-xl transition-all duration-300 hover:scale-105 active:scale-95"
            >
                <flux:icon.folder-plus class="size-5" />
                <span>Thêm Mục Con</span>
            </button>

            <button 
                wire:click="exportToCSV"
                class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-green-600 px-5 py-3 text-sm font-semibold text-white shadow-lg hover:from-emerald-600 hover:to-green-700 hover:shadow-xl transition-all duration-300 hover:scale-105 active:scale-95"
                title="Xuất file CSV (mở được bằng Excel)"
            >
                <flux:icon.arrow-down-tray class="size-5" />
                <span>Xuất CSV</span>
            </button>
  </div>

        <!-- Thanh tìm kiếm với gradient border -->
        <div class="relative flex-1 min-w-[280px]">
            <div class="absolute inset-0 rounded-full bg-gradient-to-r from-blue-500 via-cyan-500 to-teal-500 opacity-20 blur-sm"></div>
  <input
    type="search"
    wire:model.live.debounce.300ms="searchQuery"
                placeholder="🔍 Tìm kiếm tên danh mục..."
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
                <flux:icon.magnifying-glass class="size-5" />
            </button>
</div>
    </div>

        {{-- Filter Bar --}}
        <div class="bg-white p-5 dark:bg-gray-800 border-b-2 border-gray-100 dark:border-gray-700">
        <div class="flex flex-wrap items-center gap-4">
            <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                <flux:icon.funnel class="inline size-5 mr-1" />
                Bộ lọc:
            </div>

            <!-- Lọc theo loại -->
            <div class="flex-1 min-w-[180px]">
                <select wire:model.live="filterType" class="w-full rounded-lg border-2 border-gray-200 px-3 py-2 text-sm focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    <option value="all">Tất cả loại</option>
                    <option value="root">Chỉ danh mục gốc</option>
                    <option value="child">Chỉ danh mục con</option>
                </select>
</div>

            <!-- Lọc theo trạng thái -->
            <div class="flex-1 min-w-[180px]">
                <select wire:model.live="filterVisible" class="w-full rounded-lg border-2 border-gray-200 px-3 py-2 text-sm focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    <option value="all"> Tất cả trạng thái</option>
                    <option value="visible">Đang hiển thị</option>
                    <option value="hidden">Đang ẩn</option>
                </select>
            </div>
            
            <!-- Lọc theo bài viết -->
            <div class="flex-1 min-w-[180px]">
                <select wire:model.live="filterPosts" class="w-full rounded-lg border-2 border-gray-200 px-3 py-2 text-sm focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    <option value="all"> Tất cả</option>
                    <option value="has_posts">Có bài viết</option>
                    <option value="no_posts"> Chưa có bài viết</option>
                </select>
            </div>
            
            <!-- Lọc theo ngày tạo -->
            <div class="flex-1 min-w-[180px]">
                <select wire:model.live="filterDate" class="w-full rounded-lg border-2 border-gray-200 px-3 py-2 text-sm focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    <option value="all"> Tất cả ngày</option>
                    <option value="today"> Hôm nay</option>
                    <option value="last_7_days"> 7 ngày qua</option>
                    <option value="last_30_days">30 ngày qua</option>
                    <option value="older">Cũ hơn 30 ngày</option>
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
                wire:click="$set('filterType', 'all'); $set('filterVisible', 'all'); $set('filterPosts', 'all'); $set('filterDate', 'all');"
                class="rounded-lg bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 transition-colors"
                title="Xóa tất cả bộ lọc"
            >
                <flux:icon.x-mark class="size-5" />
            </button>
        </div>
    </div>





    {{-- Bảng với gradient header đẹp --}}
        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full table-auto">
                <thead class="bg-gradient-to-r from-blue-500 via-cyan-500 to-teal-500 text-white">
                    <tr>
                        {{-- Cột Tên danh mục - Có Sort --}}
                        <th scope="col" class="px-6 py-4 text-left">
                            <button wire:click="sortBy('name')" class="group inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-white hover:text-yellow-200 transition-colors">
                                <span>Tên danh mục</span>
                                <span class="inline-flex flex-col -space-y-1">
                                    @if($sortField === 'name' && $sortDirection === 'asc')
                                        <flux:icon.chevron-up class="size-3 text-yellow-300" variant="solid" />
                                        <flux:icon.chevron-down class="size-3 text-white/40" variant="solid" />
                                    @elseif($sortField === 'name' && $sortDirection === 'desc')
                                        <flux:icon.chevron-up class="size-3 text-white/40" variant="solid" />
                                        <flux:icon.chevron-down class="size-3 text-yellow-300" variant="solid" />
                                    @else
                                        <flux:icon.chevron-up class="size-3 text-white/40 group-hover:text-white/60" variant="solid" />
                                        <flux:icon.chevron-down class="size-3 text-white/40 group-hover:text-white/60" variant="solid" />
                                    @endif
                                </span>
                            </button>
                        </th>
                        {{-- Cột Danh mục gốc - Có Sort --}}
                        <th scope="col" class="px-6 py-4 text-left">
                            <button wire:click="sortBy('parent')" class="group inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-white hover:text-yellow-200 transition-colors">
                                <span>Danh mục gốc</span>
                                <span class="inline-flex flex-col -space-y-1">
                                    @if($sortField === 'parent' && $sortDirection === 'asc')
                                        <flux:icon.chevron-up class="size-3 text-yellow-300" variant="solid" />
                                        <flux:icon.chevron-down class="size-3 text-white/40" variant="solid" />
                                    @elseif($sortField === 'parent' && $sortDirection === 'desc')
                                        <flux:icon.chevron-up class="size-3 text-white/40" variant="solid" />
                                        <flux:icon.chevron-down class="size-3 text-yellow-300" variant="solid" />
                                    @else
                                        <flux:icon.chevron-up class="size-3 text-white/40 group-hover:text-white/60" variant="solid" />
                                        <flux:icon.chevron-down class="size-3 text-white/40 group-hover:text-white/60" variant="solid" />
                                    @endif
                                </span>
                            </button>
                        </th>
                        {{-- Cột Bài viết - Có Sort --}}
                        <th scope="col" class="px-4 py-4 text-center whitespace-nowrap">
                            <button wire:click="sortBy('posts_count')" class="group inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-white hover:text-yellow-200 transition-colors">
                                <span>Bài viết</span>
                                <span class="inline-flex flex-col -space-y-1">
                                    @if($sortField === 'posts_count' && $sortDirection === 'asc')
                                        <flux:icon.chevron-up class="size-3 text-yellow-300" variant="solid" />
                                        <flux:icon.chevron-down class="size-3 text-white/40" variant="solid" />
                                    @elseif($sortField === 'posts_count' && $sortDirection === 'desc')
                                        <flux:icon.chevron-up class="size-3 text-white/40" variant="solid" />
                                        <flux:icon.chevron-down class="size-3 text-yellow-300" variant="solid" />
                                    @else
                                        <flux:icon.chevron-up class="size-3 text-white/40 group-hover:text-white/60" variant="solid" />
                                        <flux:icon.chevron-down class="size-3 text-white/40 group-hover:text-white/60" variant="solid" />
                                    @endif
                                </span>
                            </button>
                        </th>
                        {{-- Cột Hiển thị --}}
                        <th scope="col" class="px-4 py-4 text-center whitespace-nowrap">
                            <span class="text-xs font-bold uppercase tracking-wider text-white">Hiển thị</span>
                        </th>
                        {{-- Cột Ngày tạo - Có Sort --}}
                        <th scope="col" class="px-6 py-4 text-left whitespace-nowrap">
                            <button wire:click="sortBy('created_at')" class="group inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-white hover:text-yellow-200 transition-colors">
                                <span>Ngày tạo</span>
                                <span class="inline-flex flex-col -space-y-1">
                                    @if($sortField === 'created_at' && $sortDirection === 'asc')
                                        <flux:icon.chevron-up class="size-3 text-yellow-300" variant="solid" />
                                        <flux:icon.chevron-down class="size-3 text-white/40" variant="solid" />
                                    @elseif($sortField === 'created_at' && $sortDirection === 'desc')
                                        <flux:icon.chevron-up class="size-3 text-white/40" variant="solid" />
                                        <flux:icon.chevron-down class="size-3 text-yellow-300" variant="solid" />
                                    @else
                                        <flux:icon.chevron-up class="size-3 text-white/40 group-hover:text-white/60" variant="solid" />
                                        <flux:icon.chevron-down class="size-3 text-white/40 group-hover:text-white/60" variant="solid" />
                                    @endif
                                </span>
                            </button>
                        </th>
                        {{-- Cột Hành động - Không Sort --}}
                        <th scope="col" class="px-6 py-4 text-center text-xs font-bold uppercase tracking-wider text-white whitespace-nowrap">Hành động</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
                    {{-- Lặp qua $displayCategories (đã lọc, sort theo tên) --}}
                    @forelse ($displayCategories as $category)
                         <tr wire:key="cat-{{ $category->id }}" class="transition-all duration-200 hover:bg-gradient-to-r hover:from-blue-50 hover:via-cyan-50 hover:to-teal-50 dark:hover:from-blue-950/30 dark:hover:via-cyan-950/30 dark:hover:to-teal-950/30 hover:shadow-md">
                             {{-- Cột Tên danh mục (CON) --}}
                             <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                                 @if($category->parent_id === null)
                                     {{-- Nếu là danh mục gốc, hiển thị ---- --}}
                                     <span class="text-gray-400">----</span>
                                 @else
                                     {{-- Nếu là danh mục con, hiển thị tên --}}
                                 {{ $category->name }}
                                 @endif
                             </td>
                             {{-- Cột Danh mục gốc (CHA) --}}
                             <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                 @if($category->parent_id === null)
                                     {{-- Nếu là danh mục gốc, hiển thị tên của nó ở đây --}}
                                     <span class="font-medium text-gray-900 dark:text-gray-100">{{ $category->name }}</span>
                                 @else
                                     {{-- Nếu là danh mục con, hiển thị tên cha --}}
                                     {{ $category->parent?->name ?? '—' }}
                                 @endif
                             </td>
                             {{-- Cột Bài viết --}}
                             <td class="whitespace-nowrap px-4 py-4 text-sm text-center text-gray-500 dark:text-gray-400">
                                 @php
                                     $postsCount = $category->posts_count ?? 0;
                                 @endphp
                                 @if ($postsCount > 0)
                                     <button wire:click="openPostModal({{ $category->id }})" class="text-cyan-600 hover:underline dark:text-cyan-400 dark:hover:underline font-semibold">
                                         {{ $postsCount }}
                                     </button>
                                 @else 
                                     <span class="text-gray-400">0</span>
                                 @endif
                             </td>
                             {{-- Cột Hiển thị - Icon Mắt --}}
                             <td class="whitespace-nowrap px-4 py-4 text-center">
                                 <button 
                                    wire:click="toggleVisibility({{ $category->id }})"
                                    class="inline-flex items-center justify-center rounded-lg p-2 transition-all duration-200 hover:scale-110 active:scale-95 focus:outline-none {{ $category->is_visible ? 'text-emerald-600 hover:bg-emerald-50 active:bg-emerald-100 dark:text-emerald-400 dark:hover:bg-emerald-950/30 dark:active:bg-emerald-950/50' : 'text-gray-400 hover:bg-gray-100 active:bg-gray-200 dark:text-gray-500 dark:hover:bg-gray-700 dark:active:bg-gray-600' }}"
                                    title="{{ $category->is_visible ? 'Đang hiển thị - Click để ẩn' : 'Đang ẩn - Click để hiển thị' }}"
                                >
                                    @if($category->is_visible)
                                        <flux:icon.eye class="size-5" />
                                    @else
                                        <flux:icon.eye-slash class="size-5" />
                                    @endif
                                </button>
                             </td>
                             {{-- Cột Ngày tạo --}}
                             <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $category->created_at->format('d/m/Y') }}</td>
                             {{-- Cột Hành động --}}
                             <td class="whitespace-nowrap px-6 py-4 text-center">
                                 <div class="flex items-center justify-center gap-2">
                                     {{-- Nút Sửa --}}
                                     <button 
                                         wire:click="edit({{ $category->id }})"
                                         title="Sửa"
                                         class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-yellow-100 text-yellow-600 hover:bg-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-400 dark:hover:bg-yellow-900/50 transition-colors"
                                     >
                                         <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                         </svg>
                                     </button>
                                     {{-- Nút Xóa --}}
                                     <button 
                                         wire:click="delete({{ $category->id }})"
                                         wire:confirm="Bạn có chắc chắn muốn xóa danh mục '{{ $category->name }}'? Không thể xóa nếu có danh mục con hoặc bài viết."
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
                         <tr><td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">{{ empty(trim($searchQuery)) ? 'Chưa có danh mục nào.' : 'Không tìm thấy kết quả.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{-- Pagination --}}
        <div class="border-t border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
            <x-custom-pagination :paginator="$displayCategories" :perPage="$perPage" />
        </div>

    </div>
    {{-- End of main card --}}

   {{-- Add/Edit Modal - HIỂN THỊ GIỮA, NÚT MÀU --}}
    {{-- Add/Edit Modal - POPUP GIỮA MÀN HÌNH --}}
    @if ($showAddEditModal)
    <div x-data="{ showModal: @entangle('showAddEditModal').live }" 
         x-show="showModal" 
         x-on:keydown.escape.window="showModal = false; @this.call('closeAddEditModal')"
         class="fixed inset-0 z-50 overflow-y-auto" 
         style="display: none;">
        
        {{-- Container để center modal --}}
        <div class="flex min-h-screen items-center justify-center p-4">
            
            {{-- Overlay nền đen --}}
            <div x-show="showModal" 
                 x-transition:enter="ease-out duration-300" 
                 x-transition:enter-start="opacity-0" 
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200" 
                 x-transition:leave-start="opacity-100" 
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-black bg-opacity-75 transition-opacity" 
                 wire:click="closeAddEditModal"></div>
            
            {{-- Modal content --}}
            <div x-show="showModal" 
                 x-transition:enter="ease-out duration-300" 
                 x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95" 
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200" 
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                 x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                 class="relative z-10 w-full max-w-lg transform overflow-hidden rounded-lg bg-white shadow-2xl transition-all dark:bg-gray-800">
                
                <form wire:submit="save">
                    {{-- Header --}}
                    <div class="border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
                        <h3 class="text-center text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ $editingCategory ? 'Cập nhật Danh mục' : ($isAddingChild ? 'Thêm Danh mục Con' : 'Thêm Danh mục Cha') }}
                        </h3>
                    </div>
                    
                    {{-- Body --}}
                    <div class="px-6 py-6">
                        <div class="space-y-5">
                            {{-- Trường Tên Danh mục --}}
                            <div>
                                <label for="modal-name" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Tên Danh mục
                                </label>
                                <input 
                                    id="modal-name" 
                                    type="text" 
                                    wire:model.lazy="name" 
                                    required 
                                    autofocus 
                                    placeholder="Ví dụ: Công nghệ"
                                    class="block w-full rounded-md border border-gray-300 px-4 py-3 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:placeholder-gray-400 sm:text-sm"
                                />
                                @error('name') 
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> 
                                @enderror
                            </div>
                            
                            {{-- Trường Danh mục Cha - CHỈ hiển thị khi thêm/sửa danh mục CON --}}
                            @if ($isAddingChild)
                                <div>
                                    <label for="modal-parentId" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ !$editingCategory ? 'Chọn Danh mục Cha *' : 'Danh mục cha' }}
                                    </label>
                                    <select 
                                        id="modal-parentId" 
                                        wire:model="parentId" 
                                        @if(!$editingCategory) required @endif
                                        class="block w-full rounded-md border border-gray-300 px-4 py-3 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:focus:border-indigo-500 dark:focus:ring-indigo-500 sm:text-sm"
                                    >
                                        @if(!$editingCategory)
                                         <option value="" selected>-- Vui lòng chọn --</option>
                                     @else
                                         <option value="">— Danh mục gốc —</option>
                                     @endif
                                        
                                    @foreach ($categoryOptions as $categoryOption)
                                        <option value="{{ $categoryOption->id }}">
                                                {{ $categoryOption->display_name }}
                                        </option>
                                    @endforeach
                                </select>
                                    @error('parentId') 
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> 
                                    @enderror
                            </div>
                        @endif
                        </div>
                    </div>
                    
                    {{-- Footer với nút --}}
                    <div class="flex justify-center gap-3 bg-gray-50 px-6 py-4 dark:bg-gray-700/50">
                        {{-- Nút Cập nhật/Lưu - Màu xanh lá --}}
                        <button 
                            type="submit" 
                            wire:loading.attr="disabled" 
                            wire:target="save"
                            style="background-color: #16a34a; color: white;"
                            class="min-w-[120px] rounded-md border border-transparent px-6 py-2.5 text-sm font-medium shadow-sm transition-all hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="save">
                                {{ $editingCategory ? 'Cập nhật' : 'Lưu' }}
                            </span>
                            <span wire:loading wire:target="save">Đang lưu...</span>
                        </button>
                        
                        {{-- Nút Hủy - Màu đỏ --}}
                        <button 
                            type="button" 
                            wire:click="closeAddEditModal"
                            style="background-color: #dc2626; color: white;"
                            class="min-w-[120px] rounded-md border border-transparent px-6 py-2.5 text-sm font-medium shadow-sm transition-all hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                        >
                            Hủy
                        </button>
                    </div>
                </form>
            </div>
            </div>
        </div>
    @endif
    {{-- View Posts Modal (Không đổi) --}}
    @if ($viewingCategory)
        <div x-data="{ showPosts: @entangle('viewingCategory').live }" x-show="showPosts"
             class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto px-4 py-6 sm:px-0" style="display: none;">
            <div x-show="showPosts" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-900/80 transition-opacity" wire:click="closePostModal"></div>
            <div x-show="showPosts" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative w-full max-w-2xl transform overflow-hidden rounded-lg bg-white shadow-xl transition-all dark:bg-gray-800">
                 <div class="border-b border-gray-200 bg-white px-4 py-5 dark:border-gray-700 dark:bg-gray-800 sm:px-6">
                     <h3 class="text-lg font-semibold leading-6 text-gray-900 dark:text-gray-100">
                         Bài viết trong: {{ $viewingCategory->name }}
                     </h3>
                 </div>
                 <div class="px-4 pb-5 pt-5 sm:p-6">
                     <div wire:loading.flex wire:target="openPostModal" class="items-center justify-center py-6 text-gray-500 dark:text-gray-400">
                         <svg class="mr-2 h-5 w-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                         Đang tải...
                     </div>
                     <div wire:loading.remove wire:target="openPostModal">
                        <ul class="max-h-96 divide-y divide-gray-200 overflow-y-auto dark:divide-gray-700 pr-2">
                            @forelse ($postsForModal ?? [] as $post)
                                <li class="py-3">
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $post->title }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Đăng ngày: {{ $post->created_at->format('d/m/Y') }} bởi {{ $post->user->name ?? 'N/A' }}</p>
                                </li>
                            @empty
                                <li class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">Không có bài viết.</li>
                            @endforelse
                        </ul>
                     </div>
                 </div>
                 <div class="bg-gray-50 px-4 py-3 dark:bg-gray-700/50 sm:flex sm:flex-row-reverse sm:items-center sm:px-6">
                     <x-button type="button" wire:click="closePostModal">
                        Đóng
                     </x-button>
                </div>
            </div>
        </div>
    @endif
</div>

