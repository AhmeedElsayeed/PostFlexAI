<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('post_target_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->foreignId('segment_id')->constrained('audience_clusters')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['post_id', 'segment_id']);
        });

        Schema::create('post_target_personas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->foreignId('persona_id')->constrained('audience_personas')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['post_id', 'persona_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('post_target_personas');
        Schema::dropIfExists('post_target_segments');
    }
}; 