<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('message_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('smart_reply_templates')->onDelete('cascade');
            $table->foreignId('message_id')->constrained('messages')->onDelete('cascade');
            $table->string('sentiment');
            $table->string('intent');
            $table->string('priority');
            $table->json('keywords');
            $table->boolean('success')->default(false);
            $table->float('response_time')->nullable();
            $table->string('category');
            $table->float('success_rate')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('message_analyses');
    }
}; 