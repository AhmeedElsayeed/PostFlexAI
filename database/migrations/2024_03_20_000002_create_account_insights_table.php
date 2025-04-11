<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('account_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_account_id')->constrained()->onDelete('cascade');
            $table->integer('followers');
            $table->integer('posts_count');
            $table->integer('reach')->nullable();
            $table->integer('impressions')->nullable();
            $table->decimal('engagement_rate', 5, 2)->nullable();
            $table->timestamp('fetched_at');
            $table->timestamps();

            $table->index(['social_account_id', 'fetched_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('account_insights');
    }
}; 