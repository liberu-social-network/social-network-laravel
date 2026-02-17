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
            $table->timestamp('scheduled_at')->nullable()->after('privacy');
            $table->boolean('is_published')->default(true)->after('scheduled_at');
            $table->index('scheduled_at');
            $table->index(['is_published', 'scheduled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['is_published', 'scheduled_at']);
            $table->dropIndex(['scheduled_at']);
            $table->dropColumn(['scheduled_at', 'is_published']);
        });
    }
};
