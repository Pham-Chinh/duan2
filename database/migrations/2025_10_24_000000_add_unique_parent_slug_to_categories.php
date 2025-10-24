<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Drop global unique on slug if it exists (ignore errors if it doesn't)
            try {
                $table->dropUnique(['slug']);
            } catch (\Throwable $e) {
                // ignore
            }

            // Add generated column normalizing NULL to 0 for composite unique
            if (!Schema::hasColumn('categories', 'parent_id_norm')) {
                $table->unsignedBigInteger('parent_id_norm')->storedAs('IFNULL(parent_id, 0)');
            }

            // Add unique index on (parent_id_norm, slug)
            $table->unique(['parent_id_norm', 'slug'], 'categories_parent_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Drop composite unique
            $table->dropUnique('categories_parent_slug_unique');

            // Optionally drop the generated column
            if (Schema::hasColumn('categories', 'parent_id_norm')) {
                $table->dropColumn('parent_id_norm');
            }

            // Optionally restore global unique on slug
            try {
                $table->unique('slug');
            } catch (\Throwable $e) {
                // ignore
            }
        });
    }
};



