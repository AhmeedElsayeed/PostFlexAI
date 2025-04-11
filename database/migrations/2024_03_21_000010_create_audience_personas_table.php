<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('audience_personas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('social_account_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name');
            $table->string('avatar')->nullable();
            $table->integer('age_range_start')->nullable();
            $table->integer('age_range_end')->nullable();
            $table->string('gender')->nullable();
            $table->string('location')->nullable();
            $table->json('interests')->nullable();
            $table->json('pain_points')->nullable();
            $table->json('goals')->nullable();
            $table->json('behaviors')->nullable();
            $table->json('preferred_content_types')->nullable();
            $table->json('active_hours')->nullable();
            $table->float('engagement_rate')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_auto_generated')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('audience_personas');
    }
}; 