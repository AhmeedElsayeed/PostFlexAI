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
        Schema::create('recycled_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_recycle_id')->constrained('content_recycles')->onDelete('cascade');
            $table->foreignId('original_media_id')->nullable()->constrained('media')->onDelete('set null');
            $table->foreignId('new_media_id')->nullable()->constrained('media')->onDelete('set null');
            $table->enum('action', ['reuse', 'replace', 'modify'])->default('reuse');
            $table->json('modifications')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recycled_media');
    }
}; 