<?php

namespace App\Models; // Đảm bảo đúng namespace

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * QUAN TRỌNG: Phải có 'parent_id' ở đây
     */
    protected $fillable = [
        'name',
        'slug',
        'parent_id', // <-- Phải có dòng này
        'is_visible',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_visible' => 'boolean',
    ];

    /**
     * Boot the model to tự động tạo slug.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = static::createUniqueSlug($category->name);
            }
        });

        static::updating(function ($category) {
            // Chỉ tạo lại slug nếu tên thay đổi HOẶC slug đang trống
            if ($category->isDirty('name') || empty($category->slug)) {
                // Đảm bảo không tạo slug trùng khi update
                $category->slug = static::createUniqueSlug($category->name, $category->id);
            }
        });
    }

    /**
     * Tạo slug duy nhất.
     */
    private static function createUniqueSlug(string $name, int $exceptId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $count = 1;

        // Bắt đầu query kiểm tra slug
        $query = static::where('slug', $slug);

        // Nếu đang update (có exceptId), loại trừ bản ghi hiện tại ra khỏi kiểm tra
        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        // Vòng lặp kiểm tra và thêm số nếu slug đã tồn tại
        while ($query->exists()) {
            $slug = $originalSlug . '-' . $count++;
            // Cập nhật lại query cho lần kiểm tra tiếp theo trong vòng lặp
            $query = static::where('slug', $slug);
             if ($exceptId) {
                $query->where('id', '!=', $exceptId);
            }
        }

        return $slug;
    }

    // --- CÁC HÀM QUAN TRỌNG CHO CHA-CON ---

    /**
     * Lấy danh mục cha của danh mục hiện tại (nếu có).
     * Mối quan hệ: Một danh mục thuộc về (BelongsTo) một danh mục cha.
     */
    public function parent(): BelongsTo
    {
        // Liên kết cột 'parent_id' của bảng này với cột 'id' của chính bảng này
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Quan hệ đệ quy để lấy tất cả các cấp cha.
     * Dùng để tính depth hiệu quả khi eager load.
     */
    public function parentRecursive(): BelongsTo
    {
       // Gọi quan hệ 'parent' và yêu cầu load tiếp 'parentRecursive' cho thằng cha đó
       return $this->parent()->with('parentRecursive');
    }


    /**
     * Lấy tất cả các danh mục con trực tiếp của danh mục hiện tại.
     * Mối quan hệ: Một danh mục có nhiều (HasMany) danh mục con.
     */
    public function children(): HasMany
    {
        // Liên kết cột 'id' của bảng này với cột 'parent_id' của chính bảng này
        // Sắp xếp con theo tên
        return $this->hasMany(Category::class, 'parent_id')->orderBy('name');
    }

    /**
     * Lấy các bài viết thuộc danh mục này.
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Accessor để tính độ sâu (cấp) của danh mục một cách hiệu quả.
     * Cấp 0 = gốc, cấp 1 = con trực tiếp, ...
     * Sử dụng quan hệ 'parentRecursive' đã được eager load.
     */
    public function getDepthAttribute(): int
    {
        $depth = 0;
        // Sử dụng $this->parentRecursive thay vì $this->parent để đảm bảo đã load đệ quy
        $parent = $this->parentRecursive;
        while ($parent) {
            $depth++;
            $parent = $parent->parentRecursive; // Đi lên cấp cha tiếp theo đã load
        }
        return $depth;
    }


    // --- HẾT PHẦN CHA-CON ---
}

