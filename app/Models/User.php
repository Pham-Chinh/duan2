<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Quan hệ: 1 user có nhiều posts
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * kiem tra adm
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * kiem tra btv
     */
    public function isEditor(): bool
    {
        return $this->role === 'editor';
    }

    /**
     * Check if user is regular user
     */
    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    /**
     * Check if user can access dashboard (Admin or Editor)
     */
    public function canAccessDashboard(): bool
    {
        return in_array($this->role, ['admin', 'editor']);
    }

    /**
     * Check if user can manage users (Only Admin)
     */
    public function canManageUsers(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Get role label with emoji
     */
    public function getRoleLabel(): string
    {
        return match($this->role) {
            'admin' => '👑 Admin',
            'editor' => '✍️ Editor',
            'user' => '👤 User',
            default => '👤 User',
        };
    }

    /**
     * Get role badge color classes
     */
    public function getRoleBadgeClasses(): string
    {
        return match($this->role) {
            'admin' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
            'editor' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
            'user' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400',
            default => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400',
        };
    }
}
