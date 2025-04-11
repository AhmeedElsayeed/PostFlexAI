<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_sets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('campaign_id')->constrained('ad_campaigns')->onDelete('cascade');
            $table->foreignId('ad_account_id')->constrained()->onDelete('cascade');
            $table->string('status');
            $table->decimal('budget', 10, 2);
            $table->decimal('bid_amount', 10, 2);
            $table->json('targeting');
            $table->string('optimization_goal');
            $table->string('billing_event');
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();
            $table->json('target_audience');
            $table->json('placement');
            $table->string('bid_strategy');
            $table->string('creative_type');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_sets');
    }
}; 