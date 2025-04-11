<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smart_replies', function (Blueprint $table) {
            $table->id();
            $table->text('content');
            $table->string('language')->default('en');
            $table->string('platform');
            $table->string('context_type');
            $table->string('tone');
            $table->string('category');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('is_template')->default(false);
            $table->integer('usage_count')->default(0);
            $table->float('success_rate')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smart_replies');
    }
}; 