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
        Schema::table('forums_categories_groups', function (Blueprint $table) {
            $table->boolean('create')->default(true)->after('group_id');
            $table->renameColumn('write', 'update');
            $table->after('delete', function (Blueprint $table) {
                $table->boolean('moderate')->default(false);
                $table->boolean('reply')->default(true);
                $table->boolean('report')->default(true);
                $table->boolean('pin')->default(false);
                $table->boolean('lock')->default(false);
                $table->boolean('move')->default(false);
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('forums_categories_groups', function (Blueprint $table) {
            //
        });
    }
};
