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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->string('image_url')->nullable();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->integer('max_attendees')->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'start_time']);
            $table->index(['is_public', 'start_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
