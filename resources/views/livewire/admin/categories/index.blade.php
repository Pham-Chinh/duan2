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
    #[Rule('boolean')]
    public bool $isVisible = true;

    // --- State Management ---
    public ?Category $editingCategory = null;
    public bool $showAddEditModal = false;
    public bool $isAddingChild = false;
    #[Url(as: 'q', history: true)]
    public string $searchQuery = '';

    // --- Data Properties ---
    public EloquentCollection $allCategories;

    // --- Post Viewing Modal ---
    public ?Category $viewingCategory = null;
    public ?EloquentCollection $postsForModal = null;


    // --- Computed Properties ---
    public function filteredCategories(): EloquentCollection
    {
        if (empty(trim($this->searchQuery))) {
            return $this->allCategories;
        }
        $searchTerm = strtolower(trim($this->searchQuery));
        return $this->allCategories->filter(function ($category) use ($searchTerm) {
             if (Str::contains(strtolower($category->name), $searchTerm)) return true;
             $parent = $category->parent_id ? $this->allCategories->find($category->parent_id) : null;
             if ($parent && Str::contains(strtolower($parent->name), $searchTerm)) return true;
            return false;
        });
    }

    public function categoryOptions(): BaseCollection
    {
        $options = new BaseCollection();
        $this->buildCategoryOptions($this->allCategories->whereNull('parent_id')->sortBy('name'), $options);
        return $options;
    }

     private function buildCategoryOptions(EloquentCollection $categoriesInLevel, BaseCollection &$options): void
    {
        foreach ($categoriesInLevel as $category) {
            $isEditingOrDescendant = false;
            if ($this->editingCategory) {
                 $tempCategory = $category;
                 while ($tempCategory) {
                     if ($tempCategory->id == $this->editingCategory->id) { $isEditingOrDescendant = true; break; }
                     $tempCategory = $tempCategory->parent_id ? $this->allCategories->find($tempCategory->parent_id) : null;
                 }
            }
            if (!$isEditingOrDescendant) {
                $optionCategory = new \stdClass();
                $optionCategory->id = $category->id;
                $optionCategory->display_name = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $category->depth ?? 0) . ($category->depth > 0 ? '• ' : '') . $category->name;
                $options->push($optionCategory);
                $children = $this->allCategories->where('parent_id', $category->id);
                if ($children->isNotEmpty()) {
                     $this->buildCategoryOptions($children, $options);
                }
            }
        }
    }

    // --- Hooks ---
    public function updatedParentId($value): void { $this->resetErrorBag('parentId'); }

    // --- Lifecycle Methods ---
    public function mount(): void { $this->loadCategories(); }

    // --- Core Logic Methods ---
    public function loadCategories(): void
    {
        // Tải lại toàn bộ để đảm bảo dữ liệu mới nhất
        $this->allCategories = Category::with(['parentRecursive'])->withCount('posts')->get();
        $this->calculateDepth($this->allCategories);
        $this->allCategories = new EloquentCollection($this->allCategories->sortBy(function($category){
            $path = []; $current = $category;
            while($current) {
                array_unshift($path, sprintf('%03d-%s', $current->depth ?? 0, $current->name));
                $current = $current->parent_id ? $this->allCategories->firstWhere('id', $current->parent_id) : null;
            }
            return implode('/', $path);
         }));
    }

     private function calculateDepth(EloquentCollection $categories): void
    {
        $depthMap = []; $getDepth = null;
        $getDepth = function($category) use (&$getDepth, $categories, &$depthMap) {
            if (!$category) return -1;
            if (isset($depthMap[$category->id])) return $depthMap[$category->id];
            if ($category->parent_id === null) return $depthMap[$category->id] = 0;
            $parent = $categories->firstWhere('id', $category->parent_id);
            if (!$parent) return $depthMap[$category->id] = 0;
            return $depthMap[$category->id] = $getDepth($parent) + 1;
        };
        foreach ($categories as $category) {
             if (!isset($category->depth)) $category->depth = $getDepth($category);
        }
    }

    public function resetForm(): void
    {
        // Reset `$parentId` về null khi reset form
        $this->reset(['name', 'parentId', 'isVisible', 'editingCategory', 'isAddingChild']);
        $this->resetErrorBag();
    }

    public function openAddRootModal(): void
    {
        $this->resetForm(); // Gọi resetForm trước
        $this->isAddingChild = false;
        // parentId đã được reset về null bởi resetForm()
        $this->showAddEditModal = true;
    }

    public function openAddChildModal(): void
    {
         $rootCategoriesExist = $this->allCategories->whereNull('parent_id')->isNotEmpty();
         if (!$rootCategoriesExist) {
            session()->flash('error', 'Cần tạo danh mục gốc trước khi thêm mục con.'); return;
         }
        $this->resetForm(); // Gọi resetForm trước
        $this->isAddingChild = true;
        // parentId đã được reset về null, dropdown sẽ hiển thị placeholder
        $this->showAddEditModal = true;
    }

    public function edit(int $id): void
    {
        $category = $this->allCategories->find($id);
        if ($category) {
            // Không gọi resetForm() ở đây vì cần giữ giá trị cũ
            $this->resetErrorBag();
            $this->editingCategory = $category;
            $this->name = $category->name;
            $this->parentId = $category->parent_id; // Set parentId từ category đang sửa
            $this->isVisible = $category->is_visible;
            $this->isAddingChild = $category->parent_id !== null; // Xác định lại isAddingChild
            $this->showAddEditModal = true;
        }
    }
    public function closeAddEditModal(): void { $this->showAddEditModal = false; $this->resetForm(); }

    protected function validateSlug(): array
    {
        $slug = Str::slug($this->name); $query = ValidationRule::unique('categories', 'slug');
        if ($this->editingCategory) $query->ignore($this->editingCategory->id);
        $validator = Validator::make(['slug' => $slug], ['slug' => [$query]]);
        if ($validator->fails()) { $this->addError('name', 'Tên đã trùng lặp (slug).'); return []; }
        return ['slug' => $slug];
    }

    public function save(): void
{
    // Validate cơ bản trước
    $this->validate([
        'name' => ['required', 'string', 'min:3', 'max:255'],
        'isVisible' => ['boolean'],
        'parentId' => ['nullable', 'exists:categories,id'],
    ]);

    // Validation thủ công cho trường hợp "Thêm Con"
    if ($this->isAddingChild && !$this->editingCategory && empty($this->parentId)) {
        $this->addError('parentId', 'Vui lòng chọn danh mục cha.');
        return;
    }

    $validatedSlug = $this->validateSlug(); 
    if (empty($validatedSlug)) return;

    // Xác định finalParentId dựa trên $this->parentId (đã được validate)
    $finalParentId = $this->parentId;

    $data = [
        'name' => $this->name, 
        'slug' => $validatedSlug['slug'], 
        'parent_id' => $finalParentId, 
        'is_visible' => $this->isVisible
    ];

    if ($this->editingCategory) {
        if ($data['parent_id'] == $this->editingCategory->id) { 
            $this->addError('parentId', 'Không thể chọn chính nó làm cha.'); 
            return; 
        }
        $newParent = $data['parent_id'] ? $this->allCategories->find($data['parent_id']) : null;
        $currentCategory = $this->editingCategory;
        while ($newParent) {
            if ($newParent->id == $currentCategory->id) { 
                $this->addError('parentId', 'Không thể đặt làm con của con cháu.'); 
                return; 
            }
            $parentOfNewParentId = $newParent->parent_id;
            $newParent = $parentOfNewParentId ? $this->allCategories->find($parentOfNewParentId) : null;
        }
    }

    try {
        if ($this->editingCategory) {
            $categoryToUpdate = Category::find($this->editingCategory->id);
            if($categoryToUpdate){ 
                $categoryToUpdate->update($data); 
                session()->flash('success', 'Cập nhật thành công.'); 
            }
            else { 
                session()->flash('error', 'Không tìm thấy danh mục.'); 
                $this->closeAddEditModal(); 
                $this->loadCategories(); 
                return; 
            }
        } else { 
            Category::create($data); 
            session()->flash('success', 'Tạo mới thành công.'); 
        }
        $this->closeAddEditModal(); 
        $this->loadCategories();
    } catch (\Exception $e) { 
        session()->flash('error', 'Lỗi: ' . $e->getMessage()); 
    }
}

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

    public function openPostModal(int $categoryId): void
    {
        $category = Category::find($categoryId);
        if ($category) {
            $this->viewingCategory = $category;
            $this->postsForModal = Post::where('category_id', $categoryId)
                                       ->with('user')->orderBy('created_at', 'desc')->get();
        }
    }
    public function closePostModal(): void { $this->reset(['viewingCategory', 'postsForModal']); }

     public function with(): array
     {
         return [
             'categoryOptions' => $this->categoryOptions(),
             'displayCategories' => $this->filteredCategories(),
         ];
     }
};
?>

{{-- Bắt đầu View --}}
<div class="space-y-6">

    {{-- Header --}}
    <header class="flex flex-col space-y-4 md:flex-row md:items-center md:justify-between md:space-y-0">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
            Quản lý Danh mục
        </h1>
    </header>

    {{-- Alert Messages --}}
    <div class="space-y-3">
        @if (session('success'))
            <div class="rounded-md bg-green-50 p-4 dark:bg-green-900/30">
                <div class="flex"><div class="flex-shrink-0"><svg class="h-5 w-5 text-green-400 dark:text-green-300" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg></div><div class="ml-3"><p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p></div></div>
            </div>
        @endif
        @if (session('error'))
             <div class="rounded-md bg-red-50 p-4 dark:bg-red-900/30">
                <div class="flex"><div class="flex-shrink-0"><svg class="h-5 w-5 text-red-400 dark:text-red-300" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" /></svg></div><div class="ml-3"><p class="text-sm font-medium text-red-800 dark:text-red-200">{{ session('error') }}</p></div></div>
            </div>
        @endif
    </div>

    {{-- Action Bar: Search & Add Buttons --}}
    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 md:flex md:items-center md:justify-between md:space-x-4">
        {{-- Search Input with Icon --}}
        <div class="relative flex-1">
             <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                 <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" /></svg>
             </div>
            <input
                type="search" wire:model.live.debounce.300ms="searchQuery" placeholder="Tìm kiếm tên danh mục..."
                class="block w-full rounded-md border-gray-300 py-2 pl-10 pr-3 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:placeholder-gray-400 dark:focus:border-indigo-500 dark:focus:ring-indigo-500 sm:text-sm"
            >
        </div>
        {{-- Add Buttons --}}
        <div class="mt-4 flex flex-shrink-0 space-x-2 md:mt-0">
            <x-button wire:click="openAddRootModal">
                <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776" /></svg>
                Thêm Mục Gốc
            </x-button>
            <x-button wire:click="openAddChildModal">
                 <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10.5v6m3-3H9m4.06-7.19-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" /></svg>
                Thêm Mục Con
            </x-button>
        </div>
    </div>

    {{-- Categories Table Card --}}
    <div class="overflow-hidden rounded-lg border border-gray-200 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="overflow-x-auto">
            <table class="min-w-full table-fixed">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th scope="col" class="w-2/5 px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">Tên danh mục</th>
                        <th scope="col" class="w-1/5 px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">Danh mục cha</th>
                        <th scope="col" class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">Trạng thái</th>
                        <th scope="col" class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">Bài viết</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">Ngày tạo</th>
                        <th scope="col" class="relative px-6 py-3"><span class="sr-only">Hành động</span></th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800">
                    @forelse ($displayCategories as $category)
                         <tr wire:key="cat-{{ $category->id }}" class="border-b border-gray-200 last:border-b-0 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/50">
                             <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                                 <span style="padding-left: {{ (($category->depth ?? 0) * 1.5) }}rem;">
                                     {{ $category->name }}
                                 </span>
                             </td>
                             <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                  {{ $category->parent_id ? ($allCategories->find($category->parent_id)?->name ?? '...') : '—' }}
                             </td>
                             <td class="whitespace-nowrap px-4 py-4 text-sm text-center">
                                 @if ($category->is_visible)
                                     <span title="Hiển thị" class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/50 dark:text-green-300"><svg class="-ml-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M10 12.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5z" /><path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 010-1.18l.877-.5d9a2.084 2.084 0 00-.38-.857l-.61-.836A1.65 1.65 0 011.8 5.869l.836-.61a2.084 2.084 0 00.858-.38l.5d9-.877A1.65 1.65 0 015.87 3.23l.61.836c.21.286.497.51.81.678l.877.5d9A1.65 1.65 0 0110 5.87l.877-.5d9c.313-.167.599-.392.81-.678l.61-.836A1.65 1.65 0 0114.13 3.23l.5d9.877a1.65 1.65 0 011.18 1.65l-.877.5d9a2.084 2.084 0 00.38.857l.61.836A1.65 1.65 0 0118.2 10.13l-.836.61a2.084 2.084 0 00-.857.38l-.5d9.877A1.65 1.65 0 0114.13 12.77l-.61-.836a2.084 2.084 0 00-.81-.678l-.877-.5d9A1.65 1.65 0 0110 10.13l-.877.5d9a2.084 2.084 0 00-.81.678l-.61.836A1.65 1.65 0 015.87 12.77l-.5d9-.877a1.65 1.65 0 01-1.18-1.65l.877-.5d9a2.084 2.084 0 00.38-.857l.61-.836A1.65 1.65 0 011.8 10.13l-.836-.61a2.084 2.084 0 00-.857-.38l-.5d9-.877zM15 10a5 5 0 11-10 0 5 5 0 0110 0z" clip-rule="evenodd" /></svg></span>
                                 @else
                                     <span title="Ẩn" class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900/50 dark:text-red-300"><svg class="-ml-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3.28 2.22a.75.75 0 00-1.06 1.06l14.5 14.5a.75.75 0 101.06-1.06l-1.745-1.745a10.029 10.029 0 003.3-4.38 1.651 1.651 0 000-1.185A10.004 10.004 0 009.999 5c-.39 0-.773.041-1.148.117L3.28 2.22zM7.75 7.75a2.5 2.5 0 00-3.5 3.5L7.75 7.75zm-2.998-.002a1.65 1.65 0 01-1.185 0A10.005 10.005 0 0110 5c.39 0 .774.041 1.148.117L7.07 8.835a2.502 2.502 0 00-2.318-1.087zM12.25 12.25a2.5 2.5 0 003.5-3.5L12.25 12.25zM15.42 13.917l1.745 1.745a.75.75 0 101.06-1.06l-14.5-14.5a.75.75 0 00-1.06 1.06L5.835 7.07a2.502 2.502 0 001.087 2.318l5.5 5.5zm-3.644-.002a1.65 1.65 0 011.185 0 10.005 10.005 0 01-9.362-4.38A1.65 1.65 0 01.664 9.41l2.616 1.745a2.504 2.504 0 004.838 1.127l3.181 3.181z" clip-rule="evenodd" /></svg></span>
                                 @endif
                             </td>
                             <td class="whitespace-nowrap px-4 py-4 text-sm text-center text-gray-500 dark:text-gray-400">
                                 @if ($category->posts_count > 0)
                                     <button wire:click="openPostModal({{ $category->id }})" class="text-indigo-600 hover:underline dark:text-indigo-400 dark:hover:underline">
                                         {{ $category->posts_count }}
                                     </button>
                                 @else 0 @endif
                             </td>
                             <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $category->created_at->format('d/m/Y') }}</td>
                             <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium space-x-1">
                                 <x-button wire:click="edit({{ $category->id }})" size="sm" icon="pencil-square" title="Sửa"/>
                                 <x-button wire:click="delete({{ $category->id }})" wire:confirm="Xóa '{{ $category->name }}'? Không thể xóa nếu có con hoặc bài viết." variant="danger" size="sm" icon="trash" title="Xóa"/>
                             </td>
                         </tr>
                    @empty
                         <tr><td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">{{ empty(trim($searchQuery)) ? 'Chưa có danh mục nào.' : 'Không tìm thấy kết quả.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Add/Edit Modal --}}
    @if ($showAddEditModal)
        <div x-data="{ showModal: @entangle('showAddEditModal').live }" x-show="showModal" x-on:keydown.escape.window="showModal = false; @this.call('closeAddEditModal')"
             class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto px-4 py-6 sm:px-0" style="display: none;">
            {{-- Backdrop --}}
            <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-900/80 transition-opacity" wire:click="closeAddEditModal"></div>
            {{-- Modal Panel --}}
            <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative w-full max-w-lg transform overflow-hidden rounded-lg bg-white shadow-xl transition-all dark:bg-gray-800">
                <form wire:submit="save">
                    <div class="border-b border-gray-200 bg-white px-4 py-5 dark:border-gray-700 dark:bg-gray-800 sm:px-6">
                        <h3 class="text-lg font-semibold leading-6 text-gray-900 dark:text-gray-100">
                            {{ $editingCategory ? 'Cập nhật Danh mục' : ($isAddingChild ? 'Thêm Danh mục Con' : 'Thêm Danh mục Cha') }}
                        </h3>
                    </div>
                    <div class="px-4 pb-5 pt-5 sm:p-6"><div class="space-y-4">
                        <div>
                            <x-label for="modal-name" :value="__('Tên Danh mục')" />
                            <x-input id="modal-name" type="text" class="mt-1 block w-full" wire:model.lazy="name" required autofocus placeholder="Ví dụ: Công nghệ" />
                            @error('name') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                        @if ($isAddingChild || $editingCategory)
                            <div>
                                <x-label for="modal-parentId" :value="($isAddingChild && !$editingCategory) ? __('Chọn Danh mục Cha *') : __('Danh mục cha')" />
                                <select id="modal-parentId" wire:model="parentId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:focus:border-indigo-500 dark:focus:ring-indigo-500 sm:text-sm"
                                    @if($isAddingChild && !$editingCategory) required @endif >
                                     @if($isAddingChild && !$editingCategory)
                                         <option value="" disabled>-- Vui lòng chọn --</option>
                                     @else
                                         <option value="">— Danh mục gốc —</option>
                                     @endif
                                    @foreach ($categoryOptions as $categoryOption)
                                        <option value="{{ $categoryOption->id }}">
                                            {!! html_entity_decode($categoryOption->display_name) !!}
                                        </option>
                                    @endforeach
                                </select>
                                @error('parentId') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                        @endif
                        <fieldset><legend class="sr-only">Trạng thái</legend><div class="relative flex items-start">
                            <div class="flex h-6 items-center"><input id="modal-isVisible" type="checkbox" wire:model="isVisible" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-indigo-600"></div>
                            <div class="ml-3 text-sm leading-6"><label for="modal-isVisible" class="font-medium text-gray-900 dark:text-gray-300">Hiển thị</label><p class="text-gray-500 dark:text-gray-400">Danh mục sẽ xuất hiện.</p></div>
                        </div></fieldset>
                    </div></div>
                    {{-- Sửa Footer Modal --}}
                    <div class="bg-gray-50 px-4 py-3 dark:bg-gray-700/50 sm:flex sm:flex-row-reverse sm:items-center sm:space-x-3 sm:space-x-reverse sm:px-6">
                        <x-button type="submit" wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading wire:target="save" class="mr-2"><svg class="inline h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg></span>
                            <span wire:loading.remove wire:target="save">{{ $editingCategory ? __('Cập nhật') : __('Lưu') }}</span>
                            <span wire:loading wire:target="save">...</span>
                        </x-button>
                        {{-- Thêm class cho nút Hủy --}}
                        <x-button type="button" wire:click="closeAddEditModal" class="mt-3 w-full sm:mt-0 sm:w-auto">
                            Hủy
                        </x-button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- View Posts Modal --}}
    @if ($viewingCategory)
        <div x-data="{ showPosts: @entangle('viewingCategory').live }" x-show="showPosts"
             class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto px-4 py-6 sm:px-0" style="display: none;">
            {{-- Backdrop --}}
            <div x-show="showPosts" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-900/80 transition-opacity" wire:click="closePostModal"></div>
            {{-- Modal Panel --}}
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

