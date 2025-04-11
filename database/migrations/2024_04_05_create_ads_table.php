<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('ad_set_id')->constrained()->onDelete('cascade');
            $table->foreignId('campaign_id')->constrained('ad_campaigns')->onDelete('cascade');
            $table->foreignId('ad_account_id')->constrained()->onDelete('cascade');
            $table->string('status');
            $table->string('creative_type');
            $table->json('creative_data');
            $table->string('objective');
            $table->json('targeting');
            $table->decimal('bid_amount', 10, 2);
            $table->string('bid_strategy');
            $table->json('tracking_specs');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ads');
    }
}; 