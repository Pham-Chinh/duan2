<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Index cho foreign key parent_id (tăng tốc JOIN và WHERE parent_id)
            $table->index('parent_id', 'idx_categories_parent_id');
            
            // Index cho is_visible (tăng tốc filter theo visibility)
            $table->index('is_visible', 'idx_categories_is_visible');
            
            // Index cho created_at (tăng tốc sort và filter theo ngày)
            $table->index('created_at', 'idx_categories_created_at');
            
            // Composite index cho parent_id + is_visible (tăng tốc query kết hợp)
            $table->index(['parent_id', 'is_visible'], 'idx_categories_parent_visible');
            
            // Index cho name (tăng tốc search và sort theo tên)
            $table->index('name', 'idx_categories_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Drop các indexes theo tên
            $table->dropIndex('idx_categories_parent_id');
            $table->dropIndex('idx_categories_is_visible');
            $table->dropIndex('idx_categories_created_at');
            $table->dropIndex('idx_categories_parent_visible');
            $table->dropIndex('idx_categories_name');
        });
    }
};
