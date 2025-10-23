<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use App\Models\Category; // Sửa namespace thành App\Models
use App\Models\Post;    // Sửa namespace thành App\Models
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Validation\Rule as ValidationRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

new #[Layout('components.layouts.app')] #[Title('Quản lý Danh mục')]
class extends Component
{
    #[Rule('required|string|min:3|max:255')]
    public string $name = '';

    // ID của danh mục cha được chọn trong dropdown
    #[Rule('nullable|exists:categories,id')]
    public ?int $parentId = null; // ID cha được chọn

    #[Rule('boolean')]
    public bool $isVisible = true;

    public Collection $categories; // Chứa TẤT CẢ categories đã được sắp xếp theo cây
    public ?Category $editingCategory = null;

    // Thuộc tính cho Modal (Popup)
    public ?Category $viewingCategory = null;
    public Collection $postsForModal;

    // Computed property để lấy danh sách categories cho dropdown (đã sắp xếp và loại trừ)
    // Nó sẽ tự cập nhật khi $categories thay đổi
    public function categoryOptions(): SupportCollection
    {
        $options = collect();
        // Lấy danh sách đã sắp xếp từ $this->categories để build options
        $this->buildCategoryOptions($this->categories->whereNull('parent_id'), $options); // Bắt đầu từ gốc
        return $options;
    }

    // Hàm đệ quy để xây dựng danh sách phẳng cho dropdown, có thụt lề tên
    private function buildCategoryOptions($categoriesInLevel, &$options, $level = 0): void
    {
        foreach ($categoriesInLevel as $category) {
            // Chỉ thêm vào options nếu không phải là category đang sửa HOẶC con cháu của nó
            $isEditingOrDescendant = false;
            if ($this->editingCategory) {
                 // Kiểm tra xem $category có phải là $editingCategory hoặc con cháu của nó không
                 $tempCategory = $category;
                 while ($tempCategory) {
                     if ($tempCategory->id == $this->editingCategory->id) {
                         $isEditingOrDescendant = true;
                         break;
                     }
                     // Lấy parent từ collection đã load để tránh query N+1
                     $tempCategory = $tempCategory->parent_id ? $this->categories->find($tempCategory->parent_id) : null;
                     //$tempCategory = $tempCategory->parent; // Dùng quan hệ có thể gây N+1 nếu chưa load đúng cách
                 }
            }

            if (!$isEditingOrDescendant) {
                 // Clone category để thêm thuộc tính 'display_name' mà không ảnh hưởng $this->categories
                $optionCategory = clone $category;
                 // Sử dụng accessor depth đã tính sẵn
                $optionCategory->display_name = str_repeat('-- ', $category->depth) . $category->name;
                $options->push($optionCategory);

                // Lấy children từ collection đã load, không query lại DB
                $children = $this->categories->where('parent_id', $category->id);
                if ($children->isNotEmpty()) {
                    // Gọi đệ quy cho con, level + 1 (không cần kiểm tra editing nữa vì đã làm ở trên)
                     $this->buildCategoryOptions($children, $options, $level + 1); // Level + 1 không cần thiết nếu dùng depth
                     //$this->buildCategoryOptions($children, $options, $category->depth + 1); // Sử dụng depth chính xác hơn
                }
            }
        }
    }


    // Hook tự động xóa lỗi khi parentId thay đổi
    public function updatedParentId($value): void
    {
        $this->resetErrorBag('parentId');
    }


    public function mount(): void
    {
        $this->loadCategories();
    }

    public function loadCategories(): void
    {
        // Tải TẤT CẢ categories, kèm quan hệ 'parentRecursive' để tính depth hiệu quả
        // và 'posts_count'
        $this->categories = Category::with(['parentRecursive']) // Load parent đệ quy
            ->withCount('posts')
            // Không cần orderBy ở đây, sẽ sort sau khi load
            ->get()
            ->sortBy(function($category) { // Sắp xếp lại theo cây thư mục sau khi load
                 $path = [];
                 $current = $category;
                 // Sử dụng quan hệ đã eager load để xây dựng path
                 while($current) {
                     array_unshift($path, $current->name); // Thêm tên vào đầu mảng
                     // Đi lên parent đã load, không query lại DB
                     $current = $current->parent_id ? $this->categories->find($current->parent_id) : null;
                    // $current = $current->parent; // Dùng quan hệ có thể gây N+1 nếu chưa load
                 }
                 return implode('/', $path); // Tạo chuỗi 'Cha/Con/Cháu' để sort
            });

         // Nạp lại quan hệ parentRecursive sau khi collection đã được sắp xếp lại
         // Điều này cần thiết để accessor 'depth' và buildCategoryOptions hoạt động đúng
         // Lưu ý: Việc load lại này có thể không cần thiết nếu logic sortBy đã truy cập parent
         // $this->categories->load('parentRecursive'); // Có thể gây N+1 nếu collection lớn, cần test lại

         // Tính toán và lưu depth vào collection để dùng trong Blade và build options
         // mà không cần accessor nữa, tránh N+1 query tiềm ẩn khi render
         $this->categories->each(function ($category) {
            $depth = 0;
            $parent = $category->parent_id ? $this->categories->find($category->parent_id) : null;
            while ($parent) {
                $depth++;
                $parent = $parent->parent_id ? $this->categories->find($parent->parent_id) : null;
            }
            $category->depth = $depth; // Gán trực tiếp vào object category trong collection
        });

    }


    public function resetForm(): void
    {
        // Reset cả parentId
        $this->reset(['name', 'parentId', 'isVisible', 'editingCategory']);
        $this->resetErrorBag();
    }

    protected function validateSlug(): array
    {
        $slug = Str::slug($this->name);
        $query = ValidationRule::unique('categories', 'slug');

        if ($this->editingCategory) {
            $query->ignore($this->editingCategory->id);
        }

        $validator = Validator::make(['slug' => $slug], [
            'slug' => [$query],
        ]);

        if ($validator->fails()) {
             $this->addError('name', 'Tên danh mục này đã được sử dụng (tạo ra slug bị trùng).');
             return [];
        }

        return ['slug' => $slug];
    }

    public function save(): void
    {
         // Validate các thuộc tính cơ bản
         $this->validate();

        $validatedSlug = $this->validateSlug();
        if (empty($validatedSlug)) {
            return;
        }

        // Dữ liệu để lưu, parent_id lấy trực tiếp từ dropdown
        $data = [
            'name' => $this->name,
            'slug' => $validatedSlug['slug'],
            'parent_id' => $this->parentId, // <-- Dùng giá trị từ dropdown
            'is_visible' => $this->isVisible,
        ];

        // Kiểm tra logic phức tạp khi sửa
        if ($this->editingCategory) {
             // 1. Không cho tự đặt mình làm cha
             if ($data['parent_id'] == $this->editingCategory->id) {
                 $this->addError('parentId', 'Không thể chọn chính danh mục này làm danh mục cha.');
                 return;
             }

             // 2. Kiểm tra vòng lặp (không cho đặt làm con của con/cháu)
            $newParent = $data['parent_id'] ? $this->categories->find($data['parent_id']) : null;
            $currentCategory = $this->editingCategory;
            // Đi ngược lên cây cha của parent mới
            while ($newParent) {
                // Sử dụng quan hệ đã load
                if ($newParent->id == $currentCategory->id) {
                     $this->addError('parentId', 'Không thể đặt danh mục làm con của chính nó hoặc con cháu của nó.');
                    return;
                }
                 // Lấy parent từ collection đã load để tránh query
                 $parentOfNewParentId = $newParent->parent_id;
                 $newParent = $parentOfNewParentId ? $this->categories->find($parentOfNewParentId) : null;
            }
        }


        if ($this->editingCategory) {
            // Lấy lại instance từ DB để update, đảm bảo tính nhất quán
            $categoryToUpdate = Category::find($this->editingCategory->id);
            if($categoryToUpdate){
                 $categoryToUpdate->update($data);
                 session()->flash('success', 'Cập nhật danh mục thành công.');
            } else {
                 session()->flash('error', 'Không tìm thấy danh mục để cập nhật.');
            }

        } else {
            Category::create($data);
            session()->flash('success', 'Tạo danh mục mới thành công.');
        }

        $this->resetForm();
        $this->loadCategories(); // Tải lại để cập nhật danh sách và dropdown
    }

    public function edit(int $id): void
    {
        // Lấy category từ collection đã load (đã có parentRecursive)
        $category = $this->categories->find($id);
        if ($category) {
            $this->resetErrorBag();
            $this->editingCategory = $category;
            $this->name = $category->name;
            $this->parentId = $category->parent_id; // <-- Lấy parent_id để chọn sẵn trong dropdown
            $this->isVisible = $category->is_visible;
        }
    }

    public function delete(int $id): void
    {
        // Lấy category từ collection đã load
        $category = $this->categories->find($id);

        if ($category) {
            // Kiểm tra con (từ collection)
             $children = $this->categories->where('parent_id', $category->id);
             if ($children->isNotEmpty()) {
                session()->flash('error', 'Không thể xóa danh mục này vì nó có danh mục con.');
                return;
            }
            // Kiểm tra bài viết (posts_count đã load)
            if ($category->posts_count > 0) {
                session()->flash('error', 'Không thể xóa danh mục này vì nó đang chứa bài viết.');
                return;
            }

            // Tiến hành xóa khỏi DB
            Category::destroy($id);

            session()->flash('success', 'Xóa danh mục thành công.');
            $this->loadCategories(); // Tải lại toàn bộ
            $this->resetForm();
        }
    }

    // Hàm mở Modal (giữ nguyên)
    public function openPostModal(int $categoryId): void
    {
        $category = Category::find($categoryId); // Query DB để lấy category mới nhất
        if ($category) {
            $this->viewingCategory = $category;
            $this->postsForModal = Post::where('category_id', $categoryId)
                                       ->orderBy('created_at', 'desc')
                                       ->get();
        }
    }

    // Hàm đóng Modal (giữ nguyên)
    public function closePostModal(): void
    {
        $this->reset(['viewingCategory', 'postsForModal']);
    }

     // Thêm with() để eager load quan hệ cần thiết
     public function with(): array
     {
         return [
             'categoryOptions' => $this->categoryOptions(),
         ];
     }

};
?>

<div>
    {{-- Tiêu đề trang --}}
    <header class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
            Quản lý Danh mục
        </h1>
    </header>

    {{-- Thông báo --}}
    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-100 p-4 text-sm text-green-700 dark:bg-green-200 dark:text-green-800" role="alert">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-100 p-4 text-sm text-red-700 dark:bg-red-200 dark:text-red-800" role="alert">
            {{ session('error') }}
        </div>
    @endif

    {{-- Layout chính: 1/3 cho form, 2/3 cho bảng --}}
    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">

        {{-- Form tạo/cập nhật (bên cạnh) --}}
        <div class="md:col-span-1">
            <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="border-b border-zinc-200 px-4 py-5 dark:border-zinc-700 sm:px-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        {{ $editingCategory ? 'Cập nhật Danh mục' : 'Tạo Danh mục Mới' }}
                    </h3>
                </div>
                <div class="px-4 py-5 sm:p-6">
                    <form wire:submit="save" class="space-y-4">

                        {{-- Tên danh mục MỚI --}}
                        <div>
                            <x-label for="name" :value="__('Tên Danh mục Mới')" />
                            <x-input id="name" type="text" class="mt-1 block w-full" wire:model.lazy="name" required autofocus placeholder="Ví dụ: Tin tức, Vàng, Bóng đá..." />
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tên của danh mục bạn đang tạo.</p>
                            @error('name') <span class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                        </div>

                        {{-- Dropdown Chọn Danh mục Cha --}}
                        <div>
                             <x-label for="parentId" :value="__('Chọn Danh mục Cha (Để trống nếu tạo mục gốc)')" />
                             <select id="parentId" wire:model="parentId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600">
                                 <option value="">-- Tạo danh mục gốc --</option>
                                 {{-- Lấy danh sách options từ computed property --}}
                                 @foreach ($categoryOptions as $categoryOption) {{-- Sử dụng biến đã truyền qua with() --}}
                                     <option value="{{ $categoryOption->id }}">
                                         {{-- Hiển thị tên đã có thụt lề --}}
                                         {{ $categoryOption->display_name }}
                                     </option>
                                 @endforeach
                             </select>
                             <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Chọn một mục có sẵn để tạo danh mục con.</p>
                             @error('parentId') <span class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                        </div>


                        {{-- Trạng thái --}}
                        <div>
                            <div class="flex items-center space-x-2">
                                <input id="isVisible" type="checkbox" wire:model="isVisible" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800">
                                <x-label for="isVisible" :value="__('Hiển thị (Trạng thái)')" />
                            </div>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Nếu bỏ chọn, danh mục này sẽ bị ẩn.</p>
                        </div>

                        {{-- Nút --}}
                        <div class="flex items-center gap-4">
                            <x-button type="submit">
                                {{ $editingCategory ? __('Cập nhật') : __('Lưu') }}
                            </x-button>
                            @if ($editingCategory)
                                <x-button type="button" wire:click="resetForm">
                                    {{ __('Hủy') }}
                                </x-button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Danh sách danh mục --}}
        <div class="md:col-span-2">
            <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="border-b border-zinc-200 px-4 py-5 dark:border-zinc-700 sm:px-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        Danh sách Danh mục
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <div class="px-4 py-5 sm:p-6">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Tên danh mục</th>
                                    {{-- Cột Danh mục cha --}}
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Danh mục cha</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Trạng thái</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Số bài viết</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Ngày tạo</th>
                                    <th scope="col" class="relative px-6 py-3">
                                        <span class="sr-only">Hành động</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                {{-- Lặp qua collection đã sắp xếp --}}
                                @forelse ($categories as $category)
                                     <tr wire:key="cat-{{ $category->id }}">
                                         {{-- Thụt lề dựa trên depth đã tính --}}
                                         <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100" style="padding-left: {{ (1 + $category->depth * 1.5) }}rem;">
                                             {{ $category->name }}
                                         </td>
                                         {{-- Hiển thị tên cha (đã được eager load) --}}
                                         <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $category->parent->name ?? '--' }}</td>
                                         <td class="whitespace-nowrap px-6 py-4 text-sm">
                                             @if ($category->is_visible)
                                                 <span class="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800">Hiển thị</span>
                                             @else
                                                 <span class="inline-flex rounded-full bg-red-100 px-2 text-xs font-semibold leading-5 text-red-800">Ẩn</span>
                                             @endif
                                         </td>
                                         <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                             {{-- posts_count đã được load --}}
                                             @if ($category->posts_count > 0)
                                                 <button wire:click="openPostModal({{ $category->id }})" class="font-medium text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                                     {{ $category->posts_count }} (Xem)
                                                 </button>
                                             @else
                                                 0
                                             @endif
                                         </td>
                                         <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $category->created_at->format('d/m/Y') }}</td>
                                         <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium space-x-2">
                                             {{-- Sử dụng component x-button --}}
                                             <x-button wire:click="edit({{ $category->id }})" size="sm">Sửa</x-button>
                                             <x-button wire:click="delete({{ $category->id }})" wire:confirm="Bạn có chắc chắn muốn xóa danh mục này? (Không thể xóa nếu có danh mục con hoặc bài viết)" variant="danger" size="sm">Xóa</x-button>
                                         </td>
                                     </tr>
                                @empty
                                     <tr>
                                        <td colspan="6" class="whitespace-nowrap px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                            Chưa có danh mục nào.
                                        </td>
                                    </tr>
                                @endforelse

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL (POPUP) - Giữ nguyên không đổi --}}
    @if ($viewingCategory)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto"
            x-data="{ show: @entangle('viewingCategory') }"
            x-show="show"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        >
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closePostModal"></div>
            <div
                class="relative w-full max-w-2xl transform overflow-hidden rounded-lg bg-white shadow-xl transition-all dark:bg-gray-800"
                x-show="show"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            >
                <div class="px-4 pb-4 pt-5 sm:p-6">
                    <div class="sm:flex sm:items-start">
                        <div class="w-full text-center sm:mt-0 sm:text-left">
                            <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100" id="modal-title">
                                Bài viết trong: {{ $viewingCategory->name }}
                            </h3>
                            <div class="mt-4">
                                <ul class="max-h-96 divide-y divide-gray-200 overflow-y-auto dark:divide-gray-700">
                                    @forelse ($postsForModal as $post)
                                        <li class="py-3">
                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $post->title }}</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Đăng ngày: {{ $post->created_at->format('d/m/Y') }}</p>
                                        </li>
                                    @empty
                                        <li class="py-3 text-center text-sm text-gray-500 dark:text-gray-400">
                                            Không có bài viết nào trong danh mục này.
                                        </li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 dark:bg-gray-800 sm:flex sm:flex-row-reverse sm:px-6">
                     {{-- Sử dụng x-button cho nút đóng --}}
                     <x-button type="button" wire:click="closePostModal" variant="secondary">
                        Đóng
                     </x-button>
                </div>
            </div>
        </div>
    @endif
</div>

