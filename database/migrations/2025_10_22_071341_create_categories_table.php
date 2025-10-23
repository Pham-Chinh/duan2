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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();

            // SỬA LỖI: Bỏ comment dòng này đi để thêm cột parent_id
            $table->foreignId('parent_id') // Tạo cột parent_id kiểu số nguyên không dấu
                  ->nullable()             // Cho phép giá trị NULL (nghĩa là danh mục gốc)
                  ->constrained('categories') // Tạo khóa ngoại liên kết với cột 'id' của chính bảng 'categories'
                  ->onDelete('set null');    // Nếu danh mục cha bị xóa, đặt parent_id của con thành NULL (hoặc dùng 'cascade' nếu muốn xóa cả con)

            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};