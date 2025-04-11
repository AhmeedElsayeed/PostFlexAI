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
        Schema::table('users', function (Blueprint $table) {
            $table->text('two_factor_secret')
                    ->after('password')
                    ->nullable();

            $table->text('two_factor_recovery_codes')
                    ->after('two_factor_secret')
                    ->nullable();

            $table->timestamp('two_factor_confirmed_at')
                    ->after('two_factor_recovery_codes')
                    ->nullable();

            $table->boolean('two_factor_enabled')
                    ->after('two_factor_confirmed_at')
                    ->default(false);

            $table->string('two_factor_method')
                    ->after('two_factor_enabled')
                    ->default('authenticator'); // authenticator, sms, whatsapp

            $table->string('phone_number')
                    ->after('two_factor_method')
                    ->nullable();

            $table->boolean('whatsapp_enabled')
                    ->after('phone_number')
                    ->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
                'two_factor_enabled',
                'two_factor_method',
                'phone_number',
                'whatsapp_enabled'
            ]);
        });
    }
}; 