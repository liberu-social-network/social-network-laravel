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
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('post_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('album_id')->nullable()->constrained()->onDelete('set null');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type'); // image/video
            $table->string('mime_type');
            $table->integer('file_size'); // in bytes
            $table->string('thumbnail_path')->nullable();
            $table->text('description')->nullable();
            $table->enum('privacy', ['public', 'friends_only', 'private'])->default('public');
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->integer('duration')->nullable(); // for videos in seconds
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('post_id');
            $table->index('album_id');
            $table->index('privacy');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
