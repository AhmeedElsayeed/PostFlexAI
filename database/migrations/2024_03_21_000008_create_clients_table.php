<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->string('name')->nullable();
            $table->string('username')->nullable(); // اسم المستخدم على المنصة
            $table->string('platform')->nullable(); // Facebook, IG, TikTok...
            $table->string('profile_link')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('location')->nullable();
            $table->enum('status', ['new', 'interested', 'vip', 'unresponsive'])->default('new');
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable(); // بيانات إضافية مثل التفضيلات والسلوك
            $table->timestamp('last_interaction_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('clients');
    }
}; 