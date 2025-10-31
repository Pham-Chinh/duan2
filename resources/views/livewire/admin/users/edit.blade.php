<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

new #[Layout('components.layouts.app')] #[Title('Chỉnh sửa Tài khoản')]
class extends Component
{
    public User $user;
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    
    public function mount(int $id): void
    {
        $this->user = User::findOrFail($id);
        $this->name = $this->user->name;
        $this->email = $this->user->email;
    }
    
    public function save(): void
    {
        $rules = [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($this->user->id)],
        ];
        
        if (!empty($this->password)) {
            $rules['password'] = ['nullable', 'string', 'min:8', 'confirmed'];
        }
        
        $validated = $this->validate($rules);
        
        try {
            DB::transaction(function () {
                $data = [
                    'name' => $this->name,
                    'email' => $this->email,
                ];
                
                if (!empty($this->password)) {
                    $data['password'] = Hash::make($this->password);
                }
                
                $this->user->update($data);
            });
            
            $this->dispatch('toast-notification', type: 'success', message: 'Cập nhật tài khoản thành công!');
            $this->redirect(route('admin.users'), navigate: true);
        } catch (\Exception $e) {
            $this->dispatch('toast-notification', type: 'error', message: 'Lỗi: ' . $e->getMessage());
        }
    }
};

?>

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Chỉnh sửa Tài khoản</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Cập nhật thông tin tài khoản: {{ $user->name }}
            </p>
        </div>
        
        <a href="{{ route('admin.users') }}"
           class="inline-flex items-center gap-2 rounded-xl bg-gray-600 px-5 py-3 text-sm font-semibold text-white hover:bg-gray-700 transition">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Quay lại
        </a>
    </div>
    
    {{-- Form --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
        <form wire:submit="save" class="space-y-6 max-w-2xl">
            {{-- Name --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Tên <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    wire:model="name"
                    class="w-full rounded-lg border-2 border-gray-200 dark:border-gray-600 dark:bg-gray-700 px-4 py-3 focus:border-cyan-500 focus:ring-4 focus:ring-cyan-200 dark:focus:ring-cyan-900 transition"
                    placeholder="Nhập tên người dùng"/>
                @error('name') 
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> 
                @enderror
            </div>
            
            {{-- Email --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Email <span class="text-red-500">*</span>
                </label>
                <input
                    type="email"
                    wire:model="email"
                    class="w-full rounded-lg border-2 border-gray-200 dark:border-gray-600 dark:bg-gray-700 px-4 py-3 focus:border-cyan-500 focus:ring-4 focus:ring-cyan-200 dark:focus:ring-cyan-900 transition"
                    placeholder="email@example.com"/>
                @error('email') 
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> 
                @enderror
            </div>
            
            {{-- Password --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Mật khẩu mới
                    <span class="text-gray-500 text-xs font-normal ml-2">(Để trống nếu không thay đổi)</span>
                </label>
                <input
                    type="password"
                    wire:model="password"
                    class="w-full rounded-lg border-2 border-gray-200 dark:border-gray-600 dark:bg-gray-700 px-4 py-3 focus:border-cyan-500 focus:ring-4 focus:ring-cyan-200 dark:focus:ring-cyan-900 transition"
                    placeholder="••••••••"/>
                @error('password') 
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> 
                @enderror
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Tối thiểu 8 ký tự</p>
            </div>
            
            {{-- Password Confirmation --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Xác nhận Mật khẩu mới
                </label>
                <input
                    type="password"
                    wire:model="password_confirmation"
                    class="w-full rounded-lg border-2 border-gray-200 dark:border-gray-600 dark:bg-gray-700 px-4 py-3 focus:border-cyan-500 focus:ring-4 focus:ring-cyan-200 dark:focus:ring-cyan-900 transition"
                    placeholder="••••••••"/>
            </div>
            
            {{-- Actions --}}
            <div class="flex items-center gap-4 pt-4">
                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white hover:bg-blue-700 transition">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Cập nhật
                </button>
                
                <a href="{{ route('admin.users') }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-gray-200 dark:bg-gray-700 px-6 py-3 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                    Hủy
                </a>
            </div>
        </form>
    </div>
</div>
