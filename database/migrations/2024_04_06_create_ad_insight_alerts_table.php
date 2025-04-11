<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ad_insight_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_id')->constrained()->onDelete('cascade');
            $table->foreignId('ad_account_id')->constrained()->onDelete('cascade');
            $table->string('type'); // performance_drop, cost_increase, conversion_drop, etc.
            $table->string('severity'); // low, medium, high
            $table->text('message');
            $table->json('metrics')->nullable(); // Store the metrics that triggered the alert
            $table->json('comparison_data')->nullable(); // Store data for comparison
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ad_insight_alerts');
    }
}; 