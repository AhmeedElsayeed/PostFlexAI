<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sentiment_analyses', function (Blueprint $table) {
            $table->id();
            $table->morphs('analyzed');
            $table->float('sentiment_score');
            $table->string('sentiment_label');
            $table->float('confidence_score');
            $table->json('keywords')->nullable();
            $table->text('summary')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sentiment_analyses');
    }
}; 