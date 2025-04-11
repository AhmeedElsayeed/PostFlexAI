<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('messages')->onDelete('cascade');
            $table->string('sentiment');
            $table->string('intent');
            $table->string('priority');
            $table->json('keywords')->nullable();
            $table->float('confidence');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_analyses');
    }
}; 