<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

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

    // Route cho quản lý danh mục
    Volt::route('admin/categories', 'admin.categories.index')->name('admin.categories');
    
    // Route cho quản lý bài viết
    Volt::route('admin/posts', 'admin.posts.index')->name('admin.posts');
    Volt::route('admin/posts/create', 'admin.posts.create')->name('admin.posts.create');
    Volt::route('admin/posts/{id}', 'admin.posts.show')->name('admin.posts.show');
    Volt::route('admin/posts/{id}/edit', 'admin.posts.edit')->name('admin.posts.edit');
});


