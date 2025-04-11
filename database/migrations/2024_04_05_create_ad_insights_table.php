<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ad_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_id')->constrained()->onDelete('cascade');
            $table->foreignId('ad_account_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->integer('impressions')->default(0);
            $table->integer('clicks')->default(0);
            $table->decimal('spend', 10, 2)->default(0);
            $table->decimal('cpc', 10, 2)->default(0);
            $table->decimal('ctr', 5, 2)->default(0);
            $table->integer('conversions')->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0);
            $table->decimal('cost_per_conversion', 10, 2)->default(0);
            $table->json('breakdown_data')->nullable();
            $table->timestamps();
            
            $table->unique(['ad_id', 'date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('ad_insights');
    }
}; 