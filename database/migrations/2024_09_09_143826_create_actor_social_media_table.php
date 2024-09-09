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
        Schema::create('actor_social_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->constrained('actors')->onDelete('cascade');
            $table->foreignId('social_media_platform_id')->constrained('social_media_platforms')->onDelete('cascade');
            $table->string('link');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actor_social_media');
    }
};
