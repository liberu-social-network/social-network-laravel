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
        Schema::create('user_privacy_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('profile_visibility', ['public', 'friends_only', 'private'])->default('public');
            $table->boolean('show_email')->default(false);
            $table->boolean('show_birth_date')->default(true);
            $table->boolean('show_location')->default(true);
            $table->boolean('allow_friend_requests')->default(true);
            $table->boolean('allow_messages_from_non_friends')->default(true);
            $table->boolean('show_online_status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_privacy_settings');
    }
};
