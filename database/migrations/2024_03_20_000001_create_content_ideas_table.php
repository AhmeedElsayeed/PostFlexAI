<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('content_ideas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('platform')->nullable(); // facebook, instagram...
            $table->string('goal')->nullable(); // awareness, engagement...
            $table->string('theme')->nullable(); // رمضان، تخفيضات...
            $table->json('suggestions')->nullable(); // AI-generated suggestions
            $table->boolean('is_ai_generated')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('content_ideas');
    }
}; 