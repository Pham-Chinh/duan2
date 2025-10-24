<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Validation\Rule as ValidationRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Attributes\Title;

new #[Layout('components.layouts.app')] #[Title('Quản lý Danh mục')]
class extends Component
{
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

    // --- Data Properties ---
    public EloquentCollection $allCategories; // Chứa tất cả categories

    // --- Post Viewing Modal ---
    public ?Category $viewingCategory = null;
    public ?EloquentCollection $postsForModal = null;


    // --- Computed Properties ---

    // Lấy danh sách categories đã lọc theo search và sắp xếp
    public function filteredCategories(): EloquentCollection
    {
        $categories = $this->allCategories;
        
        // Lọc theo search
        if (!empty(trim($this->searchQuery))) {
        $searchTerm = strtolower(trim($this->searchQuery));
            $categories = $categories->filter(function ($category) use ($searchTerm) {
             return Str::contains(strtolower($category->name), $searchTerm);
        });
    }

        // Áp dụng sắp xếp
        return $this->applySorting($categories);
    }
    
    // Áp dụng sắp xếp cho collection
    private function applySorting(EloquentCollection $categories): EloquentCollection
    {
        $direction = $this->sortDirection === 'asc' ? 1 : -1;
        
        return $categories->sort(function ($a, $b) use ($direction) {
            $valueA = $this->getSortValue($a);
            $valueB = $this->getSortValue($b);
            
            if ($valueA === $valueB) return 0;
            return ($valueA < $valueB ? -1 : 1) * $direction;
        })->values();
    }
    
    // Lấy giá trị để sort theo field
    private function getSortValue($category)
    {
        switch ($this->sortField) {
            case 'name':
                return strtolower($category->name);
            case 'parent':
                return strtolower($category->parent?->name ?? '');
            case 'posts_count':
                return $category->posts_count ?? 0;
            case 'created_at':
                return $category->created_at->timestamp;
            default:
                return strtolower($category->name);
        }
    }
    
    // Xử lý sắp xếp khi click vào cột
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
    }

    // Lấy danh sách options cho dropdown - CHỈ DANH MỤC GỐC (2 cấp)
        public function categoryOptions(): BaseCollection
    {
        $options = new BaseCollection();
        // CHỈ lấy danh mục gốc (parent_id = null) để tạo danh mục con
        // KHÔNG cho phép tạo cháu (3 cấp)
        $rootCategories = $this->allCategories->whereNull('parent_id');
        
        foreach ($rootCategories as $category) {
            // Bỏ qua nếu đang edit chính nó
            if ($this->editingCategory && $category->id == $this->editingCategory->id) {
                continue;
            }
            
            $optionCategory = new \stdClass();
            $optionCategory->id = $category->id;
            $optionCategory->display_name = $category->name;
            $options->push($optionCategory);
        }
        
        return $options;
    }


    // --- Hooks ---
    public function updatedParentId($value): void { $this->resetErrorBag('parentId'); }

    // --- Lifecycle Methods ---
    public function mount(): void { $this->loadCategories(); }

    // --- Core Logic Methods ---
    // Load categories - Không sort, sẽ sort động khi hiển thị
    public function loadCategories(): void
    {
        // Load parent để hiển thị tên cha, đếm posts
        $this->allCategories = Category::with(['parent'])
            ->withCount('posts')
            ->get();
    }

    // Reset form (không đổi)
    public function resetForm(): void
    {
        $this->reset(['name', 'parentId', 'isVisible', 'editingCategory', 'isAddingChild']);
        $this->resetErrorBag();
    }
    // Mở modal thêm cha
    public function openAddRootModal(): void
    {
        $this->resetForm(); 
        $this->isAddingChild = false; 
        $this->parentId = null;
        $this->showAddEditModal = true;
    }
    // Mở modal thêm con (không đổi)
    public function openAddChildModal(): void
    {
         if ($this->allCategories->isEmpty()) {
             session()->flash('error', 'Cần tạo danh mục gốc trước.'); return;
         }
        $this->resetForm(); $this->isAddingChild = true;
        $this->parentId = null; 
        $this->showAddEditModal = true;
    }
    // Mở modal sửa (không đổi)
    public function edit(int $id): void
    {
        $category = $this->allCategories->find($id);
        if ($category) {
            $this->resetErrorBag(); $this->editingCategory = $category;
            $this->name = $category->name; $this->parentId = $category->parent_id;
            $this->isVisible = $category->is_visible; // Vẫn giữ isVisible để biết trạng thái khi sửa
            $this->isAddingChild = $category->parent_id !== null;
            $this->showAddEditModal = true;
        }
    }
    
    // Đóng modal thêm/sửa (không đổi)
    public function closeAddEditModal(): void { $this->showAddEditModal = false; $this->resetForm(); }
    // Validate slug (không đổi)
    protected function validateSlug(): array
    {
        $slug = Str::slug($this->name); $query = ValidationRule::unique('categories', 'slug');
        if ($this->editingCategory) $query->ignore($this->editingCategory->id);
        $validator = Validator::make(['slug' => $slug], ['slug' => [$query]]);
        if ($validator->fails()) { $this->addError('name', 'Tên đã trùng lặp (slug).'); return []; }
        return ['slug' => $slug];
    }
    // Lưu
    public function save(): void
    {
         $rules = [
             'name' => ['required', 'string', 'min:3', 'max:255'],
             'isVisible' => ['boolean'], // Vẫn validate isVisible
             'parentId' => ['nullable', 'exists:categories,id'],
         ];
         if ($this->isAddingChild && !$this->editingCategory) {
             $rules['parentId'][] = 'required';
         }
         $validator = Validator::make(['name' => $this->name, 'isVisible' => $this->isVisible, 'parentId' => $this->parentId], $rules, ['parentId.required' => 'Vui lòng chọn danh mục cha.']);
         if ($validator->fails()) { $this->setErrorBag($validator->errors()); return; }
        
        // KIỂM TRA: Chỉ cho phép 2 cấp (cha-con), KHÔNG cho phép cháu
        if ($this->parentId) {
            $selectedParent = $this->allCategories->find($this->parentId);
            if ($selectedParent && $selectedParent->parent_id !== null) {
                $this->addError('parentId', 'Chỉ được tạo danh mục con (2 cấp). Không thể tạo cháu (3 cấp).');
                return;
            }
        }
        
        $validatedSlug = $this->validateSlug(); if (empty($validatedSlug)) return;
        $finalParentId = $this->parentId;
        // Vẫn lưu isVisible
        $data = ['name' => $this->name, 'slug' => $validatedSlug['slug'], 'parent_id' => $finalParentId, 'is_visible' => $this->isVisible];
        if ($this->editingCategory) {
             if ($data['parent_id'] == $this->editingCategory->id) { $this->addError('parentId', 'Không thể chọn chính nó làm cha.'); return; }
            $newParent = $data['parent_id'] ? $this->allCategories->find($data['parent_id']) : null;
            $currentCategory = $this->editingCategory;
            // BỎ kiểm tra vòng lặp vì không còn hiển thị cây
            // while ($newParent) {
            //     if ($newParent->id == $currentCategory->id) { $this->addError('parentId', 'Không thể đặt làm con của con cháu.'); return; }
            //      $parentOfNewParentId = $newParent->parent_id;
            //      $newParent = $parentOfNewParentId ? $this->allCategories->find($parentOfNewParentId) : null;
            // }
        }
        try {
            if ($this->editingCategory) {
                $categoryToUpdate = Category::find($this->editingCategory->id);
                if($categoryToUpdate){ $categoryToUpdate->update($data); session()->flash('success', 'Cập nhật thành công.'); }
                else { session()->flash('error', 'Không tìm thấy danh mục.'); $this->closeAddEditModal(); $this->loadCategories(); return; }
            } else { Category::create($data); session()->flash('success', 'Tạo mới thành công.'); }
            $this->closeAddEditModal(); $this->loadCategories();
        } catch (\Exception $e) { session()->flash('error', 'Lỗi: ' . $e->getMessage()); }
    }
    // Xóa (không đổi)
    public function delete(int $id): void
    {
        $category = $this->allCategories->find($id);
        if ($category) {
             $children = $this->allCategories->where('parent_id', $category->id);
             if ($children->isNotEmpty()) { session()->flash('error', 'Còn danh mục con.'); return; }
             if (!property_exists($category, 'posts_count')) { $category = Category::withCount('posts')->find($id); if (!$category) return; }
            if ($category->posts_count > 0) { session()->flash('error', 'Còn bài viết.'); return; }
            Category::destroy($id); session()->flash('success', 'Xóa thành công.');
            $this->loadCategories(); $this->resetForm();
            if ($this->editingCategory && $this->editingCategory->id === $id) $this->closeAddEditModal();
        }
    }
    // Mở modal xem bài viết (không đổi)
    public function openPostModal(int $categoryId): void
    {
        $category = Category::find($categoryId);
        if ($category) {
            $this->viewingCategory = $category;
            $this->postsForModal = Post::where('category_id', $categoryId)
                                       ->with('user')->orderBy('created_at', 'desc')->get();
        }
    }
    // Đóng modal xem bài viết (không đổi)
    public function closePostModal(): void { $this->reset(['viewingCategory', 'postsForModal']); }

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
<div class="space-y-6 p-6">

    {{-- Header với gradient đẹp --}}
    <header class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 p-8 shadow-xl">
        <div class="relative z-10 flex flex-col space-y-4 md:flex-row md:items-center md:justify-between md:space-y-0">
            <div class="flex items-center gap-4">
                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/20 backdrop-blur-sm shadow-lg">
                    <svg class="h-10 w-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-white drop-shadow-lg">
            Quản lý Danh mục
        </h1>
                    <p class="text-sm text-white/80 mt-1">Quản lý danh mục sản phẩm của bạn</p>
                </div>
            </div>
        </div>
        {{-- Decorative elements --}}
        <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-white/10 blur-3xl"></div>
        <div class="absolute -bottom-10 -left-10 h-40 w-40 rounded-full bg-white/10 blur-3xl"></div>
    </header>

    {{-- Alert Messages với animation --}}
    <div class="space-y-3">
        @if (session('success'))
            <div class="animate-in slide-in-from-top duration-500 rounded-xl bg-gradient-to-r from-green-50 to-emerald-50 p-5 shadow-lg border-l-4 border-green-500 dark:from-green-900/40 dark:to-emerald-900/40">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 flex h-10 w-10 items-center justify-center rounded-full bg-green-500 shadow-md">
                        <svg class="h-6 w-6 text-white" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-base font-bold text-green-900 dark:text-green-100">Thành công!</p>
                        <p class="text-sm text-green-700 dark:text-green-200">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif
        @if (session('error'))
            <div class="animate-in slide-in-from-top duration-500 rounded-xl bg-gradient-to-r from-red-50 to-pink-50 p-5 shadow-lg border-l-4 border-red-500 dark:from-red-900/40 dark:to-pink-900/40">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 flex h-10 w-10 items-center justify-center rounded-full bg-red-500 shadow-md">
                        <svg class="h-6 w-6 text-white" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-base font-bold text-red-900 dark:text-red-100">Lỗi!</p>
                        <p class="text-sm text-red-700 dark:text-red-200">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Action Bar với gradient đẹp --}}
    <div class="rounded-2xl border-2 border-transparent bg-gradient-to-r from-white via-indigo-50 to-purple-50 p-5 shadow-xl dark:from-zinc-800 dark:via-indigo-950 dark:to-purple-950 flex flex-wrap items-center gap-4">
        <!-- Nhóm nút với gradient -->
        <div class="flex flex-shrink-0 items-center gap-3">
            <button 
                wire:click="openAddRootModal"
                class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-500 to-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-lg hover:from-blue-600 hover:to-indigo-700 hover:shadow-xl transition-all duration-300 hover:scale-105 active:scale-95"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776" />
                </svg>
                <span>Thêm Mục Gốc</span>
            </button>

            <button 
                wire:click="openAddChildModal"
                class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-purple-500 to-pink-600 px-5 py-3 text-sm font-semibold text-white shadow-lg hover:from-purple-600 hover:to-pink-700 hover:shadow-xl transition-all duration-300 hover:scale-105 active:scale-95"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10.5v6m3-3H9m4.06-7.19-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                </svg>
                <span>Thêm Mục Con</span>
            </button>
  </div>

        <!-- Thanh tìm kiếm với gradient border -->
        <div class="relative flex-1 min-w-[280px]">
            <div class="absolute inset-0 rounded-full bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 opacity-20 blur-sm"></div>
  <input
    type="search"
    wire:model.live.debounce.300ms="searchQuery"
                placeholder="🔍 Tìm kiếm tên danh mục..."
                class="relative block w-full appearance-none rounded-full border-2 border-gray-200 bg-white py-3 pl-6 pr-16 shadow-md text-sm font-medium
                       focus:border-transparent focus:ring-4 focus:ring-purple-200
                       dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder-gray-400
                       dark:focus:ring-purple-900 transition-all duration-300"
            />

            <!-- Icon tìm kiếm gradient -->
  <button
    type="button"
    aria-label="Tìm kiếm"
                class="absolute right-2 top-1/2 -translate-y-1/2 flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-r from-red-500 to-pink-600 text-white shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-110 active:scale-95"
            >
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
    </svg>
  </button>
</div>
</div>





    {{-- Bảng với gradient header đẹp --}}
    <div class="overflow-hidden rounded-2xl border-2 border-gray-200 shadow-2xl dark:border-gray-700 backdrop-blur-sm">
        <div class="overflow-x-auto">
            <table class="w-full table-auto">
                <thead class="bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 text-white">
                    <tr>
                        {{-- Cột Tên danh mục - Có Sort --}}
                        <th scope="col" class="px-6 py-4 text-left">
                            <button wire:click="sortBy('name')" class="group inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-white hover:text-yellow-200 transition-colors">
                                <span>Tên danh mục</span>
                                <span class="inline-flex flex-col -space-y-1">
                                    @if($sortField === 'name' && $sortDirection === 'asc')
                                        <svg class="h-3 w-3 text-yellow-300" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3.293l4.146 4.147a.5.5 0 0 0 .708-.708l-4.5-4.5a.5.5 0 0 0-.708 0l-4.5 4.5a.5.5 0 1 0 .708.708L8 3.293z"/></svg>
                                        <svg class="h-3 w-3 text-white/40" fill="currentColor" viewBox="0 0 16 16"><path d="M8 12.707l-4.146-4.147a.5.5 0 0 1 .708-.708L8 11.293l3.438-3.44a.5.5 0 0 1 .708.707l-4.5 4.5a.5.5 0 0 1-.708 0z"/></svg>
                                    @elseif($sortField === 'name' && $sortDirection === 'desc')
                                        <svg class="h-3 w-3 text-white/40" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3.293l4.146 4.147a.5.5 0 0 0 .708-.708l-4.5-4.5a.5.5 0 0 0-.708 0l-4.5 4.5a.5.5 0 1 0 .708.708L8 3.293z"/></svg>
                                        <svg class="h-3 w-3 text-yellow-300" fill="currentColor" viewBox="0 0 16 16"><path d="M8 12.707l-4.146-4.147a.5.5 0 0 1 .708-.708L8 11.293l3.438-3.44a.5.5 0 0 1 .708.707l-4.5 4.5a.5.5 0 0 1-.708 0z"/></svg>
                                    @else
                                        <svg class="h-3 w-3 text-white/40 group-hover:text-white/60" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3.293l4.146 4.147a.5.5 0 0 0 .708-.708l-4.5-4.5a.5.5 0 0 0-.708 0l-4.5 4.5a.5.5 0 1 0 .708.708L8 3.293z"/></svg>
                                        <svg class="h-3 w-3 text-white/40 group-hover:text-white/60" fill="currentColor" viewBox="0 0 16 16"><path d="M8 12.707l-4.146-4.147a.5.5 0 0 1 .708-.708L8 11.293l3.438-3.44a.5.5 0 0 1 .708.707l-4.5 4.5a.5.5 0 0 1-.708 0z"/></svg>
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
                                        <svg class="h-3 w-3 text-yellow-300" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3.293l4.146 4.147a.5.5 0 0 0 .708-.708l-4.5-4.5a.5.5 0 0 0-.708 0l-4.5 4.5a.5.5 0 1 0 .708.708L8 3.293z"/></svg>
                                        <svg class="h-3 w-3 text-white/40" fill="currentColor" viewBox="0 0 16 16"><path d="M8 12.707l-4.146-4.147a.5.5 0 0 1 .708-.708L8 11.293l3.438-3.44a.5.5 0 0 1 .708.707l-4.5 4.5a.5.5 0 0 1-.708 0z"/></svg>
                                    @elseif($sortField === 'parent' && $sortDirection === 'desc')
                                        <svg class="h-3 w-3 text-white/40" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3.293l4.146 4.147a.5.5 0 0 0 .708-.708l-4.5-4.5a.5.5 0 0 0-.708 0l-4.5 4.5a.5.5 0 1 0 .708.708L8 3.293z"/></svg>
                                        <svg class="h-3 w-3 text-yellow-300" fill="currentColor" viewBox="0 0 16 16"><path d="M8 12.707l-4.146-4.147a.5.5 0 0 1 .708-.708L8 11.293l3.438-3.44a.5.5 0 0 1 .708.707l-4.5 4.5a.5.5 0 0 1-.708 0z"/></svg>
                                    @else
                                        <svg class="h-3 w-3 text-white/40 group-hover:text-white/60" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3.293l4.146 4.147a.5.5 0 0 0 .708-.708l-4.5-4.5a.5.5 0 0 0-.708 0l-4.5 4.5a.5.5 0 1 0 .708.708L8 3.293z"/></svg>
                                        <svg class="h-3 w-3 text-white/40 group-hover:text-white/60" fill="currentColor" viewBox="0 0 16 16"><path d="M8 12.707l-4.146-4.147a.5.5 0 0 1 .708-.708L8 11.293l3.438-3.44a.5.5 0 0 1 .708.707l-4.5 4.5a.5.5 0 0 1-.708 0z"/></svg>
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
                                        <svg class="h-3 w-3 text-yellow-300" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3.293l4.146 4.147a.5.5 0 0 0 .708-.708l-4.5-4.5a.5.5 0 0 0-.708 0l-4.5 4.5a.5.5 0 1 0 .708.708L8 3.293z"/></svg>
                                        <svg class="h-3 w-3 text-white/40" fill="currentColor" viewBox="0 0 16 16"><path d="M8 12.707l-4.146-4.147a.5.5 0 0 1 .708-.708L8 11.293l3.438-3.44a.5.5 0 0 1 .708.707l-4.5 4.5a.5.5 0 0 1-.708 0z"/></svg>
                                    @elseif($sortField === 'posts_count' && $sortDirection === 'desc')
                                        <svg class="h-3 w-3 text-white/40" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3.293l4.146 4.147a.5.5 0 0 0 .708-.708l-4.5-4.5a.5.5 0 0 0-.708 0l-4.5 4.5a.5.5 0 1 0 .708.708L8 3.293z"/></svg>
                                        <svg class="h-3 w-3 text-yellow-300" fill="currentColor" viewBox="0 0 16 16"><path d="M8 12.707l-4.146-4.147a.5.5 0 0 1 .708-.708L8 11.293l3.438-3.44a.5.5 0 0 1 .708.707l-4.5 4.5a.5.5 0 0 1-.708 0z"/></svg>
                                    @else
                                        <svg class="h-3 w-3 text-white/40 group-hover:text-white/60" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3.293l4.146 4.147a.5.5 0 0 0 .708-.708l-4.5-4.5a.5.5 0 0 0-.708 0l-4.5 4.5a.5.5 0 1 0 .708.708L8 3.293z"/></svg>
                                        <svg class="h-3 w-3 text-white/40 group-hover:text-white/60" fill="currentColor" viewBox="0 0 16 16"><path d="M8 12.707l-4.146-4.147a.5.5 0 0 1 .708-.708L8 11.293l3.438-3.44a.5.5 0 0 1 .708.707l-4.5 4.5a.5.5 0 0 1-.708 0z"/></svg>
                                    @endif
                                </span>
                            </button>
                        </th>
                        {{-- Cột Ngày tạo - Có Sort --}}
                        <th scope="col" class="px-6 py-4 text-left whitespace-nowrap">
                            <button wire:click="sortBy('created_at')" class="group inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-white hover:text-yellow-200 transition-colors">
                                <span>Ngày tạo</span>
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
                        {{-- Cột Hành động - Không Sort --}}
                        <th scope="col" class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider text-white whitespace-nowrap">Hành động</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
                    {{-- Lặp qua $displayCategories (đã lọc, sort theo tên) --}}
                    @forelse ($displayCategories as $category)
                         <tr wire:key="cat-{{ $category->id }}" class="transition-all duration-200 hover:bg-gradient-to-r hover:from-indigo-50 hover:via-purple-50 hover:to-pink-50 dark:hover:from-indigo-950/30 dark:hover:via-purple-950/30 dark:hover:to-pink-950/30 hover:shadow-md">
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
                                 @if ($category->posts_count > 0)
                                     <button wire:click="openPostModal({{ $category->id }})" class="text-indigo-600 hover:underline dark:text-indigo-400 dark:hover:underline">
                                         {{ $category->posts_count }}
                                     </button>
                                 @else 0 @endif
                             </td>
                             {{-- Cột Ngày tạo --}}
                             <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $category->created_at->format('d/m/Y') }}</td>
                             {{-- Cột Hành động --}}
                             <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                 <div class="flex items-center justify-end gap-1">
                                 <x-button wire:click="edit({{ $category->id }})" size="sm" icon="pencil-square" title="Sửa"/>
                                 <x-button wire:click="delete({{ $category->id }})" wire:confirm="Xóa '{{ $category->name }}'? Không thể xóa nếu có con hoặc bài viết." variant="danger" size="sm" icon="trash" title="Xóa"/>
                                 </div>
                             </td>
                         </tr>
                    @empty
                         <tr><td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">{{ empty(trim($searchQuery)) ? 'Chưa có danh mục nào.' : 'Không tìm thấy kết quả.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>


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

