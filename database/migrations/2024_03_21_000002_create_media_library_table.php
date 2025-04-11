<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('media_library', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('file_type'); // image, video, gif, pdf
            $table->string('platform')->nullable(); // optional (used for filtering)
            $table->json('tags')->nullable();
            $table->json('ai_labels')->nullable(); // labels from AI (optional)
            $table->boolean('is_starred')->default(false); // for pinned assets
            $table->integer('file_size')->nullable(); // in bytes
            $table->string('mime_type')->nullable();
            $table->integer('width')->nullable(); // for images/videos
            $table->integer('height')->nullable(); // for images/videos
            $table->integer('duration')->nullable(); // for videos
            $table->json('metadata')->nullable(); // additional file metadata
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('media_library');
    }
}; 