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
            $table->string('code')->unique();
            $table->enum('status', ['active', 'used', 'expired'])->default('active');
            $table->integer('max_usage')->nullable(); // كم مرة ممكن يستخدم
            $table->integer('times_used')->default(0);
            $table->foreignId('client_id')->nullable()->constrained()->onDelete('set null'); // ارتباط بعميل
            $table->timestamp('redeemed_at')->nullable();
            $table->json('usage_history')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('coupons');
    }
}; 