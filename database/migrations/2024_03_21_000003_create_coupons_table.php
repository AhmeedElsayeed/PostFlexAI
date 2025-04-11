<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->nullable()->constrained()->onDelete('set null');
            $table->string('code')->unique();
            $table->enum('status', ['active', 'used', 'expired'])->default('active');
            $table->integer('max_usage')->nullable();
            $table->integer('times_used')->default(0);
            $table->timestamp('redeemed_at')->nullable();
            $table->json('usage_history')->nullable();
            $table->timestamps();

            // Indexes for better query performance
            $table->index(['status', 'code']);
            $table->index(['offer_id', 'status']);
            $table->index(['client_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('coupons');
    }
}; 