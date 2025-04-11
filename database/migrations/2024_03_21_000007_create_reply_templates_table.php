<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('reply_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('content');
            $table->json('tags')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_global')->default(false); // لخطة Pro
            $table->integer('usage_count')->default(0);
            $table->boolean('is_starred')->default(false);
            $table->string('tone')->nullable(); // رسمي، مرح، تحفيزي، مباشر
            $table->json('metadata')->nullable(); // بيانات إضافية مثل الإحصائيات
            $table->timestamps();
        });

        // جدول لتتبع استخدام القوالب
        Schema::create('reply_template_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reply_template_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('inbox_message_id')->nullable()->constrained()->onDelete('set null');
            $table->string('platform')->nullable();
            $table->json('customized_data')->nullable(); // البيانات المخصصة المستخدمة
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('reply_template_usage');
        Schema::dropIfExists('reply_templates');
    }
}; 