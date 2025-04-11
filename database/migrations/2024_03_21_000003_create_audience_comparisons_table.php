<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('audience_comparisons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('social_account_id')->constrained()->onDelete('cascade');
            $table->string('metric_type'); // engagement, growth, demographics, etc.
            $table->json('current_period_data');
            $table->json('previous_period_data');
            $table->float('change_percentage');
            $table->string('period_type'); // week, month, quarter, year
            $table->date('current_period_start');
            $table->date('current_period_end');
            $table->date('previous_period_start');
            $table->date('previous_period_end');
            $table->json('insights')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('audience_comparisons');
    }
}; 