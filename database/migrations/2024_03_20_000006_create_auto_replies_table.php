<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('auto_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->string('trigger_keyword');
            $table->text('response_text');
            $table->enum('platform', ['facebook', 'instagram', 'tiktok']);
            $table->timestamps();

            $table->unique(['team_id', 'trigger_keyword', 'platform']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('auto_replies');
    }
}; 