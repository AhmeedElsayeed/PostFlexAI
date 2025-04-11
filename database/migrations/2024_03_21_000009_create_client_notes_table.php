<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('client_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('note');
            $table->string('type')->default('general'); // general, follow_up, important, etc.
            $table->boolean('is_private')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('client_notes');
    }
}; 