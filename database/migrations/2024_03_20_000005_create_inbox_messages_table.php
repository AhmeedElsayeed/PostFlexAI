<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('inbox_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('platform'); // facebook, instagram, tiktok
            $table->string('message_id'); // ID from the platform
            $table->string('sender_name')->nullable();
            $table->text('message_text')->nullable();
            $table->string('type')->default('comment'); // comment or message
            $table->enum('status', ['new', 'read', 'replied', 'archived'])->default('new');
            $table->boolean('is_automated')->default(false);
            $table->timestamp('received_at');
            $table->timestamps();

            $table->unique(['platform', 'message_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('inbox_messages');
    }
}; 