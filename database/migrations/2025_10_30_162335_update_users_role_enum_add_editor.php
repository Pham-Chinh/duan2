<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Thêm 'editor' vào ENUM role
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'editor', 'user') DEFAULT 'user'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Chuyển tất cả editor về user trước khi rollback
        DB::table('users')->where('role', 'editor')->update(['role' => 'user']);
        
        // Xóa 'editor' khỏi ENUM
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'user') DEFAULT 'user'");
    }
};
