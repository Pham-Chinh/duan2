<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Post;
use Livewire\Attributes\Url;
use Livewire\Attributes\Title;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule as ValidationRule;

new #[Layout('components.layouts.app')] #[Title('Quản lý Tài khoản')]
class extends Component
{
    use WithPagination;
    
    // --- Form Properties ---
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $role = 'user';
    
    // --- State Management ---
    public ?User $editingUser = null;
    public bool $showAddEditModal = false;
    public bool $showDeleteModal = false;
    public ?User $deletingUser = null;
    
    #[Url(as: 'q', history: true)]
    public string $searchQuery = '';
    
    // --- Sorting Properties ---
    #[Url(as: 'sort', history: true)]
    public string $sortField = 'name';
    #[Url(as: 'dir', history: true)]
    public string $sortDirection = 'asc';
    
    // --- Pagination ---
    #[Url(as: 'per_page', history: true)]
    public int $perPage = 10;
    
    public function updatedPerPage(): void
    {
        $this->resetPage();
    }
    
    // --- Filter Properties ---
    #[Url(as: 'role_filter', history: true)]
    public string $filterRole = 'all'; // all, admin, user
    
    #[Url(as: 'verified', history: true)]
    public string $filterVerified = 'all'; // all, verified, not_verified
    
    #[Url(as: 'posts', history: true)]
    public string $filterPosts = 'all'; // all, has_posts, no_posts
    
    #[Url(as: 'date', history: true)]
    public string $filterDate = 'all'; // all, today, last_7_days, last_30_days, specific
    
    #[Url(as: 'specific_date', history: true)]
    public string $specificDate = '';
    
    // --- Computed Properties ---
    public function filteredUsers()
    {
        $query = User::withCount('posts');
        
        // Filter by role
        if ($this->filterRole === 'admin') {
            $query->where('role', 'admin');
        } elseif ($this->filterRole === 'user') {
            $query->where('role', 'user');
        }
        
        // Filter by email verification
        if ($this->filterVerified === 'verified') {
            $query->whereNotNull('email_verified_at');
        } elseif ($this->filterVerified === 'not_verified') {
            $query->whereNull('email_verified_at');
        }
        
        // Filter by posts
        if ($this->filterPosts === 'has_posts') {
            $query->has('posts');
        } elseif ($this->filterPosts === 'no_posts') {
            $query->doesntHave('posts');
        }
        
        // Filter by search
        if (!empty(trim($this->searchQuery))) {
            $searchTerm = trim($this->searchQuery);
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('email', 'like', '%' . $searchTerm . '%');
            });
        }
        
        // Filter by date
        if ($this->filterDate === 'today') {
            $query->whereDate('created_at', today());
        } elseif ($this->filterDate === 'last_7_days') {
            $query->where('created_at', '>=', now()->subDays(7));
        } elseif ($this->filterDate === 'last_30_days') {
            $query->where('created_at', '>=', now()->subDays(30));
        } elseif ($this->filterDate === 'specific' && !empty($this->specificDate)) {
            $query->whereDate('created_at', $this->specificDate);
        }
        
        // Sorting
        $this->applySorting($query);
        
        return $query->paginate($this->perPage);
    }
    
    private function applySorting($query): void
    {
        switch ($this->sortField) {
            case 'name':
                $query->orderBy('name', $this->sortDirection);
                break;
            case 'email':
                $query->orderBy('email', $this->sortDirection);
                break;
            case 'role':
                $query->orderBy('role', $this->sortDirection);
                break;
            case 'posts_count':
                $query->orderBy('posts_count', $this->sortDirection);
                break;
            case 'created_at':
                $query->orderBy('created_at', $this->sortDirection);
                break;
            default:
                $query->orderBy('name', 'asc');
        }
    }
    
    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }
    
    public function updatedSearchQuery(): void
    {
        $this->resetPage();
    }
    
    public function updatedFilterRole(): void
    {
        $this->resetPage();
    }
    
    public function updatedFilterVerified(): void
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
    
    // --- CRUD Actions ---
    public function openCreateModal(): void
    {
        $this->reset(['name', 'email', 'password', 'password_confirmation', 'editingUser']);
        $this->role = 'user'; // Mặc định là user
        $this->showAddEditModal = true;
    }
    
    public function openEditModal(int $id): void
    {
        $this->editingUser = User::findOrFail($id);
        $this->name = $this->editingUser->name;
        $this->email = $this->editingUser->email;
        $this->role = $this->editingUser->role;
        $this->password = '';
        $this->password_confirmation = '';
        $this->showAddEditModal = true;
    }
    
    public function save(): void
    {
        $rules = [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'email', 'max:255', ValidationRule::unique('users')->ignore($this->editingUser?->id)],
            'role' => ['required', 'in:admin,editor,user'],
        ];
        
        if (!$this->editingUser) {
            // Creating new user - password required
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
        } elseif (!empty($this->password)) {
            // Editing user with password change
            $rules['password'] = ['nullable', 'string', 'min:8', 'confirmed'];
        }
        
        $validated = $this->validate($rules);
        
        try {
            DB::transaction(function () use ($validated) {
                $data = [
                    'name' => $this->name,
                    'email' => $this->email,
                    'role' => $this->role,
                ];
                
                if (!empty($this->password)) {
                    $data['password'] = Hash::make($this->password);
                }
                
                if ($this->editingUser) {
                    $this->editingUser->update($data);
                    $message = 'Cập nhật tài khoản thành công!';
                } else {
                    User::create($data);
                    $message = 'Thêm tài khoản thành công!';
                }
                
                $this->dispatch('toast-notification', type: 'success', message: $message);
            });
            
            $this->showAddEditModal = false;
            $this->reset(['name', 'email', 'password', 'password_confirmation', 'role', 'editingUser']);
        } catch (\Exception $e) {
            $this->dispatch('toast-notification', type: 'error', message: 'Lỗi: ' . $e->getMessage());
        }
    }
    
    public function openDeleteModal(int $id): void
    {
        $this->deletingUser = User::with('posts')->findOrFail($id);
        $this->showDeleteModal = true;
    }
    
    public function delete(): void
    {
        if (!$this->deletingUser) {
            return;
        }
        
        // Prevent deleting current logged in user
        if ($this->deletingUser->id === auth()->id()) {
            $this->dispatch('toast-notification', type: 'error', message: 'Không thể xóa tài khoản hiện tại!');
            $this->showDeleteModal = false;
            return;
        }
        
        try {
            DB::transaction(function () {
                // Delete user's posts first
                $this->deletingUser->posts()->delete();
                
                $this->deletingUser->delete();
                $this->dispatch('toast-notification', type: 'success', message: 'Xóa tài khoản thành công!');
            });
            
            $this->showDeleteModal = false;
            $this->deletingUser = null;
        } catch (\Exception $e) {
            $this->dispatch('toast-notification', type: 'error', message: 'Lỗi: ' . $e->getMessage());
        }
    }
    
    public function toggleVerification(int $id): void
    {
        try {
            $user = User::findOrFail($id);
            
            if ($user->email_verified_at) {
                $user->update(['email_verified_at' => null]);
                $message = 'Đã hủy xác thực email!';
            } else {
                $user->update(['email_verified_at' => now()]);
                $message = 'Đã xác thực email!';
            }
            
            $this->dispatch('toast-notification', type: 'success', message: $message);
        } catch (\Exception $e) {
            $this->dispatch('toast-notification', type: 'error', message: 'Lỗi: ' . $e->getMessage());
        }
    }
    
    public function toggleRole(int $id): void
    {
        try {
            $user = User::findOrFail($id);
            
            // Không cho phép đổi role của chính mình
            if ($user->id === auth()->id()) {
                $this->dispatch('toast-notification', type: 'error', message: 'Bạn không thể thay đổi vai trò của chính mình!');
                return;
            }
            
            // Cycle through roles: user -> editor -> admin -> user
            $newRole = match($user->role) {
                'user' => 'editor',
                'editor' => 'admin',
                'admin' => 'user',
                default => 'user',
            };
            
            $roleLabel = match($newRole) {
                'admin' => 'Admin',
                'editor' => 'Editor',
                'user' => 'User',
            };
            
            $user->update(['role' => $newRole]);
            $message = "Đã chuyển {$user->name} thành {$roleLabel}!";
            
            $this->dispatch('toast-notification', type: 'success', message: $message);
        } catch (\Exception $e) {
            $this->dispatch('toast-notification', type: 'error', message: 'Lỗi: ' . $e->getMessage());
        }
    }
    
    public function resetFilters(): void
    {
        $this->reset(['searchQuery', 'filterRole', 'filterVerified', 'filterPosts', 'filterDate', 'specificDate']);
        $this->resetPage();
    }
    
    // --- Export CSV ---
    public function exportToCSV()
    {
        $fileName = 'tai-khoan-' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        $users = User::withCount('posts')
            ->orderBy('name', 'asc')
            ->get();
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];
        
        return new StreamedResponse(function() use ($users) {
            $file = fopen('php://output', 'w');
            
            // BOM cho UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Helper function để escape CSV field
            $escapeCsvField = function($field) {
                $field = strval($field);
                if (str_contains($field, ';') || str_contains($field, '"') || str_contains($field, "\n")) {
                    $field = '"' . str_replace('"', '""', $field) . '"';
                }
                return $field;
            };
            
            // Header row
            $headerRow = ['ID', 'Tên', 'Email', 'Vai trò', 'Email Verified', 'Số Bài Viết', 'Ngày Tạo', 'Ngày Cập Nhật'];
            fwrite($file, implode(';', array_map($escapeCsvField, $headerRow)) . "\n");
            
            // Data rows
            foreach ($users as $user) {
                $roleLabel = match($user->role) {
                    'admin' => 'Admin',
                    'editor' => 'Editor',
                    'user' => 'User',
                    default => 'User',
                };
                
                $row = [
                    $user->id,
                    $user->name,
                    $user->email,
                    $roleLabel,
                    $user->email_verified_at ? 'Có' : 'Không',
                    $user->posts_count ?? 0,
                    $user->created_at->format('d/m/Y H:i'),
                    $user->updated_at->format('d/m/Y H:i'),
                ];
                fwrite($file, implode(';', array_map($escapeCsvField, $row)) . "\n");
            }
            
            fclose($file);
        }, 200, $headers);
    }
    
    public function with(): array
    {
        return [
            'users' => $this->filteredUsers(),
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
                    <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-white drop-shadow-lg">Quản lý Tài khoản</h1>
                    <p class="text-sm text-white/90 mt-0.5">Quản lý người dùng trong hệ thống</p>
                </div>
            </div>
            <div class="absolute -right-10 -top-10 h-32 w-32 rounded-full bg-white/10 blur-3xl"></div>
            <div class="absolute -bottom-10 -left-10 h-32 w-32 rounded-full bg-white/10 blur-3xl"></div>
        </div>

        {{-- Action Bar --}}
        <div class="bg-gradient-to-r from-white via-blue-50 to-cyan-50 p-5 dark:from-zinc-800 dark:via-blue-950 dark:to-cyan-950 flex flex-wrap items-center gap-4 border-b-2 border-gray-100 dark:border-gray-700">
            <!-- Nút thêm và xuất CSV -->
            <div class="flex flex-shrink-0 items-center gap-3">
                <button
                    wire:click="openCreateModal"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-500 to-cyan-600 px-5 py-3 text-sm font-semibold text-white shadow-lg hover:from-blue-600 hover:to-cyan-700 hover:shadow-xl transition-all duration-300 hover:scale-105 active:scale-95"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Thêm Tài khoản</span>
                </button>

                <button 
                    wire:click="exportToCSV"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-green-600 px-5 py-3 text-sm font-semibold text-white shadow-lg hover:from-emerald-600 hover:to-green-700 hover:shadow-xl transition-all duration-300 hover:scale-105 active:scale-95"
                    title="Xuất file CSV"
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
                    placeholder="Tìm kiếm theo tên hoặc email..."
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

                <!-- Lọc theo vai trò -->
                <div class="flex-1 min-w-[180px]">
                    <select wire:model.live="filterRole" class="w-full rounded-lg border-2 border-gray-200 px-3 py-2 text-sm focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                        <option value="all">Tất cả vai trò</option>
                        <option value="admin">👑 Admin</option>
                        <option value="editor">✍️ Editor</option>
                        <option value="user">👤 User</option>
                    </select>
                </div>

                <!-- Lọc theo email verified -->
                <div class="flex-1 min-w-[180px]">
                    <select wire:model.live="filterVerified" class="w-full rounded-lg border-2 border-gray-200 px-3 py-2 text-sm focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                        <option value="all">Tất cả xác thực</option>
                        <option value="verified">Đã xác thực</option>
                        <option value="not_verified">Chưa xác thực</option>
                    </select>
                </div>

                <!-- Lọc theo bài viết -->
                <div class="flex-1 min-w-[180px]">
                    <select wire:model.live="filterPosts" class="w-full rounded-lg border-2 border-gray-200 px-3 py-2 text-sm focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                        <option value="all">Tất cả bài viết</option>
                        <option value="has_posts">Có bài viết</option>
                        <option value="no_posts">Không có bài viết</option>
                    </select>
                </div>

                <!-- Lọc theo ngày -->
                <div class="flex-1 min-w-[180px]">
                    <select wire:model.live="filterDate" class="w-full rounded-lg border-2 border-gray-200 px-3 py-2 text-sm focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                        <option value="all">Tất cả ngày</option>
                        <option value="today">Hôm nay</option>
                        <option value="last_7_days">7 ngày qua</option>
                        <option value="last_30_days">30 ngày qua</option>
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
                    wire:click="resetFilters"
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
            <table class="w-full">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600 border-b-2 border-gray-200 dark:border-gray-600">
                    <tr>
                        <th class="px-6 py-4 text-left">
                            <button wire:click="sortBy('name')" class="flex items-center gap-1 text-xs font-semibold uppercase text-gray-700 dark:text-gray-300 hover:text-cyan-600 dark:hover:text-cyan-400 transition">
                                Tên
                                @if($sortField === 'name')
                                    <svg class="h-4 w-4 {{ $sortDirection === 'asc' ? '' : 'rotate-180' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                    </svg>
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-4 text-left">
                            <button wire:click="sortBy('email')" class="flex items-center gap-1 text-xs font-semibold uppercase text-gray-700 dark:text-gray-300 hover:text-cyan-600 dark:hover:text-cyan-400 transition">
                                Email
                                @if($sortField === 'email')
                                    <svg class="h-4 w-4 {{ $sortDirection === 'asc' ? '' : 'rotate-180' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                    </svg>
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-4 text-center">
                            <button wire:click="sortBy('role')" class="flex items-center justify-center gap-1 text-xs font-semibold uppercase text-gray-700 dark:text-gray-300 hover:text-cyan-600 dark:hover:text-cyan-400 transition mx-auto">
                                Vai trò
                                @if($sortField === 'role')
                                    <svg class="h-4 w-4 {{ $sortDirection === 'asc' ? '' : 'rotate-180' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                    </svg>
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">
                            Email Verified
                        </th>
                        <th class="px-6 py-4 text-center">
                            <button wire:click="sortBy('posts_count')" class="flex items-center justify-center gap-1 text-xs font-semibold uppercase text-gray-700 dark:text-gray-300 hover:text-cyan-600 dark:hover:text-cyan-400 transition mx-auto">
                                Số bài viết
                                @if($sortField === 'posts_count')
                                    <svg class="h-4 w-4 {{ $sortDirection === 'asc' ? '' : 'rotate-180' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                    </svg>
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-4 text-center">
                            <button wire:click="sortBy('created_at')" class="flex items-center justify-center gap-1 text-xs font-semibold uppercase text-gray-700 dark:text-gray-300 hover:text-cyan-600 dark:hover:text-cyan-400 transition mx-auto">
                                Ngày tạo
                                @if($sortField === 'created_at')
                                    <svg class="h-4 w-4 {{ $sortDirection === 'asc' ? '' : 'rotate-180' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                    </svg>
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">
                            Hành động
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($users as $user)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=3B82F6&color=fff" 
                                         alt="Avatar" 
                                         class="h-10 w-10 rounded-full">
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $user->name }}</p>
                                        @if($user->id === auth()->id())
                                            <span class="text-xs bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 px-2 py-0.5 rounded">Bạn</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ $user->email }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button
                                    wire:click="toggleRole({{ $user->id }})"
                                    class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold transition {{ $user->getRoleBadgeClasses() }} hover:brightness-95"
                                    title="Click để chuyển: {{ $user->isAdmin() ? 'Admin → User' : ($user->isEditor() ? 'Editor → Admin' : 'User → Editor') }}">
                                    @if($user->isAdmin())
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                                        </svg>
                                        👑 Admin
                                    @elseif($user->isEditor())
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        ✍️ Editor
                                    @else
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        👤 User
                                    @endif
                                </button>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button
                                    wire:click="toggleVerification({{ $user->id }})"
                                    class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold transition
                                           {{ $user->email_verified_at ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 hover:bg-green-200' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400 hover:bg-gray-200' }}">
                                    @if($user->email_verified_at)
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Đã xác thực
                                    @else
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                        Chưa xác thực
                                    @endif
                                </button>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center gap-1 px-3 py-1 bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 rounded-full text-sm font-semibold">
                                    {{ $user->posts_count }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center text-sm text-gray-600 dark:text-gray-400">
                                {{ $user->created_at->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    {{-- View --}}
                                    <a href="{{ route('admin.users.show', $user->id) }}"
                                       class="p-2 bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-900/50 transition"
                                       title="Xem chi tiết">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                    
                                    {{-- Edit --}}
                                    <button
                                        wire:click="openEditModal({{ $user->id }})"
                                        class="p-2 bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400 rounded-lg hover:bg-amber-200 dark:hover:bg-amber-900/50 transition"
                                        title="Chỉnh sửa">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    
                                    {{-- Delete --}}
                                    @if($user->id !== auth()->id())
                                        <button
                                            wire:click="openDeleteModal({{ $user->id }})"
                                            class="p-2 bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition"
                                            title="Xóa">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Không tìm thấy tài khoản nào</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{-- Pagination --}}
        <div class="border-t border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
            <x-custom-pagination :paginator="$users" :perPage="$perPage" />
        </div>
    </div>
    
    {{-- Add/Edit Modal --}}
    @if($showAddEditModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: @entangle('showAddEditModal') }">
            <div class="flex min-h-screen items-center justify-center px-4">
                <div x-show="show" 
                     @click="show = false"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     class="fixed inset-0 bg-black bg-opacity-50"></div>
                
                <div x-show="show"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-6 w-full max-w-md z-10">
                    
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                        {{ $editingUser ? 'Chỉnh sửa Tài khoản' : 'Thêm Tài khoản Mới' }}
                    </h2>
                    
                    <form wire:submit="save" class="space-y-4">
                        {{-- Name --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tên <span class="text-red-500">*</span></label>
                            <input
                                type="text"
                                wire:model="name"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200"
                                placeholder="Nhập tên người dùng"/>
                            @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        
                        {{-- Email --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email <span class="text-red-500">*</span></label>
                            <input
                                type="email"
                                wire:model="email"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200"
                                placeholder="email@example.com"/>
                            @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        
                        {{-- Role --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Vai trò <span class="text-red-500">*</span></label>
                            <select
                                wire:model="role"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200">
                                <option value="user">👤 User (Người dùng)</option>
                                <option value="editor">✍️ Editor (Biên tập viên)</option>
                                <option value="admin">👑 Admin (Quản trị viên)</option>
                            </select>
                            @error('role') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                <span class="font-medium">User:</span> Chỉ đọc tin tức<br>
                                <span class="font-medium">Editor:</span> Quản lý nội dung (Danh mục + Bài viết)<br>
                                <span class="font-medium">Admin:</span> Full quyền (Bao gồm quản lý tài khoản)
                            </p>
                        </div>
                        
                        {{-- Password --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Mật khẩu 
                                @if($editingUser)
                                    <span class="text-gray-500 text-xs">(Để trống nếu không đổi)</span>
                                @else
                                    <span class="text-red-500">*</span>
                                @endif
                            </label>
                            <input
                                type="password"
                                wire:model="password"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200"
                                placeholder="••••••••"/>
                            @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        
                        {{-- Password Confirmation --}}
<div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Xác nhận Mật khẩu</label>
                            <input
                                type="password"
                                wire:model="password_confirmation"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200"
                                placeholder="••••••••"/>
                        </div>
                        
                        {{-- Actions --}}
                        <div class="flex justify-end gap-3 mt-6">
                            <button
                                type="button"
                                wire:click="$set('showAddEditModal', false)"
                                class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                                Hủy
                            </button>
                            <button
                                type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                {{ $editingUser ? 'Cập nhật' : 'Thêm mới' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
    
    {{-- Delete Modal --}}
    @if($showDeleteModal && $deletingUser)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: @entangle('showDeleteModal') }">
            <div class="flex min-h-screen items-center justify-center px-4">
                <div x-show="show" 
                     @click="show = false"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     class="fixed inset-0 bg-black bg-opacity-50"></div>
                
                <div x-show="show"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-6 w-full max-w-md z-10">
                    
                    <div class="text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                            <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        
                        <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">Xác nhận xóa</h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            Bạn có chắc chắn muốn xóa tài khoản <strong>{{ $deletingUser->name }}</strong>?
                        </p>
                        @if($deletingUser->posts_count > 0)
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                                ⚠️ Tài khoản này có <strong>{{ $deletingUser->posts_count }}</strong> bài viết. Tất cả bài viết sẽ bị xóa!
                            </p>
                        @endif
                        
                        <div class="mt-6 flex gap-3 justify-center">
                            <button
                                wire:click="$set('showDeleteModal', false)"
                                class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                                Hủy
                            </button>
                            <button
                                wire:click="delete"
                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                                Xóa
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
    </div>
    {{-- End of main card --}}
</div>
