<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->string('platform');
            $table->string('platform_post_id')->nullable();
            $table->string('post_type');
            $table->text('content');
            $table->json('media_urls')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();

            $table->index(['team_id', 'platform']);
            $table->index('scheduled_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('posts');
    }
}; 