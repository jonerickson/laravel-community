<?php

declare(strict_types=1);

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
        Schema::table('comments', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropIndex('comments_created_by_foreign');
            $table->foreignId('created_by')->nullable()->change()->constrained('users')->nullOnDelete();
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropIndex('posts_created_by_index');
            $table->foreignId('created_by')->nullable()->change()->constrained('users')->nullOnDelete();
        });

        Schema::table('topics', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropIndex('topics_created_by_index');
            $table->foreignId('created_by')->nullable()->change()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('be_null', function (Blueprint $table) {
            //
        });
    }
};
