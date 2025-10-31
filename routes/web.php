<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

// Frontend Routes (Public)
Route::get('/', function () {
    return view('frontend.home');
})->name('home');

Route::get('/article/{slug}', function ($slug) {
    return view('frontend.article');
})->name('article.show');

Route::get('/category/{slug}', function ($slug) {
    return view('frontend.category');
})->name('category.show');

Route::get('/search', function () {
    return view('frontend.search');
})->name('search');

// Session Timeout Logout Route (không cần CSRF vì là timeout tự động)
Route::get('/session-timeout', function () {
    // Logout và clear session nếu user đang đăng nhập
    if (Auth::check()) {
        Auth::guard('web')->logout();
    }
    
    // Invalidate và regenerate session
    Session::invalidate();
    Session::regenerateToken();
    
    // Redirect về login với thông báo
    return redirect()->route('login')->with('session_timeout', 'Phiên làm việc đã hết hạn do không có hoạt động trong 30 giây.');
})->name('session.timeout');

// Settings Routes (Authenticated Users)
Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('user-password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});

// Dashboard & Content Management Routes (Admin + Editor)
Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    // Dashboard
    Route::view('dashboard', 'dashboard')->name('dashboard');
    
    // Route cho quản lý danh mục (Admin + Editor)
    Volt::route('admin/categories', 'admin.categories.index')->name('admin.categories');
    
    // Route cho quản lý bài viết (Admin + Editor)
    Volt::route('admin/posts', 'admin.posts.index')->name('admin.posts');
    Volt::route('admin/posts/create', 'admin.posts.create')->name('admin.posts.create');
    Volt::route('admin/posts/{id}', 'admin.posts.show')->name('admin.posts.show');
    Volt::route('admin/posts/{id}/edit', 'admin.posts.edit')->name('admin.posts.edit');
});

// User Management Routes (ONLY Admin)
Route::middleware(['auth', 'verified', 'can-manage-users'])->group(function () {
    Volt::route('admin/users', 'admin.users.index')->name('admin.users');
    Volt::route('admin/users/create', 'admin.users.create')->name('admin.users.create');
    Volt::route('admin/users/{id}', 'admin.users.show')->name('admin.users.show');
    Volt::route('admin/users/{id}/edit', 'admin.users.edit')->name('admin.users.edit');
});


