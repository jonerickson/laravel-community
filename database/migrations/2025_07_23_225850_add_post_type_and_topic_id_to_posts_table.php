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
        Schema::table('posts', function (Blueprint $table) {
            $table->string('type')->default('blog')->after('id');
            $table->foreignId('topic_id')->nullable()->after('is_featured')->constrained()->nullOnDelete();

            $table->index(['type', 'topic_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['topic_id']);
            $table->dropIndex(['type', 'topic_id']);
            $table->dropColumn(['type', 'topic_id']);
        });
    }
};
