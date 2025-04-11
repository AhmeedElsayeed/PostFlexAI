<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('platforms', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // facebook, instagram, tiktok, etc.
            $table->string('display_name');
            $table->string('icon')->nullable();
            $table->json('supported_content_types'); // ['text', 'image', 'video', 'story', etc.]
            $table->json('supported_features'); // ['scheduling', 'stories', 'reels', etc.]
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('platforms');
    }
}; 