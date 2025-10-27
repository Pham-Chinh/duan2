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
        Schema::table('posts', function (Blueprint $table) {
            $table->string('banner')->nullable()->after('content'); // Ảnh banner
            $table->json('gallery')->nullable()->after('banner'); // Gallery ảnh (lưu dạng JSON array)
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft')->after('gallery'); // Trạng thái
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['banner', 'gallery', 'status']);
        });
    }
};


