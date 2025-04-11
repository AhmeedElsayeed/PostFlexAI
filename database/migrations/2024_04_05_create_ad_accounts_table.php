<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('platform');
            $table->string('account_id');
            $table->string('name');
            $table->string('currency');
            $table->string('timezone');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('access_token');
            $table->text('refresh_token');
            $table->timestamp('token_expires_at');
            $table->string('status');
            $table->decimal('balance', 10, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->string('business_name')->nullable();
            $table->string('business_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_accounts');
    }
}; 