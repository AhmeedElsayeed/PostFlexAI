<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('platform');
            $table->string('status');
            $table->decimal('budget', 10, 2);
            $table->timestamp('start_date');
            $table->timestamp('end_date')->nullable();
            $table->json('target_audience');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('ad_account_id')->constrained()->onDelete('cascade');
            $table->string('campaign_type');
            $table->string('objective');
            $table->decimal('daily_budget', 10, 2)->nullable();
            $table->decimal('total_budget', 10, 2)->nullable();
            $table->string('bid_strategy');
            $table->json('targeting');
            $table->json('placement');
            $table->string('creative_type');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_campaigns');
    }
}; 