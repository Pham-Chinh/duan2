<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Database\Eloquent\Relations\BelongsTo;
    use Illuminate\Support\Str;

    class Post extends Model
    {
        use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'user_id',
        'category_id',
        'banner',
        'gallery',
        'status',
        'views',
    ];
    
    protected $casts = [
        'gallery' => 'array', // Cast JSON to array
    ];

        // Tự động tạo slug khi tạo/cập nhật
        protected static function boot()
        {
            parent::boot();

            static::creating(function ($post) {
                if (empty($post->slug)) {
                    $post->slug = static::createUniqueSlug($post->title);
                }
            });

            static::updating(function ($post) {
                if ($post->isDirty('title') || empty($post->slug)) {
                    $post->slug = static::createUniqueSlug($post->title, $post->id);
                }
            });
        }

        private static function createUniqueSlug(string $title, int $exceptId = null): string
        {
            $slug = Str::slug($title);
            $originalSlug = $slug;
            $count = 1;

            $query = static::where('slug', $slug);
            if ($exceptId) {
                $query->where('id', '!=', $exceptId);
            }

            while ($query->exists()) {
                $slug = $originalSlug . '-' . $count++;
                $query = static::where('slug', $slug);
                if ($exceptId) {
                    $query->where('id', '!=', $exceptId);
                }
            }
            return $slug;
        }

        // Quan hệ: 1 bài viết thuộc 1 danh mục
        public function category(): BelongsTo
        {
            return $this->belongsTo(Category::class);
        }

        // Quan hệ: 1 bài viết thuộc 1 người dùng (tác giả)
        public function user(): BelongsTo
        {
            return $this->belongsTo(User::class);
        }
        public function scopePublished($query)
        {
            return $query->where('status', 'published');
        }
        //tang luot xem
        public function incrementViews()
        {
            $this->increment('views');
        }
    }
    
