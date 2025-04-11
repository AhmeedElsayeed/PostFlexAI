<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('security_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('event_type'); // login_attempt, api_usage, suspicious_activity, etc.
            $table->string('status'); // success, failed, blocked, etc.
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('device_type')->nullable(); // mobile, tablet, desktop
            $table->string('location')->nullable(); // country, city
            $table->json('metadata')->nullable(); // additional data
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'event_type']);
            $table->index(['ip_address', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_logs');
    }
}; 