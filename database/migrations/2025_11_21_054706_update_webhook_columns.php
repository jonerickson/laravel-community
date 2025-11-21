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
        Schema::table('webhooks', function (Blueprint $table) {
            $table->json('payload')->nullable()->change();
        });

        Schema::table('webhooks', function (Blueprint $table) {
            $table->renameColumn('payload', 'payload_json');
            $table->text('payload_text')->nullable()->after('payload_json');
            $table->string('render')->after('payload_text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
