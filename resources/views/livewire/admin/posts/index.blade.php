    <?php

    use Livewire\Volt\Component;
    use Livewire\Attributes\Layout;
    use Livewire\Attributes\Rule;
    use Livewire\Attributes\Title;
    use App\Models\Post;
    use App\Models\Category;
    use Illuminate\Database\Eloquent\Collection;
    use Illuminate\Support\Facades\Auth;

    new #[Layout('components.layouts.app')] #[Title('Quản lý Bài viết')]
    class extends Component
    {
        #[Rule('required|string|min:5|max:255')]
        public string $title = '';

        #[Rule('nullable|string')]
        public string $content = '';

        #[Rule('required|exists:categories,id')]
        public ?int $categoryId = null;

        public Collection $posts;
        public Collection $categories;

        /**
         * Mount component, tải danh sách bài viết và danh mục.
         */
        public function mount(): void
        {
            $this->loadPosts();
            // Chỉ tải các danh mục đang hiển thị để chọn
            $this->categories = Category::where('is_visible', true)->orderBy('name', 'asc')->get();
        }

        /**
         * Tải lại danh sách bài viết.
         */
        public function loadPosts(): void
        {
            $this->posts = Post::with('category', 'user') // Lấy kèm thông tin
                ->orderBy('created_at', 'desc')
                ->get();
        }

        /**
         * Reset form.
         */
        public function resetForm(): void
        {
            $this->reset(['title', 'content', 'categoryId']);
            $this->resetErrorBag();
        }

        /**
         * Lưu bài viết mới.
         */
        public function save(): void
        {
            $this->validate();

            Post::create([
                'title' => $this->title,
                'content' => $this->content,
                'category_id' => $this->categoryId,
                'user_id' => Auth::id(), // Gán tác giả là user đang đăng nhập
            ]);

            session()->flash('success', 'Tạo bài viết mới thành công.');
            $this->resetForm();
            $this->loadPosts();
        }

        /**
         * Xóa bài viết.
         */
        public function delete(int $id): void
        {
            $post = Post::find($id);
            if ($post) {
                // (Tùy chọn: Thêm kiểm tra policy xem có đúng là tác giả không)
                $post->delete();
                session()->flash('success', 'Xóa bài viết thành công.');
                $this->loadPosts();
            }
        }
    };
    ?>

    <div>
        {{-- Tiêu đề trang --}}
        <header class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                Quản lý Bài viết
            </h1>
        </header>

        {{-- Thông báo --}}
        @if (session('success'))
            <div class="mb-4 rounded-lg bg-green-100 p-4 text-sm text-green-700 dark:bg-green-200 dark:text-green-800" role="alert">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">

            {{-- Form tạo/cập nhật --}}
            <div class="md:col-span-1">
                <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="border-b border-zinc-200 px-4 py-5 dark:border-zinc-700 sm:px-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            Tạo Bài viết Mới
                        </h3>
                    </div>

                    <div class="px-4 py-5 sm:p-6">
                        <form wire:submit="save" class="space-y-4">
                            
                            {{-- Tiêu đề Bài viết --}}
                            <div>
                                <x-label for="title" :value="__('Tiêu đề Bài viết')" />
                                <x-input id="title" type="text" class="mt-1 block w-full" wire:model.lazy="title" required autofocus placeholder="Ví dụ: Giá vàng hôm nay tăng mạnh" />
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Tiêu đề chính của bài đăng.</p>
                                @error('title') <span class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                            </div>

                            {{-- Chọn Danh mục (Dropdown) --}}
                            <div>
                                <x-label for="categoryId" :value="__('Chọn Danh mục')" />
                                <select id="categoryId" wire:model="categoryId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600">
                                    <option value=""disable selected>--Chọn thể loại--</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Bài viết sẽ thuộc về danh mục này.</p>
                                @error('categoryId') <span class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                            </div>

                            {{-- Nội dung Bài viết --}}
                            <div>
                                <x-label for="content" :value="__('Nội dung')" />
                                <textarea id="content" wire:model.lazy="content" rows="6" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600" placeholder="Viết nội dung của bạn ở đây..."></textarea>
                                @error('content') <span class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                            </div>

                            {{-- Nút --}}
                            <div class="flex items-center gap-4">
                                <x-button type="submit">
                                    {{ __('Đăng bài') }}
                                </x-button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Danh sách bài viết --}}
            <div class="md:col-span-2">
                <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="border-b border-zinc-200 px-4 py-5 dark:border-zinc-700 sm:px-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            Danh sách Bài viết
                        </h3>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <div class="px-4 py-5 sm:p-6">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Tiêu đề</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Danh mục</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Tác giả</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Ngày đăng</th>
                                        <th scope="col" class="relative px-6 py-3">
                                            <span class="sr-only">Hành động</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                    @forelse ($posts as $post)
                                        <tr wire:key="{{ $post->id }}">
                                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">{{ Str::limit($post->title, 40) }}</td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $post->category->name ?? 'N/A' }}</td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $post->user->name ?? 'N/A' }}</td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $post->created_at->format('d/m/Y') }}</td>
                                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium space-x-2">
                                                <x-button wire:click="delete({{ $post->id }})" wire:confirm="Bạn có chắc chắn muốn xóa bài viết này?" variant="danger" size="sm">
                                                    Xóa
                                                </x-button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="whitespace-nowrap px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                                Chưa có bài viết nào.
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
    </div>
    
