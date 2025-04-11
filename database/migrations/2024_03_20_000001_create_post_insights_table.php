<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('post_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->string('platform');
            $table->integer('likes')->default(0);
            $table->integer('comments')->default(0);
            $table->integer('shares')->default(0);
            $table->integer('views')->nullable();
            $table->integer('saves')->nullable();
            $table->decimal('engagement_rate', 5, 2)->nullable();
            $table->timestamp('fetched_at');
            $table->timestamps();

            $table->index(['post_id', 'platform']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('post_insights');
    }
}; 